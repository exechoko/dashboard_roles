<?php

namespace App\Console\Commands;

use App\Models\TranscripcionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarTranscripcion extends Command
{
    protected $signature = 'transcribir:proceso {job_id}';
    protected $description = 'Procesa una transcripción de audio pendiente en background';

    public function handle()
    {
        $jobId = $this->argument('job_id');
        $log   = Log::channel('transcripciones');

        $job = TranscripcionJob::find($jobId);
        if (!$job) {
            $log->error("Job #{$jobId} no encontrado en la base de datos.");
            $this->error("Job #{$jobId} no encontrado.");
            return 1;
        }

        if ($job->status !== 'pending') {
            $log->warning("Job #{$jobId} ya fue procesado (status: {$job->status}). Se omite.");
            $this->warn("Job #{$jobId} ya fue procesado (status: {$job->status}).");
            return 0;
        }

        $log->info("Job #{$jobId} iniciado.", ['audio_path' => $job->audio_path]);
        $job->update(['status' => 'processing']);

        $audioPath = Storage::disk('local')->path($job->audio_path);

        if (!file_exists($audioPath)) {
            $log->error("Job #{$jobId} fallido: archivo de audio no encontrado.", ['path' => $audioPath]);
            $job->update([
                'status'        => 'failed',
                'error_message' => 'Archivo de audio no encontrado en disco.',
            ]);
            return 1;
        }

        $url = config('services.ia.whisper_url') . '/inference';
        $log->info("Job #{$jobId} enviando a Whisper.", ['url' => $url, 'archivo' => basename($audioPath)]);

        try {
            $inicio    = microtime(true);
            $response  = Http::timeout(900)
                ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
                ->post($url, ['language' => 'es']);
            $elapsed   = round(microtime(true) - $inicio, 2);

            if ($response->successful()) {
                $duracion = $response->json('duration');
                $job->update([
                    'status'           => 'completed',
                    'result_text'      => $response->json('text'),
                    'duration_seconds' => $duracion,
                ]);
                Storage::disk('local')->delete($job->audio_path);
                $log->info("Job #{$jobId} completado.", [
                    'tiempo_whisper_s' => $elapsed,
                    'duracion_audio_s' => $duracion,
                    'chars_resultado'  => strlen($response->json('text') ?? ''),
                ]);
                $this->info("Transcripción #{$jobId} completada.");
                return 0;
            }

            $errorMsg = 'Error en Whisper (' . $response->status() . '): ' . $response->body();
            $log->error("Job #{$jobId} fallido.", [
                'http_status'      => $response->status(),
                'body'             => substr($response->body(), 0, 500),
                'tiempo_whisper_s' => $elapsed,
            ]);
            $job->update([
                'status'        => 'failed',
                'error_message' => $errorMsg,
            ]);
            return 1;

        } catch (\Exception $e) {
            $log->error("Job #{$jobId} excepción al conectar con Whisper.", [
                'mensaje' => $e->getMessage(),
                'clase'   => get_class($e),
            ]);
            $job->update([
                'status'        => 'failed',
                'error_message' => 'No se pudo conectar al servidor IA: ' . $e->getMessage(),
            ]);
            return 1;
        }
    }
}
