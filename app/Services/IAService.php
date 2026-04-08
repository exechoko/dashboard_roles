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

    public function __construct()
    {
        $this->whisperUrl  = rtrim(config('services.ia.whisper_url'), '/');
        $this->ragUrl      = rtrim(config('services.ia.rag_url'), '/');
        $this->ollamaUrl   = rtrim(config('services.ia.ollama_url'), '/');
        $this->ollamaModel = config('services.ia.ollama_model', 'llama3.2:3b');
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
        $response = Http::timeout(300)
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
     * Sube un archivo al RAG: lo indexa en ChromaDB y genera un resumen con Ollama.
     *
     * @return array{archivo: string, resumen: string|null, documentos_total: int}
     * @throws \RuntimeException
     */
    public function cargarDocumento(UploadedFile $archivo, bool $resumir = true): array
    {
        $response = Http::timeout(180)
            ->attach('file', fopen($archivo->getRealPath(), 'r'), $archivo->getClientOriginalName())
            ->post($this->ragUrl . '/cargar', ['resumir' => $resumir ? 'true' : 'false']);

        if ($response->failed()) {
            Log::error('IAService::cargarDocumento — RAG error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Error al cargar en RAG (' . $response->status() . '): ' . $response->body());
        }

        return [
            'archivo'          => $response->json('archivo', $archivo->getClientOriginalName()),
            'resumen'          => $response->json('resumen'),
            'documentos_total' => $response->json('documentos_total', 0),
            'caracteres'       => $response->json('caracteres', 0),
        ];
    }

    /**
     * Hace una pregunta al RAG y obtiene una respuesta basada en los documentos indexados.
     *
     * @throws \RuntimeException
     */
    public function consultarRAG(string $pregunta): string
    {
        $response = Http::timeout(60)
            ->post($this->ragUrl . '/preguntar', ['pregunta' => $pregunta]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al consultar RAG (' . $response->status() . ')');
        }

        return $response->json('respuesta', '');
    }

    /**
     * Re-indexa todos los documentos de DOCS_DIR en ChromaDB.
     */
    public function reindexarRAG(): int
    {
        $response = Http::timeout(120)->post($this->ragUrl . '/indexar');

        if ($response->failed()) {
            throw new \RuntimeException('Error al re-indexar RAG (' . $response->status() . ')');
        }

        return $response->json('documentos', 0);
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
            'whisper' => $this->whisperDisponible(),
            'rag'     => $this->ragDisponible(),
            'ollama'  => $this->ollamaDisponible(),
        ];
    }
}
