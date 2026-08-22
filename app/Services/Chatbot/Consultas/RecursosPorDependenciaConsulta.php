<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Destino;
use App\Models\Recurso;
use App\Models\User;
use App\Services\Chatbot\BuscadorDependencias;
use App\Services\Chatbot\ConsultaDatos;

class RecursosPorDependenciaConsulta extends ConsultaDatos
{
    /**
     * Dependencias que se listan en el ranking general.
     */
    private const LIMITE_RANKING = 12;

    /**
     * Recursos que se nombran uno por uno antes de resumir.
     */
    private const LIMITE_RECURSOS = 20;

    public function __construct(private BuscadorDependencias $dependencias)
    {
    }

    public function nombre(): string
    {
        return 'recursos_por_dependencia';
    }

    public function descripcion(): string
    {
        return 'Recursos (móviles, motos, bases y demás puestos que llevan equipos) asignados a una dependencia. Sin parámetros devuelve el ranking de dependencias con más recursos.';
    }

    public function permisos(): array
    {
        return ['ver-recurso', 'ver-flota'];
    }

    public function parametros(): array
    {
        return [
            'dependencia' => 'opcional. Nombre de la dependencia. Si se omite se devuelve el ranking general.',
            'incluir_dependientes' => 'opcional, "si" o "no". Por defecto "si": suma también los recursos de las dependencias que dependen de ella.',
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

        $alcance = $incluirDependientes
            ? $this->dependencias->detalleDeAlcance($dependencia, count($ids))
            : '';

        $recursos = Recurso::query()
            ->whereIn('destino_id', $ids)
            ->orderBy('nombre')
            ->get();

        if ($recursos->isEmpty()) {
            return $dependencia->nombre . ' no tiene recursos asignados' . $alcance
                . '. Verificalo en [Recursos](/recursos).';
        }

        $respuesta = $dependencia->nombre . ' tiene '
            . $this->pluralizar($recursos->count(), 'recurso asignado', 'recursos asignados') . $alcance . '.';

        $listado = $recursos
            ->take(self::LIMITE_RECURSOS)
            ->map(fn (Recurso $recurso): string => '- ' . ($recurso->nombre ?: 'Recurso #' . $recurso->id))
            ->implode("\n");

        $respuesta .= "\n" . $listado;

        if ($recursos->count() > self::LIMITE_RECURSOS) {
            $respuesta .= "\n- …y " . $this->numero($recursos->count() - self::LIMITE_RECURSOS) . ' más.';
        }

        return $respuesta . "\n\nDetalle en [Recursos](/recursos).";
    }

    private function ranking(): string
    {
        $conteos = Recurso::query()
            ->join('destino', 'destino.id', '=', 'recursos.destino_id')
            ->selectRaw('destino.nombre AS dependencia, COUNT(*) AS cantidad')
            ->groupBy('destino.nombre')
            ->orderByDesc('cantidad')
            ->limit(self::LIMITE_RANKING)
            ->pluck('cantidad', 'dependencia')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        if ($conteos === []) {
            return 'No hay recursos asignados a dependencias.';
        }

        return "Dependencias con más recursos asignados:\n" . $this->listaDeConteos($conteos)
            . "\n\nCada dependencia se cuenta por sí sola, sin sumar las que dependen de ella. "
            . 'Detalle en [Recursos](/recursos).';
    }
}
