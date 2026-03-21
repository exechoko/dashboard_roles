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
    private string $geoServerUrl;
    private string $user;
    private string $password;
    private int $timeout;

    private const SESSION_KEY = 'gis_jsessionid';
    private const SESSION_EXTRA_KEY = 'gis_extra_cookies';
    private const LOGIN_PATH = '/gisviewer/main/cecoco/?language=es_ES';
    private const MAP_PATH = '/gisviewer/main/cecoco/';
    private const PROXY_BASE = '/cecoco/gis-proxy';
    private const SPRING_CHECK_PATH = '/gisviewer/j_security_check';

    private const BINARY_TYPES = [
        'image/',
        'font/',
        'audio/',
        'video/',
        'application/octet-stream',
        'application/pdf',
        'application/zip',
        'application/wasm',
        'application/x-font',
    ];

    public function __construct()
    {
        $this->gisBaseUrl = rtrim(config('cecoco.gis_url', 'http://172.26.100.52'), '/');
        $this->geoServerUrl = rtrim(config('cecoco.geoserver_url', 'http://172.26.100.51'), '/');
        $this->user = config('cecoco.user', 'tecnica');
        $this->password = config('cecoco.password', 'tecnica');
        $this->timeout = (int) config('cecoco.timeout', 60);
    }

    // -----------------------------------------------------------------------
    // Vista principal: autentica, captura el HTML en la misma conexión Guzzle
    // y lo devuelve directamente (sin iframe, evita X-Frame-Options).
    // -----------------------------------------------------------------------
    public function index()
    {
        try {
            [$jsid, $extraCookies, $gisHtml] = $this->autenticarYCapturar();
            session([
                self::SESSION_KEY => $jsid,
                self::SESSION_EXTRA_KEY => $extraCookies,
            ]);

            $gisHtml = $this->procesarHtml($gisHtml);

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

        $targetUrl = $this->resolveTargetUrl($path);
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

            if ($jsid) {
                [$status, $type, $body] = $this->fetchConJsid($jsid, $targetUrl, $request);

                // Si el servidor devolvió la página de login, re-autenticar
                if ($this->esRespuestaLogin($body, $type)) {
                    Log::info('GisViewer: sesión expirada, re-autenticando');
                    [$jsid, $extraCookies, $body, $status, $type] = $this->autenticarYFetch($targetUrl, $request);
                    session([
                        self::SESSION_KEY => $jsid,
                        self::SESSION_EXTRA_KEY => $extraCookies,
                    ]);
                }
            } else {
                [$jsid, $extraCookies, $body, $status, $type] = $this->autenticarYFetch($targetUrl, $request);
                session([
                    self::SESSION_KEY => $jsid,
                    self::SESSION_EXTRA_KEY => $extraCookies,
                ]);
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
    // Autentica en 4 pasos y captura el HTML del mapa en una sola sesión Guzzle.
    //
    // Paso 1: POST /gisviewer/rest/login/profiles  → nombre del perfil
    // Paso 2: GET  /gisviewer/rest/login/organism  → id y nombre del organismo
    // Paso 3: POST /gisviewer/login/cecoco/        → login CECOCO con todos los campos
    //              → el GIS devuelve un formulario auto-submit a j_security_check
    // Paso 4: POST /gisviewer/j_security_check     → login Spring Security
    //              → redirige al mapa real
    //
    // Retorna: [jsid, extraCookies[], htmlDelMapa]
    // -----------------------------------------------------------------------
    private function autenticarYCapturar(): array
    {
        $cookieJar = new CookieJar();
        $client = $this->makeClient($cookieJar);
        $loginUrl = $this->gisBaseUrl . self::LOGIN_PATH;

        Log::info('GisViewer: iniciando autenticación', ['url' => $loginUrl]);

        // Paso 3: POST al endpoint CECOCO (los pasos 1 y 2 los ejecuta buildLoginParams)
        $loginPage = $client->get($loginUrl, ['headers' => $this->baseHeaders(), 'allow_redirects' => true]);
        $formAction = $this->parseFormAction((string) $loginPage->getBody(), $loginUrl);

        $cecocoResponse = $client->post($formAction, [
            'form_params' => $this->buildLoginParams($client),
            'allow_redirects' => true,
            'headers' => array_merge($this->baseHeaders(), ['Referer' => $loginUrl]),
        ]);

        $responseHtml = (string) $cecocoResponse->getBody();

        if ($this->esRespuestaError($responseHtml)) {
            throw new \RuntimeException('El Visor GIS rechazó la autenticación: ' . $this->extraerMensajeError($responseHtml));
        }

        // Paso 4: el GIS devuelve un formulario auto-submit a /gisviewer/j_security_check
        // (Spring Security). En el navegador JS lo envía automáticamente; nosotros lo hacemos manualmente.
        if ($this->esFormAutoSubmit($responseHtml)) {
            Log::info('GisViewer: ejecutando login Spring Security (j_security_check)');
            $springUrl = $this->gisBaseUrl . self::SPRING_CHECK_PATH;

            $springResponse = $client->post($springUrl, [
                'form_params' => [
                    'j_username' => $this->user,
                    'j_password' => $this->password,
                ],
                'allow_redirects' => true,
                'headers' => array_merge($this->baseHeaders(), [
                    'Referer' => $this->gisBaseUrl . self::MAP_PATH,
                ]),
            ]);

            $responseHtml = (string) $springResponse->getBody();
        }

        if ($this->esRespuestaError($responseHtml)) {
            throw new \RuntimeException('El Visor GIS rechazó el login Spring Security: ' . $this->extraerMensajeError($responseHtml));
        }

        if ($this->esFormAutoSubmit($responseHtml)) {
            throw new \RuntimeException('Loop de autenticación detectado en el Visor GIS.');
        }

        if ($this->esRespuestaLoginInteractivo($responseHtml)) {
            throw new \RuntimeException('Credenciales rechazadas por el Visor GIS.');
        }

        [$jsid, $extraCookies] = $this->extraerCookies($cookieJar);

        Log::info('GisViewer: autenticado correctamente.', [
            'extra_cookies' => array_keys($extraCookies),
        ]);

        return [$jsid, $extraCookies, $responseHtml];
    }

    // -----------------------------------------------------------------------
    // Fetch usando JSESSIONID + cookies extras de sesión (JWT, etc.).
    // -----------------------------------------------------------------------
    private function fetchConJsid(string $jsid, string $targetUrl, Request $request): array
    {
        $extraCookies = session(self::SESSION_EXTRA_KEY, []);
        $allCookies = array_merge(['JSESSIONID' => $jsid], $extraCookies);

        $cookieJar = CookieJar::fromArray($allCookies, parse_url($this->gisBaseUrl, PHP_URL_HOST));
        $client = $this->makeClient($cookieJar);
        $response = $client->request(
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
    // Re-autentica (4 pasos) Y fetchea el recurso en el MISMO cliente Guzzle.
    // Retorna: [jsid, extraCookies[], body, status, contentType]
    // -----------------------------------------------------------------------
    private function autenticarYFetch(string $targetUrl, Request $request): array
    {
        $cookieJar = new CookieJar();
        $client = $this->makeClient($cookieJar);
        $loginUrl = $this->gisBaseUrl . self::LOGIN_PATH;

        $loginPage = $client->get($loginUrl, ['headers' => $this->baseHeaders(), 'allow_redirects' => true]);
        $formAction = $this->parseFormAction((string) $loginPage->getBody(), $loginUrl);

        $cecocoResp = $client->post($formAction, [
            'form_params' => $this->buildLoginParams($client),
            'allow_redirects' => true,
            'headers' => array_merge($this->baseHeaders(), ['Referer' => $loginUrl]),
        ]);

        // Paso 4: formulario auto-submit a Spring Security
        if ($this->esFormAutoSubmit((string) $cecocoResp->getBody())) {
            $client->post($this->gisBaseUrl . self::SPRING_CHECK_PATH, [
                'form_params' => [
                    'j_username' => $this->user,
                    'j_password' => $this->password,
                ],
                'allow_redirects' => true,
                'headers' => array_merge($this->baseHeaders(), [
                    'Referer' => $this->gisBaseUrl . self::MAP_PATH,
                ]),
            ]);
        }

        [$jsid, $extraCookies] = $this->extraerCookies($cookieJar);

        // Fetch del recurso con el MISMO cliente (cookies ya presentes en jar)
        $response = $client->request(
            strtoupper($request->method()),
            $targetUrl,
            $this->buildOptions($request)
        );

        return [
            $jsid ?? '',
            $extraCookies,
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaderLine('Content-Type') ?: 'application/octet-stream',
        ];
    }

    // -----------------------------------------------------------------------
    // Construye los parámetros del formulario de login en 3 pasos:
    // 1. Obtiene el perfil desde la API de perfiles
    // 2. Obtiene el organismo desde la API de organismos
    // 3. Devuelve todos los campos requeridos por el GIS
    // -----------------------------------------------------------------------
    private function buildLoginParams(Client $client): array
    {
        // Paso 1: cargar perfiles
        $profile = $this->cargarPrimerPerfil($client);

        // Paso 2: obtener organismo
        [$orgId, $orgName] = $this->obtenerOrganismo($client, $profile);

        return [
            'j_username' => $this->user,
            'j_password' => $this->password,
            'j_profiles' => $profile,
            'j_subscriber' => '0',
            'j_profile_name' => $profile,
            'j_organism_id' => $orgId,
            'j_organism_name' => $orgName,
            'j_language' => 'es_ES',
            'j_desktop_login' => '0',
        ];
    }

    // -----------------------------------------------------------------------
    // POST /gisviewer/rest/login/profiles → devuelve el primer perfil disponible.
    // Si CeCoCo está caído usa el endpoint de fallback.
    // -----------------------------------------------------------------------
    private function cargarPrimerPerfil(Client $client): string
    {
        $profilesUrl = $this->gisBaseUrl . '/gisviewer/rest/login/profiles';

        $response = $client->post($profilesUrl, [
            'form_params' => [
                'idsubscriber' => '0',
                'username' => $this->user,
                'pwd' => $this->password,
            ],
            'headers' => $this->baseHeaders(),
            'allow_redirects' => true,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (!is_array($data)) {
            throw new \RuntimeException('Respuesta inválida al cargar perfiles del GIS.');
        }

        // CeCoCo caído → fallback a caché de BD
        if (!empty($data['iscecocoerror'])) {
            Log::warning('GisViewer: CeCoCo caído, usando fallback de perfiles.');
            $fallbackUrl = $this->gisBaseUrl
                . '/gisviewer/rest/login/profiles/0/'
                . rawurlencode($this->user) . '/'
                . rawurlencode($this->password) . '/fallback';

            $fbResp = $client->get($fallbackUrl, ['headers' => $this->baseHeaders()]);
            $fbData = json_decode((string) $fbResp->getBody(), true);

            if (!empty($fbData) && is_array($fbData)) {
                return (string) ($fbData[0]['value_id'] ?? '');
            }

            throw new \RuntimeException('No hay perfiles disponibles (CeCoCo caído, fallback vacío).');
        }

        // Error de validación
        if (!empty($data['isresponseerror'])) {
            $msg = $data['errormsg'] ?? 'Error desconocido';
            throw new \RuntimeException('Error al cargar perfiles del GIS: ' . $msg);
        }

        $profiles = $data['response'] ?? [];
        if (empty($profiles)) {
            throw new \RuntimeException('No hay perfiles disponibles para el usuario GIS.');
        }

        Log::info('GisViewer: perfil seleccionado.', ['profile' => $profiles[0]]);
        return (string) $profiles[0];
    }

    // -----------------------------------------------------------------------
    // GET /gisviewer/rest/login/organism/... → devuelve [id, nombre] del organismo.
    // -----------------------------------------------------------------------
    private function obtenerOrganismo(Client $client, string $profile): array
    {
        $url = $this->gisBaseUrl
            . '/gisviewer/rest/login/organism/get/id/name/0/'
            . rawurlencode($this->user) . '/'
            . rawurlencode($this->password) . '/'
            . rawurlencode($profile);

        try {
            $response = $client->get($url, [
                'headers' => $this->baseHeaders(),
                'allow_redirects' => true,
                'timeout' => 15,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (is_array($data) && isset($data['value_id'])) {
                Log::info('GisViewer: organismo obtenido.', ['organism' => $data['value_name'] ?? '']);
                return [(string) $data['value_id'], (string) ($data['value_name'] ?? '')];
            }
        } catch (\Exception $e) {
            Log::warning('GisViewer: no se pudo obtener organismo.', ['error' => $e->getMessage()]);
        }

        return ['', ''];
    }

    // -----------------------------------------------------------------------
    // Procesa el HTML del GIS: reescribe atributos HTML e inyecta
    // el interceptor JS y la barra de navegación.
    // -----------------------------------------------------------------------
    private function procesarHtml(string $html): string
    {
        $proxy = self::PROXY_BASE;
        $escapedGis = preg_quote($this->gisBaseUrl, '#');
        $escapedGeo = preg_quote($this->geoServerUrl, '#');

        // 0. Eliminar <base> que el GIS pueda tener (interfiere con resolución de URLs)
        $html = preg_replace('/<base\b[^>]*>/i', '', $html);

        // 1. Reescribir URLs absolutas al GIS Viewer en atributos HTML
        $html = preg_replace(
            "#((?:href|src|action|data-src|data-href)\\s*=\\s*['\"]){$escapedGis}(/[^'\"<>\\s]*)#i",
            '$1' . $proxy . '$2',
            $html
        );

        // 1b. Reescribir URLs absolutas al GeoServer en atributos HTML
        $html = preg_replace(
            "#((?:href|src|action|data-src|data-href)\\s*=\\s*['\"]){$escapedGeo}(/[^'\"<>\\s]*)#i",
            '$1' . $proxy . '$2',
            $html
        );

        // 2. Reescribir rutas absolutas /gisviewer/ y /geoserver/ en atributos HTML
        $html = preg_replace(
            '#((?:href|src|action|data-src|data-href|content|data-url)\s*=\s*["\'])(/(?:gisviewer|geoserver)/[^"\'<>\s]*)#i',
            '$1' . $proxy . '$2',
            $html
        );

        // 3. Catch-all para ="... y ='... que apunten a /gisviewer/ o /geoserver/
        $html = str_replace('="/gisviewer/', '="' . $proxy . '/gisviewer/', $html);
        $html = str_replace("='/gisviewer/", "='" . $proxy . '/gisviewer/', $html);
        $html = str_replace('="/geoserver/', '="' . $proxy . '/geoserver/', $html);
        $html = str_replace("='/geoserver/", "='" . $proxy . '/geoserver/', $html);

        // 4. Inyectar interceptor XHR/fetch ANTES de cualquier script del GIS
        $interceptor = $this->interceptorJs();
        $html = preg_replace('#(<head[^>]*>)#i', '$1' . $interceptor, $html, 1);

        // 5. Inyectar barra de navegación del dashboard
        $html = str_ireplace('</body>', $this->barraNavegacion() . '</body>', $html);

        return $html;
    }

    // -----------------------------------------------------------------------
    // Interceptor JavaScript: redirige XHR, fetch y asignaciones de src/href
    // dinámicas que apunten a /gisviewer/ o al servidor GIS.
    // -----------------------------------------------------------------------
    private function interceptorJs(): string
    {
        $proxy = self::PROXY_BASE;
        $gisUrl = $this->gisBaseUrl;
        $geoUrl = $this->geoServerUrl;

        return <<<JS
        <script>
        (function(){
            var PROXY    = '{$proxy}';
            var GIS_HOST = '{$gisUrl}';
            var GEO_HOST = '{$geoUrl}';

            function rw(url){
                if(!url || typeof url !== 'string') return url;
                if(url.indexOf(PROXY) === 0) return url;
                
                var u = url;
                
                // Si es una ruta absoluta que ya conocemos
                if(u.indexOf('/gisviewer/') === 0 || u.indexOf('/geoserver/') === 0) return PROXY + u;

                // Atrapar CUALQUIER URL absoluta que apunte a /geoserver/ o a /gisviewer/
                // sin importar la IP, el puerto o el protocolo (http/https).
                var match = u.match(/^(?:https?:)?\/\/[^\/]+(\/geoserver\/.*|\/gisviewer\/.*)$/i);
                if(match) {
                    return PROXY + match[1];
                }

                // Normalizar puerto :80 y fallbacks clásicos por si acaso
                u = u.replace(/^(https?:\/\/[^\/]+):80(\/|$)/i, '$1$2');
                if(u.indexOf(GIS_HOST + '/') === 0) return PROXY + u.substring(GIS_HOST.length);
                if(u === GIS_HOST)                  return PROXY + '/';
                if(u.indexOf(GEO_HOST + '/') === 0) return PROXY + u.substring(GEO_HOST.length);
                if(u === GEO_HOST)                  return PROXY + '/';
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

            // Interceptar img.src asignada dinámicamente por JS del GIS
            var srcDesc = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, 'src');
            if(srcDesc && srcDesc.set){
                Object.defineProperty(HTMLImageElement.prototype, 'src', {
                    set: function(v){ srcDesc.set.call(this, rw(v)); },
                    get: function(){ return srcDesc.get.call(this); },
                    configurable: true
                });
            }

            // Interceptar setAttribute('src',...) y setAttribute('href',...)
            var origSetAttr = Element.prototype.setAttribute;
            Element.prototype.setAttribute = function(name, value){
                var n = name.toLowerCase();
                if((n === 'src' || n === 'href' || n === 'action') && typeof value === 'string'){
                    value = rw(value);
                }
                return origSetAttr.call(this, name, value);
            };
        })();
        </script>
        JS;
    }

    // Botón flotante pequeño en esquina inferior-derecha para no tapar la UI del GIS.
    private function barraNavegacion(): string
    {
        $urlVolver = route('home');
        return <<<HTML
        <a id="gis-back-btn" href="{$urlVolver}"
            style="position:fixed;bottom:18px;right:18px;z-index:999999;
                background:rgba(20,20,40,0.88);backdrop-filter:blur(4px);
                color:#fff;text-decoration:none;font-family:sans-serif;font-size:0.78rem;
                padding:6px 13px;border-radius:20px;
                border:1px solid rgba(255,255,255,0.22);
                box-shadow:0 2px 8px rgba(0,0,0,0.45);
                display:flex;align-items:center;gap:5px;white-space:nowrap;">
            &#8592; Dashboard
        </a>
        HTML;
    }

    // -----------------------------------------------------------------------
    // Reescritura de URLs en CSS (no en JS: lo maneja el interceptor)
    // -----------------------------------------------------------------------
    private function reescribirHtml(string $html): string
    {
        $proxy = self::PROXY_BASE;
        $escaped = preg_quote($this->gisBaseUrl, '#');

        $html = preg_replace(
            "#((?:href|src|action|data-src)\\s*=\\s*['\"]){$escaped}(/[^'\"<>\\s]*)#i",
            '$1' . $proxy . '$2',
            $html
        );
        $html = preg_replace(
            '#((?:href|src|action|data-src|data-url)\s*=\s*["\'])(/gisviewer/[^"\'<>\s]*)#i',
            '$1' . $proxy . '$2',
            $html
        );
        $html = str_replace('="/gisviewer/', '="' . $proxy . '/gisviewer/', $html);
        $html = str_replace("='/gisviewer/", "='" . $proxy . '/gisviewer/', $html);
        return $html;
    }

    private function reescribirCss(string $css): string
    {
        $proxy = self::PROXY_BASE;
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
            'headers' => [
                'Referer' => $this->gisBaseUrl . self::MAP_PATH,
                'User-Agent' => $request->userAgent() ?? 'Mozilla/5.0',
                'Accept' => $request->header('Accept', '*/*'),
                'Accept-Language' => 'es-AR,es;q=0.9',
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
            'verify' => false,
            'cookies' => $cookieJar,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors' => false,
        ]);
    }

    private function baseHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-AR,es;q=0.9',
        ];
    }

    private function parseFormAction(string $html, string $currentUrl): string
    {
        if (preg_match('/<form[^>]+action=["\']([^"\']+)["\'][^>]*/i', $html, $m)) {
            return $this->toAbsoluteUrl(html_entity_decode($m[1]), $currentUrl);
        }
        return $this->gisBaseUrl . '/gisviewer/login/cecoco/';
    }

    // Resuelve la URL destino correcta según el path:
    // - /geoserver/... → GeoServer (172.26.100.51)
    // - /gisviewer/... → GIS Viewer (172.26.100.52)
    private function resolveTargetUrl(string $path): string
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'geoserver/') || $path === 'geoserver') {
            return $this->geoServerUrl . '/' . $path;
        }
        return $this->gisBaseUrl . '/' . $path;
    }

    private function toAbsoluteUrl(string $url, string $base): string
    {
        if (preg_match('#^https?://#i', $url))
            return $url;
        if (str_starts_with($url, '/')) {
            $p = parse_url($base);
            return ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? '') .
                (isset($p['port']) ? ':' . $p['port'] : '') . $url;
        }
        return rtrim(dirname($base), '/') . '/' . $url;
    }

    // Detecta el formulario auto-submit que el GIS devuelve tras el login CECOCO.
    // Este formulario hace POST a j_security_check (Spring Security).
    private function esFormAutoSubmit(string $body): bool
    {
        return str_contains($body, 'loginFormFinal')
            || str_contains($body, 'j_security_check');
    }

    // Detecta la página de login INTERACTIVA (credenciales rechazadas).
    // No confundir con el formulario auto-submit (loginFormFinal).
    private function esRespuestaLoginInteractivo(string $body): bool
    {
        return str_contains($body, 'id="password"')
            || (str_contains($body, 'var loginPage = true') && !$this->esFormAutoSubmit($body));
    }

    // Compatibilidad: usado en proxy para detectar sesión expirada.
    private function esRespuestaLogin(string $body, string $type): bool
    {
        if (!str_contains($type, 'text/html'))
            return false;
        return $this->esRespuestaLoginInteractivo($body);
    }

    // Extrae JSESSIONID y las demás cookies del jar.
    // Retorna: [jsid, ['cookieName' => 'cookieValue', ...]]
    private function extraerCookies(CookieJar $cookieJar): array
    {
        $jsid = null;
        $extra = [];

        foreach ($cookieJar as $cookie) {
            $name = $cookie->getName();
            if ($name === 'JSESSIONID') {
                $jsid = $cookie->getValue();
            } else {
                $extra[$name] = $cookie->getValue();
            }
        }

        if (!$jsid) {
            throw new \RuntimeException('No se obtuvo JSESSIONID del Visor GIS.');
        }

        return [$jsid, $extra];
    }

    private function esRespuestaError(string $body): bool
    {
        return str_contains($body, 'errorPageTitle')
            || (str_contains($body, 'msgWarning') && str_contains($body, 'ERROR:'));
    }

    private function extraerMensajeError(string $body): string
    {
        if (preg_match("/ERROR:\s*\\d+:\s*([^'\"<]+)/u", $body, $m)) {
            return trim($m[1]);
        }
        return 'Error desconocido del Visor GIS.';
    }

    private function esBinario(string $type): bool
    {
        foreach (self::BINARY_TYPES as $p) {
            if (str_contains($type, $p))
                return true;
        }
        return false;
    }
}
