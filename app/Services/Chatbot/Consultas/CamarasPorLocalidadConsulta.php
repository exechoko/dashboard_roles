<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Camara;
use App\Models\Sitio;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use Illuminate\Database\Eloquent\Builder;

class CamarasPorLocalidadConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'camaras_por_localidad';
    }

    public function descripcion(): string
    {
        return 'Cantidad de cámaras de videovigilancia por localidad (por ejemplo Paraná, Oro Verde, San Benito, Colonia Avellaneda), separando las instaladas de las desinstaladas.';
    }

    public function permisos(): array
    {
        return ['ver-camara'];
    }

    public function parametros(): array
    {
        return [
            'localidad' => 'opcional. Nombre de la localidad. Si se omite se devuelve el desglose de todas.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $localidad = $this->texto($parametros, 'localidad');

        if ($localidad === null) {
            return $this->desglose();
        }

        $localidadReal = $this->localidadRegistrada($localidad);

        if ($localidadReal === null) {
            return 'No encontré la localidad "' . $localidad . '" entre los sitios cargados. '
                . $this->desglose();
        }

        $base = fn (): Builder => Camara::query()
            ->whereHas('sitio', fn (Builder $query) => $query->where('localidad', $localidadReal));

        $total = $base()->count();
        $instaladas = $base()->whereNull('fecha_desintalacion')->count();
        $sitios = Sitio::query()->where('localidad', $localidadReal)->count();

        return 'En ' . $localidadReal . ' hay ' . $this->pluralizar($total, 'cámara registrada', 'cámaras registradas')
            . ' distribuidas en ' . $this->pluralizar($sitios, 'sitio', 'sitios') . ': '
            . $this->numero($instaladas) . ' instaladas y ' . $this->numero($total - $instaladas) . " desinstaladas.\n\n"
            . 'Detalle en [Cámaras](/camaras).';
    }

    private function desglose(): string
    {
        $conteos = Camara::query()
            ->join('sitio', 'sitio.id', '=', 'camaras.sitio_id')
            ->selectRaw('sitio.localidad AS localidad, COUNT(*) AS cantidad')
            ->groupBy('sitio.localidad')
            ->pluck('cantidad', 'localidad')
            ->map(fn ($cantidad): int => (int) $cantidad)
            ->all();

        $sinSitio = Camara::query()->whereNull('sitio_id')->count();
        if ($sinSitio > 0) {
            $conteos['Sin sitio asignado'] = $sinSitio;
        }

        if ($conteos === []) {
            return 'No hay cámaras cargadas en el sistema.';
        }

        return 'Cámaras por localidad (total ' . $this->numero(array_sum($conteos)) . "):\n"
            . $this->listaDeConteos($conteos)
            . "\n\nDetalle en [Cámaras](/camaras).";
    }

    /**
     * Devuelve el nombre tal cual está guardado, tolerando tildes y mayúsculas.
     */
    private function localidadRegistrada(string $localidad): ?string
    {
        return Sitio::query()
            ->whereRaw('LOWER(localidad) LIKE ?', ['%' . mb_strtolower($localidad) . '%'])
            ->value('localidad');
    }
}
