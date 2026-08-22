<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Camara;
use App\Models\Destino;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use App\Models\User;
use App\Services\Chatbot\BuscadorDependencias;
use App\Services\Chatbot\ConsultaDatos;

class ResumenDependenciaConsulta extends ConsultaDatos
{
    public function __construct(private BuscadorDependencias $dependencias)
    {
    }

    public function nombre(): string
    {
        return 'resumen_dependencia';
    }

    public function descripcion(): string
    {
        return 'Panorama de una dependencia: de quién depende, qué dependencias tiene a cargo y con cuántos equipos de comunicación, recursos y cámaras cuenta. Usar para preguntas abiertas como "qué tiene la Comisaría Segunda" o "contame de la División 911".';
    }

    public function permisos(): array
    {
        return ['ver-dependencia', 'ver-flota', 'ver-camara'];
    }

    public function parametros(): array
    {
        return [
            'dependencia' => 'Nombre de la dependencia a resumir.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $nombre = $this->texto($parametros, 'dependencia');

        if ($nombre === null) {
            return 'Necesito el nombre de la dependencia. Podés ver el listado en [Dependencias](/dependencias).';
        }

        $coincidencias = $this->dependencias->coincidencias($nombre);
        $aclaracion = $this->dependencias->mensajeDeAmbiguedad($nombre, $coincidencias);

        if ($aclaracion !== null) {
            return $aclaracion;
        }

        /** @var Destino $dependencia */
        $dependencia = $coincidencias->first();
        $ids = $this->dependencias->idsConDescendientes($dependencia);
        $dependientes = count($ids) - 1;

        $lineas = [];

        if ($dependencia->tipo !== null) {
            $lineas[] = '- Tipo: ' . $dependencia->tipo;
        }

        $lineas[] = '- Depende de: ' . ($dependencia->padre?->nombre ?? 'Jefatura de Policía de Entre Ríos');
        $lineas[] = '- Dependencias a cargo: ' . $this->numero($dependientes);

        $totales = [];

        if ($usuario->can('ver-flota')) {
            $equipos = FlotaGeneral::query()
                ->whereNull('fecha_desasignacion')
                ->whereIn('destino_id', $ids)
                ->count();

            $totales[] = '- Equipos de comunicación asignados: ' . $this->numero($equipos);
        }

        if ($usuario->hasAnyPermission(['ver-recurso', 'ver-flota'])) {
            $recursos = Recurso::query()->whereIn('destino_id', $ids)->count();

            $totales[] = '- Recursos (móviles, motos, bases): ' . $this->numero($recursos);
        }

        if ($usuario->can('ver-camara')) {
            $camaras = Camara::query()->whereIn('destino_id', $ids)->count();
            $instaladas = Camara::query()
                ->whereIn('destino_id', $ids)
                ->whereNull('fecha_desintalacion')
                ->count();

            $totales[] = '- Cámaras a cargo: ' . $this->numero($camaras)
                . ($camaras > 0 ? ' (' . $this->numero($instaladas) . ' instaladas)' : '');
        }

        $respuesta = $dependencia->nombre . "\n" . implode("\n", $lineas);

        if ($totales !== []) {
            $alcance = $dependientes > 0
                ? ' (incluyendo las dependencias a cargo)'
                : '';

            $respuesta .= "\n\nRecursos y equipamiento" . $alcance . ":\n" . implode("\n", $totales);
        } else {
            $respuesta .= "\n\nNo tenés permisos para ver el equipamiento de esta dependencia.";
        }

        return $respuesta . "\n\nDetalle en [Dependencias](/dependencias).";
    }
}
