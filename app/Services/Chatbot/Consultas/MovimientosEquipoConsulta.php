<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;

class MovimientosEquipoConsulta extends ConsultaDatos
{
    /**
     * Movimientos que se listan como máximo antes de derivar a la pantalla.
     */
    private const LIMITE = 15;

    public function nombre(): string
    {
        return 'movimientos_equipo';
    }

    public function descripcion(): string
    {
        return 'Historial de movimientos de un equipo en la flota general: a qué recurso y destino estuvo asignado, con fechas de asignación y desasignación.';
    }

    public function permisos(): array
    {
        return ['ver-flota'];
    }

    public function parametros(): array
    {
        return [
            'issi' => 'ISSI del equipo. Obligatorio si no se envía "tei".',
            'tei' => 'TEI del equipo. Obligatorio si no se envía "issi".',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $issi = $this->texto($parametros, 'issi', 40);
        $tei = $this->texto($parametros, 'tei', 40);

        if ($issi === null && $tei === null) {
            return 'Necesito el ISSI o el TEI del equipo para buscar sus movimientos.';
        }

        $equipo = Equipo::query()
            ->when($issi !== null, fn ($query) => $query->where('issi', $issi))
            ->when($issi === null, fn ($query) => $query->where('tei', $tei))
            ->first();

        $identificador = $issi !== null ? 'ISSI ' . $issi : 'TEI ' . $tei;

        if ($equipo === null) {
            return 'No encontré ningún equipo con ' . $identificador . '. Verificá el número en [Equipos](/equipos).';
        }

        $movimientos = FlotaGeneral::query()
            ->with(['recurso.destino', 'destino'])
            ->where('equipo_id', $equipo->id)
            ->orderByDesc('fecha_asignacion')
            ->orderByDesc('id')
            ->get();

        if ($movimientos->isEmpty()) {
            return 'El equipo con ' . $identificador . ' no registra movimientos en la flota general.';
        }

        $lineas = $movimientos
            ->take(self::LIMITE)
            ->map(fn (FlotaGeneral $movimiento): string => '- ' . $this->describirMovimiento($movimiento))
            ->implode("\n");

        $respuesta = 'El equipo con ' . $identificador . ' registra '
            . $this->pluralizar($movimientos->count(), 'movimiento', 'movimientos') . " en la flota general:\n"
            . $lineas;

        if ($movimientos->count() > self::LIMITE) {
            $respuesta .= "\n- …y " . $this->numero($movimientos->count() - self::LIMITE) . ' movimiento(s) más.';
        }

        return $respuesta . "\n\nHistorial completo en [Flota general](/flota).";
    }

    private function describirMovimiento(FlotaGeneral $movimiento): string
    {
        $recurso = $movimiento->recurso?->nombre ?: 'recurso sin nombre';
        $destino = $movimiento->destino?->nombre
            ?? $movimiento->recurso?->destino?->nombre
            ?? 'destino no informado';

        $desde = $movimiento->fecha_asignacion?->format('d/m/Y') ?? 'fecha no informada';
        $hasta = $movimiento->fecha_desasignacion?->format('d/m/Y');

        $periodo = $hasta !== null
            ? 'del ' . $desde . ' al ' . $hasta
            : 'desde el ' . $desde . ' (asignación vigente)';

        return $recurso . ' — ' . $destino . ', ' . $periodo . '.';
    }
}
