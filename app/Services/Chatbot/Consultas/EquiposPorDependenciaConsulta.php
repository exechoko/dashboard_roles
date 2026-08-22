<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Destino;
use App\Models\FlotaGeneral;
use App\Models\User;
use App\Services\Chatbot\BuscadorDependencias;
use App\Services\Chatbot\ConsultaDatos;

class EquiposPorDependenciaConsulta extends ConsultaDatos
{
    /**
     * Dependencias que se listan en el ranking general.
     */
    private const LIMITE_RANKING = 12;

    /**
     * Recursos que se detallan dentro de una dependencia.
     */
    private const LIMITE_RECURSOS = 15;

    public function __construct(private BuscadorDependencias $dependencias)
    {
    }

    public function nombre(): string
    {
        return 'equipos_por_dependencia';
    }

    public function descripcion(): string
    {
        return 'Equipos de comunicación asignados a una dependencia según la flota general: cuántos tiene, en qué recursos están y con qué modelos de terminal. Sin parámetros devuelve el ranking de dependencias con más equipos asignados. Usar esta consulta para preguntas como "cuántos equipos tiene la Comisaría Segunda" o "qué dependencia tiene más equipos".';
    }

    public function permisos(): array
    {
        return ['ver-flota'];
    }

    public function parametros(): array
    {
        return [
            'dependencia' => 'opcional. Nombre de la dependencia (comisaría, división, sección, departamental o dirección). Si se omite se devuelve el ranking general.',
            'incluir_dependientes' => 'opcional, "si" o "no". Por defecto "si": suma también los equipos de las dependencias que dependen de ella.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $nombre = $this->texto($parametros, 'dependencia');

        if ($nombre === null) {
            return $this->ranking();
        }

        $coincidencias = $this->dependencias->coincidencias($nombre);
        $aclaracion = $this->dependencias->mensajeDeAmbiguedad($nombre, $coincidencias);

        if ($aclaracion !== null) {
            return $aclaracion;
        }

        /** @var Destino $dependencia */
        $dependencia = $coincidencias->first();
        $incluirDependientes = $this->texto($parametros, 'incluir_dependientes') !== 'no';

        $ids = $incluirDependientes
            ? $this->dependencias->idsConDescendientes($dependencia)
            : [(int) $dependencia->id];

        $asignaciones = FlotaGeneral::query()
            ->with(['recurso', 'equipo.tipo_terminal'])
            ->whereNull('fecha_desasignacion')
            ->whereIn('destino_id', $ids)
            ->get();

        $alcance = $incluirDependientes
            ? $this->dependencias->detalleDeAlcance($dependencia, count($ids))
            : '';

        if ($asignaciones->isEmpty()) {
            return $dependencia->nombre . ' no tiene equipos de comunicación asignados en la flota general'
                . $alcance . '. Verificalo en [Flota general](/flota).';
        }

        $respuesta = $dependencia->nombre . ' tiene '
            . $this->pluralizar($asignaciones->count(), 'equipo asignado', 'equipos asignados')
            . $alcance . '.';

        $porRecurso = $asignaciones
            ->groupBy(fn (FlotaGeneral $asignacion): string => $asignacion->recurso?->nombre ?: 'Sin recurso asignado')
            ->map(fn ($grupo): int => $grupo->count())
            ->all();

        if (count($porRecurso) > 1) {
            arsort($porRecurso);
            $recortado = array_slice($porRecurso, 0, self::LIMITE_RECURSOS, true);

            $respuesta .= "\n\nPor recurso:\n" . $this->listaDeConteos($recortado);

            if (count($porRecurso) > self::LIMITE_RECURSOS) {
                $respuesta .= "\n- …y " . (count($porRecurso) - self::LIMITE_RECURSOS) . ' recurso(s) más.';
            }
        }

        $porModelo = $asignaciones
            ->groupBy(function (FlotaGeneral $asignacion): string {
                $terminal = $asignacion->equipo?->tipo_terminal;

                return $terminal !== null
                    ? trim($terminal->marca . ' ' . $terminal->modelo)
                    : 'Modelo no informado';
            })
            ->map(fn ($grupo): int => $grupo->count())
            ->all();

        if (count($porModelo) > 1) {
            $respuesta .= "\n\nPor modelo de terminal:\n" . $this->listaDeConteos($porModelo);
        }

        return $respuesta . "\n\nDetalle en [Flota general](/flota).";
    }

    private function ranking(): string
    {
        $conteos = FlotaGeneral::query()
            ->join('destino', 'destino.id', '=', 'flota_general.destino_id')
            ->whereNull('flota_general.fecha_desasignacion')
            ->selectRaw('destino.nombre AS dependencia, COUNT(*) AS cantidad')
            ->groupBy('destino.nombre')
            ->orderByDesc('cantidad')
            ->limit(self::LIMITE_RANKING)
            ->pluck('cantidad', 'dependencia')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        if ($conteos === []) {
            return 'No hay equipos asignados a dependencias en la flota general.';
        }

        $total = FlotaGeneral::query()->whereNull('fecha_desasignacion')->count();

        return 'Dependencias con más equipos de comunicación asignados (sobre '
            . $this->pluralizar($total, 'asignación vigente', 'asignaciones vigentes') . "):\n"
            . $this->listaDeConteos($conteos)
            . "\n\nCada dependencia se cuenta por sí sola, sin sumar las que dependen de ella. "
            . 'Detalle en [Flota general](/flota).';
    }
}
