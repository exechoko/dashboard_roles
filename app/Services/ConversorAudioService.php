<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Convierte audios GSM, OGG y WAV a MP3 con el conversor configurado en
 * grabador.ffmpeg_path (el mismo que convierte los WAV de las modulaciones
 * TETRA): FFmpeg, o LAME + grabador.decoder_path para decodificar GSM/OGG.
 */
class ConversorAudioService
{
    /**
     * Formatos que el conversor configurado sabe producir. LAME (usado en
     * producción, servidor viejo donde FFmpeg no corre) solo codifica desde
     * WAV/PCM: GSM y OGG necesitan pasar antes por grabador.decoder_path.
     *
     * @return array<int, string>
     */
    public function formatosSoportados(): array
    {
        if (!$this->esConversorLame()) {
            return ['gsm', 'ogg', 'wav'];
        }

        return $this->decoderPath() !== null ? ['gsm', 'ogg', 'wav'] : ['wav'];
    }

    private function esConversorLame(): bool
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');

        return str_contains(strtolower(basename($conversor)), 'lame');
    }

    private function decoderPath(): ?string
    {
        $decoder = (string) config('grabador.decoder_path', '');

        return $decoder !== '' ? $decoder : null;
    }

    /**
     * Convierte un archivo subido a MP3. Devuelve la ruta al MP3 temporal
     * generado (responsabilidad del caller borrarlo) o null si el conversor
     * no soporta el formato de entrada, no está disponible o la conversión
     * falla.
     */
    public function convertirAMp3(UploadedFile $archivo): ?string
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');
        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        $esLame    = $this->esConversorLame();

        if (!in_array($extension, $this->formatosSoportados(), true)) {
            Log::warning('ConversorAudioService: formato no soportado por el conversor configurado', [
                'archivo'   => $archivo->getClientOriginalName(),
                'extension' => $extension,
                'conversor' => $conversor,
            ]);

            return null;
        }

        $baseTmp = tempnam(sys_get_temp_dir(), 'conv');

        if ($baseTmp === false) {
            return null;
        }

        // FFmpeg detecta el formato de entrada por la extensión (crítico para
        // GSM, que no tiene cabecera propia); el archivo subido por PHP no la
        // conserva, así que se copia a un temporal con la extensión original.
        $tmpOrigen = $baseTmp . '.' . $extension;
        $tmpMp3    = $baseTmp . '.mp3';
        $tmpWavDecodificado = null;

        try {
            if (!copy($archivo->getRealPath(), $tmpOrigen)) {
                return null;
            }

            // LAME solo lee WAV/PCM: GSM y OGG se pasan antes por el decoder
            // configurado (ej. sox.exe) para obtener un WAV intermedio.
            $entradaParaLame = $tmpOrigen;

            if ($esLame && $extension !== 'wav') {
                $tmpWavDecodificado = $baseTmp . '_dec.wav';
                $cmdDecode = escapeshellarg((string) $this->decoderPath())
                    . ' ' . escapeshellarg($tmpOrigen) . ' ' . escapeshellarg($tmpWavDecodificado) . ' 2>&1';

                exec($cmdDecode, $outputDecode, $exitDecode);

                if ($exitDecode !== 0 || !is_file($tmpWavDecodificado) || filesize($tmpWavDecodificado) === 0) {
                    Log::warning('ConversorAudioService: decodificación previa a LAME falló', [
                        'archivo'  => $archivo->getClientOriginalName(),
                        'decoder'  => $this->decoderPath(),
                        'exit'     => $exitDecode,
                        'salida'   => implode(' ', array_slice($outputDecode ?? [], -5)),
                    ]);

                    return null;
                }

                $entradaParaLame = $tmpWavDecodificado;
            }

            $cmd = $esLame
                ? escapeshellarg($conversor)
                    . ' --silent -b 128 '
                    . escapeshellarg($entradaParaLame) . ' ' . escapeshellarg($tmpMp3) . ' 2>&1'
                : escapeshellarg($conversor)
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

            if ($tmpWavDecodificado !== null) {
                @unlink($tmpWavDecodificado);
            }
        }
    }
}
