<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

class TicketeraService
{
    private Client $clienteHttp;

    private CookieJar $cookiesSesion;

    private string $urlBase;

    public function __construct()
    {
        $this->urlBase = rtrim((string) config('services.ticketera.url'), '/');
        $this->cookiesSesion = new CookieJar();
        $this->clienteHttp = new Client([
            'base_uri'        => $this->urlBase . '/',
            'cookies'         => $this->cookiesSesion,
            'timeout'         => (int) config('services.ticketera.timeout', 30),
            'allow_redirects' => true,
            'http_errors'     => false,
        ]);
    }

    /**
     * @return array{codigo_ticketera: string|null, url_seguimiento: string|null, html: string}
     */
    public function crearTicket(array $datosTicket): array
    {
        $this->validarConfiguracion();
        $this->iniciarSesion();

        $rutaNuevoTicket = (string) config('services.ticketera.nuevo_ticket_path', 'open.php');
        $respuestaFormulario = $this->clienteHttp->get($rutaNuevoTicket);
        $htmlFormulario = (string) $respuestaFormulario->getBody();
        $camposFormulario = $this->extraerCamposOcultos($htmlFormulario);

        $camposFormulario = array_merge($camposFormulario, $this->camposTicket($datosTicket));
        $accionFormulario = $this->extraerActionFormulario($htmlFormulario, $rutaNuevoTicket);

        $respuesta = $this->clienteHttp->post($accionFormulario, [
            'form_params' => $camposFormulario,
            'headers'     => [
                'Referer' => $this->urlBase . '/' . ltrim($rutaNuevoTicket, '/'),
            ],
        ]);

        $htmlRespuesta = (string) $respuesta->getBody();
        if ($respuesta->getStatusCode() >= 400) {
            throw new RuntimeException("La ticketera devolvio HTTP {$respuesta->getStatusCode()} al crear el ticket.");
        }

        return [
            'codigo_ticketera' => $this->extraerCodigoTicketera($htmlRespuesta),
            'url_seguimiento'  => $this->extraerUrlSeguimiento($htmlRespuesta),
            'html'             => $htmlRespuesta,
        ];
    }

    public function iniciarSesion(): void
    {
        $rutaLogin = (string) config('services.ticketera.login_path', 'login.php');
        $respuestaLogin = $this->clienteHttp->get($rutaLogin);
        $htmlLogin = (string) $respuestaLogin->getBody();
        $camposLogin = $this->extraerCamposOcultos($htmlLogin);

        $camposLogin = array_merge($camposLogin, [
            'luser'    => config('services.ticketera.usuario'),
            'lemail'   => config('services.ticketera.usuario'),
            'username' => config('services.ticketera.usuario'),
            'email'    => config('services.ticketera.usuario'),
            'lpasswd'  => config('services.ticketera.password'),
            'passwd'   => config('services.ticketera.password'),
            'password' => config('services.ticketera.password'),
        ]);

        $accionLogin = $this->extraerActionFormulario($htmlLogin, $rutaLogin);
        $respuesta = $this->clienteHttp->post($accionLogin, [
            'form_params' => $camposLogin,
            'headers'     => [
                'Referer' => $this->urlBase . '/' . ltrim($rutaLogin, '/'),
            ],
        ]);

        if ($respuesta->getStatusCode() >= 400) {
            throw new RuntimeException("La ticketera devolvio HTTP {$respuesta->getStatusCode()} durante el login.");
        }
    }

    public function extraerCodigoTicketera(string $html): ?string
    {
        if (preg_match('/#?([0-9]{2,}-[A-Z0-9]{3,}-[A-Z0-9]{3,})/i', $html, $coincidencias)) {
            return strtoupper($coincidencias[1]);
        }

        if (preg_match('/ticket(?:\s+number|\s+nro|\s+no\.?|#)?\s*[:#]?\s*([0-9]+)/i', $html, $coincidencias)) {
            return $coincidencias[1];
        }

        return null;
    }

    public function extraerUrlSeguimiento(string $html): ?string
    {
        if (preg_match('/https?:\/\/[^"\'\s<>]+ticket\.php\?track=[^"\'\s<>]+/i', $html, $coincidencias)) {
            return html_entity_decode($coincidencias[0]);
        }

        if (preg_match('/href=["\']([^"\']*ticket\.php\?track=[^"\']+)["\']/i', $html, $coincidencias)) {
            $url = html_entity_decode($coincidencias[1]);

            if (str_starts_with($url, 'http')) {
                return $url;
            }

            return $this->urlBase . '/' . ltrim($url, '/');
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function camposTicket(array $datosTicket): array
    {
        $campos = [
            'name'    => (string) config('services.ticketera.nombre', 'Tecnica 911'),
            'email'   => (string) config('services.ticketera.email'),
            'subject' => (string) $datosTicket['asunto'],
            'message' => (string) $datosTicket['texto_enviado'],
        ];

        $temaId = config('services.ticketera.tema_id');
        if ($temaId !== null && $temaId !== '') {
            $campos['topicId'] = (string) $temaId;
        }

        return $campos;
    }

    /**
     * @return array<string, string>
     */
    private function extraerCamposOcultos(string $html): array
    {
        $campos = [];

        preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
        foreach ($inputs[0] as $input) {
            if (!preg_match('/type=["\']hidden["\']/i', $input)) {
                continue;
            }

            if (preg_match('/name=["\']([^"\']+)["\']/i', $input, $nombre)) {
                $valor = '';
                if (preg_match('/value=["\']([^"\']*)["\']/i', $input, $valorMatch)) {
                    $valor = html_entity_decode($valorMatch[1]);
                }
                $campos[$nombre[1]] = $valor;
            }
        }

        return $campos;
    }

    private function extraerActionFormulario(string $html, string $rutaPorDefecto): string
    {
        if (preg_match('/<form\b[^>]*action=["\']([^"\']+)["\']/i', $html, $coincidencias)) {
            $action = html_entity_decode($coincidencias[1]);

            if (str_starts_with($action, $this->urlBase)) {
                return substr($action, strlen($this->urlBase) + 1);
            }

            if (!str_starts_with($action, 'http')) {
                return ltrim($action, '/');
            }
        }

        return $rutaPorDefecto;
    }

    private function validarConfiguracion(): void
    {
        if ($this->urlBase === '' || !config('services.ticketera.usuario') || !config('services.ticketera.password')) {
            throw new RuntimeException('Falta configurar TICKETERA_URL, TICKETERA_USUARIO o TICKETERA_PASSWORD.');
        }
    }
}
