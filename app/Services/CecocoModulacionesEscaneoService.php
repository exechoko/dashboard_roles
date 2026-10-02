<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class CecocoModulacionesEscaneoService
{
    protected function crearProceso(string $token): Process
    {
        return new Process([PHP_BINARY, base_path('artisan'), 'cecoco:modulaciones-paso', $token], base_path());
    }

    public function avanzar(string $token, float $deadline): ?array
    {
        $process = $this->crearProceso($token);
        $process->setTimeout(min(10, max(0.1, $deadline - microtime(true))));
        $process->disableOutput();
        try { $process->run(); }
        catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) { $process->stop(0); }
        $scan = Cache::store('modulaciones')->get('mod_scan:' . $token);
        if ($scan) { $scan['paso_ok'] = $process->isSuccessful(); }
        return $scan;
    }
}
