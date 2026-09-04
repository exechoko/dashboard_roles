<?php

namespace App\Services;

use App\Models\Personal;
use Illuminate\Support\Str;

class PersonalPolicialMatcher
{
    /**
     * Cruza el personal policial que el resumen IA extrajo del texto libre
     * (jerarquía + apellido, tal como figuran en la novedad) contra la base de
     * personal911, para identificar de quién se trata.
     *
     * Nunca elige entre varios candidatos con el mismo apellido: si hay más de
     * una coincidencia, se listan todos para que un humano lo confirme.
     *
     * @param array<int, array{jerarquia: string, apellido: string, nombre: string, movil: string}> $mencionados
     * @param (callable(string $apellido, string $nombre): array<int, array{id: int, nombre_completo: string, jerarquia: string, lp: string}>)|null $buscador Inyectable para tests; por defecto consulta la tabla personals.
     * @return array<int, array{jerarquia: string, apellido: string, nombre: string, movil: string, estado: string, candidatos: array<int, array{id: int, nombre_completo: string, jerarquia: string, lp: string}>}>
     */
    public function cruzar(array $mencionados, ?callable $buscador = null): array
    {
        $buscador ??= fn (string $apellido, string $nombre): array => $this->buscarCandidatos($apellido, $nombre);

        return array_map(function (array $mencion) use ($buscador): array {
            $apellido = trim((string) ($mencion['apellido'] ?? ''));
            $nombre = trim((string) ($mencion['nombre'] ?? ''));

            $candidatos = $apellido !== '' ? $buscador($apellido, $nombre) : [];

            $estado = match (count($candidatos)) {
                0 => 'sin_coincidencia',
                1 => 'confirmado',
                default => 'ambiguo',
            };

            return [
                'jerarquia' => (string) ($mencion['jerarquia'] ?? ''),
                'apellido' => $apellido,
                'nombre' => $nombre,
                'movil' => (string) ($mencion['movil'] ?? ''),
                'estado' => $estado,
                'candidatos' => $candidatos,
            ];
        }, $mencionados);
    }

    /**
     * Busca en personal911 (tabla personals) por apellido, acotando por nombre
     * si se conoce. Comparación insensible a mayúsculas/acentos.
     *
     * @return array<int, array{id: int, nombre_completo: string, jerarquia: string, lp: string}>
     */
    private function buscarCandidatos(string $apellido, string $nombre): array
    {
        $query = Personal::query()
            ->activos()
            ->whereRaw('LOWER(apellido) LIKE ?', ['%' . $this->normalizar($apellido) . '%']);

        if ($nombre !== '') {
            $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . $this->normalizar($nombre) . '%']);
        }

        return $query->orderBy('apellido')->limit(6)->get(['id', 'nombre', 'apellido', 'jerarquia', 'lp'])
            ->map(fn (Personal $p): array => [
                'id' => $p->id,
                'nombre_completo' => trim($p->jerarquia . ' ' . $p->apellido . ', ' . $p->nombre),
                'jerarquia' => (string) $p->jerarquia,
                'lp' => (string) $p->lp,
            ])
            ->all();
    }

    private function normalizar(string $texto): string
    {
        $texto = Str::lower(trim($texto));
        $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        return $transliterado !== false ? $transliterado : $texto;
    }
}
