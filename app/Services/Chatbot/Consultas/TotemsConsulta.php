<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Camara;
use App\Models\Sitio;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use Illuminate\Database\Eloquent\Builder;

class TotemsConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'totems';
    }

    public function descripcion(): string
    {
        return 'Tótems BDE (botones de emergencia): cuántos hay y su desglose por localidad, o el listado de cuáles son con nombre, sitio y localidad si el usuario pregunta cuáles son.';
    }

    public function permisos(): array
    {
        return ['ver-camara', 'ver-activacion-totem'];
    }

    public function parametros(): array
    {
        return [
            'localidad' => 'opcional. Limita el conteo a los tótems de esa localidad.',
            'listar' => 'opcional. "si" cuando el usuario pide cuáles son y no sólo cuántos hay.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $localidad = $this->texto($parametros, 'localidad');
        $localidadReal = null;

        if ($localidad !== null) {
            $localidadReal = Sitio::query()
                ->whereRaw('LOWER(localidad) LIKE ?', ['%' . mb_strtolower($localidad) . '%'])
                ->value('localidad');

            if ($localidadReal === null) {
                return 'No encontré la localidad "' . $localidad . '" entre los sitios cargados.';
            }
        }

        $base = fn (): Builder => Camara::query()
            ->whereHas('tipoCamara', fn (Builder $query) => $query
                ->where(fn (Builder $tipo) => $tipo
                    ->where('tipo', 'LIKE', '%Totem%')
                    ->orWhere('tipo', 'LIKE', '%BDE%')))
            ->when($localidadReal !== null, fn (Builder $query) => $query
                ->whereHas('sitio', fn (Builder $sitio) => $sitio->where('localidad', $localidadReal)));

        $total = $base()->count();

        if ($total === 0) {
            return $localidadReal !== null
                ? 'No hay tótems BDE registrados en ' . $localidadReal . '.'
                : 'No hay tótems BDE registrados en el sistema.';
        }

        if ($this->pidioListado($parametros)) {
            return $this->listar($base(), $total, $localidadReal);
        }

        $instalados = $base()->whereNull('fecha_desintalacion')->count();

        $respuesta = 'Hay ' . $this->pluralizar($total, 'tótem BDE registrado', 'tótems BDE registrados')
            . ($localidadReal !== null ? ' en ' . $localidadReal : '')
            . ': ' . $this->numero($instalados) . ' instalados y '
            . $this->numero($total - $instalados) . ' desinstalados.';

        if ($localidadReal === null) {
            $conteos = $base()
                ->join('sitio', 'sitio.id', '=', 'camaras.sitio_id')
                ->selectRaw('sitio.localidad AS localidad, COUNT(*) AS cantidad')
                ->groupBy('sitio.localidad')
                ->pluck('cantidad', 'localidad')
                ->map(fn ($cantidad): int => (int) $cantidad)
                ->all();

            if ($conteos !== []) {
                $respuesta .= "\n\nPor localidad:\n" . $this->listaDeConteos($conteos);
            }
        }

        return $respuesta . "\n\nDetalle en [Cámaras](/camaras).";
    }

    /**
     * Listado de tótems, uno por línea, con dónde está cada uno.
     */
    private function listar(Builder $consulta, int $total, ?string $localidad): string
    {
        $items = $consulta
            ->with('sitio:id,nombre,localidad')
            ->orderBy('nombre')
            ->get()
            ->map(function (Camara $totem): string {
                $partes = array_filter([
                    $totem->nombre ?: 'Sin nombre',
                    $totem->sitio->nombre ?? null,
                    $totem->sitio->localidad ?? null,
                ]);

                $linea = implode(' — ', $partes);

                return $totem->fecha_desintalacion !== null ? $linea . ' (desinstalado)' : $linea;
            })
            ->all();

        $encabezado = $this->pluralizar($total, 'tótem BDE', 'tótems BDE')
            . ($localidad !== null ? ' en ' . $localidad : '') . ':';

        return $encabezado . "\n" . $this->listaDeItems($items, '[Cámaras](/camaras)')
            . "\n\nDetalle en [Cámaras](/camaras).";
    }
}
