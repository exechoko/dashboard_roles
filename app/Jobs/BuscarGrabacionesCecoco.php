<?php

namespace App\Jobs;

use App\Models\EventoCecoco;
use App\Services\CecocoGrabacionesLocalService;
use App\Services\CecocoGrabacionesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BuscarGrabacionesCecoco implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $tries   = 1;

    public function __construct(
        private int    $eventoId,
        private string $cacheKey
    ) {}

    public function handle(): void
    {
        $evento = EventoCecoco::find($this->eventoId);

        if (!$evento) {
            Cache::put($this->cacheKey, [
                'success' => false,
                'message' => 'Evento no encontrado.',
            ], now()->addMinutes(10));
            return;
        }

        try {
            $localService = new CecocoGrabacionesLocalService();
            $resultado    = $localService->buscarGrabaciones(
                $evento->telefono,
                $evento->fecha_hora
            );

            // Completar URLs de stream local
            foreach ($resultado['grabaciones'] as &$g) {
                $g['url'] = route('api.cecoco.grabacion.stream.local', [
                    'path' => base64_encode($g['path']),
                ]);
                unset($g['path']);
            }
            unset($g);

            // Fallback a CECOCO web si no hay resultados locales
            if (empty($resultado['grabaciones']) && config('cecoco.url')) {
                $cecocoService = new CecocoGrabacionesService();
                $resultado     = $cecocoService->buscarGrabaciones(
                    $evento->telefono,
                    $evento->fecha_hora
                );
            }

            Cache::put($this->cacheKey, [
                'success'     => true,
                'grabaciones' => $resultado['grabaciones'],
                'total'       => count($resultado['grabaciones']),
                'ventana'     => $resultado['ventana'],
                'fuente'      => $resultado['fuente'] ?? 'cecoco',
            ], now()->addMinutes(30));

        } catch (\Throwable $e) {
            Log::error('BuscarGrabacionesCecoco: error', [
                'evento_id' => $this->eventoId,
                'error'     => $e->getMessage(),
            ]);

            Cache::put($this->cacheKey, [
                'success' => false,
                'message' => 'Error al buscar grabaciones: ' . $e->getMessage(),
            ], now()->addMinutes(10));
        }
    }
}
