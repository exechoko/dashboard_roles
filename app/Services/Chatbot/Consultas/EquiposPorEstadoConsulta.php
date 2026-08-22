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
        return 'Cantidad de equipos de comunicación (radios TETRA) del parque completo, en total y por estado (operativos, no operativos, Nuevo, Usado, Reparado, Temporal, Baja, No funciona, Perdido, Recambio, En revision, Degradado - Sin Accesorios). Cuenta todos los equipos sin importar a quién estén asignados; para los equipos de una dependencia usar equipos_por_dependencia.';
    }

    public function permisos(): array
    {
        return ['ver-equipo'];
    }

    public function parametros(): array
    {
        return [
            'estado' => 'opcional. "operativos", "no operativos" o el nombre exacto de un estado. Si se omite se devuelve el resumen completo.',
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
            $cantidad = Equipo::query()->operativo()->count();

            return 'Hay ' . $this->pluralizar($cantidad, 'equipo operativo', 'equipos operativos')
                . ' sobre un total de ' . $this->numero($total) . '. '
                . 'Cuentan como operativos los estados ' . implode(', ', Equipo::ESTADOS_OPERATIVOS) . '. '
                . 'Detalle en [Equipos](/equipos).';
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
        $operativos = Equipo::query()->operativo()->count();
        $noOperativos = Equipo::query()->noOperativo()->count();

        $porEstado = Estado::query()
            ->withCount('equipo')
            ->get()
            ->filter(fn (Estado $estado): bool => $estado->equipo_count > 0)
            ->mapWithKeys(fn (Estado $estado): array => [$estado->nombre => (int) $estado->equipo_count])
            ->all();

        return 'Hay ' . $this->pluralizar($total, 'equipo cargado', 'equipos cargados') . ' en el sistema: '
            . $this->numero($operativos) . ' operativos y ' . $this->numero($noOperativos) . " no operativos.\n\n"
            . "Desglose por estado:\n" . $this->listaDeConteos($porEstado)
            . "\n\nDetalle en [Equipos](/equipos).";
    }
}
