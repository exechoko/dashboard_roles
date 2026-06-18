<?php

namespace App\Services;

use App\Models\Noticia;
use RuntimeException;

class GeneradorNoticiasJson
{
    /**
     * Regenera el archivo noticias.json que consume la web estática.
     * Incluye solo las noticias publicadas, las más recientes primero.
     */
    public function generar(): string
    {
        $ruta = $this->rutaArchivo();
        $directorio = dirname($ruta);

        if (! is_dir($directorio)) {
            @mkdir($directorio, 0775, true);
        }
        if (! is_dir($directorio) || ! is_writable($directorio)) {
            throw new RuntimeException("No se puede escribir en el directorio de noticias: {$directorio}");
        }

        $noticias = Noticia::query()
            ->where('publicada', true)
            ->with('imagenes')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Noticia $noticia): array => $this->mapearNoticia($noticia))
            ->all();

        $payload = [
            'generado' => now()->toIso8601String(),
            'noticias' => $noticias,
        ];

        $contenido = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $temporal = $ruta . '.tmp';
        if (file_put_contents($temporal, $contenido) === false) {
            throw new RuntimeException("No se pudo escribir el archivo temporal: {$temporal}");
        }
        if (! @rename($temporal, $ruta)) {
            @unlink($temporal);
            throw new RuntimeException("No se pudo reemplazar el archivo: {$ruta}");
        }

        return $ruta;
    }

    public function rutaArchivo(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.noticias_json'));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearNoticia(Noticia $noticia): array
    {
        $imgDir = trim(config('landing.noticias_img_dir'), '/');

        $rutaImagen = static fn (string $archivo): string => $imgDir . '/' . $archivo;

        $miniatura = $noticia->imagenes->firstWhere('es_miniatura', true)
            ?? $noticia->imagenes->first();

        return [
            'id'          => $noticia->id,
            'titulo'      => $noticia->titulo,
            'bajada'      => $noticia->bajada,
            'cuerpo'      => $noticia->cuerpo,
            'fecha'       => optional($noticia->fecha_publicacion)->toDateString(),
            'fecha_texto' => optional($noticia->fecha_publicacion)?->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'miniatura'   => $miniatura ? $rutaImagen($miniatura->archivo) : null,
            'imagenes'    => $noticia->imagenes->map(fn ($img): string => $rutaImagen($img->archivo))->values()->all(),
        ];
    }
}
