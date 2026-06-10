<?php

namespace App\Console\Commands;

use App\Services\LibreNmsService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorearCpuVideo extends Command
{
    protected $signature = 'librenms:monitorear-cpu
        {--umbral= : Umbral de CPU (%) que dispara la alerta (por defecto el de config)}
        {--sin-telegram : Sólo muestra el estado en consola, no envía alertas}';

    protected $description = 'Consulta en LibreNMS el uso de CPU de las PCs de los operadores de video (grupo CCTV) y avisa por Telegram cuando un equipo supera el umbral.';

    private const CACHE_FLAG_ALERTA = 'librenms.cpu_alerta.';

    private const CACHE_COOLDOWN = 'librenms.cpu_cooldown.';

    public function handle(LibreNmsService $libreNms, TelegramService $telegram): int
    {
        $umbral     = (int) ($this->option('umbral') ?: config('librenms.umbral_cpu'));
        $histeresis = (int) config('librenms.histeresis');
        $cooldown   = (int) config('librenms.cooldown_minutos');

        try {
            $dispositivos = $libreNms->obtenerUsoCpuGrupo();
        } catch (\Throwable $e) {
            Log::warning('LibreNMS: no se pudo consultar el uso de CPU', ['error' => $e->getMessage()]);
            $this->error('❌ ' . $e->getMessage());

            return Command::FAILURE;
        }

        if (empty($dispositivos)) {
            $this->warn('LibreNMS no devolvió dispositivos para el grupo configurado.');

            return Command::SUCCESS;
        }

        Cache::put(LibreNmsService::CACHE_KEY_ULTIMO_USO, [
            'dispositivos'  => $dispositivos,
            'consultado_en' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        $this->table(
            ['Equipo', 'CPU promedio', 'Núcleo máx', 'Núcleos'],
            array_map(fn (array $d) => [
                $d['hostname'],
                $d['promedio'] . '%' . ($d['promedio'] > $umbral ? ' ⚠️' : ''),
                $d['maximo'] . '%',
                $d['nucleos'],
            ], $dispositivos)
        );

        $simulacro = (bool) $this->option('sin-telegram');

        $nuevasAlertas = [];
        $recuperados   = [];

        foreach ($dispositivos as $dispositivo) {
            $flagKey     = self::CACHE_FLAG_ALERTA . $dispositivo['device_id'];
            $cooldownKey = self::CACHE_COOLDOWN . $dispositivo['device_id'];

            if ($dispositivo['promedio'] > $umbral) {
                if (!Cache::has($cooldownKey)) {
                    $nuevasAlertas[] = $dispositivo;
                    if (!$simulacro) {
                        Cache::put($cooldownKey, true, now()->addMinutes($cooldown));
                    }
                }
                if (!$simulacro) {
                    Cache::put($flagKey, true, now()->addDay());
                }
            } elseif ($dispositivo['promedio'] < $umbral - $histeresis && Cache::has($flagKey)) {
                $recuperados[] = $dispositivo;
                if (!$simulacro) {
                    Cache::forget($flagKey);
                    Cache::forget($cooldownKey);
                }
            }
        }

        if ($simulacro) {
            $this->info(sprintf('Modo sin Telegram: %d alerta(s) y %d recuperado(s) sin enviar.', count($nuevasAlertas), count($recuperados)));

            return Command::SUCCESS;
        }

        if (!empty($nuevasAlertas)) {
            $this->enviarATodos($telegram, $this->mensajeAlerta($nuevasAlertas, $umbral));
            $this->info('📨 Alerta enviada por Telegram: ' . implode(', ', array_column($nuevasAlertas, 'hostname')));
        }

        if (!empty($recuperados)) {
            $this->enviarATodos($telegram, $this->mensajeRecuperacion($recuperados, $umbral));
            $this->info('📨 Recuperación enviada por Telegram: ' . implode(', ', array_column($recuperados, 'hostname')));
        }

        if (empty($nuevasAlertas) && empty($recuperados)) {
            $this->info("✅ Sin novedades (umbral {$umbral}%).");
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array{hostname: string, promedio: float, maximo: int}> $dispositivos
     */
    private function mensajeAlerta(array $dispositivos, int $umbral): string
    {
        $mensaje = "🖥 <b>Alerta CPU — Operadores de video</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "⚠️ Equipos con CPU sobre el {$umbral}%:\n";

        foreach ($dispositivos as $d) {
            $mensaje .= "\n• <b>{$d['hostname']}</b>: {$d['promedio']}% promedio (núcleo máx {$d['maximo']}%)";
        }

        return $mensaje;
    }

    /**
     * @param array<int, array{hostname: string, promedio: float}> $dispositivos
     */
    private function mensajeRecuperacion(array $dispositivos, int $umbral): string
    {
        $mensaje = "🖥 <b>CPU normalizada — Operadores de video</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "✅ Volvieron por debajo del {$umbral}%:\n";

        foreach ($dispositivos as $d) {
            $mensaje .= "\n• <b>{$d['hostname']}</b>: {$d['promedio']}% promedio";
        }

        return $mensaje;
    }

    private function enviarATodos(TelegramService $telegram, string $mensaje): void
    {
        $chatIds = array_filter(array_map('trim', explode(',', (string) config('librenms.telegram_chat_ids'))));

        if (empty($chatIds)) {
            $telegram->enviarMensaje($mensaje);

            return;
        }

        foreach ($chatIds as $chatId) {
            $telegram->enviarMensaje($mensaje, $chatId);
        }
    }
}
