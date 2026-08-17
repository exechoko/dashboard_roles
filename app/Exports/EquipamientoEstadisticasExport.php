<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipamientoEstadisticasExport implements WithMultipleSheets
{
    protected array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function sheets(): array
    {
        $resumen = $this->datos['resumen'];

        return [
            'Resumen' => $this->hoja('Resumen', [
                ['Total de equipos', $resumen['total']],
                ['Operativos (Nuevo/Usado/Reparado, sin HTT500)', $resumen['operativos']],
                ['No operativos (Baja/No funciona/Perdido/Degradado/Recambio, sin HTT500)', $resumen['no_operativos']],
                ['Operativos HTT500 (aparte: sin baterías/antenas disponibles)', $resumen['operativos_htt500']],
                ['No operativos HTT500', $resumen['no_operativos_htt500']],
                ['Otros estados (Temporal/En revisión)', $resumen['otros_estados']],
                ['En revisión técnica (último movimiento histórico)', $resumen['en_revision_tecnica']],
                ['Instalados (Móvil/Base)', $resumen['instalados']],
                ['Asignados (Portátiles TETRA, sin HTT500 ni VX-261)', $resumen['asignados_portatiles']],
                ['HTT500 asignados (sin accesorios, no cuentan como operativo disponible)', $resumen['htt500_asignados']],
                ['VX-261 asignados (no es TETRA)', $resumen['vertex_asignados']],
                ['No operativos en terreno (asignados, fuera de Stock 911, Sección Técnica y Telecom)', $resumen['no_operativos_en_terreno']],
                ['En Sección Técnica (Stock 911 y demás recursos, ver hoja aparte)', $resumen['seccion_tecnica_total']],
                ['% Operativo', $resumen['pct_operativo'] . '%'],
                ['% No operativo', $resumen['pct_no_operativo'] . '%'],
                ['% HTT500', $resumen['pct_htt500'] . '%'],
            ], ['Indicador', 'Valor']),

            'Reconciliación' => $this->hoja(
                'Reconciliación',
                $this->datos['situacionPorTipoUso']->map(fn ($f) => [
                    $f->uso,
                    $f->instalados,
                    $f->no_operativo_terreno,
                    $f->otros_terreno,
                    $f->en_stock,
                    $f->total,
                ])->all(),
                ['Uso', 'Instalados (operativo, terreno)', 'No Operativo en Terreno', 'Otros Estados en Terreno', 'En Stock 911', 'Total']
            ),

            'Por Tipo de Uso' => $this->hoja(
                'Por Tipo de Uso',
                $this->datos['porTipoUso']->map(fn ($f) => [$f->uso, $f->cantidad])->all(),
                ['Uso', 'Cantidad Instalados (Operativos)']
            ),

            'Móviles y Bases' => $this->hoja(
                'Móviles y Bases',
                $this->datos['instaladosMovilBasePorMarca']->map(fn ($f) => [
                    $f->uso,
                    trim($f->marca . ' ' . $f->modelo),
                    $f->cantidad,
                ])->all(),
                ['Uso', 'Marca / Modelo', 'Cantidad']
            ),

            'Por Estado' => $this->hoja(
                'Por Estado',
                $this->datos['porEstado']->map(fn ($f) => [$f->estado, $f->cantidad])->all(),
                ['Estado', 'Cantidad']
            ),

            'Por Tipo de Equipo' => $this->hoja(
                'Por Tipo de Equipo',
                $this->datos['porTipoEquipo']->map(fn ($f) => [
                    trim($f->marca . ' ' . $f->modelo),
                    $f->uso,
                    $f->total,
                    $f->operativos,
                    $f->no_operativos,
                    $f->total > 0 ? round($f->operativos / $f->total * 100, 1) . '%' : '0%',
                ])->all(),
                ['Marca / Modelo', 'Uso', 'Total', 'Operativos', 'No Operativos', '% Operativo']
            ),

            'Por Dependencia' => $this->hoja(
                'Por Dependencia',
                $this->datos['porDependencia']->map(fn ($f) => [
                    $f->destino_nombre,
                    $f->destino_tipo,
                    $f->total,
                    $f->operativos,
                    $f->no_operativos,
                ])->all(),
                ['Dependencia', 'Tipo', 'Total Instalados', 'Operativos', 'No Operativos']
            ),

            'Sección Técnica' => $this->hoja(
                'Sección Técnica',
                $this->datos['seccionTecnicaPorRecursoYTipo']->map(fn ($f) => [
                    $f->recurso,
                    trim($f->marca . ' ' . $f->modelo),
                    $f->cantidad,
                ])->all(),
                ['Recurso', 'Marca / Modelo', 'Cantidad']
            ),

            'Sin Movimiento Reciente' => $this->hoja(
                'Sin Movimiento Reciente',
                $this->datos['htt500SinMovimiento']->map(fn ($eq) => [
                    $eq->tei,
                    $eq->issi,
                    $eq->estado,
                    $eq->recurso,
                    $eq->dependencia,
                    $eq->ultimo_movimiento_tipo ?? 'Sin histórico',
                    $eq->ultimo_movimiento_fecha ? \Carbon\Carbon::parse($eq->ultimo_movimiento_fecha)->format('d-m-Y') : null,
                ])->all(),
                ['TEI', 'ISSI', 'Estado', 'Recurso', 'Dependencia', 'Último Movimiento', 'Fecha Último Movimiento']
            ),
        ];
    }

    private function hoja(string $titulo, array $filas, array $encabezados)
    {
        return new class($titulo, $filas, $encabezados) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
        {
            public function __construct(
                private string $titulo,
                private array $filas,
                private array $encabezados
            ) {
            }

            public function array(): array
            {
                return $this->filas;
            }

            public function headings(): array
            {
                return $this->encabezados;
            }

            public function title(): string
            {
                return $this->titulo;
            }

            public function styles(Worksheet $sheet)
            {
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
                $sheet->freezePane('A2');

                return [];
            }
        };
    }
}
