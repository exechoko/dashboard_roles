<?php

namespace App\Services;

use App\Models\ActivacionTotem;
use RuntimeException;

/**
 * Hashea y copia a la carpeta de red del tótem correspondiente el video ya
 * recibido en disco local. Corre desde el comando
 * `totem:procesar-videos-pendientes`, desacoplado del request HTTP que
 * recibió la subida.
 */
class SubidaVideoTotemService
{
    private const RUTA_BASE_DEFAULT = '\\\\193.169.1.247\\totems';

    public function __construct(
        private ArchivoHashService $hashService,
        private string $rutaBase = self::RUTA_BASE_DEFAULT,
    ) {
    }

    /**
     * No borra el archivo temporal: eso queda a cargo de quien llama, y solo
     * después de que el registro haya quedado guardado en la base. Así, si el
     * proceso se corta a mitad de camino (ej. se reinicia el servicio del
     * scheduler justo en ese momento), el archivo temporal sigue estando en
     * el próximo intento y se puede detectar que ya se había copiado.
     *
     * @return array{ruta_archivo: string, hash_sha256: string}
     */
    public function procesar(ActivacionTotem $activacion): array
    {
        $camara = $activacion->camara;

        if ($camara === null || empty($camara->carpeta_red)) {
            throw new RuntimeException('El tótem seleccionado no tiene carpeta de red configurada.');
        }

        $rutaTemporal = $this->rutaTemporal($activacion);

        if (!file_exists($rutaTemporal)) {
            throw new RuntimeException("No se encontró el archivo temporal: {$rutaTemporal}");
        }

        $hash = $this->hashService->calcularSha256DesdeRuta($rutaTemporal);

        $carpetaDestino = $this->rutaBase . '\\' . $camara->carpeta_red;
        if (!file_exists($carpetaDestino)) {
            throw new RuntimeException("La carpeta de red no existe o no es accesible: {$carpetaDestino}");
        }

        // Se conserva el nombre original tal cual lo exporta el sistema de
        // video (trae sus propios timestamps de inicio/fin).
        $nombreDestino = $activacion->nombre_archivo_original ?: ($activacion->nro_expediente . '.mp4');
        $rutaDestino = $carpetaDestino . '\\' . $nombreDestino;

        if (file_exists($rutaDestino)) {
            // Si el contenido ya es idéntico, es un reintento de un intento
            // anterior que llegó a copiar el archivo pero se cortó antes de
            // guardar el resultado en la base: no hay que volver a copiar ni
            // duplicar, este ya es el archivo correcto.
            if (hash_file('sha256', $rutaDestino) === $hash) {
                return [
                    'ruta_archivo' => $rutaDestino,
                    'hash_sha256' => $hash,
                ];
            }

            // Nombre coincide mas el contenido es distinto: no pisar en silencio.
            $info = pathinfo($nombreDestino);
            $base = $info['filename'];
            $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
            $sufijo = 2;
            do {
                $rutaDestino = $carpetaDestino . '\\' . $base . '_(' . $sufijo . ')' . $ext;
                $sufijo++;
            } while (file_exists($rutaDestino));
        }

        if (!@copy($rutaTemporal, $rutaDestino)) {
            throw new RuntimeException("No se pudo copiar el video a la carpeta de red: {$rutaDestino}");
        }

        return [
            'ruta_archivo' => $rutaDestino,
            'hash_sha256' => $hash,
        ];
    }

    public function rutaTemporal(ActivacionTotem $activacion): string
    {
        return storage_path('app/totem-uploads-temp/' . $activacion->id . '_' . $activacion->nombre_archivo_original);
    }
}
