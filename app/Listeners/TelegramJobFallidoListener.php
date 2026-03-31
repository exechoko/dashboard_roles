<?php

namespace App\Listeners;

use App\Services\TelegramService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class TelegramJobFallidoListener
{
    public function handle(JobFailed $event): void
    {
        try {
            $jobName = $event->job->resolveName();
            $error = $event->exception->getMessage();
            $telegram = app(TelegramService::class);
            $telegram->notificarJobFallido($jobName, $error);
        } catch (\Exception $e) {
            Log::error('TelegramJobFallidoListener: error', ['error' => $e->getMessage()]);
        }
    }
}
