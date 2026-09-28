<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Services\GrabadorTetraService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitorearReplayServer extends Command
{
    protected $signature = 'grabador:monitorear-replay
        {--sin-telegram : Sólo muestra el estado en consola, no envía alertas}';

    protected $description = 'Prueba el Replay Server del grabador TETRA (localhost:8880) y lo reinicia automáticamente si detecta que quedó colgado, antes de que lo sufra un operador escuchando modulaciones.';

    private const CACHE_FLAG_REINICIO_FALLIDO = 'grabador.replay_reinicio_fallido';

    /** Cache key: último chequeo del watchdog, para mostrarlo en Infraestructura > Grabador. */
    public const CACHE_ULTIMO_CHEQUEO = 'grabador.replay_watchdog_ultimo_chequeo';

    public function handle(GrabadorTetraService $grabador, TelegramService $telegram): int
    {
        $simulacro = (bool) $this->option('sin-telegram');

        $ok = $grabador->probarReplayServer();

        Cache::put(self::CACHE_ULTIMO_CHEQUEO, [
            'hora' => now()->toDateTimeString(),
            'ok'   => $ok,
        ], now()->addHours(2));

        if ($ok) {
            $this->info('✅ Replay Server responde con normalidad.');

            return Command::SUCCESS;
        }

        $this->warn('⚠️ El Replay Server no respondió, reiniciando el servicio...');

        $resultado = $grabador->reiniciarReplayServer(['origen' => 'watchdog']);

        if ($simulacro) {
            $this->info($resultado['success']
                ? '✅ Reinicio (simulado) ok, no se envía Telegram.'
                : '❌ Reinicio (simulado) falló: ' . $resultado['mensaje']);

            return $resultado['success'] ? Command::SUCCESS : Command::FAILURE;
        }

        if ($resultado['success']) {
            Cache::forget(self::CACHE_FLAG_REINICIO_FALLIDO);

            $this->registrarNotificacion(true, 'Se detectó colgado y se reinició automáticamente.');
            $this->info('✅ Replay Server reiniciado automáticamente.');

            return Command::SUCCESS;
        }

        // El reinicio automático requiere intervención manual: avisar por Telegram,
        // pero sin repetir el aviso en cada corrida de 5 min mientras siga fallando.
        if (!Cache::has(self::CACHE_FLAG_REINICIO_FALLIDO)) {
            Cache::put(self::CACHE_FLAG_REINICIO_FALLIDO, true, now()->addMinutes(30));

            $this->registrarNotificacion(false, 'El reinicio automático falló: ' . $resultado['mensaje']);
            $this->enviarTelegram($telegram, $resultado['mensaje']);
        }

        $this->error('❌ No se pudo reiniciar el Replay Server: ' . $resultado['mensaje']);

        return Command::FAILURE;
    }

    private function registrarNotificacion(bool $exito, string $mensaje): void
    {
        Notificacion::create([
            'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
            'tipo'      => $exito ? Notificacion::TIPO_RECUPERACION : Notificacion::TIPO_ALERTA,
            'nivel'     => $exito ? 'warning' : 'danger',
            'titulo'    => $exito ? 'Replay Server reiniciado automáticamente' : 'Replay Server: reinicio automático falló',
            'mensaje'   => $mensaje,
            'datos'     => ['origen' => 'watchdog', 'servicio' => config('grabador.replay_service_name')],
        ]);
    }

    private function enviarTelegram(TelegramService $telegram, string $detalle): void
    {
        $mensaje = "🎙 <b>Replay Server del grabador TETRA colgado</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "❌ El reinicio automático falló: {$detalle}\n"
            . 'Requiere reiniciarlo a mano desde Infraestructura > Grabador.';

        $chatIds = array_filter(array_map('trim', explode(',', (string) config('infraestructura.telegram_chat_ids'))));

        if (empty($chatIds)) {
            $telegram->enviarMensaje($mensaje);

            return;
        }

        foreach ($chatIds as $chatId) {
            $telegram->enviarMensaje($mensaje, $chatId);
        }
    }
}
