<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Services\CentralTelefonicaTroncalesService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorearTroncalesCentralTelefonica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'central-telefonica:monitorear-troncales {--sin-telegram : Sólo muestra el estado en consola, no envía alertas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta en el panel de la central telefonica (SSW) el estado de los troncales SIP y avisa por Telegram si alguno cae o se recupera';

    private const CACHE_FLAG_CAIDO = 'central_telefonica.troncal_caido.';

    public function handle(CentralTelefonicaTroncalesService $service, TelegramService $telegram): int
    {
        try {
            $troncales = $service->obtenerEstadoTroncales();
        } catch (\Throwable $e) {
            Log::warning('CentralTelefonicaTroncalesService: no se pudo consultar el estado de los troncales SIP', ['error' => $e->getMessage()]);
            $this->error('❌ ' . $e->getMessage());

            return self::FAILURE;
        }

        if (empty($troncales)) {
            $this->warn('La central telefonica no devolvió troncales SIP configurados.');

            return self::SUCCESS;
        }

        Cache::put(CentralTelefonicaTroncalesService::CACHE_KEY, [
            'troncales' => $troncales,
            'consultado_en' => now()->toDateTimeString(),
        ], now()->addMinutes(15));

        $this->table(
            ['Troncal', 'Host', 'Estado', 'Latencia'],
            array_map(fn (array $t) => [
                $t['nombre'],
                $t['host'],
                $t['estado'] === 'online' ? 'online' : 'OFFLINE ⚠️',
                $t['latencia_ms'] !== null ? $t['latencia_ms'] . 'ms' : '—',
            ], $troncales)
        );

        $simulacro = (bool) $this->option('sin-telegram');
        $caidos = [];
        $recuperados = [];

        foreach ($troncales as $troncal) {
            $flagKey = self::CACHE_FLAG_CAIDO . $troncal['nombre'];

            if ($troncal['estado'] !== 'online') {
                if (!Cache::has($flagKey)) {
                    $caidos[] = $troncal;
                    if (!$simulacro) {
                        Cache::put($flagKey, true, now()->addDay());
                    }
                }
            } elseif (Cache::has($flagKey)) {
                $recuperados[] = $troncal;
                if (!$simulacro) {
                    Cache::forget($flagKey);
                }
            }
        }

        if ($simulacro) {
            $this->info(sprintf('Modo sin Telegram: %d caído(s) y %d recuperado(s) sin enviar.', count($caidos), count($recuperados)));

            return self::SUCCESS;
        }

        foreach ($caidos as $troncal) {
            $this->registrarNotificacion($troncal, Notificacion::TIPO_ALERTA);
            $telegram->enviarMensaje($this->mensajeCaido($troncal));
            $this->info("📨 Alerta enviada por Telegram: {$troncal['nombre']} caído");
        }

        foreach ($recuperados as $troncal) {
            $this->registrarNotificacion($troncal, Notificacion::TIPO_RECUPERACION);
            $telegram->enviarMensaje($this->mensajeRecuperado($troncal));
            $this->info("📨 Recuperación enviada por Telegram: {$troncal['nombre']}");
        }

        if (empty($caidos) && empty($recuperados)) {
            $this->info('✅ Sin novedades, todos los troncales SIP online.');
        }

        return self::SUCCESS;
    }

    /**
     * @param array{nombre: string, host: string} $troncal
     */
    private function registrarNotificacion(array $troncal, string $tipo): void
    {
        $esAlerta = $tipo === Notificacion::TIPO_ALERTA;

        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo' => $tipo,
            'nivel' => $esAlerta ? 'danger' : 'success',
            'titulo' => $esAlerta ? "Troncal SIP caído: {$troncal['nombre']}" : "Troncal SIP recuperado: {$troncal['nombre']}",
            'mensaje' => $esAlerta
                ? "{$troncal['host']} — puede afectar el ingreso o egreso de llamadas al 911."
                : "{$troncal['host']} volvió a estar online.",
            'datos' => $troncal,
        ]);
    }

    /**
     * @param array{nombre: string, host: string} $troncal
     */
    private function mensajeCaido(array $troncal): string
    {
        return "☎️ <b>Troncal SIP caído — Central telefónica 911</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "⚠️ <b>{$troncal['nombre']}</b> ({$troncal['host']}) está offline.\n"
            . 'Puede afectar el ingreso o egreso de llamadas al 911.';
    }

    /**
     * @param array{nombre: string, host: string} $troncal
     */
    private function mensajeRecuperado(array $troncal): string
    {
        return "☎️ <b>Troncal SIP recuperado — Central telefónica 911</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "✅ <b>{$troncal['nombre']}</b> ({$troncal['host']}) volvió a estar online.";
    }
}
