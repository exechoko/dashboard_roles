<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;

class CentralTelefonicaSincronizacionService
{
    private string $baseUrl;
    private string $user;
    private string $password;
    private int $timeout;

    public function __construct(private LlamadaCentralTelefonicaImportService $importService)
    {
        $this->baseUrl = rtrim(dirname(config('central_telefonica.url', 'http://172.40.20.65/ssw/index.php')), '/');
        $this->user = config('central_telefonica.user', '');
        $this->password = config('central_telefonica.password', '');
        $this->timeout = (int) config('central_telefonica.timeout', 60);
    }

    /**
     * Exporta los CDR de la central telefonica en el rango dado y los importa
     * (upsert por uid) a la tabla llamadas_central_telefonica.
     *
     * @return array{total: int, omitidos: int, archivo: string}
     */
    public function sincronizar(Carbon $desde, Carbon $hasta): array
    {
        $client = new Client([
            'cookies' => new CookieJar(),
            'timeout' => $this->timeout,
            'http_errors' => false,
            'verify' => false,
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        $this->autenticar($client);

        $nombreArchivo = $this->exportarCdrs($client, $desde, $hasta);
        $rutaTemporal = $this->descargarCsv($client, $nombreArchivo);

        try {
            $resultado = $this->importService->importarArchivo($rutaTemporal, $nombreArchivo);
        } finally {
            @unlink($rutaTemporal);
        }

        Log::info('CentralTelefonicaSincronizacionService: sincronizacion completada', [
            'desde' => $desde->toDateTimeString(),
            'hasta' => $hasta->toDateTimeString(),
            'total' => $resultado['total'],
            'omitidos' => $resultado['omitidos'],
        ]);

        return $resultado + ['archivo' => $nombreArchivo];
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

        $body = (string) $response->getBody();

        if (str_contains($body, 'login_form')) {
            throw new \RuntimeException('No se pudo autenticar contra la central telefonica: usuario o contraseña incorrectos.');
        }
    }

    private function exportarCdrs(Client $client, Carbon $desde, Carbon $hasta): string
    {
        $response = $client->post($this->baseUrl . '/endpoints/cdr-endpoint.php', [
            'form_params' => [
                'ajax_request' => 'export_cdrs',
                'order' => 'descending',
                'start-time' => $desde->format('Y-m-d\TH:i:s'),
                'end-time' => $hasta->format('Y-m-d\TH:i:s'),
                'filters[ANI]' => '',
                'filters[Dialed number]' => '',
                'filters[Final DNIS]' => '',
                'filters[Forwarded to]' => '',
                'filters[Duration]' => '',
                'filters[Bill duration]' => '',
                'filters[Hangup reason]' => '',
                'filters[Call type]' => '',
            ],
        ]);

        $datos = json_decode((string) $response->getBody(), true);

        if (!is_array($datos) || empty($datos['filename'])) {
            throw new \RuntimeException(
                'La central telefonica no devolvió un archivo de exportación válido: ' . (string) $response->getBody()
            );
        }

        if (!empty($datos['error'])) {
            throw new \RuntimeException('Error exportando CDRs de la central telefonica: ' . $datos['error']);
        }

        return $datos['filename'];
    }

    private function descargarCsv(Client $client, string $nombreArchivo): string
    {
        $response = $client->get($this->baseUrl . '/CDRs/' . $nombreArchivo);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("No se pudo descargar el archivo exportado {$nombreArchivo} (HTTP {$response->getStatusCode()})");
        }

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'central_telefonica_');
        file_put_contents($rutaTemporal, (string) $response->getBody());

        return $rutaTemporal;
    }
}
