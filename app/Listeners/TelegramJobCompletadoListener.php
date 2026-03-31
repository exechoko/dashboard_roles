<?php

namespace App\Listeners;

use App\Services\TelegramService;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;

class TelegramJobCompletadoListener
{
    public function handle(JobProcessed $event): void
    {
        try {
            $jobName = $event->job->resolveName();
            $telegram = app(TelegramService::class);
            $telegram->notificarJobCompletado($jobName);
        } catch (\Exception $e) {
            Log::error('TelegramJobCompletadoListener: error', ['error' => $e->getMessage()]);
        }
    }
}
