<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

class CecocoEventosReporteService
{
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
        'p_activa_filtro_negado_tiposervicio'      => 'false',
        'p_activa_mostrar_informacion_duplicidad'  => 'false',
        'p_activa_filtro_descartar_duplicados'     => 'false',
    ];

    public function descargar(Carbon $fecha): string
    {
        $baseUrl = rtrim(config('cecoco.url', 'http://172.26.100.34:8080'), '/') . '/CECOCO_webapp';
        $fechaMin = $fecha->format('Y-m-d') . ' 00:00:00';
        $fechaMax = $fecha->format('Y-m-d') . ' 23:59:59';

        $client = new Client([
            'base_uri'    => $baseUrl,
            'cookies'     => new CookieJar(),
            'timeout'     => 120,
            'http_errors' => false,
            'headers'     => [
                'User-Agent' => 'Mozilla/5.0',
            ],
        ]);

        $client->get('/CECOCO_webapp');
        $login = $client->post('/CECOCO_webapp/ajax/perfil/AjaxServletPerfil', [
            'form_params' => [
                'LoginForm:Usuario'  => config('cecoco.user'),
                'LoginForm:Password' => config('cecoco.password'),
            ],
        ]);

        if ($login->getStatusCode() >= 400) {
            throw new RuntimeException('Error al iniciar sesión en CECOCO: HTTP ' . $login->getStatusCode());
        }

        $paramsReporte = array_merge(self::PARAMS_COMUNES, [
            '__report'                     => 'reports/issues/report_issues_list.rptdesign',
            'p_date_start_min'             => $fechaMin,
            'p_date_start_max'             => $fechaMax,
            'p_exclude_operations'         => '"false"',
            'p_exclude_system_operations'  => '"false"',
            'p_exclude_involved'           => '"false"',
        ]);

        $inicioReporte = $client->get('/CECOCO_webapp/run', ['query' => $paramsReporte]);
        if ($inicioReporte->getStatusCode() >= 400) {
            throw new RuntimeException('Error al inicializar reporte CECOCO: HTTP ' . $inicioReporte->getStatusCode());
        }

        $paramsDescarga = array_merge(self::PARAMS_COMUNES, [
            'p_date_start_min'             => $fechaMin,
            'p_date_start_max'             => $fechaMax,
            'p_exclude_operations'         => 'false',
            'p_exclude_system_operations'  => 'false',
            'p_exclude_involved'           => 'false',
            '__format'                     => 'xls',
            '__pageoverflow'               => '0',
            '__asattachment'               => 'true',
            '__overwrite'                  => 'false',
            '__emitterid'                  => 'uk.co.spudsoft.birt.emitters.excel.XlsEmitter',
            '__ExcelEmitter.SingleSheet'   => 'true',
        ]);

        $response = $client->post(
            '/CECOCO_webapp/frameset?__report=reports/issues/report_issues_list.rptdesign',
            [
                'form_params' => $paramsDescarga,
                'headers'     => [
                    'Referer'      => $baseUrl . '/frameset?__report=reports/issues/report_issues_list.rptdesign',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]
        );

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException('Error al descargar Excel CECOCO: HTTP ' . $response->getStatusCode());
        }

        $contenido = (string) $response->getBody();
        if (strlen($contenido) < 512) {
            throw new RuntimeException('El archivo descargado parece vacío o incorrecto (' . strlen($contenido) . ' bytes).');
        }

        return $contenido;
    }
}
