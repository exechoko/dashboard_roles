<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Equipo;
use App\Models\Estado;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;

class EquiposPorEstadoConsulta extends ConsultaDatos
{
    /**
     * Sinónimos que el usuario suele usar para pedir el total operativo.
     */
    private const ALIAS_OPERATIVOS = [
        'operativo', 'operativos', 'operativa', 'operativas',
        'funcionamiento', 'en funcionamiento', 'funcionando', 'activos', 'activo',
        'en servicio', 'en uso',
    ];

    /**
     * Sinónimos para los equipos que funcionan pero les falta un accesorio.
     */
    private const ALIAS_DEGRADADOS = [
        'degradado', 'degradados', 'sin accesorios', 'sin accesorio',
        'sin antena', 'falta antena', 'falta accesorio', 'faltan accesorios',
        'incompletos', 'incompleto',
    ];

    private const ALIAS_NO_OPERATIVOS = [
        'no operativo', 'no operativos', 'no funciona', 'no funcionan',
        'fuera de servicio', 'inoperativo', 'inoperativos', 'rotos', 'roto',
    ];

    public function nombre(): string
    {
        return 'equipos_por_estado';
    }

    public function descripcion(): string
    {
        return 'Cantidad de equipos de comunicación (radios TETRA) del parque completo, en total y por estado (operativos, degradados por falta de accesorios, no operativos, Nuevo, Usado, Reparado, Temporal, Baja, No funciona, Perdido, Recambio, En revision, Degradado - Sin Accesorios). Cuenta todos los equipos sin importar a quién estén asignados; para los equipos de una dependencia usar equipos_por_dependencia.';
    }

    public function permisos(): array
    {
        return ['ver-equipo'];
    }

    public function parametros(): array
    {
        return [
            'estado' => 'opcional. "operativos", "degradados", "no operativos" o el nombre exacto de un estado. Si se omite se devuelve el resumen completo.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $estado = $this->texto($parametros, 'estado');
        $total = Equipo::query()->count();

        if ($estado === null) {
            return $this->resumen($total);
        }

        $normalizado = mb_strtolower($estado);

        if (in_array($normalizado, self::ALIAS_OPERATIVOS, true)) {
            $cantidad = Equipo::query()->disponible()->count();
            $degradados = Equipo::query()->degradado()->count();

            $respuesta = 'Hay ' . $this->pluralizar($cantidad, 'equipo operativo', 'equipos operativos')
                . ' sobre un total de ' . $this->numero($total) . '. '
                . 'Cuentan como operativos los estados ' . implode(', ', Equipo::ESTADOS_OPERATIVOS)
                . ', siempre que no les falte ningún accesorio relevado. ';

            if ($degradados > 0) {
                $respuesta .= 'Aparte hay ' . $this->pluralizar($degradados, 'equipo degradado', 'equipos degradados')
                    . ': funcionan, pero les falta un accesorio y no salen a la calle hasta conseguir el repuesto. ';
            }

            return $respuesta . 'Detalle en [Equipos](/equipos).';
        }

        if (in_array($normalizado, self::ALIAS_DEGRADADOS, true)) {
            $cantidad = Equipo::query()->degradado()->count();

            if ($cantidad === 0) {
                return 'No hay equipos degradados: a ninguno le falta un accesorio relevado. '
                    . 'Detalle en [Equipos](/equipos).';
            }

            $porAccesorio = collect(Equipo::ACCESORIOS)
                ->mapWithKeys(fn (string $etiqueta, string $campo): array => [
                    $etiqueta => Equipo::query()->operativo()->where($campo, false)->count(),
                ])
                ->filter(fn (int $cantidadAccesorio): bool => $cantidadAccesorio > 0)
                ->all();

            return 'Hay ' . $this->pluralizar($cantidad, 'equipo degradado', 'equipos degradados')
                . ' sobre un total de ' . $this->numero($total) . '. '
                . "Funcionan, pero les falta un accesorio y no cuentan como operativos hasta conseguir el repuesto.\n\n"
                . "Equipos a recuperar por repuesto:\n" . $this->listaDeConteos($porAccesorio)
                . "\n\nDetalle en [Equipos](/equipos).";
        }

        if (in_array($normalizado, self::ALIAS_NO_OPERATIVOS, true)) {
            $cantidad = Equipo::query()->noOperativo()->count();

            return 'Hay ' . $this->pluralizar($cantidad, 'equipo no operativo', 'equipos no operativos')
                . ' sobre un total de ' . $this->numero($total) . '. '
                . 'Cuentan como no operativos los estados ' . implode(', ', Equipo::ESTADOS_NO_OPERATIVOS) . '. '
                . 'Detalle en [Equipos](/equipos).';
        }

        $estadoEncontrado = Estado::query()
            ->whereRaw('LOWER(nombre) = ?', [$normalizado])
            ->first();

        if ($estadoEncontrado === null) {
            return 'No encontré un estado llamado "' . $estado . '". ' . $this->resumen($total);
        }

        $cantidad = Equipo::query()->where('estado_id', $estadoEncontrado->id)->count();

        return 'Hay ' . $this->pluralizar($cantidad, 'equipo', 'equipos') . ' en estado '
            . $estadoEncontrado->nombre . ', sobre un total de ' . $this->numero($total) . '. '
            . 'Detalle en [Equipos](/equipos).';
    }

    private function resumen(int $total): string
    {
        $operativos = Equipo::query()->disponible()->count();
        $degradados = Equipo::query()->degradado()->count();
        $noOperativos = Equipo::query()->noOperativo()->count();

        $porEstado = Estado::query()
            ->withCount('equipo')
            ->get()
            ->filter(fn (Estado $estado): bool => $estado->equipo_count > 0)
            ->mapWithKeys(fn (Estado $estado): array => [$estado->nombre => (int) $estado->equipo_count])
            ->all();

        return 'Hay ' . $this->pluralizar($total, 'equipo cargado', 'equipos cargados') . ' en el sistema: '
            . $this->numero($operativos) . ' operativos, ' . $this->numero($degradados)
            . ' degradados por falta de accesorios y ' . $this->numero($noOperativos) . " no operativos.\n\n"
            . "Desglose por estado:\n" . $this->listaDeConteos($porEstado)
            . "\n\nDetalle en [Equipos](/equipos).";
    }
}
