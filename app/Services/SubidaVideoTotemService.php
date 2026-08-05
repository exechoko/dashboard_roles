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

        try {
            $hash = $this->hashService->calcularSha256DesdeRuta($rutaTemporal);

            $extension = pathinfo($activacion->nombre_archivo_original ?? '', PATHINFO_EXTENSION) ?: 'mp4';
            // Se agrega el momento de la subida (no solo expediente+fecha del evento):
            // si esta activación ya tuvo un video antes y se volvió a subir tras
            // "Marcar como eliminado", el nombre anterior podría seguir físicamente
            // en la carpeta (el borrado es manual) y no hay que arriesgarse a
            // sobreescribirlo en silencio.
            $nombreDestino = $activacion->nro_expediente . '_' . $activacion->fecha_evento->format('Ymd_His')
                . '_subido' . now()->format('YmdHis') . '.' . $extension;

            $carpetaDestino = $this->rutaBase . '\\' . $camara->carpeta_red;
            if (!file_exists($carpetaDestino)) {
                throw new RuntimeException("La carpeta de red no existe o no es accesible: {$carpetaDestino}");
            }

            $rutaDestino = $carpetaDestino . '\\' . $nombreDestino;

            if (!@copy($rutaTemporal, $rutaDestino)) {
                throw new RuntimeException("No se pudo copiar el video a la carpeta de red: {$rutaDestino}");
            }

            return [
                'ruta_archivo' => $rutaDestino,
                'hash_sha256' => $hash,
            ];
        } finally {
            @unlink($rutaTemporal);
        }
    }

    public function rutaTemporal(ActivacionTotem $activacion): string
    {
        return storage_path('app/totem-uploads-temp/' . $activacion->id . '_' . $activacion->nombre_archivo_original);
    }
}
