<?php

namespace App\Console\Commands;

use App\Jobs\ProcesarArchivoEventoCecoco;
use App\Models\Importacion;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportarEventosDiaAnterior extends Command
{
    protected $signature = 'cecoco:importar-dia-anterior
                            {--fecha= : Fecha a importar en formato Y-m-d (default: ayer)}
                            {--dry-run : Descarga el archivo pero no lo importa}';

    protected $description = 'Descarga automáticamente el reporte XLS del día anterior desde CECOCO e importa los eventos';

    private const BASE_URL  = 'http://172.26.100.34:8080/CECOCO_webapp';

    private const PARAMS_COMUNES = [
        'p_max_chars'                              => '3750',
        'showToolBar'                              => 'no',
        '__title'                                  => '',
        'p_dbworking_namedb'                       => 'bdmatriz',
        'p_dbrestore_namedb'                       => 'bdrestauraciones',
        'p_dbconnection_host'                      => 'localhost',
        '__locale'                                 => 'es',
        '__rtl'                                    => 'false',
        'p_date_format'                            => 'dd/MM/yyyy HH:mm:ss',
        'p_time_format'                            => 'HH:mm',
        'p_shift'                                  => 'false',
        'p_shift_time_min'                         => '00:00:00',
        'p_shift_time_max'                         => '23:59:59',
        'p_service_id'                             => '',
        'p_operator_name'                          => '',
        'p_caller_name'                            => '',
        'p_phone_number_caller'                    => '',
        'p_types_of_services'                      => '-',
        'p_address_service'                        => '',
        'p_description_service'                    => '',
        'p_involved_name'                          => '',
        'p_involved_field'                         => '',
        'p_id_server'                              => '-',
        'p_nombre_server'                          => '',
        'p_full_report_links'                      => 'false',
        'p_activa_filtro_negado_tiposervicio'       => 'false',
        'p_activa_mostrar_informacion_duplicidad'   => 'false',
        'p_activa_filtro_descartar_duplicados'      => 'false',
    ];

    public function handle(): int
    {
        $fecha = $this->option('fecha')
            ? \Carbon\Carbon::parse($this->option('fecha'))
            : now()->subDay();

        $fechaMin = $fecha->format('Y-m-d') . ' 00:00:00';
        $fechaMax = $fecha->format('Y-m-d') . ' 23:59:59';
        $nombreArchivo = 'reporte_' . $fecha->format('Y_m_d') . '.xls';

        $ahora = now()->format('Y-m-d H:i:s');
        $this->line("========================================");
        $this->line("[{$ahora}] cecoco:importar-dia-anterior iniciado");
        $this->info("[{$ahora}] Importando eventos del {$fecha->format('d/m/Y')}...");
        Log::info('cecoco:importar-dia-anterior iniciado', ['fecha' => $fecha->toDateString()]);

        $jar = new CookieJar();
        $client = new Client([
            'base_uri'    => self::BASE_URL,
            'cookies'     => $jar,
            'timeout'     => 120,
            'http_errors' => false,
            'headers'     => [
                'User-Agent' => 'Mozilla/5.0',
            ],
        ]);

        // 1) Iniciar sesión
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] Autenticando...');
        try {
            $client->get('/CECOCO_webapp');
            $client->post('/CECOCO_webapp/ajax/perfil/AjaxServletPerfil', [
                'form_params' => [
                    'LoginForm:Usuario'  => config('cecoco.user'),
                    'LoginForm:Password' => config('cecoco.password'),
                ],
            ]);
        } catch (\Exception $e) {
            $this->error('[' . now()->format('Y-m-d H:i:s') . '] Error de autenticación: ' . $e->getMessage());
            Log::error('cecoco:importar-dia-anterior - error autenticación', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        // 2) Inicializar reporte
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] Inicializando reporte...');
        $paramsReporte = array_merge(self::PARAMS_COMUNES, [
            '__report'          => 'reports/issues/report_issues_list.rptdesign',
            'p_date_start_min'  => $fechaMin,
            'p_date_start_max'  => $fechaMax,
            'p_exclude_operations'         => '"false"',
            'p_exclude_system_operations'  => '"false"',
            'p_exclude_involved'           => '"false"',
        ]);

        try {
            $client->get('/CECOCO_webapp/run', ['query' => $paramsReporte]);
        } catch (\Exception $e) {
            $this->error('[' . now()->format('Y-m-d H:i:s') . '] Error inicializando reporte: ' . $e->getMessage());
            Log::error('cecoco:importar-dia-anterior - error init reporte', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        // 3) Descargar Excel
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] Descargando Excel...');
        $paramsDescarga = array_merge(self::PARAMS_COMUNES, [
            'p_date_start_min'                     => $fechaMin,
            'p_date_start_max'                     => $fechaMax,
            'p_exclude_operations'                 => 'false',
            'p_exclude_system_operations'          => 'false',
            'p_exclude_involved'                   => 'false',
            '__format'                             => 'xls',
            '__pageoverflow'                       => '0',
            '__asattachment'                       => 'true',
            '__overwrite'                          => 'false',
            '__emitterid'                          => 'uk.co.spudsoft.birt.emitters.excel.XlsEmitter',
            '__ExcelEmitter.SingleSheet'            => 'true',
        ]);

        try {
            $response = $client->post(
                '/CECOCO_webapp/frameset?__report=reports/issues/report_issues_list.rptdesign',
                [
                    'form_params' => $paramsDescarga,
                    'headers'     => [
                        'Referer'      => self::BASE_URL . '/frameset?__report=reports/issues/report_issues_list.rptdesign',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]
            );
        } catch (\Exception $e) {
            $this->error('[' . now()->format('Y-m-d H:i:s') . '] Error descargando Excel: ' . $e->getMessage());
            Log::error('cecoco:importar-dia-anterior - error descarga', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $contenido = (string) $response->getBody();

        if (strlen($contenido) < 512) {
            $this->error('[' . now()->format('Y-m-d H:i:s') . '] El archivo descargado parece vacío o incorrecto (' . strlen($contenido) . ' bytes).');
            Log::error('cecoco:importar-dia-anterior - archivo inválido', [
                'bytes'    => strlen($contenido),
                'preview'  => substr($contenido, 0, 200),
            ]);
            return self::FAILURE;
        }

        // Verificar que es un XLS real (magic bytes D0 CF 11 E0 para OLE2 / Compound Document)
        if (substr($contenido, 0, 4) !== "\xD0\xCF\x11\xE0") {
            // Podría ser HTML de error; loguear primero 500 chars
            Log::warning('cecoco:importar-dia-anterior - respuesta no parece XLS', [
                'preview' => substr($contenido, 0, 500),
            ]);
            $this->warn('[' . now()->format('Y-m-d H:i:s') . '] Advertencia: la respuesta no parece un archivo XLS válido. Continuando de todas formas...');
        }

        if ($this->option('dry-run')) {
            $this->info("Dry-run: archivo de {$bytes} bytes descargado. No se importa.");
            return self::SUCCESS;
        }

        // 4) Guardar en storage temporal y disparar job
        $rutaTemporal = 'importaciones_temp/' . $nombreArchivo;
        Storage::disk('local')->put($rutaTemporal, $contenido);

        $bytes = strlen($contenido);
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Archivo guardado (' . $bytes . ' bytes). Encolando procesamiento...');

        $importacion = Importacion::create([
            'nombre_archivo' => $nombreArchivo,
            'estado'         => 'pendiente',
        ]);

        ProcesarArchivoEventoCecoco::dispatch($rutaTemporal, $nombreArchivo, $importacion->id);

        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Job despachado. Importacion ID: ' . $importacion->id);
        $this->line("========================================");
        Log::info('cecoco:importar-dia-anterior - job despachado', [
            'importacion_id' => $importacion->id,
            'archivo'        => $nombreArchivo,
            'bytes'          => $bytes,
        ]);

        return self::SUCCESS;
    }
}
