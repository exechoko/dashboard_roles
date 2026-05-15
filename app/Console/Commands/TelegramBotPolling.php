<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotPolling extends Command
{
    protected $signature = 'telegram:polling';

    protected $description = 'Consulta mensajes nuevos del bot de Telegram y responde automáticamente.';

    private const CACHE_KEY_OFFSET = 'telegram_bot_update_offset';
    private const MAX_RUN_SECONDS  = 55; // el scheduler relanza el comando cada 60 s
    private const POLL_INTERVAL    = 2;  // segundos entre consultas si no hay mensajes

    public function handle(TelegramService $telegram): int
    {
        $inicio = time();
        Log::channel('telegram')->debug('Telegram polling iniciado');

        while (time() - $inicio < self::MAX_RUN_SECONDS) {
            $offset  = Cache::get(self::CACHE_KEY_OFFSET);
            $updates = $telegram->getUpdates($offset);

            if (empty($updates)) {
                sleep(self::POLL_INTERVAL);
                continue;
            }

            foreach ($updates as $update) {
                $updateId = $update['update_id'] ?? null;

                if ($updateId === null) {
                    continue;
                }

                if (isset($update['message'])) {
                    Log::channel('telegram')->info('Telegram mensaje recibido', [
                        'update_id' => $updateId,
                        'chat_id' => $update['message']['chat']['id'] ?? null,
                        'text' => $update['message']['text'] ?? null,
                    ]);
                    $telegram->procesarMensaje($update['message']);
                } elseif (isset($update['callback_query'])) {
                    Log::channel('telegram')->info('Telegram callback recibido', [
                        'update_id' => $updateId,
                        'chat_id' => $update['callback_query']['message']['chat']['id'] ?? null,
                        'data' => $update['callback_query']['data'] ?? null,
                    ]);
                    $telegram->procesarCallbackQuery($update['callback_query']);
                }

                Cache::put(self::CACHE_KEY_OFFSET, $updateId + 1, now()->addDays(7));
            }
        }

        return 0;
    }
}
