<?php

namespace App\Services\Chatbot;

use App\Models\User;
use App\Services\Chatbot\Consultas\ActivacionesTotemConsulta;
use App\Services\Chatbot\Consultas\BodycamsPorEstadoConsulta;
use App\Services\Chatbot\Consultas\BuzonMensajesConsulta;
use App\Services\Chatbot\Consultas\BuzonesDisponiblesConsulta;
use App\Services\Chatbot\Consultas\CamarasPorDependenciaConsulta;
use App\Services\Chatbot\Consultas\CamarasPorLocalidadConsulta;
use App\Services\Chatbot\Consultas\CamarasPorTipoConsulta;
use App\Services\Chatbot\Consultas\EquiposDeRecursoConsulta;
use App\Services\Chatbot\Consultas\EquiposPorDependenciaConsulta;
use App\Services\Chatbot\Consultas\EquiposPorEstadoConsulta;
use App\Services\Chatbot\Consultas\EquiposPorTipoTerminalConsulta;
use App\Services\Chatbot\Consultas\MovimientosEquipoConsulta;
use App\Services\Chatbot\Consultas\RecursosPorDependenciaConsulta;
use App\Services\Chatbot\Consultas\ResumenDependenciaConsulta;
use App\Services\Chatbot\Consultas\TotemsConsulta;
use Illuminate\Support\Collection;

/**
 * Registro de las consultas de datos que el chatbot puede ejecutar, filtradas
 * por los permisos del usuario que pregunta.
 */
class CatalogoConsultas
{
    /**
     * @var array<int, class-string<ConsultaDatos>>
     */
    private const CONSULTAS = [
        EquiposPorEstadoConsulta::class,
        EquiposPorTipoTerminalConsulta::class,
        EquiposPorDependenciaConsulta::class,
        EquiposDeRecursoConsulta::class,
        MovimientosEquipoConsulta::class,
        RecursosPorDependenciaConsulta::class,
        ResumenDependenciaConsulta::class,
        CamarasPorLocalidadConsulta::class,
        CamarasPorDependenciaConsulta::class,
        CamarasPorTipoConsulta::class,
        TotemsConsulta::class,
        ActivacionesTotemConsulta::class,
        BuzonesDisponiblesConsulta::class,
        BuzonMensajesConsulta::class,
        BodycamsPorEstadoConsulta::class,
    ];

    /**
     * Consultas habilitadas para el usuario, indexadas por nombre.
     *
     * @return Collection<string, ConsultaDatos>
     */
    public function disponiblesPara(User $usuario): Collection
    {
        return collect(self::CONSULTAS)
            ->map(fn (string $clase): ConsultaDatos => app($clase))
            ->filter(fn (ConsultaDatos $consulta): bool => $consulta->disponiblePara($usuario))
            ->keyBy(fn (ConsultaDatos $consulta): string => $consulta->nombre());
    }

    /**
     * Devuelve la consulta pedida sólo si el usuario tiene permiso para ella.
     */
    public function resolver(User $usuario, string $nombre): ?ConsultaDatos
    {
        return $this->disponiblesPara($usuario)->get($nombre);
    }

    /**
     * Catálogo en texto plano para incluir en el prompt del modelo. Devuelve
     * una cadena vacía si el usuario no tiene ninguna consulta habilitada.
     */
    public function describirPara(User $usuario): string
    {
        $consultas = $this->disponiblesPara($usuario);

        if ($consultas->isEmpty()) {
            return '';
        }

        return $consultas
            ->map(function (ConsultaDatos $consulta): string {
                $linea = '- ' . $consulta->nombre() . ': ' . $consulta->descripcion();

                foreach ($consulta->parametros() as $parametro => $explicacion) {
                    $linea .= "\n  - parametro \"{$parametro}\": {$explicacion}";
                }

                return $linea;
            })
            ->implode("\n");
    }
}
