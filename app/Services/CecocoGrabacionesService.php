<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CecocoGrabacionesService
{
    private string $baseUrl;
    private string $user;
    private string $password;
    private int $timeout;

    private const PROTOCOLO_BRI  = '5'; // option value="5">BRI en BuscadorLlamadas
    private const BUSCADOR_PATH  = '/app/informes/llamadas/BuscadorLlamadas.faces';
    private const AUDIO_PATH     = '/ajax/administracion/AjaxServletGrabaciones';

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('cecoco.url', 'http://172.26.100.34:8080'), '/') . '/CECOCO_webapp';
        $this->user     = config('cecoco.user', '');
        $this->password = config('cecoco.password', '');
        $this->timeout  = (int) config('cecoco.timeout', 60);
    }

    /**
     * Busca grabaciones BRI en CECOCO para un teléfono y ventana de tiempo.
     *
     * @return array{grabaciones: array, ventana: array}
     */
    public function buscarGrabaciones(
        string $telefono,
        Carbon $fechaEvento,
        int    $minAntes      = 5,
        int    $minDespues    = 30
    ): array {
        $desde = $fechaEvento->copy()->subMinutes($minAntes);
        $hasta = $fechaEvento->copy()->addMinutes($minDespues);

        Log::info('CecocoGrabacionesService: buscando grabaciones BRI', [
            'telefono' => $telefono,
            'desde'    => $desde->format('Y-m-d H:i:s'),
            'hasta'    => $hasta->format('Y-m-d H:i:s'),
        ]);

        $jar    = new CookieJar();
        $client = new Client([
            'cookies'     => $jar,
            'timeout'     => $this->timeout,
            'http_errors' => false,
            'verify'      => false,
            'headers'     => [
                'User-Agent'  => 'Mozilla/5.0',
                'Connection'  => 'close',
            ],
            'curl'        => [
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_FRESH_CONNECT => true,
            ],
        ]);

        // 1. Autenticar
        $client->get($this->baseUrl);
        $client->post($this->baseUrl . '/ajax/perfil/AjaxServletPerfil', [
            'form_params' => [
                'LoginForm:Usuario'  => $this->user,
                'LoginForm:Password' => $this->password,
            ],
        ]);

        // 2. GET del buscador para obtener ViewState inicial
        $buscadorUrl = $this->baseUrl . self::BUSCADOR_PATH;
        $initHtml    = (string) $client->get($buscadorUrl)->getBody();
        $viewState   = $this->extraerViewState($initHtml);

        if (!$viewState) {
            throw new \RuntimeException(
                'No se pudo obtener el ViewState de BuscadorLlamadas.faces (posible error de autenticación)'
            );
        }

        // 3. Armar datos del formulario de búsqueda
        $formData = [
            'busquedaForm:indicePag'           => '0',
            'busquedaForm:direccionOrden'      => 'ascending',
            'busquedaForm:campoOrden'          => '11',
            'busquedaForm:tipoInforme'         => '0',
            'busquedaForm:idLlamada'           => '',
            'busquedaForm:idServidor'          => '',
            'busquedaForm:protocolo'           => self::PROTOCOLO_BRI,
            'busquedaForm:fechaInicio'         => $desde->format('d/m/Y H:i:s'),
            'busquedaForm:fechaFin'            => $hasta->format('d/m/Y H:i:s'),
            'busquedaForm:terminal'            => '',
            'busquedaForm:conGrabaciones'      => 'true',
            'busquedaForm:operador'            => '',
            'busquedaForm:numeroONombreRemoto' => $telefono,
            'busquedaForm:atributoRemoto'      => '0',
            'busquedaForm:servidorUbicacion'   => '-1',
            'modificado'                       => '1',
            'busquedaForm:botonBusqueda'       => 'Buscar',
            'autoScroll'                       => '',
            'busquedaForm_SUBMIT'              => '1',
            'busquedaForm:_idcl'               => '',
            'busquedaForm:_link_hidden_'       => '',
            'javax.faces.ViewState'            => $viewState,
        ];

        // 4. POST búsqueda
        $searchResp = $client->post($buscadorUrl, [
            'form_params' => $formData,
            'headers'     => ['Referer' => $buscadorUrl],
        ]);
        $searchHtml = (string) $searchResp->getBody();

        Log::debug('CecocoGrabacionesService: respuesta búsqueda', [
            'status'  => $searchResp->getStatusCode(),
            'bytes'   => strlen($searchHtml),
            'preview' => mb_substr(strip_tags($searchHtml), 0, 300),
        ]);

        // 5. Extraer ViewState de los resultados (necesario para el siguiente POST)
        $searchViewState = $this->extraerViewState($searchHtml) ?? $viewState;

        // 6. Parsear llamadas encontradas
        $llamadas = $this->parsearLlamadas($searchHtml);

        Log::info('CecocoGrabacionesService: llamadas encontradas', [
            'telefono' => $telefono,
            'total'    => count($llamadas),
        ]);

        $grabaciones = [];

        // 7. Para cada llamada, navegar a BuscadorGrabacionesLlamadasOperador.faces
        foreach ($llamadas as $i => $llamada) {
            $imageInput = $llamada['imageInput'] ?? 'busquedaForm:listado:_idJsp32';

            $grabHtml = $this->obtenerPaginaGrabaciones(
                $client,
                $buscadorUrl,
                $formData,
                $searchViewState,
                (int) $llamada['idLlamada'],
                (int) $llamada['idServidor'],
                $imageInput
            );

            $audios = $this->parsearAudiosDePopup($grabHtml);

            if (!empty($audios)) {
                foreach ($audios as $audio) {
                    $grabaciones[] = [
                        'tipo'          => 'bri',
                        'nombreFichero' => $audio['nombre'],
                        'url'           => $audio['url'],
                        'fechaInicio'   => $llamada['fechaInicio'],
                        'duracion'      => $llamada['duracion'],
                        'numero'        => $llamada['numeroRemoto'],
                        'operador'      => $llamada['operador'],
                        'terminal'      => $llamada['terminal'],
                        'idLlamada'     => $llamada['idLlamada'],
                        'idServidor'    => $llamada['idServidor'],
                    ];
                }
            } else {
                // Llamada encontrada pero sin audio (incluir para informar)
                $grabaciones[] = [
                    'tipo'          => 'bri',
                    'nombreFichero' => 'Llamada ' . $llamada['idLlamada'],
                    'url'           => null,
                    'fechaInicio'   => $llamada['fechaInicio'],
                    'duracion'      => $llamada['duracion'],
                    'numero'        => $llamada['numeroRemoto'],
                    'operador'      => $llamada['operador'],
                    'terminal'      => $llamada['terminal'],
                    'idLlamada'     => $llamada['idLlamada'],
                    'idServidor'    => $llamada['idServidor'],
                ];
            }
        }

        // Cachear cookies para que descargarAudio pueda reutilizar la sesión JSF
        // (AjaxServletGrabaciones requiere que BuscadorGrabacionesLlamadasOperador haya sido visitado)
        Cache::put(
            'cecoco_jar_' . md5($this->user),
            $jar->toArray(),
            now()->addMinutes(25)
        );

        return [
            'grabaciones' => $grabaciones,
            'ventana'     => [
                'desde' => $desde->format('Y-m-d H:i:s'),
                'hasta' => $hasta->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * POST a BuscadorLlamadas simulando el click en el ícono de grabaciones de una llamada.
     * Retorna el HTML de BuscadorGrabacionesLlamadasOperador.faces
     */
    private function obtenerPaginaGrabaciones(
        Client $client,
        string $buscadorUrl,
        array  $formData,
        string $viewState,
        int    $idLlamada,
        int    $idServidor,
        string $imageInput
    ): string {
        $postData = array_merge($formData, [
            'busquedaForm:idLlamada'    => (string) $idLlamada,
            'busquedaForm:idServidor'   => (string) $idServidor,
            'modificado'                => '',
            'autoScroll'                => '0,0',
            'busquedaForm_SUBMIT'       => '1',
            'busquedaForm:_idcl'        => '',
            'busquedaForm:_link_hidden_'=> '',
            'javax.faces.ViewState'     => $viewState,
            // Simular click en la imagen de grabaciones
            $imageInput . '.x'          => '10',
            $imageInput . '.y'          => '10',
        ]);

        // Quitar el botón Buscar — esta es una acción diferente
        unset($postData['busquedaForm:botonBusqueda']);

        try {
            $resp = $client->post($buscadorUrl, [
                'form_params' => $postData,
                'headers'     => ['Referer' => $buscadorUrl],
            ]);
            $html = (string) $resp->getBody();

            Log::debug('CecocoGrabacionesService: página grabaciones operador', [
                'idLlamada'    => $idLlamada,
                'status'       => $resp->getStatusCode(),
                'bytes'        => strlen($html),
                'tiene_popup'  => str_contains($html, 'abrirPopup'),
                'preview'      => mb_substr(strip_tags($html), 0, 400),
            ]);

            return $html;
        } catch (\Exception $e) {
            Log::warning('CecocoGrabacionesService: error obteniendo página grabaciones', [
                'idLlamada' => $idLlamada,
                'error'     => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Extrae el javax.faces.ViewState del HTML.
     */
    private function extraerViewState(string $html): ?string
    {
        if (preg_match('/<input[^>]+name="javax\.faces\.ViewState"[^>]+value="([^"]+)"/i', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        return null;
    }

    /**
     * Parsea las filas de llamadas del HTML de BuscadorLlamadas.
     */
    private function parsearLlamadas(string $html): array
    {
        $llamadas = [];

        preg_match_all('/idsLlamada\.push\((\d+)\)/', $html, $mIds);
        preg_match_all('/idsServidor\.push\((\d+)\)/', $html, $mServ);

        if (empty($mIds[1])) {
            return [];
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $filas = $xpath->query('//div[@id="ArrayFilasTablaScroll"]');
        $i = 0;

        foreach ($filas as $fila) {
            $idLlamada  = $mIds[1][$i]  ?? null;
            $idServidor = $mServ[1][$i] ?? 0;

            if (!$idLlamada) {
                $i++;
                continue;
            }

            // Obtener title de cada span por su id
            $get = function (string $spanId) use ($xpath, $fila): string {
                $nodes = $xpath->query('.//span[@id="' . $spanId . '"]', $fila);
                return $nodes->length > 0 ? trim($nodes->item(0)->getAttribute('title')) : '';
            };

            // Obtener el nombre del input de imagen de grabaciones para usarlo en el POST
            $imageInputNodes = $xpath->query('.//span[@id="grabaciones"]//input[@type="image"]', $fila);
            $imageInput = $imageInputNodes->length > 0
                ? $imageInputNodes->item(0)->getAttribute('name')
                : 'busquedaForm:listado:_idJsp32';

            $llamadas[] = [
                'idLlamada'    => $idLlamada,
                'idServidor'   => $idServidor,
                'imageInput'   => $imageInput,
                'fechaInicio'  => $get('fechaInicio'),
                'duracion'     => $get('duracion'),
                'numeroRemoto' => $get('numeroRemoto'),
                'operador'     => $get('operador'),
                'terminal'     => $get('terminal'),
                'protocolo'    => $get('protocolo'),
            ];

            $i++;
        }

        return $llamadas;
    }

    /**
     * Parsea los links de audio de la página BuscadorGrabacionesLlamadasOperador.
     * Extrae los datos del onclick: abrirPopup("context", "server>>ruta>>nombre>>id")
     * y construye la URL de AjaxServletGrabaciones.
     */
    private function parsearAudiosDePopup(string $html): array
    {
        if (empty($html)) return [];

        $audios = [];

        // Intentar primero sobre el HTML crudo (separador &gt;&gt;) y luego sobre el decodificado
        $candidatos = [];

        // Intento 1: HTML crudo con &gt;&gt; como separador
        if (preg_match_all('/abrirPopup\s*\([^,]+,\s*["\']([^"\']*)["\']/', $html, $m1)) {
            foreach ($m1[1] as $raw) {
                // Puede venir con &gt;&gt; o ya con >>
                $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (str_contains($decoded, '>>')) {
                    $candidatos[] = $decoded;
                }
            }
        }

        // Intento 2: HTML completamente decodificado
        if (empty($candidatos)) {
            $htmlDecoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match_all('/abrirPopup\s*\([^,]+,\s*["\']([^"\']+)["\']/', $htmlDecoded, $m2)) {
                foreach ($m2[1] as $raw) {
                    if (str_contains($raw, '>>')) {
                        $candidatos[] = $raw;
                    }
                }
            }
        }

        Log::debug('CecocoGrabacionesService: parsearAudiosDePopup', [
            'html_bytes'  => strlen($html),
            'candidatos'  => count($candidatos),
            'preview_html'=> mb_substr(strip_tags($html), 0, 200),
        ]);

        foreach ($candidatos as $popupData) {
            $parts = explode('>>', $popupData);

            // Formato: server>>rutaFichero>>nombreFichero>>idGrabacion
            $rutaFichero   = isset($parts[1]) ? trim($parts[1]) : '';
            $nombreFichero = isset($parts[2]) ? trim($parts[2]) : '';

            if (empty($nombreFichero)) continue;

            // Usar rawurlencode y dejar paréntesis sin codificar (CECOCO no decodifica %28/%29)
            $url = $this->baseUrl . self::AUDIO_PATH
                 . '?rutaFichero=' . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($rutaFichero))
                 . '&nombreFichero=' . str_replace(['%28', '%29'], ['(', ')'], rawurlencode($nombreFichero));

            $audios[] = [
                'url'    => $url,
                'nombre' => $nombreFichero,
                'ruta'   => $rutaFichero,
            ];
        }

        return $audios;
    }

    /**
     * Resuelve una URL relativa contra la base del CECOCO.
     */
    private function resolverUrl(string $src): string
    {
        if (preg_match('/^https?:\/\//i', $src)) return $src;

        $parts  = parse_url($this->baseUrl);
        $origin = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? '')
                . (isset($parts['port']) ? ':' . $parts['port'] : '');

        if (str_starts_with($src, '/')) return $origin . $src;

        return rtrim($this->baseUrl, '/') . '/' . ltrim($src, '/');
    }

    /**
     * Proxy: descarga un archivo de audio desde CECOCO.
     *
     * AjaxServletGrabaciones NO sirve el audio directamente: devuelve una página HTML
     * con un jPlayer que apunta al MP3 real en /recursos/tmp-recordings/.
     * Este método llama al servlet (step 1), parsea la URL del MP3 (step 2),
     * y descarga el audio real (step 3).
     */
    public function descargarAudio(string $url): \GuzzleHttp\Psr7\Response
    {
        $cacheKey      = 'cecoco_jar_' . md5($this->user);
        $cachedCookies = Cache::get($cacheKey);

        $jar = $cachedCookies
            ? new CookieJar(false, array_map(fn ($c) => new SetCookie($c), $cachedCookies))
            : new CookieJar();

        $client = new Client([
            'cookies'     => $jar,
            'timeout'     => $this->timeout,
            'http_errors' => false,
            'verify'      => false,
            'headers'     => ['User-Agent' => 'Mozilla/5.0', 'Connection' => 'close'],
            'curl'        => [CURLOPT_FORBID_REUSE => true, CURLOPT_FRESH_CONNECT => true],
        ]);

        if (!$cachedCookies) {
            $client->get($this->baseUrl);
            $client->post($this->baseUrl . '/ajax/perfil/AjaxServletPerfil', [
                'form_params' => [
                    'LoginForm:Usuario'  => $this->user,
                    'LoginForm:Password' => $this->password,
                ],
            ]);
            $client->get($this->baseUrl . self::BUSCADOR_PATH);
        }

        // 1. Llamar a AjaxServletGrabaciones: CECOCO copia el archivo a tmp-recordings
        //    y devuelve la página HTML del jPlayer
        $playerResp = $client->get($url);
        $playerHtml = (string) $playerResp->getBody();

        // 2. Extraer la URL real del MP3 del HTML del jPlayer
        $mp3Url = null;
        if (preg_match('/mp3:\s*["\']([^"\']+)["\']/', $playerHtml, $m)) {
            $mp3Url = str_replace(' ', '%20', trim($m[1]));
        }

        // Fallback: construir URL a partir del parámetro nombreFichero
        if (!$mp3Url) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $qParams);
            $nombreFichero = $qParams['nombreFichero'] ?? '';
            if ($nombreFichero) {
                $mp3Url = $this->baseUrl . '/recursos/tmp-recordings/' . rawurlencode($nombreFichero);
            }
        }

        Log::debug('descargarAudio: URL MP3 extraída', [
            'player_status' => $playerResp->getStatusCode(),
            'mp3_url'       => $mp3Url,
        ]);

        if (!$mp3Url) {
            throw new \RuntimeException('No se pudo obtener la URL del MP3 desde el player CECOCO');
        }

        // 3. Descargar el archivo MP3 real
        $audioResp = $client->get($mp3Url);
        $bodyRaw   = (string) $audioResp->getBody();

        Log::debug('descargarAudio: resultado', [
            'audio_status'  => $audioResp->getStatusCode(),
            'audio_ct'      => $audioResp->getHeaderLine('Content-Type'),
            'audio_size'    => strlen($bodyRaw),
            'audio_preview' => bin2hex(substr($bodyRaw, 0, 8)),
        ]);

        return new \GuzzleHttp\Psr7\Response(
            $audioResp->getStatusCode(),
            $audioResp->getHeaders(),
            $bodyRaw
        );
    }
}
