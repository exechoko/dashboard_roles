<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Camara;
use App\Models\Destino;
use App\Models\User;
use App\Services\Chatbot\BuscadorDependencias;
use App\Services\Chatbot\ConsultaDatos;

class CamarasPorDependenciaConsulta extends ConsultaDatos
{
    /**
     * Dependencias que se listan en el ranking general.
     */
    private const LIMITE_RANKING = 12;

    public function __construct(private BuscadorDependencias $dependencias)
    {
    }

    public function nombre(): string
    {
        return 'camaras_por_dependencia';
    }

    public function descripcion(): string
    {
        return 'Cámaras de videovigilancia a cargo de una dependencia: cuántas tiene y de qué tipo. Sin parámetros devuelve el ranking de dependencias con más cámaras. Usar para preguntas como "cuántas cámaras tiene la Comisaría Cuarta".';
    }

    public function permisos(): array
    {
        return ['ver-camara'];
    }

    public function parametros(): array
    {
        return [
            'dependencia' => 'opcional. Nombre de la dependencia. Si se omite se devuelve el ranking general.',
            'incluir_dependientes' => 'opcional, "si" o "no". Por defecto "si": suma también las cámaras de las dependencias que dependen de ella.',
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

        $total = Camara::query()->whereIn('destino_id', $ids)->count();

        if ($total === 0) {
            return $dependencia->nombre . ' no tiene cámaras a cargo' . $alcance
                . '. Verificalo en [Cámaras](/camaras).';
        }

        $instaladas = Camara::query()
            ->whereIn('destino_id', $ids)
            ->whereNull('fecha_desintalacion')
            ->count();

        $porTipo = Camara::query()
            ->join('tipo_camara', 'tipo_camara.id', '=', 'camaras.tipo_camara_id')
            ->whereIn('camaras.destino_id', $ids)
            ->selectRaw('tipo_camara.tipo AS tipo, COUNT(*) AS cantidad')
            ->groupBy('tipo_camara.tipo')
            ->pluck('cantidad', 'tipo')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        $respuesta = $dependencia->nombre . ' tiene '
            . $this->pluralizar($total, 'cámara a cargo', 'cámaras a cargo') . $alcance . ': '
            . $this->numero($instaladas) . ' instaladas y '
            . $this->numero($total - $instaladas) . ' desinstaladas.';

        if (count($porTipo) > 1) {
            $respuesta .= "\n\nPor tipo:\n" . $this->listaDeConteos($porTipo);
        }

        return $respuesta . "\n\nDetalle en [Cámaras](/camaras).";
    }

    private function ranking(): string
    {
        $conteos = Camara::query()
            ->join('destino', 'destino.id', '=', 'camaras.destino_id')
            ->selectRaw('destino.nombre AS dependencia, COUNT(*) AS cantidad')
            ->groupBy('destino.nombre')
            ->orderByDesc('cantidad')
            ->limit(self::LIMITE_RANKING)
            ->pluck('cantidad', 'dependencia')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        if ($conteos === []) {
            return 'No hay cámaras asignadas a dependencias.';
        }

        $sinDependencia = Camara::query()->whereNull('destino_id')->count();

        $respuesta = "Dependencias con más cámaras a cargo:\n" . $this->listaDeConteos($conteos);

        if ($sinDependencia > 0) {
            $respuesta .= "\n\nAdemás hay " . $this->pluralizar($sinDependencia, 'cámara', 'cámaras')
                . ' sin dependencia asignada.';
        }

        return $respuesta . "\n\nCada dependencia se cuenta por sí sola, sin sumar las que dependen de ella. "
            . 'Detalle en [Cámaras](/camaras).';
    }
}
