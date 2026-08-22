<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\ActivacionTotem;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use Illuminate\Support\Carbon;

class ActivacionesTotemConsulta extends ConsultaDatos
{
    /**
     * Ventana en días para avisar que el video está por perder respaldo legal.
     */
    private const DIAS_AVISO_VENCIMIENTO = 30;

    public function nombre(): string
    {
        return 'activaciones_totem';
    }

    public function descripcion(): string
    {
        return 'Estado de las activaciones de tótem BDE: cuántas hay pendientes, descargadas, descartadas o eliminadas, y cuántas están vencidas o por vencer según el plazo legal de retención de 6 meses.';
    }

    public function permisos(): array
    {
        return ['ver-activacion-totem'];
    }

    public function parametros(): array
    {
        return [
            'estado' => 'opcional. "pendiente", "descargado", "descartado" o "eliminado".',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $estado = $this->texto($parametros, 'estado');

        if ($estado !== null) {
            $clave = mb_strtolower($estado);

            if (!array_key_exists($clave, ActivacionTotem::ESTADOS)) {
                return 'El estado "' . $estado . '" no existe. Los estados válidos son: '
                    . implode(', ', array_keys(ActivacionTotem::ESTADOS)) . '.';
            }

            $cantidad = ActivacionTotem::query()->where('estado', $clave)->count();

            return 'Hay ' . $this->pluralizar($cantidad, 'activación de tótem', 'activaciones de tótem')
                . ' en estado ' . ActivacionTotem::ESTADOS[$clave] . '. '
                . 'Detalle en [Activaciones Tótem](/tareas/activaciones-totem).';
        }

        $conteos = ActivacionTotem::query()
            ->selectRaw('estado, COUNT(*) AS cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado')
            ->mapWithKeys(fn ($cantidad, $estado): array => [
                ActivacionTotem::ESTADOS[$estado] ?? $estado => (int) $cantidad,
            ])
            ->all();

        if ($conteos === []) {
            return 'Todavía no hay activaciones de tótem registradas. Detalle en [Activaciones Tótem](/tareas/activaciones-totem).';
        }

        $limiteLegal = Carbon::now()->subMonths(ActivacionTotem::MESES_RETENCION_LEGAL);

        $vencidas = ActivacionTotem::query()
            ->where('estado', ActivacionTotem::ESTADO_PENDIENTE)
            ->where('fecha_evento', '<', $limiteLegal)
            ->count();

        $porVencer = ActivacionTotem::query()
            ->where('estado', ActivacionTotem::ESTADO_PENDIENTE)
            ->whereBetween('fecha_evento', [
                $limiteLegal,
                $limiteLegal->copy()->addDays(self::DIAS_AVISO_VENCIMIENTO),
            ])
            ->count();

        return 'Activaciones de tótem registradas (total ' . $this->numero(array_sum($conteos)) . "):\n"
            . $this->listaDeConteos($conteos) . "\n\n"
            . 'Pendientes con el plazo legal de ' . ActivacionTotem::MESES_RETENCION_LEGAL
            . ' meses ya vencido: ' . $this->numero($vencidas) . '. '
            . 'Pendientes que vencen dentro de los próximos ' . self::DIAS_AVISO_VENCIMIENTO
            . ' días: ' . $this->numero($porVencer) . ".\n\n"
            . 'Detalle en [Activaciones Tótem](/tareas/activaciones-totem).';
    }
}
