<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Convierte audios GSM, OGG y WAV a MP3 con FFmpeg (mismo binario configurado
 * en grabador.ffmpeg_path para convertir los WAV de las modulaciones TETRA).
 */
class ConversorAudioService
{
    /**
     * Convierte un archivo subido a MP3. Devuelve la ruta al MP3 temporal
     * generado (responsabilidad del caller borrarlo) o null si FFmpeg no está
     * disponible o la conversión falla.
     */
    public function convertirAMp3(UploadedFile $archivo): ?string
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');
        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        $baseTmp   = tempnam(sys_get_temp_dir(), 'conv');

        if ($baseTmp === false) {
            return null;
        }

        // FFmpeg detecta el formato de entrada por la extensión (crítico para
        // GSM, que no tiene cabecera propia); el archivo subido por PHP no la
        // conserva, así que se copia a un temporal con la extensión original.
        $tmpOrigen = $baseTmp . '.' . $extension;
        $tmpMp3    = $baseTmp . '.mp3';

        try {
            if (!copy($archivo->getRealPath(), $tmpOrigen)) {
                return null;
            }

            $cmd = escapeshellarg($conversor)
                . ' -y -i ' . escapeshellarg($tmpOrigen)
                . ' -codec:a libmp3lame -b:a 128k '
                . escapeshellarg($tmpMp3) . ' 2>&1';

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0 || !is_file($tmpMp3) || filesize($tmpMp3) === 0) {
                Log::warning('ConversorAudioService: conversión a MP3 falló', [
                    'archivo'   => $archivo->getClientOriginalName(),
                    'conversor' => $conversor,
                    'exit'      => $exitCode,
                    'salida'    => implode(' ', array_slice($output ?? [], -5)),
                ]);

                @unlink($tmpMp3);

                return null;
            }

            return $tmpMp3;
        } catch (\Exception $e) {
            Log::warning('ConversorAudioService: error al convertir a MP3', [
                'archivo' => $archivo->getClientOriginalName(),
                'error'   => $e->getMessage(),
            ]);

            return null;
        } finally {
            @unlink($baseTmp);
            @unlink($tmpOrigen);
        }
    }
}
