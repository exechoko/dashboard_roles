<?php

namespace App\Console\Commands;

use App\Models\CallAnalysisJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarCallAnalysisPendientes extends Command
{
    protected $signature   = 'callanalysis:pendientes';
    protected $description = 'Procesa los análisis de llamadas 911 pendientes';

    public function handle()
    {
        $log = Log::channel('transcripciones');

        $pendientes = CallAnalysisJob::where('status', 'pending')->orderBy('id')->get();

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $log->info("[CallAnalysis] {$pendientes->count()} trabajo(s) pendiente(s).");

        foreach ($pendientes as $job) {
            $log->info("[CallAnalysis] Job #{$job->id} iniciado.", [
                'modo'     => $job->mode,
                'archivo'  => $job->original_name,
            ]);

            $job->update(['status' => 'processing']);

            $audioPath = Storage::disk('local')->path($job->audio_path);

            if (!file_exists($audioPath)) {
                $log->error("[CallAnalysis] Job #{$job->id} fallido: archivo no encontrado.", ['path' => $audioPath]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => 'Archivo de audio no encontrado en disco.',
                ]);
                continue;
            }

            try {
                $inicio = microtime(true);

                if ($job->mode === 'transcribe') {
                    // Solo transcripción — usa el Whisper local (puerto 8080)
                    $url      = config('services.ia.whisper_url') . '/inference';
                    $response = Http::timeout(900)
                        ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
                        ->post($url, ['language' => 'es']);

                    if ($response->successful()) {
                        $result = [
                            'mode'    => 'transcribe',
                            'text'    => $response->json('text'),
                            'duracion'=> $response->json('duration'),
                        ];
                        $job->update([
                            'status'      => 'completed',
                            'result_json' => json_encode($result),
                        ]);
                    } else {
                        throw new \RuntimeException('Whisper error (' . $response->status() . '): ' . substr($response->body(), 0, 300));
                    }
                } else {
                    // Análisis completo — usa el servidor CallAnalysis (puerto 8082)
                    $url      = 'http://193.169.1.246:8082/analyze';
                    $response = Http::timeout(700)
                        ->attach('file', fopen($audioPath, 'r'), $job->original_name ?? basename($audioPath))
                        ->post($url);

                    if ($response->successful()) {
                        $job->update([
                            'status'      => 'completed',
                            'result_json' => $response->body(),
                        ]);
                    } elseif ($response->status() === 422) {
                        throw new \RuntimeException('El audio no pudo transcribirse (archivo dañado o silencio).');
                    } else {
                        throw new \RuntimeException('CallAnalysis error (' . $response->status() . '): ' . substr($response->body(), 0, 300));
                    }
                }

                $elapsed = round(microtime(true) - $inicio, 2);
                $log->info("[CallAnalysis] Job #{$job->id} completado en {$elapsed}s.");
                Storage::disk('local')->delete($job->audio_path);

            } catch (\Exception $e) {
                $log->error("[CallAnalysis] Job #{$job->id} excepción.", [
                    'mensaje' => $e->getMessage(),
                ]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
