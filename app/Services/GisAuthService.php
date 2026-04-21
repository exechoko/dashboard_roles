<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Autenticación contra el GIS viewer de CECOCO.
 *
 * Replica el flujo de login de GisViewerController pero empaquetado como
 * servicio reutilizable para consumo directo de APIs REST del GIS (p.ej.
 * histórico de posiciones de un móvil) sin pasar por el proxy HTML.
 *
 * Flujo (4 pasos):
 *   1) POST /gisviewer/rest/login/profiles      → perfil
 *   2) GET  /gisviewer/rest/login/organism/...  → id/nombre organismo
 *   3) POST /gisviewer/login/cecoco/            → login CECOCO (autosubmit a Spring)
 *   4) POST /gisviewer/j_security_check         → login Spring Security
 */
class GisAuthService
{
    private string $gisBaseUrl;
    private string $user;
    private string $password;
    private int $timeout;

    private const LOGIN_PATH = '/gisviewer/main/cecoco/?language=es_ES';
    private const MAP_PATH = '/gisviewer/main/cecoco/';
    private const SPRING_CHECK_PATH = '/gisviewer/j_security_check';

    public function __construct()
    {
        $this->gisBaseUrl = rtrim(config('cecoco.gis_url', 'http://172.26.100.52'), '/');
        $this->user       = (string) config('cecoco.user', '');
        $this->password   = (string) config('cecoco.password', '');
        $this->timeout    = (int) config('cecoco.timeout', 60);
    }

    public function getGisBaseUrl(): string
    {
        return $this->gisBaseUrl;
    }

    /**
     * Autentica contra el GIS y devuelve un Guzzle Client ya preparado
     * (con CookieJar lleno). El llamador puede usarlo directamente para
     * consumir endpoints REST del GIS.
     *
     * @return array{0:Client,1:CookieJar}
     */
    public function getAuthenticatedClient(): array
    {
        $jar    = new CookieJar();
        $client = $this->makeClient($jar);
        $loginUrl = $this->gisBaseUrl . self::LOGIN_PATH;

        Log::info('GisAuthService: iniciando autenticación', ['url' => $loginUrl]);

        $loginPage  = $client->get($loginUrl, ['headers' => $this->baseHeaders(), 'allow_redirects' => true]);
        $formAction = $this->parseFormAction((string) $loginPage->getBody(), $loginUrl);

        $cecocoResp = $client->post($formAction, [
            'form_params'     => $this->buildLoginParams($client),
            'allow_redirects' => true,
            'headers'         => array_merge($this->baseHeaders(), ['Referer' => $loginUrl]),
        ]);

        $body = (string) $cecocoResp->getBody();

        if ($this->esRespuestaError($body)) {
            throw new RuntimeException('El GIS rechazó la autenticación: ' . $this->extraerMensajeError($body));
        }

        // Paso 4: auto-submit a Spring Security
        if ($this->esFormAutoSubmit($body)) {
            $client->post($this->gisBaseUrl . self::SPRING_CHECK_PATH, [
                'form_params'     => [
                    'j_username' => $this->user,
                    'j_password' => $this->password,
                ],
                'allow_redirects' => true,
                'headers'         => array_merge($this->baseHeaders(), [
                    'Referer' => $this->gisBaseUrl . self::MAP_PATH,
                ]),
            ]);
        }

        // Validar JSESSIONID presente
        $tieneJsid = false;
        foreach ($jar as $cookie) {
            if ($cookie->getName() === 'JSESSIONID') {
                $tieneJsid = true;
                break;
            }
        }
        if (!$tieneJsid) {
            throw new RuntimeException('No se obtuvo JSESSIONID del GIS tras el login.');
        }

        Log::info('GisAuthService: autenticado correctamente');
        return [$client, $jar];
    }

    private function buildLoginParams(Client $client): array
    {
        $profile = $this->cargarPrimerPerfil($client);
        [$orgId, $orgName] = $this->obtenerOrganismo($client, $profile);

        return [
            'j_username'      => $this->user,
            'j_password'      => $this->password,
            'j_profiles'      => $profile,
            'j_subscriber'    => '0',
            'j_profile_name'  => $profile,
            'j_organism_id'   => $orgId,
            'j_organism_name' => $orgName,
            'j_language'      => 'es_ES',
            'j_desktop_login' => '0',
        ];
    }

    private function cargarPrimerPerfil(Client $client): string
    {
        $url = $this->gisBaseUrl . '/gisviewer/rest/login/profiles';

        $response = $client->post($url, [
            'form_params'     => [
                'idsubscriber' => '0',
                'username'     => $this->user,
                'pwd'          => $this->password,
            ],
            'headers'         => $this->baseHeaders(),
            'allow_redirects' => true,
        ]);

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new RuntimeException('Respuesta inválida al cargar perfiles del GIS.');
        }

        if (!empty($data['iscecocoerror'])) {
            Log::warning('GisAuthService: CeCoCo caído, usando fallback de perfiles.');
            $fallbackUrl = $this->gisBaseUrl
                . '/gisviewer/rest/login/profiles/0/'
                . rawurlencode($this->user) . '/'
                . rawurlencode($this->password) . '/fallback';
            $fb = $client->get($fallbackUrl, ['headers' => $this->baseHeaders()]);
            $fbData = json_decode((string) $fb->getBody(), true);
            if (!empty($fbData) && is_array($fbData)) {
                return (string) ($fbData[0]['value_id'] ?? '');
            }
            throw new RuntimeException('No hay perfiles disponibles (fallback vacío).');
        }

        if (!empty($data['isresponseerror'])) {
            throw new RuntimeException('Error al cargar perfiles del GIS: ' . ($data['errormsg'] ?? ''));
        }

        $profiles = $data['response'] ?? [];
        if (empty($profiles)) {
            throw new RuntimeException('No hay perfiles disponibles para el usuario GIS.');
        }

        return (string) $profiles[0];
    }

    private function obtenerOrganismo(Client $client, string $profile): array
    {
        $url = $this->gisBaseUrl
            . '/gisviewer/rest/login/organism/get/id/name/0/'
            . rawurlencode($this->user) . '/'
            . rawurlencode($this->password) . '/'
            . rawurlencode($profile);

        try {
            $response = $client->get($url, [
                'headers'         => $this->baseHeaders(),
                'allow_redirects' => true,
                'timeout'         => 15,
            ]);
            $data = json_decode((string) $response->getBody(), true);
            if (is_array($data) && isset($data['value_id'])) {
                return [(string) $data['value_id'], (string) ($data['value_name'] ?? '')];
            }
        } catch (\Exception $e) {
            Log::warning('GisAuthService: no se pudo obtener organismo.', ['error' => $e->getMessage()]);
        }

        return ['', ''];
    }

    private function makeClient(CookieJar $jar): Client
    {
        return new Client([
            'verify'          => false,
            'cookies'         => $jar,
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
        return $this->gisBaseUrl . '/gisviewer/login/cecoco/';
    }

    private function toAbsoluteUrl(string $url, string $base): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '/')) {
            $p = parse_url($base);
            return ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? '')
                . (isset($p['port']) ? ':' . $p['port'] : '') . $url;
        }
        return rtrim(dirname($base), '/') . '/' . $url;
    }

    private function esFormAutoSubmit(string $body): bool
    {
        return str_contains($body, 'loginFormFinal') || str_contains($body, 'j_security_check');
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
        return 'Error desconocido del GIS.';
    }
}
