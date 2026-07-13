<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integración con la ticketera HESK (lado admin).
 *
 * Flujo real verificado:
 *  1. POST admin/index.php  (a=do_login, user, pass, remember_user, goto)
 *  2. GET  admin/new_ticket.php?category=<id>  -> trae token CSRF y el form
 *  3. POST admin/admin_submit_ticket.php (multipart) con token + campos del ticket
 */
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
     * @param array<string, mixed> $datosTicket
     * @return array{codigo_ticketera: string|null, url_seguimiento: string|null, html: string}
     */
    public function crearTicket(array $datosTicket): array
    {
        if ($this->esDryRun()) {
            return $this->simularCreacion($datosTicket);
        }

        $this->validarConfiguracion();
        $this->iniciarSesion();

        $categoriaId = $this->categoriaHesk($datosTicket);
        $rutaNuevoTicket = (string) config('services.ticketera.nuevo_ticket_path', 'admin/new_ticket.php');

        $respuestaFormulario = $this->clienteHttp->get($rutaNuevoTicket, [
            'query' => ['category' => $categoriaId],
        ]);
        $htmlFormulario = (string) $respuestaFormulario->getBody();

        $camposFormulario = $this->extraerCamposOcultos($htmlFormulario);
        $camposFormulario = array_merge($camposFormulario, $this->camposTicket($datosTicket, $categoriaId));

        $rutaSubmit = $this->extraerActionFormulario(
            $htmlFormulario,
            (string) config('services.ticketera.submit_path', 'admin/admin_submit_ticket.php')
        );

        $respuesta = $this->clienteHttp->post($rutaSubmit, [
            'multipart' => $this->comoMultipart($camposFormulario),
            'headers'   => [
                'Referer' => $this->urlBase . '/' . ltrim($rutaNuevoTicket, '/') . '?category=' . $categoriaId,
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

    public function esDryRun(): bool
    {
        return (bool) config('services.ticketera.dry_run', true);
    }

    /**
     * Modo seguro: arma el payload y lo registra en el log SIN realizar ninguna
     * llamada a la ticketera (ni login, ni GET, ni POST). Devuelve un código
     * simulado con prefijo DRYRUN para que sea evidente que no se envió.
     *
     * @param array<string, mixed> $datosTicket
     * @return array{codigo_ticketera: string|null, url_seguimiento: string|null, html: string}
     */
    private function simularCreacion(array $datosTicket): array
    {
        $categoriaId = $this->categoriaHesk($datosTicket);
        $campos = $this->camposTicket($datosTicket, $categoriaId);
        $codigoSimulado = 'DRYRUN-' . now()->format('ymd-His');

        Log::warning('[Ticketera][DRY-RUN] Ticket NO enviado (TICKETERA_DRY_RUN activo).', [
            'codigo_interno' => $datosTicket['codigo_interno'] ?? null,
            'url_base'       => $this->urlBase,
            'submit_path'    => config('services.ticketera.submit_path'),
            'campos'         => $campos,
        ]);

        return [
            'codigo_ticketera' => $codigoSimulado,
            'url_seguimiento'  => null,
            'html'             => '[DRY-RUN] Ticket no enviado a la ticketera. Payload registrado en el log.',
        ];
    }

    public function iniciarSesion(): void
    {
        $rutaLogin = (string) config('services.ticketera.login_path', 'admin/index.php');
        $goto = '/' . ltrim((string) config('services.ticketera.admin_path', 'admin/admin_main.php'), '/');

        $respuesta = $this->clienteHttp->post($rutaLogin, [
            'form_params' => [
                'user'          => config('services.ticketera.usuario'),
                'pass'          => config('services.ticketera.password'),
                'remember_user' => 'NOTHANKS',
                'a'             => 'do_login',
                'goto'          => $goto,
            ],
        ]);

        $html = (string) $respuesta->getBody();
        if ($respuesta->getStatusCode() >= 400 || str_contains($html, 'a=do_login')) {
            throw new RuntimeException('No se pudo iniciar sesion en la ticketera (credenciales o formulario de login).');
        }
    }

    public function extraerCodigoTicketera(string $html): ?string
    {
        if (preg_match('/[?&]track=([A-Za-z0-9]{3,}(?:-[A-Za-z0-9]{3,})*)/', $html, $coincidencias)) {
            return strtoupper($coincidencias[1]);
        }

        if (preg_match('/ticket(?:\s+id|\s+nro|\s+n[°º]|#)?\s*[:#]?\s*([A-Z0-9]{3,}(?:-[A-Z0-9]{3,})+)/i', $html, $coincidencias)) {
            return strtoupper($coincidencias[1]);
        }

        return null;
    }

    public function extraerUrlSeguimiento(string $html): ?string
    {
        if (preg_match('/href=["\']([^"\']*(?:admin_ticket|ticket)\.php\?track=[^"\']+)["\']/i', $html, $coincidencias)) {
            $url = html_entity_decode($coincidencias[1]);

            if (str_starts_with($url, 'http')) {
                return $url;
            }

            return $this->urlBase . '/' . ltrim(preg_replace('#^\.\./#', '', $url) ?? $url, '/');
        }

        $codigo = $this->extraerCodigoTicketera($html);
        if ($codigo !== null) {
            return $this->urlBase . '/ticket.php?track=' . $codigo;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $datosTicket
     * @return array<string, string>
     */
    private function camposTicket(array $datosTicket, int $categoriaId): array
    {
        $campos = [
            'token'         => '',
            'category'      => (string) $categoriaId,
            'customer_type' => 'CUSTOMER',
            'subject'       => (string) ($datosTicket['asunto'] ?? ''),
            'message'       => (string) ($datosTicket['texto_enviado'] ?? ''),
            'priority'      => (string) $this->prioridadHesk($datosTicket),
            'notify'        => '1',
            'show'          => '1',
        ];

        $customerId = config('services.ticketera.customer_id');
        if ($customerId !== null && $customerId !== '') {
            $campos['customer_id'] = (string) $customerId;
        } else {
            $campos['name'] = (string) config('services.ticketera.nombre', 'Tecnica 911');
            $campos['email'] = (string) config('services.ticketera.email');
        }

        $ownerId = config('services.ticketera.owner_id');
        if ($ownerId !== null && $ownerId !== '') {
            $campos['owner'] = (string) $ownerId;
        }

        $status = config('services.ticketera.status');
        if ($status !== null && $status !== '') {
            $campos['status'] = (string) $status;
        }

        return $campos;
    }

    private function categoriaHesk(array $datosTicket): int
    {
        $categoria = (string) ($datosTicket['tipo_equipo'] ?? '');
        $mapa = (array) config('ticketera_categorias.hesk_categorias', []);

        return (int) ($mapa[$categoria] ?? 1);
    }

    private function prioridadHesk(array $datosTicket): int
    {
        $prioridad = (string) ($datosTicket['prioridad'] ?? '');
        $mapa = (array) config('ticketera_categorias.hesk_prioridades', []);

        return (int) ($mapa[$prioridad] ?? 3);
    }

    /**
     * @param array<string, string> $campos
     * @return array<int, array{name: string, contents: string}>
     */
    private function comoMultipart(array $campos): array
    {
        $multipart = [];
        foreach ($campos as $nombre => $valor) {
            $multipart[] = ['name' => $nombre, 'contents' => (string) $valor];
        }

        return $multipart;
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
                $action = ltrim($action, '/');

                return str_contains($action, '/') ? $action : 'admin/' . $action;
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
