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
        return 'Cantidad de tótems BDE (botones de emergencia) instalados, con su desglose por localidad.';
    }

    public function permisos(): array
    {
        return ['ver-camara', 'ver-activacion-totem'];
    }

    public function parametros(): array
    {
        return [
            'localidad' => 'opcional. Limita el conteo a los tótems de esa localidad.',
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
}
