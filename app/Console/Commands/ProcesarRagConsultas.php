<?php

namespace App\Console\Commands;

use App\Models\RagChatMensaje;
use App\Models\RagConsultaJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcesarRagConsultas extends Command
{
    protected $signature   = 'rag:consultar-pendientes';
    protected $description = 'Procesa las consultas RAG pendientes (sin límite de Cloudflare)';

    public function handle()
    {
        $log        = Log::channel('rag');
        $pendientes = RagConsultaJob::where('status', 'pending')->orderBy('id')->get();

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $log->info("RAG consultas: {$pendientes->count()} pendiente(s).");

        $ragUrl = rtrim(config('services.ia.rag_url'), '/');

        foreach ($pendientes as $job) {
            $log->info("RAG consulta #{$job->id} iniciada.", [
                'coleccion' => $job->coleccion,
                'pregunta'  => substr($job->pregunta, 0, 80),
            ]);
            $job->update(['status' => 'processing']);

            try {
                $inicio   = microtime(true);
                $response = Http::timeout(600)   // sin Cloudflare: hasta 10 min
                    ->post($ragUrl . '/preguntar', [
                        'pregunta'  => $job->pregunta,
                        'coleccion' => $job->coleccion,
                    ]);
                $elapsed = round(microtime(true) - $inicio, 2);

                if ($response->successful()) {
                    $respuesta = $response->json('respuesta', '');
                    $job->update(['status' => 'completed', 'respuesta' => $respuesta]);

                    if ($job->user_id) {
                        RagChatMensaje::create([
                            'user_id'   => $job->user_id,
                            'coleccion' => $job->coleccion,
                            'role'      => 'assistant',
                            'contenido' => $respuesta,
                        ]);
                    }
                    $log->info("RAG consulta #{$job->id} completada.", ['tiempo_s' => $elapsed]);
                } else {
                    $err = 'Error RAG (' . $response->status() . '): ' . substr($response->body(), 0, 300);
                    $log->error("RAG consulta #{$job->id} fallida.", ['body' => substr($response->body(), 0, 300)]);
                    $job->update(['status' => 'failed', 'error_message' => $err]);
                }
            } catch (\Exception $e) {
                $log->error("RAG consulta #{$job->id} excepción.", ['mensaje' => $e->getMessage()]);
                $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }

        return 0;
    }
}
