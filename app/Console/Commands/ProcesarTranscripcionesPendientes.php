<?php

namespace App\Console\Commands;

use App\Models\AudioTranscripcion;
use App\Models\TranscripcionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarTranscripcionesPendientes extends Command
{
    protected $signature   = 'transcribir:pendientes';
    protected $description = 'Procesa todas las transcripciones de audio pendientes';

    public function handle()
    {
        $log = Log::channel('transcripciones');

        $pendientes = TranscripcionJob::where('status', 'pending')->orderBy('id')->get();

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $log->info("Scheduler: {$pendientes->count()} trabajo(s) pendiente(s) encontrado(s).");

        foreach ($pendientes as $job) {
            $log->info("Job #{$job->id} iniciado.", ['audio_path' => $job->audio_path]);
            $job->update(['status' => 'processing']);

            $audioPath = Storage::disk('local')->path($job->audio_path);

            if (!file_exists($audioPath)) {
                $log->error("Job #{$job->id} fallido: archivo no encontrado.", ['path' => $audioPath]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => 'Archivo de audio no encontrado en disco.',
                ]);
                continue;
            }

            $url = config('services.ia.whisper_url') . '/inference';
            $log->info("Job #{$job->id} enviando a Whisper.", ['url' => $url, 'archivo' => basename($audioPath)]);

            try {
                $inicio   = microtime(true);
                $response = Http::timeout(900)
                    ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
                    ->post($url, ['language' => 'es']);
                $elapsed  = round(microtime(true) - $inicio, 2);

                if ($response->successful()) {
                    $duracion   = $response->json('duration');
                    $resultText = $response->json('text');
                    $resultJson = json_encode($response->json());

                    $job->update([
                        'status'           => 'completed',
                        'result_text'      => $resultText,
                        'result_json'      => $resultJson,
                        'duration_seconds' => $duracion,
                    ]);

                    // Guardar en audio_transcripciones para historial persistente
                    AudioTranscripcion::create([
                        'nombre_archivo'     => $job->original_filename ?? basename($job->audio_path),
                        'telefono'           => $job->telefono,
                        'transcripcion_json' => $resultJson,
                    ]);

                    Storage::disk('local')->delete($job->audio_path);
                    $log->info("Job #{$job->id} completado y guardado en historial.", [
                        'tiempo_whisper_s' => $elapsed,
                        'duracion_audio_s' => $duracion,
                        'chars_resultado'  => strlen($resultText ?? ''),
                        'nombre_archivo'   => $job->original_filename,
                    ]);
                } else {
                    $errorMsg = 'Error en Whisper (' . $response->status() . '): ' . $response->body();
                    $log->error("Job #{$job->id} fallido.", [
                        'http_status'      => $response->status(),
                        'body'             => substr($response->body(), 0, 500),
                        'tiempo_whisper_s' => $elapsed,
                    ]);
                    $job->update([
                        'status'        => 'failed',
                        'error_message' => $errorMsg,
                    ]);
                }
            } catch (\Exception $e) {
                $log->error("Job #{$job->id} excepción al conectar con Whisper.", [
                    'mensaje' => $e->getMessage(),
                    'clase'   => get_class($e),
                ]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => 'No se pudo conectar al servidor IA: ' . $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
