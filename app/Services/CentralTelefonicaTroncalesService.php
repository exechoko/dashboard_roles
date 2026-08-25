<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class CentralTelefonicaTroncalesService
{
    public const CACHE_KEY = 'central_telefonica.troncales';

    private string $baseUrl;
    private string $user;
    private string $password;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(dirname(config('central_telefonica.url', 'http://172.40.20.65/ssw/index.php')), '/');
        $this->user = config('central_telefonica.user', '');
        $this->password = config('central_telefonica.password', '');
        $this->timeout = (int) config('central_telefonica.timeout', 60);
    }

    /**
     * Consulta el estado (online/offline) de los troncales SIP configurados en la
     * central telefonica (panel SSW: Maintenance > SIP trunks monitor).
     *
     * @return array<int, array{nombre: string, descripcion: string, host: string, puerto: string, estado: string, latencia_ms: int|null}>
     */
    public function obtenerEstadoTroncales(): array
    {
        $client = new Client([
            'cookies' => new CookieJar(),
            'timeout' => $this->timeout,
            'http_errors' => false,
            'verify' => false,
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        $this->autenticar($client);

        $response = $client->get($this->baseUrl . '/endpoints/siptrunks-endpoint.php', [
            'query' => ['ajax_request' => 'get_siptrunks_status'],
        ]);

        $datos = json_decode((string) $response->getBody(), true);

        if (!is_array($datos)) {
            throw new \RuntimeException(
                'La central telefonica no devolvió el estado de los troncales SIP: ' . (string) $response->getBody()
            );
        }

        return array_map(fn (array $troncal) => [
            'nombre' => $troncal['name'] ?? '',
            'descripcion' => $troncal['description'] ?? '',
            'host' => $troncal['host'] ?? '',
            'puerto' => $troncal['port'] ?? '',
            'estado' => strtolower($troncal['status'] ?? 'unknown'),
            'latencia_ms' => isset($troncal['lastms']) && $troncal['lastms'] !== null ? (int) $troncal['lastms'] : null,
        ], $datos);
    }

    private function autenticar(Client $client): void
    {
        $response = $client->post($this->baseUrl . '/loginproc.php', [
            'form_params' => [
                'username' => $this->user,
                'password' => $this->password,
                'submit' => 'Submit',
            ],
        ]);

        if (str_contains((string) $response->getBody(), 'login_form')) {
            throw new \RuntimeException('No se pudo autenticar contra la central telefonica: usuario o contraseña incorrectos.');
        }
    }
}
