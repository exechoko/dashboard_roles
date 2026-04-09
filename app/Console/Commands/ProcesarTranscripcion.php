<?php

namespace App\Console\Commands;

use App\Models\TranscripcionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcesarTranscripcion extends Command
{
    protected $signature = 'transcribir:proceso {job_id}';
    protected $description = 'Procesa una transcripción de audio pendiente en background';

    public function handle()
    {
        $jobId = $this->argument('job_id');

        $job = TranscripcionJob::find($jobId);
        if (!$job) {
            $this->error("Job #{$jobId} no encontrado.");
            return 1;
        }

        if ($job->status !== 'pending') {
            $this->warn("Job #{$jobId} ya fue procesado (status: {$job->status}).");
            return 0;
        }

        $job->update(['status' => 'processing']);

        $audioPath = Storage::disk('local')->path($job->audio_path);

        if (!file_exists($audioPath)) {
            $job->update([
                'status'        => 'failed',
                'error_message' => 'Archivo de audio no encontrado en disco.',
            ]);
            return 1;
        }

        $url = config('services.ia.whisper_url') . '/inference';

        try {
            $response = Http::timeout(300)
                ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
                ->post($url, ['language' => 'es']);

            if ($response->successful()) {
                $job->update([
                    'status'           => 'completed',
                    'result_text'      => $response->json('text'),
                    'duration_seconds' => $response->json('duration'),
                ]);
                // Limpiar archivo temporal
                Storage::disk('local')->delete($job->audio_path);
                $this->info("Transcripción #{$jobId} completada.");
                return 0;
            }

            $job->update([
                'status'        => 'failed',
                'error_message' => 'Error en Whisper (' . $response->status() . '): ' . $response->body(),
            ]);
            return 1;

        } catch (\Exception $e) {
            $job->update([
                'status'        => 'failed',
                'error_message' => 'No se pudo conectar al servidor IA: ' . $e->getMessage(),
            ]);
            return 1;
        }
    }
}
