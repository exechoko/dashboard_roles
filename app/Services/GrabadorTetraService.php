<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Integración con el grabador de modulaciones TETRA (Red Box Recorders "Quantify").
 *
 * Flujo:
 *  1. Login: el password se cifra en el cliente con el algoritmo propio de Red Box
 *     y se postea a la web del grabador, que devuelve un SessionID + cookie quantifysession.
 *  2. Búsqueda: GET al handler "searchapi" con un criterio de rango fecha/hora.
 *     Devuelve un JSON con las filas (gridRows) de cada modulación.
 *  3. Audio: lo sirve el "Replay Server" local (localhost:8880), que proxea al grabador.
 */
class GrabadorTetraService
{
    private string $baseUrl;
    private string $replayUrl;
    private string $recorderAddress;
    private string $user;
    private string $password;
    private string $langId;
    private int    $timeout;

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0';

    /** Ids de descriptores TETRA devueltos por el grabador en cada fila. */
    private const F_INICIO       = '1';
    private const F_FIN          = '14';
    private const F_DURACION     = '18';
    private const F_GRUPO        = '23';
    private const F_CANAL        = '24';
    private const F_TIPO_COM     = '98';
    private const F_SSI_LLAMANTE = '102';
    private const F_SSI_LLAMADO  = '105';

    public function __construct()
    {
        $this->baseUrl         = rtrim(config('grabador.url', 'http://172.20.123.1'), '/');
        $this->replayUrl       = rtrim(config('grabador.replay_url', 'http://localhost:8880'), '/');
        $this->recorderAddress = (string) config('grabador.recorder_address', parse_url($this->baseUrl, PHP_URL_HOST));
        $this->user            = (string) config('grabador.user', '');
        $this->password        = (string) config('grabador.password', '');
        $this->langId          = (string) config('grabador.lang_id', 'es');
        $this->timeout         = (int) config('grabador.timeout', 60);
    }

    /**
     * Busca modulaciones grabadas dentro de una ventana de tiempo.
     *
     * @return array{modulaciones: array<int, array<string, mixed>>, ventana: array{desde: string, hasta: string}}
     */
    public function buscarModulaciones(Carbon $desde, Carbon $hasta): array
    {
        $sessionId = $this->autenticar();

        $client = $this->httpClient();

        $params = [
            'id'                => 'searchapi',
            'SessionID'         => $sessionId,
            'action'            => 'startsearch',
            'criteriacount'     => '1',
            'replaytophone'     => '0',
            'SearchDirection'   => '1',
            'MaximumResults'    => (string) config('grabador.max_resultados', 500),
            'Criteria1FieldID'  => '1',
            'Criteria1FieldType'=> '3',
            'Criteria1Type'     => '2',
            'Criteria1Date1'    => $desde->format('Ymd'),
            'Criteria1Time1'    => $desde->format('Hi'),
            'Criteria1Date2'    => $hasta->format('Ymd'),
            'Criteria1Time2'    => $hasta->format('Hi'),
        ];

        $resp = $client->get($this->baseUrl . '/', [
            'query'   => $params,
            'headers' => $this->cookieHeader($sessionId),
        ]);

        $json = json_decode((string) $resp->getBody(), true);

        if (!is_array($json) || !isset($json['results']['gridRows'])) {
            Log::warning('GrabadorTetraService: respuesta de búsqueda inesperada', [
                'status'  => $resp->getStatusCode(),
                'preview' => mb_substr((string) $resp->getBody(), 0, 300),
            ]);

            return [
                'modulaciones' => [],
                'ventana'      => $this->ventana($desde, $hasta),
            ];
        }

        $modulaciones = [];
        foreach ($json['results']['gridRows'] as $row) {
            $modulacion = $this->parsearFila($row);
            if ($modulacion !== null) {
                $modulaciones[] = $modulacion;
            }
        }

        usort($modulaciones, fn ($a, $b) => strcmp($a['fechaInicio'], $b['fechaInicio']));

        Log::info('GrabadorTetraService: modulaciones encontradas', [
            'desde' => $desde->format('Y-m-d H:i'),
            'hasta' => $hasta->format('Y-m-d H:i'),
            'total' => count($modulaciones),
        ]);

        return [
            'modulaciones' => $modulaciones,
            'ventana'      => $this->ventana($desde, $hasta),
        ];
    }

    /**
     * Descarga el audio WAV de una modulación desde el Replay Server local.
     *
     * @param string $itemid Formato "rowid_horaInicio" (solo dígitos y un guion bajo).
     */
    public function descargarAudio(string $itemid): \GuzzleHttp\Psr7\Response
    {
        if (!preg_match('/^\d+_\d+$/', $itemid)) {
            throw new \InvalidArgumentException('itemid de modulación inválido.');
        }

        $sessionId = $this->autenticar();

        // El Replay Server usa "+" como separador de parámetros (no "&"), por eso
        // se arma la query string a mano. Los valores son seguros (validados/propios).
        $query = implode('+', [
            'address=' . $this->recorderAddress,
            'databaseid=0',
            'sessionid=' . $sessionId,
            'langid=' . $this->langId,
            'replaymode=0',
            'itemid=' . $itemid,
        ]);

        $client = $this->httpClient();

        $resp = $client->get($this->replayUrl . '/replay/?' . $query, [
            'headers' => [
                'Referer' => $this->baseUrl . '/',
                'Origin'  => $this->baseUrl,
                'Accept'  => '*/*',
            ],
        ]);

        return new \GuzzleHttp\Psr7\Response(
            $resp->getStatusCode(),
            $resp->getHeaders(),
            (string) $resp->getBody()
        );
    }

    /**
     * Convierte un WAV (contenido binario) a MP3. El conversor se configura en
     * grabador.ffmpeg_path y puede ser ffmpeg o lame.exe (útil en servidores
     * viejos donde los builds nuevos de ffmpeg no corren: el WAV del grabador es
     * PCM 16 bits / 8 kHz, que LAME codifica directo). Devuelve null si el
     * conversor no está disponible o falla (se sirve el WAV original).
     */
    public function convertirWavAMp3(string $wav): ?string
    {
        $conversor = (string) config('grabador.ffmpeg_path', 'ffmpeg');
        $baseTmp   = tempnam(sys_get_temp_dir(), 'mod');

        if ($baseTmp === false) {
            return null;
        }

        $tmpWav = $baseTmp . '.wav';
        $tmpMp3 = $baseTmp . '.mp3';

        try {
            file_put_contents($tmpWav, $wav);

            $cmd = str_contains(strtolower(basename($conversor)), 'lame')
                ? escapeshellarg($conversor)
                    . ' --silent -b 64 '
                    . escapeshellarg($tmpWav) . ' ' . escapeshellarg($tmpMp3) . ' 2>&1'
                : escapeshellarg($conversor)
                    . ' -y -i ' . escapeshellarg($tmpWav)
                    . ' -codec:a libmp3lame -b:a 64k '
                    . escapeshellarg($tmpMp3) . ' 2>&1';

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0 || !is_file($tmpMp3) || filesize($tmpMp3) === 0) {
                Log::warning('GrabadorTetraService: conversión WAV a MP3 falló, se sirve el WAV', [
                    'conversor' => $conversor,
                    'exit'      => $exitCode,
                    'salida'    => implode(' ', array_slice($output ?? [], -3)),
                ]);

                return null;
            }

            return file_get_contents($tmpMp3) ?: null;
        } catch (\Exception $e) {
            Log::warning('GrabadorTetraService: error al convertir a MP3', ['error' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($baseTmp);
            @unlink($tmpWav);
            @unlink($tmpMp3);
        }
    }

    /**
     * Verifica si el Replay Server (que sirve los WAV) es alcanzable desde este
     * servidor. Está instalado como aplicación local: en máquinas sin él los WAV
     * del grabador no se pueden reproducir. Resultado cacheado 5 minutos.
     */
    public function replayDisponible(): bool
    {
        $cacheKey = 'grabador_replay_ok_' . md5($this->replayUrl);

        return (bool) Cache::remember($cacheKey, now()->addMinutes(5), function (): bool {
            $host = parse_url($this->replayUrl, PHP_URL_HOST) ?: 'localhost';
            $port = parse_url($this->replayUrl, PHP_URL_PORT) ?: 8880;

            $errno  = 0;
            $errstr = '';
            $socket = @fsockopen($host, (int) $port, $errno, $errstr, 2);

            if ($socket === false) {
                Log::info('GrabadorTetraService: Replay Server no disponible', [
                    'replay_url' => $this->replayUrl,
                    'error'      => $errstr ?: ('errno ' . $errno),
                ]);

                return false;
            }

            fclose($socket);

            return true;
        });
    }

    /**
     * Autentica contra el grabador y devuelve un SessionID válido (cacheado ~15 min).
     */
    private function autenticar(): string
    {
        $cacheKey = 'grabador_sid_' . md5($this->user . '|' . $this->baseUrl);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $client = $this->httpClient();

        $resp = $client->post($this->baseUrl . '/', [
            'form_params' => [
                'id'                          => 'login',
                'action'                      => 'secureauthorise',
                'username'                    => $this->user,
                'password'                    => $this->cifrarPassword($this->password),
                'calllogging'                 => '0',
                'analytics'                   => '0',
                'agentassessment'             => '0',
                'muralvisualizer'             => '0',
                'configurablebutton'          => '0',
                'partialbranding'             => 'default',
                'useminimalnavigation'        => '0',
                'showaltbrowserloadingmessage'=> '',
                'extreplay'                   => '',
            ],
        ]);

        $html = (string) $resp->getBody();

        if (!preg_match('/name="SessionID"\s+value="([^"]+)"/', $html, $m)) {
            throw new \RuntimeException('No se pudo autenticar con el grabador (SessionID no encontrado).');
        }

        $sessionId = $m[1];
        Cache::put($cacheKey, $sessionId, now()->addMinutes(15));

        return $sessionId;
    }

    /**
     * Cifra el password con el algoritmo del login web de Red Box ("rb_PasswordHandler").
     */
    private function cifrarPassword(string $plain): string
    {
        $legacy  = 'fXN7j83UQl1BRHm4aZoqFbVzhA6pkduODC0ELw5xg-cSrWveIPtTGn_29MKJYisy[]';
        $special = "@\\\"|.,?><\$`}{:;'~!#=+)%^&*/( ";
        $table   = $legacy . $special;
        $tlen    = strlen($table);

        $map = [
            '-' => 62, '_' => 63, '@' => 64, '.' => 65, '$' => 66, '`' => 67,
            '~' => 68, '!' => 69, '#' => 70, '%' => 71, '^' => 72, '&' => 73,
            '*' => 74, '(' => 75, ')' => 76, '+' => 77, '=' => 78, '{' => 79,
            '}' => 80, '|' => 81, '[' => 82, ']' => 83, '\\' => 84, ':' => 85,
            '"' => 86, ';' => 87, "'" => 88, '<' => 89, '>' => 90, '?' => 91,
            ',' => 92, '/' => 93,
        ];

        $out = '';
        for ($i = 0, $len = strlen($plain); $i < $len; $i++) {
            $c = $plain[$i];
            $o = ord($c);

            if ($c >= '0' && $c <= '9') {
                $idx = $o - 48;
            } elseif ($c >= 'A' && $c <= 'Z') {
                $idx = $o - 65 + 10;
            } elseif ($c >= 'a' && $c <= 'z') {
                $idx = $o - 97 + 36;
            } elseif (isset($map[$c])) {
                $idx = $map[$c];
            } else {
                return '';
            }

            $idx = ($idx + $i) % $tlen;
            $out .= $table[$idx];
        }

        return $out;
    }

    /**
     * Convierte una fila del grid del grabador en una modulación con metadatos limpios.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function parsearFila(array $row): ?array
    {
        $rowId = $row['rowid'] ?? null;
        if (!$rowId) {
            return null;
        }

        $campos = [];
        foreach ($row['rowdata'] ?? [] as $campo) {
            if (isset($campo['id'])) {
                $campos[(string) $campo['id']] = $this->decodificar($campo['data'] ?? '');
            }
        }

        $inicioRaw = $campos[self::F_INICIO] ?? '';
        if ($inicioRaw === '' || !preg_match('/^\d{14}/', $inicioRaw)) {
            return null;
        }

        $grupo = $campos[self::F_GRUPO] ?? '';
        $canal = $campos[self::F_CANAL] ?? '';

        return [
            'itemid'      => $rowId . '_' . $inicioRaw,
            'fechaInicio' => $this->formatearFecha($inicioRaw),
            'fechaFin'    => $this->formatearFecha($campos[self::F_FIN] ?? ''),
            'duracion'    => $campos[self::F_DURACION] ?? '',
            'grupo'       => $grupo,
            'canal'       => $canal,
            'recurso'     => $this->extraerRecurso($canal !== '' ? $canal : $grupo),
            'tipo'        => $campos[self::F_TIPO_COM] ?? '',
            'ssiLlamante' => $campos[self::F_SSI_LLAMANTE] ?? '',
            'ssiLlamado'  => $campos[self::F_SSI_LLAMADO] ?? '',
        ];
    }

    /**
     * Extrae el recurso/unidad de la etiqueta del canal: lo que está entre corchetes.
     * Ej: "GENERAL (Grupo) [Cria 904 (M2230904)] (TETRA)" → "Cria 904 (M2230904)".
     */
    private function extraerRecurso(string $canal): string
    {
        if (preg_match('/\[([^\]]+)\]/', $canal, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Da formato legible a un timestamp del grabador (YYYYMMDDHHMMSS + centésimas).
     */
    private function formatearFecha(string $raw): string
    {
        if (!preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $raw, $m)) {
            return '';
        }

        return sprintf('%s-%s-%s %s:%s:%s', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
    }

    /**
     * Decodifica entidades numéricas sueltas ("S&#195&#173mplex") y entidades HTML normales.
     */
    private function decodificar(string $value): string
    {
        $value = preg_replace_callback('/&#(\d+);?/', fn ($m) => mb_chr((int) $m[1], 'UTF-8'), $value) ?? $value;

        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return array{desde: string, hasta: string}
     */
    private function ventana(Carbon $desde, Carbon $hasta): array
    {
        return [
            'desde' => $desde->format('Y-m-d H:i:s'),
            'hasta' => $hasta->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Las cookies del grabador son "Secure" pero el servidor es http; hay que
     * reenviar quantifysession (== SessionID) manualmente en cada request.
     *
     * @return array<string, string>
     */
    private function cookieHeader(string $sessionId): array
    {
        return ['Cookie' => 'quantifysession=' . $sessionId];
    }

    private function httpClient(): Client
    {
        return new Client([
            'timeout'     => $this->timeout,
            'http_errors' => false,
            'verify'      => false,
            'headers'     => [
                'User-Agent' => self::USER_AGENT,
                'Connection' => 'close',
            ],
        ]);
    }
}
