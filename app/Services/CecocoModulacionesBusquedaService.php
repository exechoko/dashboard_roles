<?php

namespace App\Services;

use App\Models\EventoCecoco;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CecocoModulacionesBusquedaService
{
    public const LIMITE = 2000;
    public const AVISO_LIMITE = 'Se alcanzó el límite de 2000 modulaciones. La búsqueda puede estar incompleta y algunos móviles pueden no aparecer.';

    public function ventana(EventoCecoco $evento): array
    {
        abort_if(!$evento->fecha_hora, 422, 'El evento no tiene fecha/hora registrada.');
        return [
            'desde' => $evento->fecha_hora->copy()->subMinutes((int) config('grabador.minutos_antes', 10))->format('Y-m-d H:i:s'),
            'hasta' => ($evento->fecha_cierre ?: $evento->fecha_hora->copy()->addMinutes((int) config('grabador.minutos_despues_sin_cierre', 60)))->format('Y-m-d H:i:s'),
        ];
    }

    private function key(int $user, EventoCecoco $evento): string
    {
        return 'mod_search:' . $user . ':' . $evento->getKey();
    }

    public function recuperar(int $user, EventoCecoco $evento, ?string $token = null): ?array
    {
        $state = Cache::store('modulaciones')->get($this->key($user, $evento));
        if (!$state || $state['ventana'] !== $this->ventana($evento)) { return null; }
        abort_if($token !== null && !hash_equals($state['busqueda_id'], $token), 404);
        if ($state['estado'] === 'completa' && now()->timestamp - $state['terminada'] > ($evento->fecha_cierre ? 600 : 60)) {
            return null;
        }
        return $state;
    }

    private function guardar(array $state, int $user, EventoCecoco $evento): array
    {
        Cache::store('modulaciones')->put($this->key($user, $evento), $state, now()->addMinutes(30));
        return $state;
    }

    public function iniciar(int $user, EventoCecoco $evento, bool $refresh): array
    {
        $lock = Cache::store('modulaciones')->lock($this->key($user, $evento) . ':lock', 45);
        abort_unless($lock->get(), 409, 'La búsqueda está ocupada; recuperá su estado.');
        try {
            $previous = $this->recuperar($user, $evento);
            if ($previous && !$refresh) { return $this->guardar($previous, $user, $evento); }
            $state = ['busqueda_id' => bin2hex(random_bytes(32)), 'revision' => 0,
                'estado' => 'buscando', 'ventana' => $this->ventana($evento),
                'fuente' => config('grabador.url') ? 'grabador' : 'local',
                'cursor' => ['fase' => 'startsearch'], 'items' => [], 'agotada' => false,
                'fase' => config('grabador.url') ? 'grabador' : 'local',
                'progreso' => now()->timestamp, 'max_skip' => 0, 'terminada' => null];
            return $this->guardar($state, $user, $evento);
        } finally { $lock->release(); }
    }

    public function estado(int $user, EventoCecoco $evento, ?string $token): ?array
    {
        // Refresh inactivity only while holding the writer's lock.
        $state = $this->recuperar($user, $evento, $token);
        if ($state) {
            $lock = Cache::store('modulaciones')->lock($this->key($user, $evento) . ':lock', 45);
            if ($lock->get()) {
                try {
                    $state = $this->recuperar($user, $evento, $token);
                    if ($state) { $this->guardar($state, $user, $evento); }
                } finally { $lock->release(); }
            }
        }
        return $state;
    }

    public function avanzar(int $user, EventoCecoco $evento, string $token, int $revision): array
    {
        @set_time_limit(100);
        $deadline = microtime(true) + 70; // Una página de 1000 filas tarda ~35 s en el grabador; margen para guardar y bajo el límite de 100 s de Cloudflare.
        $lock = Cache::store('modulaciones')->lock($this->key($user, $evento) . ':lock', 100);
        abort_unless($lock->get(), 409, 'La búsqueda está ocupada; recuperá su estado.');
        try {
            $state = $this->recuperar($user, $evento, $token);
            abort_unless($state, 410, 'La búsqueda caducó. Actualizá para comenzar otra.');
            if ($revision !== $state['revision'] || in_array($state['estado'], ['completa', 'limite_alcanzado'], true)) {
                return $this->guardar($state, $user, $evento);
            }
            $state['revision']++;
            if ($state['estado'] === 'pausada') { $state['progreso'] = now()->timestamp; }
            $state['estado'] = 'buscando';
            try {
                if ($state['fase'] === 'grabador') {
                    $sessionLock = Cache::store('modulaciones')->lock('mod_search_recorder:' . hash('sha256', config('grabador.url') . '|' . config('grabador.user')), 100);
                    if (!$sessionLock->get()) { return $this->guardar($state, $user, $evento); }
                    try {
                        $page = app(GrabadorTetraService::class)->avanzarBusqueda($state['cursor'], Carbon::parse($state['ventana']['desde']), Carbon::parse($state['ventana']['hasta']), $deadline);
                    } finally { $sessionLock->release(); }
                    $oldCount = count($state['items']);
                    $state['cursor'] = $page['cursor'];
                    $truncated = false;
                    foreach ($page['modulaciones'] as $item) {
                        if (count($state['items']) >= self::LIMITE && !isset($state['items'][$item['itemid']])) {
                            $truncated = true; continue;
                        }
                        $state['items'][$item['itemid']] = $item;
                    }
                    if (count($state['items']) > $oldCount || ($state['cursor']['skip'] ?? 0) > $state['max_skip']) {
                        $state['progreso'] = now()->timestamp;
                    }
                    $state['max_skip'] = max($state['max_skip'], $state['cursor']['skip'] ?? 0);
                    $state['agotada'] = $page['agotada'] && !$truncated;
                    if ($state['agotada'] && !$state['items']) { $state['fuente'] = 'local'; }
                    if ($state['agotada'] || count($state['items']) >= self::LIMITE) {
                        // Save rows before any optional filesystem operation.
                        $state['fase'] = 'local';
                        $this->guardar($state, $user, $evento);
                    }
                } else {
                    if ($state['fuente'] === 'grabador' && app(GrabadorTetraService::class)->replayDisponible()) {
                        $state['estado'] = $state['agotada'] ? 'completa' : 'limite_alcanzado';
                        $state['terminada'] = now()->timestamp;
                        return $this->guardar($state, $user, $evento);
                    }
                    $jobKey = 'mod_scan:' . $token;
                    if (!Cache::store('modulaciones')->has($jobKey)) {
                        $minutes = [];
                        foreach ($state['items'] as $item) {
                            $time = Carbon::parse($item['fechaInicio']);
                            foreach ([-1, 0, 1] as $offset) { $minutes[$time->copy()->addMinutes($offset)->format('Ymd_Hi')] = true; }
                        }
                        Cache::store('modulaciones')->put($jobKey, ['ventana' => $state['ventana'], 'checkpoint' => [], 'archivos' => [],
                            'minutos' => $minutes, 'local' => $state['fuente'] === 'local', 'completa' => false, 'agotada' => true], now()->addMinutes(30));
                    }
                    $scan = app(CecocoModulacionesEscaneoService::class)->avanzar($token, $deadline);
                    if (!$scan) { throw new \RuntimeException('Missing scan checkpoint'); }
                    if (($state['checkpoint'] ?? null) !== $scan['checkpoint']) { $state['progreso'] = now()->timestamp; }
                    $state['checkpoint'] = $scan['checkpoint'];
                    if ($state['fuente'] === 'local') {
                        $items = [];
                        foreach ($scan['archivos'] as $item) {
                            $key = $item['fechaInicio'] . '|' . $item['duracion'] . '|' . $item['canal'];
                            if (isset($items[$key])) {
                                $items[$key]['copias']++;
                                $items[$key]['operadores'] = array_values(array_unique(array_merge($items[$key]['operadores'], [$item['operador']])));
                            } else {
                                $item['copias'] = 1; $item['operadores'] = [$item['operador']]; $items[$key] = $item;
                            }
                        }
                        $state['agotada'] = $scan['completa'] && $scan['agotada'] && count($items) <= self::LIMITE;
                        $state['items'] = array_slice(array_values($items), 0, self::LIMITE);
                    }
                    // At the recorder cap, local enrichment must not hold the snapshot indefinitely.
                    $atCap = $state['fuente'] === 'grabador' && count($state['items']) >= self::LIMITE;
                    if ($scan['completa'] || $atCap) {
                        $local = app(CecocoModulacionesLocalService::class);
                        if ($state['fuente'] === 'grabador') {
                            $state['items'] = $local->emparejarConArchivos(array_values($state['items']), $scan['archivos']);
                            if ($scan['completa']) {
                                foreach ($state['items'] as &$item) {
                                    if (empty($item['path']) && empty($item['candidatosAudio'])) { $item['audioDisponible'] = false; }
                                }
                                unset($item);
                            }
                        }
                        $state['estado'] = $state['agotada'] ? 'completa' : 'limite_alcanzado';
                        $state['terminada'] = now()->timestamp;
                        Cache::store('modulaciones')->forget($jobKey);
                    } elseif (!($scan['paso_ok'] ?? true)) { $state['estado'] = 'pausada'; }
                }
                if ($state['estado'] === 'buscando' && now()->timestamp - $state['progreso'] >= 120) { $state['estado'] = 'pausada'; }
            } catch (\Throwable $e) {
                // Preserve source, rows and the pending operation after transport failures.
                $diagnostic = ['fase' => $state['fase'], 'tipo_error' => get_class($e),
                    'archivo' => basename($e->getFile()), 'linea' => $e->getLine()];
                if ($e instanceof \GuzzleHttp\Exception\RequestException || $e instanceof \GuzzleHttp\Exception\ConnectException) {
                    $diagnostic['errno'] = $e->getHandlerContext()['errno'] ?? null;
                    $diagnostic['http'] = $e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()
                        ? $e->getResponse()->getStatusCode() : null;
                }
                \Illuminate\Support\Facades\Log::warning('modulaciones: paso pausado', $diagnostic);
                $state['estado'] = 'pausada';
            }
            return $this->guardar($state, $user, $evento);
        } finally { $lock->release(); }
    }

    public function respuesta(array $state): array
    {
        $status = $state['estado'];
        $result = ['success' => true, 'estado' => $status, 'busqueda_id' => $state['busqueda_id'],
            'revision' => $state['revision'], 'total' => count($state['items']), 'limite' => self::LIMITE,
            'hayMas' => !in_array($status, ['completa', 'limite_alcanzado'], true),
            'ventana' => $state['ventana'], 'fuente' => $state['fuente'], 'reintentar_en' => 1.5,
            'avance' => $state['progreso'],
            'message' => $status === 'completa' ? 'Búsqueda completa: ' . count($state['items']) . ' modulaciones' :
                ($status === 'limite_alcanzado' ? self::AVISO_LIMITE : ($status === 'pausada' ? 'Búsqueda pausada; conservamos el avance' : 'Buscando modulaciones de la ventana horaria…'))];
        if (!$result['hayMas']) { $result['modulaciones'] = array_values($state['items']); }
        return $result;
    }
}
