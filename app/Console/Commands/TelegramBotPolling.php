<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TelegramBotPolling extends Command
{
    protected $signature = 'telegram:polling';

    protected $description = 'Consulta mensajes nuevos del bot de Telegram y responde automáticamente.';

    private const CACHE_KEY_OFFSET = 'telegram_bot_update_offset';

    public function handle(TelegramService $telegram): int
    {
        $offset = Cache::get(self::CACHE_KEY_OFFSET);
        $updates = $telegram->getUpdates($offset);

        if (empty($updates)) {
            return 0;
        }

        $procesados = 0;

        foreach ($updates as $update) {
            $updateId = $update['update_id'] ?? null;

            if ($updateId === null) {
                continue;
            }

            if (isset($update['message'])) {
                $telegram->procesarMensaje($update['message']);
                $procesados++;
            }

            Cache::put(self::CACHE_KEY_OFFSET, $updateId + 1, now()->addDays(7));
        }

        if ($procesados > 0) {
            $this->info("Mensajes procesados: {$procesados}");
        }

        return 0;
    }
}
