<?php

namespace App\Services\Descargas;

use App\Models\DescargaArchivo;
use App\Models\DescargaVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DescargaRepositorio
{
    private const DISK = 'descargas';

    public function subirArchivo(UploadedFile $archivo, array $data): DescargaArchivo
    {
        return DB::transaction(function () use ($archivo, $data) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
            $nombreSanitizado = $this->sanitizarNombre(pathinfo($nombreOriginal, PATHINFO_FILENAME));
            $nombreUnico = $this->generarNombreUnico($nombreSanitizado, $extension);
            $ruta = $this->determinarRuta(now());
            $rutaCompleta = $ruta . '/' . $nombreUnico;

            $archivo->storeAs($ruta, $nombreUnico, self::DISK);

            $descargaArchivo = DescargaArchivo::create([
                'categoria_id' => $data['categoria_id'],
                'nombre_original' => $nombreOriginal,
                'nombre_archivo' => $nombreUnico,
                'ruta_relativa' => $rutaCompleta,
                'mime_type' => $archivo->getMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $archivo->getSize(),
                'descripcion' => $data['descripcion'] ?? null,
                'destacado' => $data['destacado'] ?? false,
                'user_id' => $data['user_id'],
                'expira_at' => $data['expira_at'] ?? null,
                'activo' => true,
            ]);

            if (!empty($data['roles'])) {
                $descargaArchivo->roles()->sync($data['roles']);
            }

            if (!empty($data['tags'])) {
                $this->sincronizarTags($descargaArchivo, $data['tags']);
            }

            return $descargaArchivo;
        });
    }

    public function reemplazarArchivo(DescargaArchivo $archivoActual, UploadedFile $nuevoArchivo, ?int $userId, ?string $motivo = null): DescargaArchivo
    {
        return DB::transaction(function () use ($archivoActual, $nuevoArchivo, $userId, $motivo) {
            $versionNumero = $archivoActual->versiones()->count() + 1;

            DescargaVersion::create([
                'archivo_id' => $archivoActual->id,
                'version_numero' => $versionNumero,
                'nombre_archivo_anterior' => $archivoActual->nombre_archivo,
                'ruta_anterior' => $archivoActual->ruta_relativa,
                'tamano_anterior' => $archivoActual->tamano_bytes,
                'user_id' => $userId,
                'motivo' => $motivo,
                'created_at' => now(),
            ]);

            $extension = strtolower($nuevoArchivo->getClientOriginalExtension() ?: $nuevoArchivo->extension());
            $nombreSanitizado = $this->sanitizarNombre(pathinfo($nuevoArchivo->getClientOriginalName(), PATHINFO_FILENAME));
            $nombreUnico = $this->generarNombreUnico($nombreSanitizado, $extension);
            $ruta = $this->determinarRuta(now());
            $rutaCompleta = $ruta . '/' . $nombreUnico;

            $nuevoArchivo->storeAs($ruta, $nombreUnico, self::DISK);

            $archivoActual->update([
                'nombre_original' => $nuevoArchivo->getClientOriginalName(),
                'nombre_archivo' => $nombreUnico,
                'ruta_relativa' => $rutaCompleta,
                'mime_type' => $nuevoArchivo->getMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $nuevoArchivo->getSize(),
            ]);

            return $archivoActual;
        });
    }

    public function cargarComoCopia(DescargaArchivo $archivoOriginal, UploadedFile $nuevoArchivo, array $data): DescargaArchivo
    {
        $nombreOriginal = $nuevoArchivo->getClientOriginalName();
        $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $extension = strtolower($nuevoArchivo->getClientOriginalExtension() ?: $nuevoArchivo->extension());

        $contador = 1;
        $nombreCopia = $nombreBase . '(' . $contador . ')';

        while (DescargaArchivo::where('nombre_original', 'like', $nombreBase . '(' . $contador . ').%')
            ->whereYear('created_at', now()->year)
            ->exists()) {
            $contador++;
            $nombreCopia = $nombreBase . '(' . $contador . ')';
        }

        $nombreUnico = $this->generarNombreUnico($nombreCopia, $extension);
        $ruta = $this->determinarRuta(now());
        $rutaCompleta = $ruta . '/' . $nombreUnico;

        $nuevoArchivo->storeAs($ruta, $nombreUnico, self::DISK);

        return DB::transaction(function () use ($rutaCompleta, $nombreUnico, $nombreCopia, $extension, $nuevoArchivo, $data) {
            $descargaArchivo = DescargaArchivo::create([
                'categoria_id' => $data['categoria_id'],
                'nombre_original' => $nombreCopia . '.' . $extension,
                'nombre_archivo' => $nombreUnico,
                'ruta_relativa' => $rutaCompleta,
                'mime_type' => $nuevoArchivo->getMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $nuevoArchivo->getSize(),
                'descripcion' => $data['descripcion'] ?? null,
                'destacado' => $data['destacado'] ?? false,
                'user_id' => $data['user_id'],
                'expira_at' => $data['expira_at'] ?? null,
                'activo' => true,
            ]);

            if (!empty($data['roles'])) {
                $descargaArchivo->roles()->sync($data['roles']);
            }

            if (!empty($data['tags'])) {
                $this->sincronizarTags($descargaArchivo, $data['tags']);
            }

            return $descargaArchivo;
        });
    }

    public function verificarConflicto(string $nombreOriginal): ?DescargaArchivo
    {
        $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

        return DescargaArchivo::where('nombre_original', $nombreOriginal)
            ->whereYear('created_at', now()->year)
            ->first();
    }

    public function obtenerRutaAbsoluta(DescargaArchivo $archivo): string
    {
        return Storage::disk(self::DISK)->path($archivo->ruta_relativa);
    }

    public function obtenerStream(DescargaArchivo $archivo)
    {
        return Storage::disk(self::DISK)->readStream($archivo->ruta_relativa);
    }

    public function existeArchivo(DescargaArchivo $archivo): bool
    {
        return Storage::disk(self::DISK)->exists($archivo->ruta_relativa);
    }

    public function eliminarArchivo(DescargaArchivo $archivo): bool
    {
        return DB::transaction(function () use ($archivo) {
            Storage::disk(self::DISK)->delete($archivo->ruta_relativa);

            foreach ($archivo->versiones as $version) {
                Storage::disk(self::DISK)->delete($version->ruta_anterior);
            }

            return $archivo->delete();
        });
    }

    public function desactivarExpirados(): int
    {
        $expirados = DescargaArchivo::where('activo', true)
            ->where('expira_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expirados as $archivo) {
            $archivo->update(['activo' => false]);
            $count++;
        }

        return $count;
    }

    public function reactivarArchivo(DescargaArchivo $archivo): void
    {
        $archivo->update([
            'activo' => true,
            'expira_at' => null,
        ]);
    }

    private function generarNombreUnico(string $nombreBase, string $extension): string
    {
        $hash = Str::random(12);
        return $hash . '-' . $nombreBase . '.' . $extension;
    }

    private function determinarRuta(Carbon $fecha): string
    {
        return $fecha->format('Y') . '/' . $fecha->format('m');
    }

    private function sanitizarNombre(string $nombre): string
    {
        $nombre = Str::ascii($nombre);
        $nombre = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $nombre);
        $nombre = preg_replace('/\s+/', '-', trim($nombre));
        $nombre = preg_replace('/-+/', '-', $nombre);

        return Str::lower($nombre) ?: 'archivo';
    }

    private function sincronizarTags(DescargaArchivo $archivo, array $tags): void
    {
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = \App\Models\DescargaTag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['nombre' => $tagName]
            );
            $tagIds[] = $tag->id;
        }
        $archivo->tags()->sync($tagIds);
    }
}
