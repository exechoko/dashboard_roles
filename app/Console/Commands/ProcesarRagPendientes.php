<?php

namespace App\Console\Commands;

use App\Models\RagCargaJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarRagPendientes extends Command
{
    protected $signature   = 'rag:procesar-pendientes';
    protected $description = 'Procesa las cargas de documentos RAG pendientes (con resumen Ollama)';

    public function handle()
    {
        $log        = Log::channel('rag');
        $pendientes = RagCargaJob::where('status', 'pending')->orderBy('id')->get();

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $log->info("RAG scheduler: {$pendientes->count()} carga(s) pendiente(s).");

        $ragUrl = rtrim(config('services.ia.rag_url'), '/');

        foreach ($pendientes as $job) {
            $log->info("RAG job #{$job->id} iniciado.", [
                'archivo'   => $job->archivo_nombre,
                'coleccion' => $job->coleccion,
                'resumir'   => $job->resumir,
            ]);
            $job->update(['status' => 'processing']);

            $filePath = Storage::disk('local')->path($job->archivo_path);

            if (!file_exists($filePath)) {
                $log->error("RAG job #{$job->id} fallido: archivo no encontrado.", ['path' => $filePath]);
                $job->update([
                    'status'        => 'failed',
                    'error_message' => 'Archivo no encontrado en disco.',
                ]);
                continue;
            }

            try {
                $inicio   = microtime(true);
                $response = Http::timeout(300)
                    ->attach('file', fopen($filePath, 'r'), $job->archivo_nombre)
                    ->post($ragUrl . '/cargar', [
                        'coleccion' => $job->coleccion,
                        'resumir'   => $job->resumir ? 'true' : 'false',
                    ]);
                $elapsed = round(microtime(true) - $inicio, 2);

                if ($response->successful()) {
                    $job->update([
                        'status'          => 'completed',
                        'resumen'         => $response->json('resumen'),
                        'documentos_total'=> $response->json('documentos_total', 0),
                    ]);
                    Storage::disk('local')->delete($job->archivo_path);
                    $log->info("RAG job #{$job->id} completado.", [
                        'tiempo_s'        => $elapsed,
                        'documentos_total'=> $response->json('documentos_total'),
                    ]);
                } else {
                    $err = 'Error RAG (' . $response->status() . '): ' . substr($response->body(), 0, 300);
                    $log->error("RAG job #{$job->id} fallido.", ['http_status' => $response->status(), 'body' => substr($response->body(), 0, 300)]);
                    $job->update(['status' => 'failed', 'error_message' => $err]);
                }
            } catch (\Exception $e) {
                $log->error("RAG job #{$job->id} excepción.", ['mensaje' => $e->getMessage()]);
                $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }

        return 0;
    }
}
