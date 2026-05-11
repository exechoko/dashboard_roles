<?php

namespace App\Jobs;

use App\Services\CecocoExpedienteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsultarTamanoRestauracionesCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private bool $gps;

    public function __construct(bool $gps = false)
    {
        $this->gps = $gps;
    }

    public function handle(CecocoExpedienteService $service): void
    {
        try {
            $this->gps
                ? $service->actualizarCacheTamanoBaseRestauracionesGps()
                : $service->actualizarCacheTamanoBaseRestauraciones();
        } catch (\Throwable $e) {
            Log::warning('Job ConsultarTamanoRestauracionesCecoco falló', [
                'gps' => $this->gps,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
