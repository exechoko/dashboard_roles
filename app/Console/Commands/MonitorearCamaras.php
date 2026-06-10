<?php

namespace App\Console\Commands;

use App\Services\LibreNmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorearCamaras extends Command
{
    protected $signature = 'librenms:monitorear-camaras';

    protected $description = 'Consulta en LibreNMS el estado de las cámaras 911 (grupo Camaras) y cachea el total y las que están offline para el dashboard y el bot de Telegram.';

    public function handle(LibreNmsService $libreNms): int
    {
        try {
            $estado = $libreNms->obtenerEstadoCamaras();
        } catch (\Throwable $e) {
            Log::warning('LibreNMS: no se pudo consultar el estado de las cámaras', ['error' => $e->getMessage()]);
            $this->error('❌ ' . $e->getMessage());

            return Command::FAILURE;
        }

        if ($estado['total'] === 0) {
            $this->warn('LibreNMS no devolvió cámaras para el grupo configurado.');

            return Command::SUCCESS;
        }

        Cache::put(LibreNmsService::CACHE_KEY_CAMARAS, [
            'total'         => $estado['total'],
            'offline'       => $estado['offline'],
            'consultado_en' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        $caidas = count($estado['offline']);
        $this->info("📷 Cámaras monitoreadas: {$estado['total']} — offline: {$caidas}");

        if ($caidas > 0) {
            $this->table(
                ['Cámara', 'IP', 'Sin responder hace'],
                array_map(fn (array $c) => [$c['nombre'], $c['ip'] ?? '—', $c['caida_hace'] ?: '—'], $estado['offline'])
            );
        }

        return Command::SUCCESS;
    }
}
