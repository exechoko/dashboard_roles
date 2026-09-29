<?php

namespace App\Services;

use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\Recurso;
use App\Models\TipoMovimiento;
use App\Models\TipoTerminal;
use App\Models\Vehiculo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginador;

/**
 * Catálogos que alimentan el componente <x-combobox-ajax>.
 *
 * Cada catálogo es una consulta Eloquent o una lista fija [id => texto]; ambos responden
 * con el mismo formato paginado.
 */
class OpcionesComboboxService
{
    public const PATRIMONIO = [
        'sin_patrimoniar' => 'Sin patrimoniar',
        'patrimoniado' => 'Patrimoniado (Firmado/Sin firma req.)',
        'pendiente' => 'Pendiente de firma',
    ];

    /**
     * @param  array<string, mixed>  $filtros  filtros opcionales; solo se aplican los que declara el catálogo
     */
    public function buscar(string $catalogo, string $termino, int $perPage, array $filtros = []): LengthAwarePaginator
    {
        $definicion = $this->definicion($catalogo);

        if (isset($definicion['lista'])) {
            $opciones = collect($definicion['lista'])
                ->filter(fn (string $texto) => $termino === '' || stripos($texto, $termino) !== false)
                ->map(fn (string $texto, string $id) => ['id' => $id, 'label' => $texto])
                ->values();

            return new Paginador($opciones, $opciones->count(), max($opciones->count(), 1));
        }

        $consulta = $definicion['consulta']();

        foreach ($definicion['filtros'] ?? [] as $nombre => $aplicar) {
            if (! empty($filtros[$nombre])) {
                $aplicar($consulta);
            }
        }

        $paginado = $consulta
            ->when($termino !== '', fn (Builder $query) => $definicion['buscar']($query, $termino))
            ->paginate($perPage);

        $paginado->getCollection()->transform(fn ($modelo) => [
            'id' => $modelo->id,
            'label' => $definicion['etiqueta']($modelo),
        ]);

        return $paginado;
    }

    /**
     * Ítems {id, text} de los valores ya elegidos, para precargar el combobox.
     *
     * @param  array<int, int|string|null>  $ids
     * @return array<int, array{id: int|string, text: string}>
     */
    public function seleccionados(string $catalogo, array $ids): array
    {
        $ids = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));

        if (empty($ids)) {
            return [];
        }

        $definicion = $this->definicion($catalogo);

        if (isset($definicion['lista'])) {
            return collect($ids)
                ->filter(fn ($id) => isset($definicion['lista'][$id]))
                ->map(fn ($id) => ['id' => $id, 'text' => $definicion['lista'][$id]])
                ->values()
                ->all();
        }

        return $definicion['consulta']()
            ->whereIn('id', $ids)
            ->get()
            ->map(fn ($modelo) => ['id' => $modelo->id, 'text' => $definicion['etiqueta']($modelo)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function definicion(string $catalogo): array
    {
        return match ($catalogo) {
            'equipos' => [
                'consulta' => fn () => Equipo::select('id', 'tei', 'issi', 'tipo_terminal_id')
                    ->with('tipo_terminal:id,marca,modelo,tipo_uso_id', 'tipo_terminal.tipo_uso:id,uso')
                    ->orderBy('tei', 'desc'),
                'filtros' => [
                    'sin_flota' => fn (Builder $query) => $query->doesntHave('flota_general'),
                ],
                'buscar' => function (Builder $query, string $termino) {
                    $tiposTerminal = TipoTerminal::where('marca', 'like', "%{$termino}%")
                        ->orWhere('modelo', 'like', "%{$termino}%")
                        ->pluck('id');

                    $query->where(function (Builder $q) use ($termino, $tiposTerminal) {
                        $q->where('tei', 'like', "%{$termino}%")
                            ->orWhere('issi', 'like', "%{$termino}%")
                            ->orWhereIn('tipo_terminal_id', $tiposTerminal);
                    });
                },
                'etiqueta' => fn (Equipo $equipo) => trim(
                    $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo
                    . ' - ' . $equipo->tipo_terminal->tipo_uso->uso
                    . ' - ' . $equipo->tei . ' ' . $equipo->issi
                ),
            ],
            'recursos' => [
                'consulta' => fn () => Recurso::select('id', 'nombre')->orderBy('nombre'),
                'buscar' => $this->buscarPorColumnas(['nombre']),
                'etiqueta' => fn (Recurso $recurso) => $recurso->nombre,
            ],
            'destinos' => [
                'consulta' => fn () => Destino::select('id', 'nombre', 'parent_id')->with('padre:id,nombre')->orderBy('nombre'),
                'buscar' => $this->buscarPorColumnas(['nombre']),
                'etiqueta' => fn (Destino $destino) => $destino->nombre . ' - ' . $destino->dependeDe(),
            ],
            'estados' => [
                'consulta' => fn () => Estado::select('id', 'nombre')->orderBy('nombre'),
                'buscar' => $this->buscarPorColumnas(['nombre']),
                'etiqueta' => fn (Estado $estado) => $estado->nombre,
            ],
            'tipos-terminal' => [
                'consulta' => fn () => TipoTerminal::select('id', 'marca', 'modelo')->orderBy('marca'),
                'buscar' => $this->buscarPorColumnas(['marca', 'modelo']),
                'etiqueta' => fn (TipoTerminal $tipo) => $tipo->marca . ' ' . $tipo->modelo,
            ],
            'tipos-movimiento' => [
                'consulta' => fn () => TipoMovimiento::select('id', 'nombre')->orderBy('nombre'),
                'buscar' => $this->buscarPorColumnas(['nombre']),
                'etiqueta' => fn (TipoMovimiento $tipo) => $tipo->nombre,
            ],
            'vehiculos' => [
                'consulta' => fn () => Vehiculo::select('id', 'tipo_vehiculo', 'marca', 'modelo', 'dominio')->orderBy('dominio'),
                'buscar' => $this->buscarPorColumnas(['tipo_vehiculo', 'marca', 'modelo', 'dominio']),
                'etiqueta' => fn (Vehiculo $vehiculo) => $vehiculo->tipo_vehiculo . ' - ' . $vehiculo->marca . ' - ' . $vehiculo->modelo . ' - ' . $vehiculo->dominio,
            ],
            'patrimonio' => [
                'lista' => self::PATRIMONIO,
            ],
            default => abort(404),
        };
    }

    /**
     * @param  array<int, string>  $columnas
     */
    private function buscarPorColumnas(array $columnas): \Closure
    {
        return function (Builder $query, string $termino) use ($columnas) {
            $query->where(function (Builder $q) use ($columnas, $termino) {
                foreach ($columnas as $columna) {
                    $q->orWhere($columna, 'like', "%{$termino}%");
                }
            });
        };
    }
}
