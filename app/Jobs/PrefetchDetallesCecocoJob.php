<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PrefetchDetallesCecocoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    public function __construct(
        private readonly string $desde,
        private readonly string $hasta
    ) {
    }

    public function handle(): void
    {
        Log::info('PrefetchDetallesCecocoJob: iniciado', ['desde' => $this->desde, 'hasta' => $this->hasta]);

        Artisan::call('cecoco:prefetch-detalles', [
            '--desde' => $this->desde,
            '--hasta' => $this->hasta,
        ]);

        Log::info('PrefetchDetallesCecocoJob: finalizado', [
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'output' => Artisan::output(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PrefetchDetallesCecocoJob: falló', [
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'error' => $exception->getMessage(),
        ]);
    }
}
