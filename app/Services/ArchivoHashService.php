<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ArchivoHashService
{
    private const CHUNK_SIZE = 1048576;

    public function calcularSha256(UploadedFile $archivo): string
    {
        $ruta = $archivo->getRealPath();

        if ($ruta === false) {
            throw new RuntimeException('No se pudo acceder al archivo temporal.');
        }

        return $this->calcularSha256DesdeRuta($ruta);
    }

    public function calcularSha256DesdeRuta(string $ruta): string
    {
        $stream = fopen($ruta, 'rb');

        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir el archivo para calcular su hash.');
        }

        $contexto = hash_init('sha256');

        try {
            while (!feof($stream)) {
                $fragmento = fread($stream, self::CHUNK_SIZE);

                if ($fragmento === false) {
                    throw new RuntimeException('No se pudo leer el archivo completo.');
                }

                if ($fragmento === '') {
                    if (feof($stream)) {
                        break;
                    }

                    throw new RuntimeException('La lectura del archivo se interrumpió.');
                }

                hash_update($contexto, $fragmento);
            }

            return hash_final($contexto);
        } finally {
            fclose($stream);
        }
    }
}
