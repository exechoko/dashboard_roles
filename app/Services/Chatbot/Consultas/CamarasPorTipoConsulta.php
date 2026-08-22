<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Camara;
use App\Models\Sitio;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use Illuminate\Database\Eloquent\Builder;

class CamarasPorTipoConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'camaras_por_tipo';
    }

    public function descripcion(): string
    {
        return 'Cantidad de cámaras por tipo (Fija, Domo, Domo Dual, Fija - LPR, Fija - FR, BDE (Totem), etc.), opcionalmente acotada a una localidad.';
    }

    public function permisos(): array
    {
        return ['ver-camara'];
    }

    public function parametros(): array
    {
        return [
            'tipo' => 'opcional. Nombre del tipo de cámara. Si se omite se devuelve el desglose de todos los tipos.',
            'localidad' => 'opcional. Limita el conteo a los sitios de esa localidad.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $tipo = $this->texto($parametros, 'tipo');
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

        $consulta = Camara::query()
            ->join('tipo_camara', 'tipo_camara.id', '=', 'camaras.tipo_camara_id')
            ->when($localidadReal !== null, fn (Builder $query) => $query
                ->whereHas('sitio', fn (Builder $sitio) => $sitio->where('localidad', $localidadReal)))
            ->when($tipo !== null, fn (Builder $query) => $query
                ->whereRaw('LOWER(tipo_camara.tipo) LIKE ?', ['%' . mb_strtolower((string) $tipo) . '%']));

        $conteos = $consulta
            ->selectRaw('tipo_camara.tipo AS tipo, COUNT(*) AS cantidad')
            ->groupBy('tipo_camara.tipo')
            ->pluck('cantidad', 'tipo')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        if ($conteos === []) {
            return 'No hay cámaras cargadas que coincidan con esos filtros.';
        }

        $encabezado = 'Cámaras por tipo';
        if ($localidadReal !== null) {
            $encabezado .= ' en ' . $localidadReal;
        }

        return $encabezado . ' (total ' . $this->numero(array_sum($conteos)) . "):\n"
            . $this->listaDeConteos($conteos)
            . "\n\nDetalle en [Cámaras](/camaras).";
    }
}
