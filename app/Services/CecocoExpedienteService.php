<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class CecocoExpedienteService
{
    private string $baseUrl;
    private string $cecocoUser;
    private string $cecocoPassword;
    private int $timeout;
    private string $tempPath;

    public function __construct()
    {
        $this->baseUrl = config('cecoco.url', 'http://172.26.100.34:8080') . '/CECOCO_webapp';
        $this->cecocoUser = config('cecoco.user', 'tecnica');
        $this->cecocoPassword = config('cecoco.password', 'tecnica');
        $this->timeout = config('cecoco.timeout', 60);
        $this->tempPath = storage_path('app/temp');
        
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    public function obtenerDetalleExpediente(string $nroExpediente): array
    {
        try {
            Log::info('Consultando expediente CECOCO', ['expediente' => $nroExpediente]);

            Log::info('Paso 1: Iniciando sesión CECOCO');
            $client = $this->iniciarSesion();
            
            Log::info('Paso 2: Obteniendo reporte HTML del expediente');
            $htmlReporte = $this->obtenerReporteHTML($client, $nroExpediente);
            
            Log::info('Paso 3: Parseando datos del HTML');
            $datosExpediente = $this->parsearHTMLExpediente($htmlReporte, $nroExpediente);

            if (empty($datosExpediente)) {
                throw new Exception("No se encontraron datos para el expediente {$nroExpediente}");
            }

            return $datosExpediente;

        } catch (Exception $e) {
            Log::error('Error al obtener expediente CECOCO', [
                'expediente' => $nroExpediente,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function iniciarSesion()
    {
        try {
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
            
            $client = Http::withOptions([
                'verify' => false,
                'cookies' => $cookieJar,
            ]);

            $client->timeout($this->timeout)->get($this->baseUrl);

            $loginData = [
                'LoginForm:Usuario' => $this->cecocoUser,
                'LoginForm:Password' => $this->cecocoPassword,
            ];

            $response = $client->asForm()->post(
                $this->baseUrl . '/ajax/perfil/AjaxServletPerfil',
                $loginData
            );

            if (!$response->successful()) {
                throw new Exception("Error al iniciar sesión en CECOCO: " . $response->status());
            }

            Log::info('Sesión CECOCO iniciada correctamente', [
                'cookies_count' => count($cookieJar)
            ]);

            return $client;

        } catch (Exception $e) {
            Log::error('Error al iniciar sesión CECOCO', ['error' => $e->getMessage()]);
            throw new Exception("No se pudo conectar con el servidor CECOCO: " . $e->getMessage());
        }
    }

    private function obtenerReporteHTML($client, string $nroExpediente): string
    {
        try {
            $params = [
                '__report' => 'reports/issues/report_history.rptdesign',
                '__format' => 'html',
                'p_dbworking_namedb' => 'bdmatriz',
                'p_dbrestore_namedb' => 'bdrestauraciones',
                'p_shift' => 'false',
                'p_shift_time_min' => '00:00:00',
                'p_shift_time_max' => '23:59:59',
                'p_exclude_operations' => 'false',
                'p_exclude_system_operations' => 'false',
                'p_exclude_involved' => 'false',
                'p_activa_mostrar_informacion_duplicidad' => 'false',
                'p_date_format' => 'dd/MM/yyyy HH:mm:ss',
                'p_time_format' => 'HH:mm',
                'p_id' => $nroExpediente,
                '__locale' => 'es',
            ];

            $response = $client->get($this->baseUrl . '/run', $params);

            if (!$response->successful()) {
                throw new Exception("Error al obtener reporte: " . $response->status());
            }

            $html = $response->body();
            
            if (strlen($html) < 1000) {
                throw new Exception('El expediente requiere restauración desde backup. Contacte al administrador del sistema CECOCO.');
            }

            Log::info('Reporte HTML obtenido correctamente', ['tamaño' => strlen($html)]);

            return $html;

        } catch (Exception $e) {
            Log::error('Error al obtener reporte HTML', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function parsearHTMLExpediente(string $html, string $nroExpediente): array
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXPath($dom);
            
            $timeline = [];
            
            // Buscar todas las filas de la tabla (tr)
            $filas = $xpath->query('//table//tr');
            
            $encabezados = [];
            $primeraFila = true;
            
            foreach ($filas as $fila) {
                $celdas = $xpath->query('.//td | .//th', $fila);
                
                if ($celdas->length === 0) {
                    continue;
                }
                
                // Primera fila son los encabezados
                if ($primeraFila) {
                    foreach ($celdas as $celda) {
                        $encabezados[] = trim($celda->textContent);
                    }
                    $primeraFila = false;
                    continue;
                }
                
                // Extraer datos de la fila
                $datos = [];
                $i = 0;
                foreach ($celdas as $celda) {
                    $valor = trim($celda->textContent);
                    if ($i < count($encabezados)) {
                        $datos[$encabezados[$i]] = $valor;
                    }
                    $i++;
                }
                
                if (!empty($datos)) {
                    $timeline[] = $this->normalizarDatosEvento($datos, $nroExpediente);
                }
            }
            
            if (empty($timeline)) {
                throw new Exception("No se encontraron eventos en el expediente");
            }
            
            // Ordenar por fecha
            usort($timeline, function($a, $b) {
                return strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']);
            });
            
            $primerEvento = $timeline[0];
            
            Log::info('HTML parseado correctamente', ['eventos' => count($timeline)]);
            
            return [
                'nro_expediente' => $nroExpediente,
                'fecha_hora_inicial' => $primerEvento['fecha_hora'] ?? '',
                'operador_inicial' => $primerEvento['operador'] ?? '',
                'tipo_servicio' => $primerEvento['tipo_servicio'] ?? '',
                'direccion' => $primerEvento['direccion'] ?? '',
                'telefono' => $primerEvento['telefono'] ?? '',
                'descripcion_inicial' => $primerEvento['descripcion'] ?? '',
                'timeline' => $timeline,
                'total_eventos' => count($timeline),
            ];
            
        } catch (Exception $e) {
            Log::error('Error al parsear HTML expediente', ['error' => $e->getMessage()]);
            throw new Exception("Error al procesar el reporte del expediente: " . $e->getMessage());
        }
    }

    private function normalizarDatosEvento(array $datos, string $nroExpediente): array
    {
        $camposPosibles = [
            'nro_expediente' => ['Nro Expediente', 'Expediente', 'Nro. Expediente', 'Número de Expediente'],
            'fecha_hora' => ['Fecha Hora', 'Fecha y Hora', 'Fecha', 'FechaHora'],
            'operador' => ['Operador', 'Usuario'],
            'descripcion' => ['Descripción', 'Descripcion', 'Detalle'],
            'tipo_servicio' => ['Tipo Servicio', 'Tipo de Servicio', 'Tipo', 'Servicio'],
            'direccion' => ['Dirección', 'Direccion', 'Domicilio'],
            'telefono' => ['Teléfono', 'Telefono', 'Tel'],
            'estado' => ['Estado'],
            'recurso' => ['Recurso', 'Móvil', 'Movil'],
        ];
        
        $evento = [];
        
        foreach ($camposPosibles as $campoNormalizado => $variantes) {
            $evento[$campoNormalizado] = '';
            
            foreach ($variantes as $variante) {
                if (isset($datos[$variante])) {
                    $evento[$campoNormalizado] = $datos[$variante];
                    break;
                }
            }
        }
        
        // Si no se encontró el número de expediente en los datos, usar el parámetro
        if (empty($evento['nro_expediente'])) {
            $evento['nro_expediente'] = $nroExpediente;
        }
        
        return $evento;
    }


    public function validarConfiguracion(): array
    {
        $errores = [];

        if (empty($this->baseUrl)) {
            $errores[] = "URL de CECOCO no configurada. Configure CECOCO_URL en .env";
        }

        if (empty($this->cecocoUser)) {
            $errores[] = "Usuario de CECOCO no configurado. Configure CECOCO_USER en .env";
        }

        if (empty($this->cecocoPassword)) {
            $errores[] = "Contraseña de CECOCO no configurada. Configure CECOCO_PASSWORD en .env";
        }

        if (!is_dir($this->tempPath) || !is_writable($this->tempPath)) {
            $errores[] = "Directorio temporal no existe o no tiene permisos de escritura: {$this->tempPath}";
        }

        try {
            $response = Http::timeout(5)
                ->withOptions(['verify' => false])
                ->get($this->baseUrl);
            
            if (!$response->successful() && $response->status() !== 302) {
                $errores[] = "No se puede conectar con el servidor CECOCO: {$this->baseUrl}";
            }
        } catch (Exception $e) {
            $errores[] = "Error al conectar con CECOCO: " . $e->getMessage();
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'configuracion' => [
                'cecoco_url' => $this->baseUrl,
                'cecoco_user' => $this->cecocoUser,
                'timeout' => $this->timeout,
                'temp_path' => $this->tempPath,
            ]
        ];
    }
}
