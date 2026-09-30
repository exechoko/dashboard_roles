<?php

namespace App\Services\Descargas;

use Illuminate\Support\Facades\Log;

/**
 * Transcodifica a H.264/AAC los videos subidos en códecs que los navegadores
 * no reproducen de forma nativa en <video> (típico: HEVC/H.265 de celulares).
 * Usa el mismo conversor configurado en grabador.ffmpeg_path (FFmpeg, igual
 * que ConversorAudioService); si apunta a LAME (fallback de audio en el
 * servidor viejo) o la conversión falla, no se toca el archivo original —
 * se pierde la vista previa inline, pero la descarga sigue funcionando.
 */
class DescargaVideoTranscoder
{
    // Códecs que los navegadores soportan de forma nativa en <video>.
    private const CODECS_COMPATIBLES = ['h264', 'vp8', 'vp9', 'av1'];

    public function haceFaltaTranscodificar(string $rutaAbsoluta): bool
    {
        if ($this->esConversorLame()) {
            return false;
        }

        $codec = $this->detectarCodecVideo($rutaAbsoluta);

        return $codec !== null && !in_array($codec, self::CODECS_COMPATIBLES, true);
    }

    /**
     * Genera una copia H.264/AAC del video en un archivo temporal. Devuelve
     * la ruta al temporal generado (responsabilidad del caller moverlo y
     * borrarlo), o null si la conversión falla.
     */
    public function transcodificar(string $rutaAbsolutaOrigen): ?string
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');
        $tmpSalida = tempnam(sys_get_temp_dir(), 'descvid') . '.mp4';

        $cmd = escapeshellarg($conversor)
            . ' -y -i ' . escapeshellarg($rutaAbsolutaOrigen)
            . ' -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart '
            . escapeshellarg($tmpSalida) . ' 2>&1';

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !is_file($tmpSalida) || filesize($tmpSalida) === 0) {
            Log::warning('DescargaVideoTranscoder: conversión a H.264 falló', [
                'archivo' => $rutaAbsolutaOrigen,
                'conversor' => $conversor,
                'exit' => $exitCode,
                'salida' => implode(' ', array_slice($output ?? [], -5)),
            ]);

            @unlink($tmpSalida);

            return null;
        }

        return $tmpSalida;
    }

    private function esConversorLame(): bool
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');

        return str_contains(strtolower(basename($conversor)), 'lame');
    }

    private function detectarCodecVideo(string $rutaAbsoluta): ?string
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');

        // Sin archivo de salida, ffmpeg termina en error — pero antes
        // imprime por stderr la info de los streams (ahí sale "Video: hevc").
        $cmd = escapeshellarg($conversor) . ' -i ' . escapeshellarg($rutaAbsoluta) . ' 2>&1';
        exec($cmd, $output, $exitCode);

        $texto = implode("\n", $output ?? []);

        if (preg_match('/Video:\s*([a-z0-9_]+)/i', $texto, $match)) {
            return strtolower($match[1]);
        }

        Log::warning('DescargaVideoTranscoder: no se pudo detectar el códec de video', [
            'archivo' => $rutaAbsoluta,
        ]);

        return null;
    }
}
