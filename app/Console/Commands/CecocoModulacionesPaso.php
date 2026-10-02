<?php

namespace App\Console\Commands;

use App\Services\CecocoModulacionesLocalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CecocoModulacionesPaso extends Command
{
    protected $signature = 'cecoco:modulaciones-paso {token}';
    protected $description = 'Ejecuta un paso recuperable del escaneo local de modulaciones.';

    public function handle(): int
    {
        $token = $this->argument('token');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return 1; }
        $key = 'mod_scan:' . $token;
        $job = Cache::store('modulaciones')->get($key);
        if (!is_array($job)) { return 1; }
        app(CecocoModulacionesLocalService::class)->escanearPaso($job, function ($checkpoint) use ($key) {
            Cache::store('modulaciones')->put($key, $checkpoint, now()->addMinutes(30));
        });
        return 0;
    }
}
