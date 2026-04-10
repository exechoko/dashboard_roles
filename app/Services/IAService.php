<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IAService
{
    private string $whisperUrl;
    private string $ragUrl;
    private string $ollamaUrl;
    private string $ollamaModel;
    private string $callAnalysisUrl;

    public function __construct()
    {
        $this->whisperUrl      = rtrim(config('services.ia.whisper_url'), '/');
        $this->ragUrl          = rtrim(config('services.ia.rag_url'), '/');
        $this->ollamaUrl       = rtrim(config('services.ia.ollama_url'), '/');
        $this->ollamaModel     = config('services.ia.ollama_model', 'llama3.2:3b');
        $this->callAnalysisUrl = rtrim(config('services.ia.call_analysis_url'), '/');
    }

    // ─── Whisper ────────────────────────────────────────────────────────────

    /**
     * Transcribe un archivo de audio usando Whisper en el servidor IA.
     *
     * @return array{text: string, language: string, duration: float}
     * @throws \RuntimeException
     */
    public function transcribirAudio(UploadedFile $archivo, string $idioma = 'es'): array
    {
        $response = Http::timeout(900)
            ->attach('file', fopen($archivo->getRealPath(), 'r'), $archivo->getClientOriginalName())
            ->post($this->whisperUrl . '/inference', ['language' => $idioma]);

        if ($response->failed()) {
            Log::error('IAService::transcribirAudio — Whisper error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Error en Whisper (' . $response->status() . '): ' . $response->body());
        }

        return [
            'text'     => $response->json('text', ''),
            'language' => $response->json('language', $idioma),
            'duration' => $response->json('duration', 0),
        ];
    }

    /**
     * Verifica que el servidor Whisper esté disponible.
     */
    public function whisperDisponible(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->whisperUrl . '/health');
            return $response->successful() && $response->json('status') === 'ok';
        } catch (\Exception) {
            return false;
        }
    }

    // ─── RAG ────────────────────────────────────────────────────────────────

    /**
     * Sube un archivo al RAG: lo indexa en ChromaDB bajo la colección indicada
     * y genera un resumen con Ollama.
     *
     * @return array{archivo: string, coleccion: string, resumen: string|null, documentos_total: int, caracteres: int}
     * @throws \RuntimeException
     */
    public function cargarDocumento(UploadedFile $archivo, bool $resumir = true, string $coleccion = 'general'): array
    {
        $response = Http::timeout(180)
            ->attach('file', fopen($archivo->getRealPath(), 'r'), $archivo->getClientOriginalName())
            ->post($this->ragUrl . '/cargar', [
                'resumir'   => $resumir ? 'true' : 'false',
                'coleccion' => $coleccion,
            ]);

        if ($response->failed()) {
            Log::error('IAService::cargarDocumento — RAG error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Error al cargar en RAG (' . $response->status() . '): ' . $response->body());
        }

        return [
            'archivo'          => $response->json('archivo', $archivo->getClientOriginalName()),
            'coleccion'        => $response->json('coleccion', $coleccion),
            'resumen'          => $response->json('resumen'),
            'documentos_total' => $response->json('documentos_total', 0),
            'caracteres'       => $response->json('caracteres', 0),
        ];
    }

    /**
     * Hace una pregunta al RAG sobre una colección específica.
     *
     * @throws \RuntimeException
     */
    public function consultarRAG(string $pregunta, string $coleccion = 'general'): string
    {
        $log = Log::channel('rag');
        $log->info('RAG consulta iniciada.', ['coleccion' => $coleccion, 'pregunta' => substr($pregunta, 0, 100)]);

        try {
            $inicio   = microtime(true);
            $response = Http::timeout(90)   // 90s < 100s límite de Cloudflare
                ->post($this->ragUrl . '/preguntar', [
                    'pregunta'  => $pregunta,
                    'coleccion' => $coleccion,
                ]);
            $elapsed = round(microtime(true) - $inicio, 2);

            if ($response->failed()) {
                $log->error('RAG consulta fallida.', ['status' => $response->status(), 'body' => substr($response->body(), 0, 300)]);
                throw new \RuntimeException('Error al consultar RAG (' . $response->status() . '): ' . $response->body());
            }

            $log->info('RAG consulta completada.', ['tiempo_s' => $elapsed, 'coleccion' => $coleccion]);
            return $response->json('respuesta', '');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $log->error('RAG consulta: timeout o conexión fallida.', ['mensaje' => $e->getMessage()]);
            throw new \RuntimeException('El servidor IA tardó demasiado en responder. Intentá de nuevo o usá una pregunta más corta.');
        }
    }

    /**
     * Re-indexa documentos desde disco. Si se pasa coleccion, solo esa; si no, todas.
     */
    public function reindexarRAG(?string $coleccion = null): int
    {
        $body     = $coleccion ? ['coleccion' => $coleccion] : [];
        $response = Http::timeout(120)->post($this->ragUrl . '/indexar', $body);

        if ($response->failed()) {
            throw new \RuntimeException('Error al re-indexar RAG (' . $response->status() . ')');
        }

        return $response->json('documentos', 0);
    }

    /**
     * Lista las colecciones disponibles en ChromaDB con cantidad de chunks.
     *
     * @return array<array{nombre: string, documentos: int}>
     */
    public function listarColecciones(): array
    {
        try {
            $response = Http::timeout(10)->get($this->ragUrl . '/colecciones');
            return $response->successful() ? ($response->json('colecciones') ?? []) : [];
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Verifica que el servidor RAG esté disponible.
     */
    public function ragDisponible(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->ragUrl . '/health');
            return $response->successful() && $response->json('status') === 'ok';
        } catch (\Exception) {
            return false;
        }
    }

    // ─── Ollama ─────────────────────────────────────────────────────────────

    /**
     * Genera texto con Ollama (sin historial).
     *
     * @throws \RuntimeException
     */
    public function generarTexto(string $prompt, ?string $modelo = null): string
    {
        $response = Http::timeout(120)->post($this->ollamaUrl . '/api/generate', [
            'model'  => $modelo ?? $this->ollamaModel,
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error en Ollama (' . $response->status() . ')');
        }

        return $response->json('response', '');
    }

    /**
     * Verifica que Ollama esté disponible.
     */
    public function ollamaDisponible(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->ollamaUrl . '/api/tags');
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Devuelve el estado de los tres servicios del servidor IA.
     */
    public function estadoServicios(): array
    {
        return [
            'whisper'      => $this->whisperDisponible(),
            'rag'          => $this->ragDisponible(),
            'ollama'       => $this->ollamaDisponible(),
            'callanalysis' => $this->callAnalysisDisponible(),
        ];
    }

    // ─── Call Analysis ──────────────────────────────────────────────────────

    /**
     * Envía un audio al servidor de análisis de llamadas 911.
     * Retorna el JSON completo de la respuesta (transcripción, resumen, datos extraídos).
     *
     * @throws \RuntimeException
     */
    public function analizarLlamada(string $audioPath, string $nombreArchivo): array
    {
        $response = Http::timeout(700)
            ->attach('file', fopen($audioPath, 'r'), $nombreArchivo)
            ->post($this->callAnalysisUrl . '/analyze');

        if ($response->status() === 422) {
            throw new \RuntimeException('El audio no pudo transcribirse (archivo dañado o sin voz).');
        }

        if ($response->failed()) {
            Log::error('IAService::analizarLlamada — CallAnalysis error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            throw new \RuntimeException('Error en CallAnalysis (' . $response->status() . '): ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Verifica que el servidor CallAnalysis esté disponible.
     */
    public function callAnalysisDisponible(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->callAnalysisUrl . '/health');
            return $response->successful() && $response->json('status') === 'ok';
        } catch (\Exception) {
            return false;
        }
    }
}
