<?php

namespace App\Jobs;

use App\Services\GeocodificacionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodificarLoteEventosCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $tries = 2;
    public $backoff = 120;

    /** @var string[] */
    protected array $direcciones;

    /** Pausa en milisegundos entre cada llamada a la API de Google */
    protected int $pausaMs;

    /** Contexto para logging (ej. "2025-04-03", "importacion_42") */
    protected string $contexto;

    public function __construct(array $direcciones, int $pausaMs = 500, string $contexto = '')
    {
        $this->direcciones = $direcciones;
        $this->pausaMs     = max(0, $pausaMs);
        $this->contexto    = $contexto;
    }

    public function handle(GeocodificacionService $geocoder): void
    {
        $geocodificadas = 0;
        $fallidas       = 0;
        $omitidas       = 0; // ya estaban en caché (el service las maneja internamente)

        foreach ($this->direcciones as $direccion) {
            $direccion = trim((string) $direccion);
            if ($direccion === '' || $direccion === '-') {
                $omitidas++;
                continue;
            }

            try {
                $resultado = $geocoder->geocodificar($direccion);
                if ($resultado) {
                    $geocodificadas++;
                } else {
                    $fallidas++;
                }
            } catch (\Exception $e) {
                $fallidas++;
                Log::warning('GeocodificarLoteEventosCecoco: error en dirección', [
                    'direccion' => $direccion,
                    'contexto'  => $this->contexto,
                    'error'     => $e->getMessage(),
                ]);
            }

            if ($this->pausaMs > 0) {
                usleep($this->pausaMs * 1000);
            }
        }

        Log::info('GeocodificarLoteEventosCecoco: lote completado', [
            'contexto'       => $this->contexto,
            'total'          => count($this->direcciones),
            'geocodificadas' => $geocodificadas,
            'fallidas'       => $fallidas,
            'omitidas'       => $omitidas,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GeocodificarLoteEventosCecoco: job falló definitivamente', [
            'contexto'     => $this->contexto,
            'total'        => count($this->direcciones),
            'error'        => $e->getMessage(),
        ]);
    }
}
