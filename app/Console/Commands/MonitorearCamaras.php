<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Services\LibreNmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorearCamaras extends Command
{
    protected $signature = 'librenms:monitorear-camaras';

    protected $description = 'Consulta en LibreNMS el estado de las cámaras 911 (grupo Camaras) y cachea el total y las que están offline para el dashboard y el bot de Telegram.';

    private const CACHE_KEY_ULTIMO_CONTEO = 'librenms.camaras.ultimo_offline_count';

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

        $this->registrarNotificacionSiCambio($estado['total'], $estado['offline']);

        return Command::SUCCESS;
    }

    /**
     * Registra una única notificación resumen cuando cambia la cantidad de
     * cámaras offline respecto de la última corrida (no una por cámara, para
     * no inundar el historial con las +300 cámaras del grupo). La primera
     * corrida sólo establece la base, sin notificar.
     *
     * @param array<int, array{nombre: string, ip: string|null, caida_hace: string}> $offline
     */
    private function registrarNotificacionSiCambio(int $total, array $offline): void
    {
        $caidas = count($offline);
        $anterior = Cache::get(self::CACHE_KEY_ULTIMO_CONTEO);

        Cache::put(self::CACHE_KEY_ULTIMO_CONTEO, $caidas, now()->addDay());

        if ($anterior === null || $anterior === $caidas) {
            return;
        }

        $empeoro = $caidas > $anterior;
        $umbralCritico = (int) config('librenms.umbral_camaras_criticas');

        $nombres = implode(', ', array_slice(array_column($offline, 'nombre'), 0, 5));
        $restantes = max(0, $caidas - 5);

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_CAMARAS_CCTV,
            'tipo' => $empeoro ? Notificacion::TIPO_ALERTA : Notificacion::TIPO_RECUPERACION,
            'nivel' => $empeoro ? ($caidas >= $umbralCritico ? 'danger' : 'warning') : 'success',
            'titulo' => $caidas === 0
                ? 'Cámaras CCTV: todas recuperadas'
                : ($empeoro ? "Cámaras CCTV: {$caidas} caídas" : "Cámaras CCTV: bajó a {$caidas} caídas"),
            'mensaje' => $caidas === 0
                ? "Las {$total} cámaras del grupo CCTV responden con normalidad."
                : $nombres . ($restantes > 0 ? " y {$restantes} más" : ''),
            'datos' => ['tipo' => 'camara_cctv', 'total' => $total, 'caidas' => $caidas, 'anterior' => $anterior, 'offline' => $offline],
        ]);
    }
}
