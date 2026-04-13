<?php

namespace App\Console\Commands;

use App\Models\CallAnalysisJob;
use App\Services\IAService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarCallAnalysisPendientes extends Command
{
    protected $signature   = 'callanalysis:pendientes';
    protected $description = 'Procesa los análisis de llamadas 911 pendientes';

    public function handle(IAService $ia)
    {
        $log = Log::channel('transcripciones');

        $pendientes = CallAnalysisJob::where('status', 'pending')->orderBy('id')->get();

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $log->info("[CallAnalysis] {$pendientes->count()} trabajo(s) pendiente(s).");

        foreach ($pendientes as $job) {
            $log->info("[CallAnalysis] Job #{$job->id} iniciado.", [
                'modo'    => $job->mode,
                'archivo' => $job->original_name,
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
                $inicio  = microtime(true);
                $nombre  = $job->original_name ?? basename($audioPath);

                if ($job->mode === 'transcribe') {
                    $data   = $ia->transcribirAudio(new \Illuminate\Http\UploadedFile($audioPath, $nombre, null, null, true), 'es');
                    $result = ['mode' => 'transcribe', 'text' => $data['text'], 'duracion' => $data['duration']];
                    $job->update(['status' => 'completed', 'result_json' => json_encode($result)]);
                } else {
                    $result = $ia->analizarLlamada($audioPath, $nombre);
                    $job->update(['status' => 'completed', 'result_json' => json_encode($result)]);
                }

                $elapsed = round(microtime(true) - $inicio, 2);
                $log->info("[CallAnalysis] Job #{$job->id} completado en {$elapsed}s.");
                Storage::disk('local')->delete($job->audio_path);

            } catch (\Exception $e) {
                $log->error("[CallAnalysis] Job #{$job->id} excepción.", ['mensaje' => $e->getMessage()]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
