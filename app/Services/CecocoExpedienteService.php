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
        $archivoTemporal = null;
        
        try {
            Log::info('Consultando expediente CECOCO', ['expediente' => $nroExpediente]);

            Log::info('Paso 1: Iniciando sesión CECOCO');
            $client = $this->iniciarSesion();
            
            Log::info('Paso 2: Inicializando reporte expediente');
            $this->inicializarReporte($client, $nroExpediente);
            
            Log::info('Paso 3: Descargando Excel expediente');
            $archivoTemporal = $this->descargarExcel($client, $nroExpediente);
            
            Log::info('Paso 4: Parseando datos del expediente');
            $datosExpediente = $this->parsearArchivoExpediente($archivoTemporal);

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
        } finally {
            if ($archivoTemporal && file_exists($archivoTemporal)) {
                @unlink($archivoTemporal);
            }
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

    private function inicializarReporte($client, string $nroExpediente): void
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
                throw new Exception("Error al inicializar reporte: " . $response->status());
            }

            Log::info('Reporte inicializado correctamente');

        } catch (Exception $e) {
            Log::error('Error al inicializar reporte', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function descargarExcel($client, string $nroExpediente): string
    {
        try {
            $data = [
                '__report' => 'reports/issues/report_history.rptdesign',
                '__format' => 'xls',
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
                '__pageoverflow' => '0',
                '__asattachment' => 'true',
                '__emitterid' => 'uk.co.spudsoft.birt.emitters.excel.XlsEmitter',
                '__ExcelEmitter.SingleSheet' => 'true',
            ];

            $response = $client->asForm()
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => $this->baseUrl . '/frameset?__report=reports/issues/report_history.rptdesign',
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->post(
                    $this->baseUrl . '/frameset?__report=reports/issues/report_history.rptdesign',
                    $data
                );

            if (!$response->successful()) {
                throw new Exception("Error al descargar Excel: " . $response->status());
            }

            $contenido = $response->body();

            if (strlen($contenido) < 10000) {
                throw new Exception('El expediente requiere restauración desde backup. Contacte al administrador del sistema CECOCO.');
            }

            $archivoTemporal = $this->tempPath . '/expediente_' . $nroExpediente . '_' . time() . '.xls';
            file_put_contents($archivoTemporal, $contenido);

            Log::info('Excel descargado correctamente', ['archivo' => $archivoTemporal, 'tamaño' => strlen($contenido)]);

            return $archivoTemporal;

        } catch (Exception $e) {
            Log::error('Error al descargar Excel', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function parsearArchivoExpediente(string $rutaArchivo): array
    {
        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $sheet = $spreadsheet->getActiveSheet();
            $filas = $sheet->toArray();

            if (empty($filas)) {
                throw new Exception("El archivo de expediente está vacío");
            }

            $mapa = $this->mapearColumnasExpediente($filas[0]);

            if (empty($mapa)) {
                throw new Exception("No se pudieron mapear las columnas del expediente");
            }

            $timeline = [];
            for ($i = 1; $i < count($filas); $i++) {
                $fila = $filas[$i];
                if ($this->filaVacia($fila)) {
                    continue;
                }
                $evento = $this->extraerEventoTimeline($fila, $mapa);
                if (!empty($evento['fecha_hora'])) {
                    $timeline[] = $evento;
                }
            }

            if (empty($timeline)) {
                throw new Exception("No se encontraron eventos en el expediente");
            }

            usort($timeline, function($a, $b) {
                return strtotime($a['fecha_hora']) - strtotime($b['fecha_hora']);
            });

            $primerEvento = $timeline[0];

            return [
                'nro_expediente' => $primerEvento['nro_expediente'],
                'fecha_hora_inicial' => $primerEvento['fecha_hora'],
                'operador_inicial' => $primerEvento['operador'],
                'tipo_servicio' => $primerEvento['tipo_servicio'],
                'direccion' => $primerEvento['direccion'],
                'telefono' => $primerEvento['telefono'],
                'descripcion_inicial' => $primerEvento['descripcion'],
                'timeline' => $timeline,
                'total_eventos' => count($timeline),
            ];

        } catch (Exception $e) {
            Log::error('Error al parsear archivo expediente', [
                'archivo' => $rutaArchivo,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Error al procesar el archivo de expediente: " . $e->getMessage());
        }
    }

    private function mapearColumnasExpediente(array $encabezados): array
    {
        $mapa = [];
        $columnasEsperadas = [
            'nro_expediente' => ['nro expediente', 'expediente', 'nro_expediente', 'número de expediente'],
            'fecha_hora' => ['fecha hora', 'fecha_hora', 'fecha y hora', 'fecha'],
            'operador' => ['operador', 'usuario'],
            'descripcion' => ['descripcion', 'descripción', 'detalle'],
            'tipo_servicio' => ['tipo servicio', 'tipo_servicio', 'tipo', 'servicio'],
            'direccion' => ['direccion', 'dirección', 'domicilio'],
            'telefono' => ['telefono', 'teléfono', 'tel'],
            'estado' => ['estado'],
            'recurso' => ['recurso', 'movil', 'móvil'],
        ];

        foreach ($encabezados as $indice => $encabezado) {
            $encabezadoLimpio = strtolower(trim($encabezado));
            
            foreach ($columnasEsperadas as $campo => $variantes) {
                foreach ($variantes as $variante) {
                    if ($encabezadoLimpio === $variante) {
                        $mapa[$campo] = $indice;
                        break 2;
                    }
                }
            }
        }

        return $mapa;
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $celda) {
            if (!empty($celda) && trim($celda) !== '') {
                return false;
            }
        }
        return true;
    }

    private function extraerEventoTimeline(array $fila, array $mapa): array
    {
        $get = function($campo) use ($fila, $mapa) {
            if (!isset($mapa[$campo])) {
                return '';
            }
            $valor = $fila[$mapa[$campo]] ?? '';
            return is_string($valor) ? trim($valor) : (string)$valor;
        };

        return [
            'nro_expediente' => $get('nro_expediente'),
            'fecha_hora' => $get('fecha_hora'),
            'operador' => $get('operador'),
            'descripcion' => $get('descripcion'),
            'tipo_servicio' => $get('tipo_servicio'),
            'direccion' => $get('direccion'),
            'telefono' => $get('telefono'),
            'estado' => $get('estado'),
            'recurso' => $get('recurso'),
        ];
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
