<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GisViewerController extends Controller
{
    private string $gisBaseUrl;
    private string $user;
    private string $password;
    private int    $timeout;

    private const SESSION_KEY = 'gis_jsessionid';
    private const LOGIN_PATH  = '/gisviewer/main/cecoco/?language=es_ES';
    private const MAP_PATH    = '/gisviewer/main/cecoco/';
    private const PROXY_BASE  = '/cecoco/gis-proxy';

    private const BINARY_TYPES = [
        'image/', 'font/', 'audio/', 'video/',
        'application/octet-stream', 'application/pdf',
        'application/zip', 'application/wasm', 'application/x-font',
    ];

    public function __construct()
    {
        $this->gisBaseUrl = rtrim(config('cecoco.gis_url', 'http://172.26.100.52'), '/');
        $this->user       = config('cecoco.user', 'tecnica');
        $this->password   = config('cecoco.password', 'tecnica');
        $this->timeout    = (int) config('cecoco.timeout', 60);
    }

    // -----------------------------------------------------------------------
    // Vista principal: autentica, captura el HTML en la misma conexión Guzzle
    // y lo devuelve directamente (sin iframe, evita X-Frame-Options).
    // -----------------------------------------------------------------------
    public function index()
    {
        try {
            [$jsid, $gisHtml] = $this->autenticarYCapturar();
            session([self::SESSION_KEY => $jsid]);

            // DEBUG: guardar HTML crudo para diagnóstico
            file_put_contents(storage_path('logs/gis_raw.html'), $gisHtml);

            $gisHtml = $this->procesarHtml($gisHtml);

            // DEBUG: guardar HTML procesado para diagnóstico
            file_put_contents(storage_path('logs/gis_processed.html'), $gisHtml);

            return response($gisHtml, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            Log::error('GisViewer index error: ' . $e->getMessage());
            return view('cecoco.mapas.mapa_gis_error', ['mensaje' => $e->getMessage()]);
        }
    }

    // -----------------------------------------------------------------------
    // Proxy para todos los recursos del GIS (JS, CSS, imágenes, API REST).
    // Usa la JSESSIONID almacenada en sesión. Si el servidor responde con
    // la página de login (sesión expirada), re-autentica en el mismo cliente.
    // -----------------------------------------------------------------------
    public function proxy(Request $request, string $path = '')
    {
        if (empty($path)) {
            $path = ltrim(self::MAP_PATH, '/');
        }

        $targetUrl = $this->gisBaseUrl . '/' . ltrim($path, '/');
        if ($qs = $request->getQueryString()) {
            $targetUrl .= '?' . $qs;
        }

        // Caché para recursos estáticos
        $isStatic = (bool) preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|webp|woff|woff2|ttf|eot|ico|map)(\?.*)?$/i', $path);
        $cacheKey = 'gis_static_' . md5($targetUrl);

        if ($isStatic && Cache::has($cacheKey)) {
            $c = Cache::get($cacheKey);
            return response($c['body'], 200)
                ->header('Content-Type', $c['type'])
                ->header('Cache-Control', 'public, max-age=3600');
        }

        try {
            $jsid = session(self::SESSION_KEY);

            // Intentar con JSESSIONID en sesión (nuevo cliente Guzzle, sin problema de timeout
            // porque es un proceso PHP nuevo sin conexiones previas al GIS)
            if ($jsid) {
                [$status, $type, $body] = $this->fetchConJsid($jsid, $targetUrl, $request);

                // Si el servidor devolvió la página de login, re-autenticar
                if ($this->esRespuestaLogin($body, $type)) {
                    Log::info('GisViewer: sesión expirada, re-autenticando');
                    [$jsid, $body, $status, $type] = $this->autenticarYFetch($targetUrl, $request);
                    session([self::SESSION_KEY => $jsid]);
                }
            } else {
                [$jsid, $body, $status, $type] = $this->autenticarYFetch($targetUrl, $request);
                session([self::SESSION_KEY => $jsid]);
            }

            // Reescribir URLs en HTML y CSS (no en JS, el interceptor se encarga)
            if (!$this->esBinario($type)) {
                if (str_contains($type, 'text/html')) {
                    $body = $this->reescribirHtml($body);
                } elseif (str_contains($type, 'text/css')) {
                    $body = $this->reescribirCss($body);
                }
            }

            if ($isStatic && $status === 200) {
                Cache::put($cacheKey, ['body' => $body, 'type' => $type], 3600);
            }

            return response($body, $status)->header('Content-Type', $type);

        } catch (\Exception $e) {
            Log::error('GisViewer proxy error', ['url' => $targetUrl, 'error' => $e->getMessage()]);
            return response('', 502)->header('Content-Type', 'text/plain');
        }
    }

    // -----------------------------------------------------------------------
    // Autentica y captura el HTML del mapa en una sola sesión Guzzle.
    // La respuesta POST → redirect → mapa ES el HTML del GIS.
    // -----------------------------------------------------------------------
    private function autenticarYCapturar(): array
    {
        $cookieJar = new CookieJar();
        $client    = $this->makeClient($cookieJar);
        $loginUrl  = $this->gisBaseUrl . self::LOGIN_PATH;

        Log::info('GisViewer: autenticando', ['url' => $loginUrl]);

        $loginPage  = $client->get($loginUrl, ['headers' => $this->baseHeaders(), 'allow_redirects' => true]);
        $html       = (string) $loginPage->getBody();
        $formAction = $this->parseFormAction($html, $loginUrl);

        $mapResponse = $client->post($formAction, [
            'form_params'     => array_merge($this->parseHiddenFields($html), [
                'username' => $this->user,
                'password' => $this->password,
            ]),
            'allow_redirects' => true,
            'headers'         => array_merge($this->baseHeaders(), ['Referer' => $loginUrl]),
        ]);

        $jsid = null;
        foreach ($cookieJar as $cookie) {
            if ($cookie->getName() === 'JSESSIONID') {
                $jsid = $cookie->getValue();
                break;
            }
        }

        if (!$jsid) {
            throw new \RuntimeException('No se obtuvo JSESSIONID del Visor GIS.');
        }

        Log::info('GisViewer: autenticado correctamente.');
        return [$jsid, (string) $mapResponse->getBody()];
    }

    // -----------------------------------------------------------------------
    // Fetch usando JSESSIONID de sesión (proceso PHP nuevo = sin timeout).
    // -----------------------------------------------------------------------
    private function fetchConJsid(string $jsid, string $targetUrl, Request $request): array
    {
        $cookieJar = CookieJar::fromArray(
            ['JSESSIONID' => $jsid],
            parse_url($this->gisBaseUrl, PHP_URL_HOST)
        );
        $client    = $this->makeClient($cookieJar);
        $response  = $client->request(
            strtoupper($request->method()),
            $targetUrl,
            $this->buildOptions($request)
        );

        return [
            $response->getStatusCode(),
            $response->getHeaderLine('Content-Type') ?: 'application/octet-stream',
            (string) $response->getBody(),
        ];
    }

    // -----------------------------------------------------------------------
    // Re-autentica Y fetchea el recurso en el MISMO cliente Guzzle.
    // Evita el timeout TCP que ocurría con clientes separados.
    // -----------------------------------------------------------------------
    private function autenticarYFetch(string $targetUrl, Request $request): array
    {
        $cookieJar = new CookieJar();
        $client    = $this->makeClient($cookieJar);
        $loginUrl  = $this->gisBaseUrl . self::LOGIN_PATH;

        $loginPage  = $client->get($loginUrl, ['headers' => $this->baseHeaders(), 'allow_redirects' => true]);
        $html       = (string) $loginPage->getBody();
        $formAction = $this->parseFormAction($html, $loginUrl);

        $client->post($formAction, [
            'form_params'     => array_merge($this->parseHiddenFields($html), [
                'username' => $this->user,
                'password' => $this->password,
            ]),
            'allow_redirects' => true,
            'headers'         => array_merge($this->baseHeaders(), ['Referer' => $loginUrl]),
        ]);

        $jsid = null;
        foreach ($cookieJar as $cookie) {
            if ($cookie->getName() === 'JSESSIONID') {
                $jsid = $cookie->getValue();
                break;
            }
        }

        // Fetch del recurso con el MISMO cliente (misma conexión keep-alive)
        $response = $client->request(
            strtoupper($request->method()),
            $targetUrl,
            $this->buildOptions($request)
        );

        return [
            $jsid ?? '',
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaderLine('Content-Type') ?: 'application/octet-stream',
        ];
    }

    // -----------------------------------------------------------------------
    // Procesa el HTML del GIS: reescribe atributos HTML e inyecta
    // el interceptor JS y la barra de navegación.
    // -----------------------------------------------------------------------
    private function procesarHtml(string $html): string
    {
        $proxy   = self::PROXY_BASE;
        $escaped = preg_quote($this->gisBaseUrl, '#');

        // 0. Eliminar <base> que el GIS pueda tener (interfiere con resolución de URLs)
        $html = preg_replace('/<base\b[^>]*>/i', '', $html);

        // 1. Reescribir URLs absolutas al servidor GIS en atributos HTML
        $html = preg_replace(
            "#((?:href|src|action|data-src|data-href)\\s*=\\s*['\"]){$escaped}(/[^'\"<>\\s]*)#i",
            '$1' . $proxy . '$2',
            $html
        );

        // 2. Reescribir rutas absolutas /gisviewer/ en atributos HTML
        $html = preg_replace(
            '#((?:href|src|action|data-src|data-href|content|data-url)\s*=\s*["\'])(/gisviewer/[^"\'<>\s]*)#i',
            '$1' . $proxy . '$2',
            $html
        );

        // 3. Catch-all: str_replace para cualquier ="... o ='... que todavía
        //    apunte a /gisviewer/ (no puede causar doble-rewrite porque el paso 2
        //    ya convirtió ="/gisviewer/ en ="[proxy]/gisviewer/).
        $html = str_replace('="/gisviewer/', '="' . $proxy . '/gisviewer/', $html);
        $html = str_replace("='/gisviewer/", "='" . $proxy . '/gisviewer/', $html);

        // 4. Inyectar interceptor XHR/fetch ANTES de cualquier script del GIS
        $interceptor = $this->interceptorJs();
        $html = preg_replace('#(<head[^>]*>)#i', '$1' . $interceptor, $html, 1);

        // 5. Inyectar barra de navegación del dashboard
        $html = str_ireplace('</body>', $this->barraNavegacion() . '</body>', $html);

        return $html;
    }

    // -----------------------------------------------------------------------
    // Interceptor JavaScript: redirige todas las llamadas XHR y fetch del GIS
    // que apunten a /gisviewer/ o al servidor GIS a través de nuestro proxy.
    // Esto evita el rewrite frágil sobre código JS y resuelve el error
    // "Wrong Parameters in authentication".
    // -----------------------------------------------------------------------
    private function interceptorJs(): string
    {
        $proxy  = self::PROXY_BASE;
        $gisUrl = $this->gisBaseUrl;

        return <<<JS
        <script>
        (function(){
            var PROXY    = '{$proxy}';
            var GIS_HOST = '{$gisUrl}';

            function rw(url){
                if(!url || typeof url !== 'string') return url;
                // URL absoluta al servidor GIS
                if(url.indexOf(GIS_HOST) === 0){
                    return PROXY + url.substring(GIS_HOST.length);
                }
                // Ruta absoluta /gisviewer/
                if(url.indexOf('/gisviewer/') === 0){
                    return PROXY + url;
                }
                return url;
            }

            // Interceptar XMLHttpRequest
            var origOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(m, u){
                var args = Array.prototype.slice.call(arguments);
                args[1] = rw(u);
                return origOpen.apply(this, args);
            };

            // Interceptar fetch
            if(window.fetch){
                var origFetch = window.fetch.bind(window);
                window.fetch = function(input, init){
                    if(typeof input === 'string') input = rw(input);
                    else if(input instanceof Request) input = new Request(rw(input.url), input);
                    return origFetch(input, init);
                };
            }
        })();
        </script>
        JS;
    }

    private function barraNavegacion(): string
    {
        $urlVolver = route('cecoco.index');
        return <<<HTML
        <div id="gis-db-bar" style="position:fixed;top:0;left:0;right:0;z-index:999999;
            background:rgba(20,20,40,0.93);backdrop-filter:blur(6px);
            padding:5px 16px;display:flex;align-items:center;gap:12px;
            box-shadow:0 2px 8px rgba(0,0,0,0.5);font-family:sans-serif;height:36px;">
            <span style="color:#4fc3f7;font-size:1rem;">&#127947;</span>
            <span style="color:#fff;font-size:0.83rem;font-weight:600;">Mapa GIS &mdash; CeCoCo</span>
            <a href="{$urlVolver}" style="margin-left:auto;color:#fff;text-decoration:none;
                background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);
                padding:3px 10px;border-radius:4px;font-size:0.76rem;">
                &larr; Volver al Dashboard
            </a>
        </div>
        <div style="height:36px;"></div>
        HTML;
    }

    // -----------------------------------------------------------------------
    // Reescritura de URLs en CSS (no en JS: lo maneja el interceptor)
    // -----------------------------------------------------------------------
    private function reescribirHtml(string $html): string
    {
        $proxy   = self::PROXY_BASE;
        $escaped = preg_quote($this->gisBaseUrl, '#');

        $html = preg_replace(
            "#((?:href|src|action|data-src)\\s*=\\s*['\"]){$escaped}(/[^'\"<>\\s]*)#i",
            '$1' . $proxy . '$2', $html
        );
        $html = preg_replace(
            '#((?:href|src|action|data-src|data-url)\s*=\s*["\'])(/gisviewer/[^"\'<>\s]*)#i',
            '$1' . $proxy . '$2', $html
        );
        $html = str_replace('="/gisviewer/', '="' . $proxy . '/gisviewer/', $html);
        $html = str_replace("='/gisviewer/", "='" . $proxy . '/gisviewer/', $html);
        return $html;
    }

    private function reescribirCss(string $css): string
    {
        $proxy   = self::PROXY_BASE;
        $escaped = preg_quote($this->gisBaseUrl, '#');

        $css = preg_replace("#url\(['\"]?{$escaped}(/[^'\")\s]*)['\"]?\)#i", 'url("' . $proxy . '$1")', $css);
        $css = preg_replace("#url\(['\"]?(/gisviewer/[^'\")\s]*)['\"]?\)#i", 'url("' . $proxy . '$1")', $css);
        return $css;
    }

    // -----------------------------------------------------------------------
    // Opciones Guzzle para la petición al GIS, incluyendo cuerpo POST/PUT
    // -----------------------------------------------------------------------
    private function buildOptions(Request $request): array
    {
        $options = [
            'headers'         => [
                'Referer'          => $this->gisBaseUrl . self::MAP_PATH,
                'User-Agent'       => $request->userAgent() ?? 'Mozilla/5.0',
                'Accept'           => $request->header('Accept', '*/*'),
                'Accept-Language'  => 'es-AR,es;q=0.9',
                'X-Requested-With' => $request->header('X-Requested-With', ''),
            ],
            'allow_redirects' => false,
        ];

        $method = strtoupper($request->method());
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $ct = $request->header('Content-Type', '');
            if (str_contains($ct, 'application/json')) {
                $options['body'] = $request->getContent();
                $options['headers']['Content-Type'] = 'application/json';
            } else {
                $options['form_params'] = $request->all();
            }
        }

        return $options;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    private function makeClient(CookieJar $cookieJar): Client
    {
        return new Client([
            'verify'          => false,
            'cookies'         => $cookieJar,
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors'     => false,
        ]);
    }

    private function baseHeaders(): array
    {
        return [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-AR,es;q=0.9',
        ];
    }

    private function parseFormAction(string $html, string $currentUrl): string
    {
        if (preg_match('/<form[^>]+action=["\']([^"\']+)["\'][^>]*/i', $html, $m)) {
            return $this->toAbsoluteUrl(html_entity_decode($m[1]), $currentUrl);
        }
        return $this->gisBaseUrl . '/gisviewer/j_spring_security_check';
    }

    private function parseHiddenFields(string $html): array
    {
        $fields = [];
        preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $html, $inputs);
        foreach ($inputs[0] as $input) {
            $name = $value = null;
            if (preg_match('/name=["\']([^"\']+)["\']/i', $input, $mn)) $name = $mn[1];
            if (preg_match('/value=["\']([^"\']*)["\']?/i', $input, $mv)) $value = $mv[1];
            if ($name !== null && $value !== null) $fields[$name] = $value;
        }
        return $fields;
    }

    private function toAbsoluteUrl(string $url, string $base): string
    {
        if (preg_match('#^https?://#i', $url)) return $url;
        if (str_starts_with($url, '/')) {
            $p = parse_url($base);
            return ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? '') .
                   (isset($p['port']) ? ':' . $p['port'] : '') . $url;
        }
        return rtrim(dirname($base), '/') . '/' . $url;
    }

    private function esRespuestaLogin(string $body, string $type): bool
    {
        if (!str_contains($type, 'text/html')) return false;
        return str_contains($body, 'loginForm') || str_contains($body, 'id="password"');
    }

    private function esBinario(string $type): bool
    {
        foreach (self::BINARY_TYPES as $p) {
            if (str_contains($type, $p)) return true;
        }
        return false;
    }
}
