<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class CecocoExpedienteService
{
    private string $baseUrl;
    private string $cecocoUser;
    private string $cecocoPassword;
    private string $cecocoUserMonitor;
    private string $cecocoPasswordMonitor;
    private string $gpsBaseUrl;
    private string $gpsLoginUrl;
    private string $gpsUserMonitor;
    private string $gpsPasswordMonitor;
    private int $timeout;
    private string $tempPath;

    public function __construct()
    {
        $this->baseUrl = config('cecoco.url', 'http://172.26.100.34:8080') . '/CECOCO_webapp';
        $this->cecocoUser = config('cecoco.user', '');
        $this->cecocoPassword = config('cecoco.password', '');
        // Credenciales separadas para el monitoreo del dashboard (login JSF completo).
        // Si no están configuradas, caemos a las generales — útil en entornos donde
        // todavía no se creó el usuario dedicado.
        $this->cecocoUserMonitor = config('cecoco.user_monitor') ?: $this->cecocoUser;
        $this->cecocoPasswordMonitor = config('cecoco.password_monitor') ?: $this->cecocoPassword;
        $gpsUrl = rtrim((string) config('cecoco.gps_url', config('cecoco.url', '')), '/');
        $this->gpsLoginUrl = config('cecoco.gps_login_url')
            ?: $gpsUrl . '/CECOCO_webapp/app/login/IndexLogin.faces';
        $this->gpsBaseUrl = $this->baseUrlDesdeLoginUrl($this->gpsLoginUrl);
        $this->gpsUserMonitor = config('cecoco.gps_user_monitor') ?: $this->cecocoUserMonitor;
        $this->gpsPasswordMonitor = config('cecoco.gps_password_monitor') ?: $this->cecocoPasswordMonitor;
        $this->timeout = config('cecoco.timeout', 60);
        $this->tempPath = storage_path('app/temp');

        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    private function extraerHtmlDeFrameset(string $framesetHtml, $client): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($framesetHtml, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $nodos = [];
        foreach (['//frame', '//iframe'] as $query) {
            foreach ($xpath->query($query) as $node) {
                $nodos[] = $node;
            }
        }

        // Priorizar frames con nombre/id que indiquen contenido de reporte
        usort($nodos, function ($a, $b) {
            $score = function ($n) {
                $name = strtolower($n->getAttribute('name') . ' ' . $n->getAttribute('id'));
                if (strpos($name, 'report') !== false)
                    return 0;
                if (strpos($name, 'birt') !== false)
                    return 1;
                return 2;
            };
            return $score($a) <=> $score($b);
        });

        foreach ($nodos as $node) {
            $src = trim($node->getAttribute('src'));
            if ($src === '')
                continue;

            $url = $this->resolverUrlAbsoluta($src);
            try {
                $res = $client->get($url);
                if (!$res->successful()) {
                    continue;
                }
                $body = $res->body();
                // Heurística simple: debe contener al menos una tabla con filas
                if (stripos($body, '<table') !== false && stripos($body, '<tr') !== false) {
                    return $body;
                }
            } catch (Exception $e) {
                // Ignorar y probar siguiente frame
                continue;
            }
        }

        // Si no encontramos una tabla, devolver el primero exitoso por si el reporte es puro HTML sin tablas
        foreach ($nodos as $node) {
            $src = trim($node->getAttribute('src'));
            if ($src === '')
                continue;
            $url = $this->resolverUrlAbsoluta($src);
            try {
                $res = $client->get($url);
                if ($res->successful()) {
                    return $res->body();
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $framesetHtml; // fallback
    }

    private function resolverUrlAbsoluta(string $src): string
    {
        // Si ya es absoluta, devolverla
        if (preg_match('/^https?:\/\//i', $src)) {
            return $src;
        }

        // Base CECOCO_webapp
        $base = rtrim($this->baseUrl, '/');

        if (strpos($src, '/') === 0) {
            // Ruta absoluta en el host
            // Extraer esquema+host+puerto de baseUrl
            $parts = parse_url($base);
            $scheme = $parts['scheme'] ?? 'http';
            $host = $parts['host'] ?? '';
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            return $scheme . '://' . $host . $port . $src;
        }

        // Ruta relativa
        return $base . '/' . ltrim($src, '/');
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
            // Primero inicializar el reporte
            $params = [
                '__report' => 'reports/issues/report_history.rptdesign',
                '__format' => 'html',
                '__parameterpage' => 'false',
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
                'p_id_selection' => $nroExpediente,
                '__locale' => 'es',
            ];

            // Intentar obtener el HTML del reporte directamente desde /output (formato HTML)
            $outputParams = $params + [
                '__format' => 'html',
                '__page' => 'all',
                '__asattachment' => 'false',
                '__overwrite' => 'false',
                '__dpi' => '96',
                '__pageoverflow' => '0',
                '__designer' => 'false',
                '__rtl' => 'false',
                '__isnull' => 'p_shift_interval',
            ];

            $respOutput = $client->get($this->baseUrl . '/output', $outputParams);
            if ($respOutput->successful()) {
                $htmlOut = $respOutput->body();
                if (stripos($htmlOut, '<frameset') === false && stripos($htmlOut, '<table') !== false) {
                    // Chequear si parece incluir la sección Acciones
                    if (stripos($htmlOut, 'Acciones') !== false || stripos($htmlOut, 'Fecha - Hora') !== false) {
                        Log::info('Reporte HTML obtenido desde /output', ['tamaño' => strlen($htmlOut)]);
                        return $htmlOut;
                    }
                }
            }

            // Intentar obtener el HTML del reporte directamente desde /run
            $respRun = $client->get($this->baseUrl . '/run', $params);
            if ($respRun->successful()) {
                $htmlRun = $respRun->body();
                // Si ya es HTML del reporte (no viewer) y contiene tablas, usarlo
                if (stripos($htmlRun, '<frameset') === false && stripos($htmlRun, 'BirtViewer_Body') === false && stripos($htmlRun, '<table') !== false) {
                    Log::info('Reporte HTML obtenido desde /run', ['tamaño' => strlen($htmlRun)]);
                    return $htmlRun;
                }
            }

            // Intentar /preview (HTML sin viewer)
            $respPreview = $client->get($this->baseUrl . '/preview', $params + [
                '__showtitle' => 'false',
            ]);
            if ($respPreview->successful()) {
                $htmlPrev = $respPreview->body();
                if (stripos($htmlPrev, '<frameset') === false && stripos($htmlPrev, 'BirtViewer_Body') === false && stripos($htmlPrev, '<table') !== false) {
                    Log::info('Reporte HTML obtenido desde /preview', ['tamaño' => strlen($htmlPrev)]);
                    return $htmlPrev;
                }
            }

            // Intentar obtener directamente el HTML del reporte con POST (sin viewer)
            $postData = $params + [
                '__pageoverflow' => '0',
                '__asattachment' => 'false',
                '__navigationbar' => 'false',
                '__page' => 'all',
            ];

            $respDirecto = $client->asForm()
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => $this->baseUrl . '/frameset?__report=reports/issues/report_history.rptdesign',
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->post($this->baseUrl . '/frameset?__report=reports/issues/report_history.rptdesign', $postData);

            if ($respDirecto->successful()) {
                $htmlDirecto = $respDirecto->body();
                if (stripos($htmlDirecto, '<frameset') === false && strlen($htmlDirecto) > 500) {
                    Log::info('Reporte HTML directo obtenido correctamente', ['tamaño' => strlen($htmlDirecto)]);
                    return $htmlDirecto;
                }
            }

            // Si aún así es frameset, obtener el frameset renderizado, reenviando los mismos parámetros del reporte
            $response = $client->get($this->baseUrl . '/frameset', $params + [
                '__navigationbar' => 'false',
            ]);

            if (!$response->successful()) {
                throw new Exception("Error al obtener reporte: " . $response->status());
            }

            $framesetHtml = $response->body();
            if (strlen($framesetHtml) < 500) {
                throw new Exception('El expediente requiere restauración desde backup. Contacte al administrador del sistema CECOCO.');
            }

            Log::info('Frameset HTML obtenido correctamente', ['tamaño' => strlen($framesetHtml)]);

            // Extraer el contenido real del reporte desde el frame/iframe
            $contenidoHtml = $this->extraerHtmlDeFrameset($framesetHtml, $client);

            if (strlen($contenidoHtml) < 500) {
                throw new Exception('No se pudo obtener el contenido del reporte desde el frameset');
            }

            Log::info('Contenido de reporte obtenido correctamente', ['tamaño' => strlen($contenidoHtml)]);

            return $contenidoHtml;

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

            // Seleccionar la MEJOR tabla de Acciones (la más completa, no todas)
            $tabla = $this->seleccionarMejorTablaAcciones($xpath);
            if ($tabla === null) {
                $tabla = $this->seleccionarTablaAcciones($xpath);
            }
            if ($tabla === null) {
                $tabla = $this->seleccionarTablaDatos($xpath);
            }
            if ($tabla === null) {
                $totalTablas = $xpath->query('//table')->length;
                $dump = $this->tempPath . '/cecoco_reporte_' . $nroExpediente . '_' . time() . '.html';
                @file_put_contents($dump, $html);
                Log::warning('No se encontró una tabla de datos adecuada en el HTML del reporte', [
                    'total_tablas' => $totalTablas,
                    'dump' => $dump,
                    'html_sample' => substr(strip_tags($html), 0, 300)
                ]);
                throw new Exception("No se encontraron eventos en el expediente");
            }
            $timeline = $this->parseEventosEnTabla($xpath, $tabla, $nroExpediente);

            if (empty($timeline)) {
                $dump = $this->tempPath . '/cecoco_reporte_' . $nroExpediente . '_' . time() . '.html';
                @file_put_contents($dump, $html);
                Log::warning('No se pudieron extraer filas de eventos de la tabla seleccionada', [
                    'dump' => $dump,
                ]);
                throw new Exception("No se encontraron eventos en el expediente");
            }

            // Eliminar duplicados globales (entre todas las tablas parseadas)
            $timelineUnique = [];
            $seen = [];
            foreach ($timeline as $evento) {
                $key = $evento['fecha_hora'] . '|' . $evento['operador'] . '|' . $evento['descripcion'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $timelineUnique[] = $evento;
                }
            }
            $timeline = $timelineUnique;

            // Ordenar por fecha con soporte d/m/Y
            usort($timeline, function ($a, $b) {
                $ta = $this->parseFecha($a['fecha_hora'] ?? '');
                $tb = $this->parseFecha($b['fecha_hora'] ?? '');
                return $ta <=> $tb;
            });

            $primerEvento = $this->primerEventoConDatos($timeline);
            Log::info('Muestra eventos CECOCO (3)', ['sample' => array_slice($timeline, 0, 3)]);

            // Extraer resumen general (encabezados) del reporte
            $resumen = $this->extraerResumenKeyValue($xpath);

            Log::info('HTML parseado correctamente', ['eventos' => count($timeline)]);

            // Extraer tabla de Trámites
            $tramites = $this->extraerTramites($xpath);

            // Extraer el bloque "Datos del cierre" (fecha, tipo y observaciones de cierre)
            $cierre = $this->extraerDatosCierre($xpath);

            return [
                'nro_expediente' => $resumen['expediente'] ?? $nroExpediente,
                'fecha_hora_inicial' => $primerEvento['fecha_hora'] ?? ($resumen['fecha_inicio'] ?? null),
                'operador_inicial' => $primerEvento['operador'] ?? ($resumen['operador'] ?? null),
                'tipo_servicio' => $resumen['tipo'] ?? null,
                'direccion' => $resumen['direccion'] ?? null,
                'telefono' => $resumen['telefono'] ?? null,
                'descripcion_inicial' => $resumen['descripcion'] ?? ($primerEvento['descripcion'] ?? null),
                'historial' => $resumen,
                'timeline' => $timeline,
                'total_eventos' => count($timeline),
                'tramites' => $tramites,
                'total_tramites' => count($tramites),
                'cierre' => $cierre,
            ];

        } catch (Exception $e) {
            Log::error('Error al parsear HTML expediente', ['error' => $e->getMessage()]);
            throw new Exception("Error al procesar el reporte del expediente: " . $e->getMessage());
        }
    }

    private function seleccionarTablaDatos(\DOMXPath $xpath): ?\DOMNode
    {
        $tablas = $xpath->query('//table');
        if ($tablas->length === 0) {
            return null;
        }

        $mejor = null;
        $mejorScore = -1;

        foreach ($tablas as $tabla) {
            $ths = $xpath->query('.//th', $tabla)->length;
            $trs = $xpath->query('.//tr', $tabla)->length;
            $tds = $xpath->query('.//td', $tabla)->length;

            // Heurística: más filas y celdas; ignorar tablas pequeñas (ej. toolbars)
            $score = ($trs * 10) + $tds + ($ths * 2);
            if ($trs < 2 || $tds < 4) {
                continue; // probablemente no es tabla de datos
            }
            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejor = $tabla;
            }
        }

        return $mejor;
    }

    private function seleccionarMejorTablaAcciones(\DOMXPath $xpath): ?\DOMNode
    {
        $tablas = $xpath->query('//table');
        $mejorTabla = null;
        $maxEventos = 0;

        foreach ($tablas as $tabla) {
            // Verificar si es tabla de Acciones
            $headerTr = null;
            foreach ($xpath->query('.//tr', $tabla) as $trPosible) {
                if ($xpath->query('.//th', $trPosible)->length > 0) {
                    $headerTr = $trPosible;
                    break;
                }
            }
            if (!$headerTr)
                continue;

            $enc = [];
            foreach ($xpath->query('.//th|.//td', $headerTr) as $celda) {
                $enc[] = $this->normalizarClaveColumna(trim($celda->textContent));
            }
            $h = implode(' ', $enc);
            $esAcciones = strpos($h, 'fecha') !== false && strpos($h, 'operador') !== false &&
                (strpos($h, 'accion') !== false || strpos($h, 'acci_on') !== false) &&
                (strpos($h, 'caracteristicas') !== false || strpos($h, 'caracter_isticas') !== false);

            if (!$esAcciones)
                continue;

            // Contar filas válidas (4 columnas con fecha parseable)
            $countValidas = 0;
            foreach ($xpath->query('.//tr', $tabla) as $fila) {
                $celdas = $xpath->query('.//td', $fila);
                if ($celdas->length !== 4)
                    continue;
                $primeraColumna = trim($celdas->item(0)->textContent);
                $primeraColumna = preg_replace('/\x{00A0}|\xC2\xA0/u', ' ', $primeraColumna);
                $primeraColumna = preg_replace('/\s+/u', ' ', $primeraColumna);
                $primeraColumna = trim($primeraColumna);
                if ($this->parseFecha($primeraColumna) !== PHP_INT_MAX) {
                    $countValidas++;
                }
            }

            if ($countValidas > $maxEventos) {
                $maxEventos = $countValidas;
                $mejorTabla = $tabla;
            }
        }

        if ($mejorTabla !== null) {
            Log::info('Tabla de Acciones seleccionada', ['filas_validas' => $maxEventos]);
        }

        return $mejorTabla;
    }

    private function seleccionarTablasAcciones(\DOMXPath $xpath): array
    {
        $tablas = $xpath->query('//table');
        $candidatas = [];
        foreach ($tablas as $tabla) {
            $headerTr = null;
            foreach ($xpath->query('.//tr', $tabla) as $trPosible) {
                if ($xpath->query('.//th', $trPosible)->length > 0) {
                    $headerTr = $trPosible;
                    break;
                }
            }
            if (!$headerTr)
                continue;
            $enc = [];
            foreach ($xpath->query('.//th|.//td', $headerTr) as $celda) {
                $enc[] = $this->normalizarClaveColumna(trim($celda->textContent));
            }
            $h = implode(' ', $enc);
            $ok = strpos($h, 'fecha') !== false && strpos($h, 'operador') !== false &&
                (strpos($h, 'accion') !== false || strpos($h, 'acci_on') !== false) &&
                (strpos($h, 'caracteristicas') !== false || strpos($h, 'caracter_isticas') !== false);
            if ($ok) {
                $candidatas[] = $tabla;
            }
        }
        return $candidatas;
    }

    private function parseEventosEnTabla(\DOMXPath $xpath, \DOMNode $tabla, string $nroExpediente): array
    {
        $result = [];

        foreach ($xpath->query('.//tr', $tabla) as $fila) {
            $celdas = $xpath->query('.//td', $fila);
            // Solo procesar filas con exactamente 4 columnas (fecha, operador, accion, caracteristicas)
            if ($celdas->length !== 4) {
                continue;
            }

            $valores = [];
            foreach ($celdas as $celda) {
                $valor = $celda->textContent;
                $valor = preg_replace('/\x{00A0}|\xC2\xA0/u', ' ', $valor);
                $valor = preg_replace('/\s+/u', ' ', (string) $valor);
                $valores[] = trim((string) $valor);
            }

            // Validar que la primera columna sea una fecha válida
            $fechaTs = $this->parseFecha($valores[0]);
            if ($fechaTs === PHP_INT_MAX) {
                continue;
            }

            // Construir evento directamente
            $evento = [
                'nro_expediente' => $nroExpediente,
                'fecha_hora' => $valores[0],
                'operador' => $valores[1],
                'descripcion' => $valores[2],
                'estado' => $valores[3],
                'tipo_servicio' => '',
                'direccion' => '',
                'telefono' => '',
                'recurso' => '',
            ];

            // Si descripción vacía pero hay estado, usar estado
            if (empty($evento['descripcion']) && !empty($evento['estado'])) {
                $evento['descripcion'] = $evento['estado'];
            }

            $result[] = $evento;
        }

        return $result;
    }

    private function seleccionarTablaAcciones(\DOMXPath $xpath): ?\DOMNode
    {
        $tablas = $xpath->query('//table');
        $mejor = null;
        $mejorScore = -1;

        foreach ($tablas as $tabla) {
            // Detectar encabezados
            $headerTr = $xpath->query('.//thead/tr[1]', $tabla)->item(0);
            if (!$headerTr) {
                foreach ($xpath->query('.//tr', $tabla) as $trPosible) {
                    if ($xpath->query('.//th', $trPosible)->length > 0) {
                        $headerTr = $trPosible;
                        break;
                    }
                }
            }
            if (!$headerTr) {
                continue;
            }

            $enc = [];
            foreach ($xpath->query('.//th|.//td', $headerTr) as $celda) {
                $enc[] = $this->normalizarClaveColumna(trim($celda->textContent));
            }

            if (empty($enc)) {
                continue;
            }

            // Calcular score por coincidencias típicas de la tabla Acciones
            $tokens = [
                'fecha',      // Fecha o Fecha - Hora
                'hora',       // Hora cuando viene separada
                'operador',
                'accion',
                'caracteristicas',
            ];

            $score = 0;
            $hasFecha = false;
            $hasHora = false;
            foreach ($enc as $h) {
                if (strpos($h, 'fecha') !== false) {
                    $hasFecha = true;
                    $score += 2;
                }
                if (strpos($h, 'hora') !== false) {
                    $hasHora = true;
                    $score += 1;
                }
                if (strpos($h, 'operador') !== false) {
                    $score += 2;
                }
                if (strpos($h, 'accion') !== false) {
                    $score += 2;
                }
                if (strpos($h, 'caracteristicas') !== false) {
                    $score += 2;
                }
            }
            // Bonus por tamaño (más filas de datos)
            $trs = $xpath->query('.//tr', $tabla)->length;
            $tds = $xpath->query('.//td', $tabla)->length;
            $score += max(0, ($trs - 2));
            $score += intdiv($tds, 10);

            // Requerir al menos 3 señales y que tenga filas de datos
            if ($score >= 6 && $trs >= 3) {
                if ($score > $mejorScore) {
                    $mejorScore = $score;
                    $mejor = $tabla;
                }
            }
        }

        return $mejor;
    }

    private function parseFecha(string $val): int
    {
        $s = trim($val);
        if ($s === '') {
            return PHP_INT_MAX;
        }
        $formatos = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
        foreach ($formatos as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $s);
            if ($dt instanceof \DateTime) {
                return $dt->getTimestamp();
            }
        }
        $ts = strtotime($s);
        return $ts !== false ? $ts : PHP_INT_MAX;
    }

    private function bestHeaderWindow(array $headers, int $nCols): array
    {
        $headers = array_values(array_filter($headers, function ($h) {
            return $h !== null && $h !== '';
        }));
        $m = count($headers);
        if ($m === 0 || $nCols <= 0) {
            return [];
        }
        if ($m === $nCols) {
            return $headers;
        }
        $tokens = ['fecha_hora', 'fecha', 'hora', 'operador', 'accion', 'acci_on', 'caracteristicas', 'caracter_isticas'];
        $best = [];
        $bestScore = -1;
        for ($i = 0; $i <= $m - $nCols; $i++) {
            $win = array_slice($headers, $i, $nCols);
            $score = 0;
            foreach ($win as $h) {
                foreach ($tokens as $t) {
                    if (strpos($h, $t) !== false) {
                        $score++;
                        break;
                    }
                }
            }
            // prefer ventanas con menos vacíos
            $nonEmpty = count(array_filter($win, function ($h) {
                return $h !== '';
            }));
            $score += $nonEmpty * 0.1;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $win;
            }
        }
        if (!empty($best)) {
            return $best;
        }
        // Fallback: encabezados por defecto
        $defaults = ['fecha_hora', 'operador', 'accion', 'caracteristicas_de_la_accion'];
        return array_slice($defaults, 0, $nCols);
    }

    private function normalizarDatosEvento(array $datos, string $nroExpediente): array
    {
        $camposPosibles = [
            'nro_expediente' => ['nro_expediente', 'expediente', 'numero_expediente', 'nro'],
            'fecha_hora' => ['fecha_hora', 'fecha_y_hora', 'fecha_-_hora', 'fechahora', 'fecha', 'fecha_hora_creacion', 'fecha_hora_evento'],
            'operador' => ['operador', 'usuario', 'operador_telefonista'],
            'descripcion' => ['descripcion', 'detalle', 'observacion', 'observaciones', 'accion'],
            'tipo_servicio' => ['tipo_servicio', 'tipo_de_servicio', 'tipo', 'servicio'],
            'direccion' => ['direccion', 'domicilio', 'ubicacion'],
            'telefono' => ['telefono', 'tel', 'numero_telefono'],
            'estado' => ['estado', 'caracteristicas_de_la_accion', 'caracteristicas', 'caracteristicas_accion'],
            'recurso' => ['recurso', 'movil', 'unidad'],
        ];

        $evento = [];

        foreach ($camposPosibles as $campoNormalizado => $variantes) {
            $evento[$campoNormalizado] = '';

            foreach ($variantes as $variante) {
                $clave = $this->normalizarClaveColumna($variante);
                if ($clave !== '' && isset($datos[$clave])) {
                    $evento[$campoNormalizado] = $datos[$clave];
                    break;
                }
            }

            // Si no hubo match exacto, intentar búsqueda aproximada por tokens
            if ($evento[$campoNormalizado] === '') {
                $valorAprox = $this->buscarEnDatosPorTokens($datos, $variantes);
                if ($valorAprox !== '') {
                    $evento[$campoNormalizado] = $valorAprox;
                }
            }
        }

        // Si no se encontró el número de expediente en los datos, usar el parámetro
        if (empty($evento['nro_expediente'])) {
            $evento['nro_expediente'] = $nroExpediente;
        }
        // Si la descripción quedó vacía pero hay estado, usar estado como descripción
        if (empty($evento['descripcion']) && !empty($evento['estado'])) {
            $evento['descripcion'] = $evento['estado'];
        }

        return $evento;
    }

    private function buscarEnDatosPorTokens(array $datos, array $tokens): string
    {
        if (empty($datos)) {
            return '';
        }

        // Mapa de claves normalizadas existentes en los datos
        $map = [];
        foreach ($datos as $k => $v) {
            $kn = $this->normalizarClaveColumna((string) $k);
            if ($kn !== '') {
                $map[$kn] = $v;
            }
        }

        foreach ($tokens as $tok) {
            $t = $this->normalizarClaveColumna($tok);
            if ($t === '') {
                continue;
            }
            // 1) Coincidencia exacta
            if (isset($map[$t])) {
                $val = $map[$t];
                return is_string($val) ? trim($val) : (string) $val;
            }
            // 2) Coincidencia por contiene (sin guiones bajos)
            $t2 = str_replace('_', '', $t);
            foreach ($map as $kn => $val) {
                $k2 = str_replace('_', '', $kn);
                if ($t2 !== '' && strpos($k2, $t2) !== false) {
                    return is_string($val) ? trim($val) : (string) $val;
                }
            }
        }

        return '';
    }

    private function normalizarClaveColumna(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($texto === false) {
            $texto = '';
        }

        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
        $texto = trim((string) $texto, '_');

        return $texto;
    }

    private function primerEventoConDatos(array $timeline): array
    {
        foreach ($timeline as $evento) {
            if (!empty($evento['fecha_hora']) || !empty($evento['operador']) || !empty($evento['descripcion']) || !empty($evento['tipo_servicio'])) {
                return $evento;
            }
        }

        return $timeline[0] ?? [];
    }

    private function extraerTramites(\DOMXPath $xpath): array
    {
        // Elegir la tabla de Trámites real (encabezado limpio que empieza en "Unidad"),
        // descartando las tablas combinadas Acciones+Trámites que el reporte anida y
        // cuyo encabezado arranca en "Fecha - Hora".
        [$tabla, $enc, $headerTr] = $this->seleccionarMejorTablaTramites($xpath);
        if ($tabla === null) {
            return [];
        }

        $tramites = [];

        foreach ($xpath->query('.//tr', $tabla) as $fila) {
            if ($headerTr && $fila->isSameNode($headerTr)) {
                continue;
            }
            $celdas = $xpath->query('.//td', $fila);
            if ($celdas->length < 2) {
                continue;
            }

            $valores = [];
            foreach ($celdas as $celda) {
                $valor = $this->limpiarTextoCelda($celda->textContent);
                // Normalizar sentinel de fecha nula de CECOCO
                if ($valor === '01/01/2000 00:00:00' || $valor === '01/01/2000') {
                    $valor = '';
                }
                $valores[] = $valor;
            }

            // La primera columna es la Unidad: debe tener un valor real
            if (($valores[0] ?? '') === '' || ($valores[0] ?? '') === '-') {
                continue;
            }

            $tramite = [];
            for ($i = 0; $i < count($valores) && $i < count($enc); $i++) {
                if ($enc[$i] !== '') {
                    $tramite[$enc[$i]] = $valores[$i];
                }
            }

            $tramites[] = $tramite;
        }

        if (!empty($tramites)) {
            Log::info('Trámites extraídos', ['total' => count($tramites)]);
        }

        return $tramites;
    }

    /**
     * Selecciona la tabla de Trámites con más unidades. El encabezado de la tabla
     * real comienza en "Unidad"; las tablas combinadas Acciones+Trámites (cuyo
     * encabezado arranca en "Fecha - Hora") quedan descartadas.
     *
     * @return array{0: ?\DOMNode, 1: array<int, string>, 2: ?\DOMNode}
     */
    private function seleccionarMejorTablaTramites(\DOMXPath $xpath): array
    {
        $mejorTabla = null;
        $mejorEnc = [];
        $mejorHeader = null;
        $maxUnidades = 0;

        foreach ($xpath->query('//table') as $tabla) {
            $headerTr = null;
            foreach ($xpath->query('.//tr', $tabla) as $trPosible) {
                if ($xpath->query('.//th', $trPosible)->length > 1) {
                    $headerTr = $trPosible;
                    break;
                }
            }
            if (!$headerTr) {
                continue;
            }

            $enc = [];
            foreach ($xpath->query('.//th|.//td', $headerTr) as $celda) {
                $enc[] = $this->normalizarClaveColumna(trim($celda->textContent));
            }

            // La tabla de Trámites tiene su primera columna "Unidad" y horarios de asignación
            if (empty($enc) || $enc[0] !== 'unidad' || strpos(implode(' ', $enc), 'asig') === false) {
                continue;
            }

            $unidades = 0;
            foreach ($xpath->query('.//tr', $tabla) as $fila) {
                if ($fila->isSameNode($headerTr)) {
                    continue;
                }
                $celdas = $xpath->query('.//td', $fila);
                if ($celdas->length < 2) {
                    continue;
                }
                $unidad = $this->limpiarTextoCelda($celdas->item(0)->textContent);
                if ($unidad !== '' && $unidad !== '-') {
                    $unidades++;
                }
            }

            if ($unidades > $maxUnidades) {
                $maxUnidades = $unidades;
                $mejorTabla = $tabla;
                $mejorEnc = $enc;
                $mejorHeader = $headerTr;
            }
        }

        return [$mejorTabla, $mejorEnc, $mejorHeader];
    }

    /**
     * Extrae el bloque "Datos del cierre" del reporte BIRT: fecha de cierre,
     * tipo de cierre y observaciones de cierre. El bloque aparece después del
     * encabezado "Datos del cierre" con las etiquetas "Fecha:", "Tipo de cierre:"
     * y "Observaciones:" en las filas siguientes.
     *
     * @return array{fecha: string, tipo: string, observaciones: string}
     */
    private function extraerDatosCierre(\DOMXPath $xpath): array
    {
        $cierre = [
            'fecha' => '',
            'tipo' => '',
            'observaciones' => '',
        ];

        $titulos = $xpath->query('//div[normalize-space()="Datos del cierre"]');
        if ($titulos->length === 0) {
            return $cierre;
        }

        // Subir desde el título hasta la fila <tr> que lo contiene
        $fila = $titulos->item(0);
        while ($fila !== null && strtolower($fila->nodeName) !== 'tr') {
            $fila = $fila->parentNode;
        }
        if ($fila === null) {
            return $cierre;
        }

        // Recorrer las filas siguientes hasta encontrar las etiquetas o llegar a "Acciones"
        $hermano = $fila->nextSibling;
        $filasRecorridas = 0;
        while ($hermano !== null && $filasRecorridas < 8) {
            if ($hermano->nodeType === XML_ELEMENT_NODE && strtolower($hermano->nodeName) === 'tr') {
                $filasRecorridas++;

                if (strpos($this->normalizarEtiquetaCierre($hermano->textContent), 'acciones') !== false) {
                    break;
                }

                $celdas = [];
                foreach ($xpath->query('.//td', $hermano) as $celda) {
                    $celdas[] = $celda;
                }

                $totalCeldas = count($celdas);
                for ($i = 0; $i < $totalCeldas - 1; $i++) {
                    $etiqueta = $this->normalizarEtiquetaCierre($celdas[$i]->textContent);
                    $valor = $this->limpiarTextoCelda($celdas[$i + 1]->textContent);

                    if ($etiqueta === 'fecha' && $cierre['fecha'] === '') {
                        $cierre['fecha'] = $valor;
                    } elseif ($etiqueta === 'tipo de cierre') {
                        $cierre['tipo'] = $valor;
                    } elseif ($etiqueta === 'observaciones') {
                        $cierre['observaciones'] = $valor;
                    }
                }
            }
            $hermano = $hermano->nextSibling;
        }

        return $cierre;
    }

    /**
     * Normaliza el contenido textual de una celda: colapsa espacios y reemplaza
     * el espacio duro (&#xa0;) que BIRT usa en celdas vacías.
     */
    private function limpiarTextoCelda(string $texto): string
    {
        $texto = preg_replace('/\x{00A0}|\xC2\xA0/u', ' ', $texto);
        $texto = preg_replace('/\s+/u', ' ', (string) $texto);

        return trim((string) $texto);
    }

    /**
     * Normaliza una etiqueta del bloque de cierre a minúsculas sin los dos puntos
     * finales, para compararla con "fecha", "tipo de cierre" u "observaciones".
     */
    private function normalizarEtiquetaCierre(string $texto): string
    {
        return rtrim(mb_strtolower($this->limpiarTextoCelda($texto), 'UTF-8'), ': ');
    }

    /**
     * Extrae pares clave:valor del encabezado del reporte BIRT para completar
     * expediente, tipo, operador, fecha_inicio, direccion, telefono, descripcion.
     */
    private function extraerResumenKeyValue(\DOMXPath $xpath): array
    {
        $resumen = [];

        // Etiquetas objetivo vistas en el reporte BIRT
        $objetivos = [
            'expediente' => ['expediente:', 'expediente'],
            'tipo' => ['tipo:', 'tipo', 'tipo_servicio:', 'tipo_servicio'],
            'operador' => ['operador:', 'operador', 'usuario:', 'usuario', 'telefonista:', 'telefonista'],
            'fecha_inicio' => ['fecha_inicio:', 'fecha de creacion:', 'fecha de creación:', 'fecha de ejecucion:', 'fecha de ejecución:', 'fecha:', 'fecha/hora:'],
            'direccion' => ['direccion:', 'dirección:', 'domicilio:', 'ubicacion:', 'ubicación:', 'calle:'],
            'telefono' => ['telefono:', 'teléfono:', 'tel:', 'nro telefono:', 'nro. telefono:'],
            'descripcion' => ['descripcion:', 'descripción:', 'observaciones:', 'detalle:', 'motivo:'],
            'estado' => ['estado:', 'estado'],
            'barrio' => ['barrio:', 'barrio'],
            'jurisdiccion' => ['jurisdiccion:', 'jurisdicción:', 'comisaria:', 'comisaría:'],
            'municipio' => ['municipio:', 'municipio', 'localidad:', 'localidad'],
            'puesto' => ['puesto:', 'puesto', 'box:', 'box'],
            'sector' => ['sector:', 'sector'],
        ];

        $tablas = $xpath->query('//table');
        foreach ($tablas as $tabla) {
            $filas = $xpath->query('.//tr', $tabla);
            foreach ($filas as $fila) {
                // Obtener celdas en orden (th y td)
                $cells = [];
                foreach ($xpath->query('.//th|.//td', $fila) as $celda) {
                    $cells[] = $celda;
                }
                $n = count($cells);

                if ($n >= 2) {
                    // Caso típico: celda etiqueta + celda valor en la misma fila
                    for ($i = 0; $i < $n - 1; $i++) {
                        $labelText = trim($cells[$i]->textContent);
                        $labelNorm = $this->normalizarClaveColumna($labelText);
                        if ($labelNorm === '')
                            continue;

                        foreach ($objetivos as $clave => $variantes) {
                            foreach ($variantes as $token) {
                                $tokenNorm = $this->normalizarClaveColumna($token);
                                if ($tokenNorm !== '' && strpos($labelNorm, $tokenNorm) !== false) {
                                    // Tomar el valor de la próxima celda de datos si existe
                                    $j = $i + 1;
                                    if ($j < $n) {
                                        $valor = trim($cells[$j]->textContent);
                                        if ($valor !== '') {
                                            // Verificar que el valor no sea otra etiqueta conocida
                                            $valorEsEtiqueta = false;
                                            // Si termina en ":" es otra etiqueta (aplica a cualquier longitud)
                                            if (preg_match('/:\s*$/', $valor)) {
                                                $valorEsEtiqueta = true;
                                            }
                                            // Solo para valores cortos (<50 chars): verificar si coincide con
                                            // otra clave objetivo (textos largos como descripciones pueden
                                            // contener palabras como "calle", "estado", etc.)
                                            if (!$valorEsEtiqueta && mb_strlen($valor) < 50) {
                                                $valorNorm = $this->normalizarClaveColumna($valor);
                                                if ($valorNorm !== '') {
                                                    foreach ($objetivos as $otraClave => $otrasVariantes) {
                                                        if ($otraClave === $clave)
                                                            continue;
                                                        foreach ($otrasVariantes as $otraVar) {
                                                            $otraVarNorm = $this->normalizarClaveColumna($otraVar);
                                                            if ($otraVarNorm !== '' && ($valorNorm === $otraVarNorm || strpos($valorNorm, $otraVarNorm) !== false)) {
                                                                $valorEsEtiqueta = true;
                                                                break 2;
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            if (!$valorEsEtiqueta) {
                                                if (!isset($resumen[$clave]) || $resumen[$clave] === '') {
                                                    $resumen[$clave] = $valor;
                                                }
                                            }
                                        }
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                } else {
                    // Caso alternativo: "Etiqueta: valor" en una sola celda
                    foreach ($cells as $celda) {
                        $texto = trim($celda->textContent);
                        if (strpos($texto, ':') === false)
                            continue;
                        $partes = explode(':', $texto, 2);
                        $labelNorm = $this->normalizarClaveColumna($partes[0]);
                        $valor = trim($partes[1]);
                        if ($labelNorm === '' || $valor === '')
                            continue;

                        foreach ($objetivos as $clave => $variantes) {
                            foreach ($variantes as $token) {
                                $tokenNorm = $this->normalizarClaveColumna($token);
                                if ($tokenNorm !== '' && $labelNorm === $tokenNorm) {
                                    if (!isset($resumen[$clave]) || $resumen[$clave] === '') {
                                        $resumen[$clave] = $valor;
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if (count($resumen) >= 5)
                break;
        }

        return $resumen;
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

    public const CACHE_KEY_TAMANO_RESTAURACIONES = 'cecoco_tamano_bd_restauraciones';
    public const CACHE_KEY_TAMANO_RESTAURACIONES_GPS = 'cecoco_gps_tamano_bd_restauraciones';
    public const CACHE_KEY_FICHEROS_RESTAURADOS_GPS = 'cecoco_gps_ficheros_restaurados';

    /**
     * Devuelve el último valor cacheado del tamaño de la BD de restauraciones de CECOCO.
     * No realiza llamadas HTTP: depende del schedule que ejecuta actualizarCacheTamanoBaseRestauraciones().
     *
     * @return array{mb: float, consultado_en: string}|null
     */
    public function obtenerTamanoBaseRestauraciones(): ?array
    {
        return Cache::get(self::CACHE_KEY_TAMANO_RESTAURACIONES);
    }

    /**
     * Devuelve el último valor cacheado del tamaño de la BD de restauraciones GPS.
     *
     * @return array{mb: float, consultado_en: string}|null
     */
    public function obtenerTamanoBaseRestauracionesGps(): ?array
    {
        return Cache::get(self::CACHE_KEY_TAMANO_RESTAURACIONES_GPS);
    }

    /**
     * Consulta CECOCO en vivo, parsea el HTML de Históricos > Gestión > Restauraciones
     * y guarda el tamaño en MB en cache. Pensado para correr desde un schedule horario.
     *
     * @return array{mb: float, consultado_en: string}
     * @throws Exception si el login o el parseo falla.
     */
    public function actualizarCacheTamanoBaseRestauraciones(): array
    {
        $result = $this->fetchTamanoBaseRestauracionesEnVivo();
        $mb = $result['mb'];
        $payload = [
            'mb' => $mb,
            'consultado_en' => Carbon::now()->toIso8601String(),
        ];

        // TTL 90 minutos: el schedule corre cada hora; damos margen ante una corrida fallida.
        Cache::put(self::CACHE_KEY_TAMANO_RESTAURACIONES, $payload, 60 * 90);

        Log::info('Tamaño BD restauraciones CECOCO actualizado', $payload);

        return $payload;
    }

    /**
     * Consulta el CECOCO GPS y guarda el tamaño en MB en cache.
     *
     * @return array{mb: float, consultado_en: string}
     * @throws Exception si el login o el parseo falla.
     */
    public function actualizarCacheTamanoBaseRestauracionesGps(): array
    {
        $result = $this->fetchTamanoBaseRestauracionesEnVivo(
            $this->gpsBaseUrl,
            $this->gpsLoginUrl,
            $this->gpsUserMonitor,
            $this->gpsPasswordMonitor,
            'CECOCO GPS'
        );
        $mb = $result['mb'];
        $payload = [
            'mb' => $mb,
            'consultado_en' => Carbon::now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY_TAMANO_RESTAURACIONES_GPS, $payload, 60 * 90);

        // Cachear ficheros restaurados GPS
        if (!empty($result['html'])) {
            $ficheros = $this->extraerFicherosRestauradosDesdeHtml($result['html']);
            Cache::put(self::CACHE_KEY_FICHEROS_RESTAURADOS_GPS, $ficheros, 60 * 90);
        }

        Log::info('Tamaño BD restauraciones GPS actualizado', $payload);

        return $payload;
    }

    /**
     * Login JSF + dispatch + lectura de la pantalla GestionRestauraciones.
     * Usa cURL nativo porque el flujo JSF es sensible al orden y formato del body.
     */
    private function fetchTamanoBaseRestauracionesEnVivo(
        ?string $baseUrl = null,
        ?string $loginUrl = null,
        ?string $usuario = null,
        ?string $password = null,
        string $contexto = 'CECOCO'
    ): array
    {
        $baseUrl = $baseUrl ?: $this->baseUrl;
        $loginUrl = $loginUrl ?: $baseUrl . '/app/login/Login.faces';
        $usuario = $usuario ?: $this->cecocoUserMonitor;
        $password = $password ?: $this->cecocoPasswordMonitor;
        $cookieFile = tempnam(sys_get_temp_dir(), 'cecoco_jar_');
        if ($cookieFile === false) {
            throw new Exception('No se pudo crear el cookie jar temporal');
        }

        try {
            // 1) Bootstrap de sesión.
            $this->curlRequest($baseUrl . '/', 'GET', [], null, $cookieFile);

            // 2) GET Login.faces para extraer el ViewState que MyFaces espera en el POST.
            $loginFormUrl = $loginUrl;
            $resp = $this->curlRequest($loginFormUrl, 'GET', [], null, $cookieFile);
            if (!preg_match('/id="javax\.faces\.ViewState"\s+value="([^"]+)"/i', $resp['body']) && stripos($resp['body'], 'Login.faces') !== false) {
                $loginFormUrl = $baseUrl . '/app/login/Login.faces';
                $resp = $this->curlRequest($loginFormUrl, 'GET', ['Referer: ' . $loginUrl], null, $cookieFile);
            }
            if (!preg_match('/id="javax\.faces\.ViewState"\s+value="([^"]+)"/i', $resp['body'], $m)) {
                throw new Exception('No se pudo extraer ViewState del formulario de login');
            }
            $viewState = html_entity_decode($m[1]);
            $perfil = $this->obtenerPerfilLoginCecoco($baseUrl, $usuario, $password, $cookieFile) ?: 'Coordinador Despacho';

            // 3) POST Login.faces con el body en el orden exacto del navegador
            //    (autoScroll vacío, no "0,0", o JSF rebota al login).
            //    Usa el usuario MONITOR para no chocar con la sesión del usuario operativo.
            $body = 'LoginForm%3AidiomaSelect=es_ES'
                . '&LoginForm%3AUsuario=' . rawurlencode($usuario)
                . '&LoginForm%3APassword=' . rawurlencode($password)
                . '&LoginForm%3AperfilSelect=' . rawurlencode($perfil)
                . '&LoginForm%3AbotonLogin=Login'
                . '&autoScroll='
                . '&LoginForm_SUBMIT=1'
                . '&LoginForm%3A_idcl='
                . '&LoginForm%3A_link_hidden_='
                . '&javax.faces.ViewState=' . rawurlencode($viewState);

            $resp = $this->curlRequest(
                $loginFormUrl,
                'POST',
                [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: es-419,es;q=0.9',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Origin: ' . parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST)
                        . (parse_url($baseUrl, PHP_URL_PORT) ? ':' . parse_url($baseUrl, PHP_URL_PORT) : ''),
                    'Referer: ' . $loginFormUrl,
                    'Upgrade-Insecure-Requests: 1',
                ],
                $body,
                $cookieFile
            );

            // CECOCO permite UNA sesión por usuario: si ya hay otra activa, rebota con
            // mensaje = 'Usuario en sesión' y url = Login.faces (popup MensajeError).
            if (stripos($resp['body'], 'Usuario en sesi') !== false) {
                throw new Exception("{$contexto} ya tiene una sesión activa para el usuario '{$usuario}'. Verificar que ese usuario sea exclusivo del dashboard y no esté abierto en ningún navegador.");
            }
            if (stripos($resp['body'], 'LoginForm:Usuario') !== false) {
                throw new Exception('Login JSF rechazado por CECOCO (volvió al formulario de login)');
            }

            // 4a) GET Menu.faces para consolidar la sesión post-login
            //     (el dispatch falla si se hace inmediatamente después del POST de Login).
            $this->curlRequest(
                $baseUrl . '/app/inicio/Menu.faces',
                'GET',
                ['Referer: ' . $loginFormUrl],
                null,
                $cookieFile
            );

            // 4) Dispatch del menú: instancia el bean gestionRestauracionesHistoricos en sesión.
            $respDispatch = $this->curlRequest(
                $baseUrl . '/app/inicio/DispatchAdministracionMenu.faces?id=historicosGestionRestauraciones',
                'GET',
                ['Referer: ' . $baseUrl . '/app/inicio/Menu.faces'],
                null,
                $cookieFile
            );

            if ($respDispatch['status'] === 302 && preg_match('/^Location:\s*(.+)$/mi', $respDispatch['headers'], $mLocation)) {
                $this->curlRequest(
                    $this->resolverUrlAbsolutaConBase(trim($mLocation[1]), $baseUrl),
                    'GET',
                    ['Referer: ' . $baseUrl . '/app/inicio/DispatchAdministracionMenu.faces?id=historicosGestionRestauraciones'],
                    null,
                    $cookieFile
                );
            }

            // 5) El dato puede venir directamente desde la monitorización AJAX que dispara la pantalla.
            $respAjax = $this->curlRequest(
                $baseUrl . '/app/shale/gestionRestauracionesHistoricos/doMonitorizarAjax.faces',
                'POST',
                [
                    'Accept: text/javascript, text/html, application/xml, text/xml, */*',
                    'Accept-Language: es-419,es;q=0.9',
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin: ' . parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST)
                        . (parse_url($baseUrl, PHP_URL_PORT) ? ':' . parse_url($baseUrl, PHP_URL_PORT) : ''),
                    'Referer: ' . $baseUrl . '/app/inicio/DispatchAdministracionMenu.faces?id=historicosGestionRestauraciones',
                    'X-Prototype-Version: 1.7',
                    'X-Requested-With: XMLHttpRequest',
                ],
                '',
                $cookieFile
            );

            $htmlConTabla = null;

            $mb = $this->extraerTamanoBaseRestauracionesDesdeHtml($respAjax['body']);

            if ($mb === null) {
                // Fallback para el CECOCO principal: algunas versiones lo exponen en el GET de GestionRestauraciones.
                $resp = $this->curlRequest(
                    $baseUrl . '/app/historicos/gestion/GestionRestauraciones.faces',
                    'GET',
                    ['Referer: ' . $baseUrl . '/app/inicio/DispatchAdministracionMenu.faces?id=historicosGestionRestauraciones'],
                    null,
                    $cookieFile
                );

                $mb = $this->extraerTamanoBaseRestauracionesDesdeHtml($resp['body']);
                $htmlConTabla = $resp['body'];

                if ($mb === null && (stripos($resp['body'], 'NullPointerException') !== false || stripos($respAjax['body'], 'NullPointerException') !== false)) {
                    throw new Exception('Bean gestionRestauracionesHistoricos no inicializado tras dispatch');
                }
            } else {
                $htmlConTabla = $respAjax['body'];
            }

            // Enviar búsqueda con rango desde 2011 para obtener el listado completo de ficheros
            if ($htmlConTabla !== null) {
                try {
                    $searchHtml = $this->enviarBusquedaRestauraciones($htmlConTabla, $baseUrl, $cookieFile, $contexto);
                    if ($searchHtml !== null) {
                        $htmlConTabla = $searchHtml;
                    }
                } catch (\Throwable $e) {
                    \Log::warning("{$contexto}: no se pudo enviar búsqueda de restauraciones, se usan datos sin filtrar", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($mb === null) {
                throw new Exception('No se encontró el patrón de tamaño BD en la respuesta de GestionRestauraciones');
            }

            return ['mb' => $mb, 'html' => $htmlConTabla];
        } finally {
            // Logout siempre (best-effort): CECOCO sólo permite UNA sesión por usuario,
            // así que si dejamos la sesión abierta el próximo run se traba con
            // "Usuario en sesión" hasta que el server haga timeout (≈30 min).
            $this->logoutCecoco($cookieFile, $baseUrl);
            @unlink($cookieFile);
        }
    }

    private function extraerTamanoBaseRestauracionesDesdeHtml(string $html): ?float
    {
        if (!preg_match('/EL\s+TAMA[ÑN]O\s+DE\s+LA\s+BASE\s+DE\s+DATOS\s+DE\s+RESTAURACIONES\s+ES\s+([\d.,]+)\s*MB/iu', $html, $m)) {
            return null;
        }

        // CECOCO usa formato europeo: punto = miles, coma = decimales (ej. "3.977 MB" = 3977 MB).
        $valor = str_replace('.', '', $m[1]);
        $valor = str_replace(',', '.', $valor);

        return (float) $valor;
    }

    /**
     * Envía el formulario de búsqueda de GestionRestauraciones con rango desde 2011
     * y devuelve el HTML resultante. Usa el ViewState extraído de la página actual.
     */
    private function enviarBusquedaRestauraciones(string $pageHtml, string $baseUrl, string $cookieFile, string $contexto): ?string
    {
        if (!preg_match('/<form[^>]*id="GeneralForm"[^>]*>.*?<input[^>]*name="javax\\.faces\\.ViewState"[^>]*value="([^"]+)"[^>]*\/?>/si', $pageHtml, $m)) {
            \Log::warning("{$contexto}: no se encontró ViewState en GeneralForm");
            return null;
        }
        $viewState = html_entity_decode($m[1]);

        $body = 'GeneralForm%3AfechaInicio=' . rawurlencode('01/01/2011 00:00:00')
            . '&GeneralForm%3AfechaFin=' . rawurlencode('31/12/2099 23:59:59')
            . '&GeneralForm%3AtipoFicheroExtraccion=1'
            . '&GeneralForm%3AbotonBusqueda=Buscar'
            . '&autoScroll='
            . '&GeneralForm_SUBMIT=1'
            . '&GeneralForm%3A_idcl='
            . '&GeneralForm%3A_link_hidden_='
            . '&javax.faces.ViewState=' . rawurlencode($viewState);

        $resp = $this->curlRequest(
            $baseUrl . '/app/historicos/gestion/GestionRestauraciones.faces',
            'POST',
            [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-419,es;q=0.9',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: ' . parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST)
                    . (parse_url($baseUrl, PHP_URL_PORT) ? ':' . parse_url($baseUrl, PHP_URL_PORT) : ''),
                'Referer: ' . $baseUrl . '/app/historicos/gestion/GestionRestauraciones.faces',
                'Upgrade-Insecure-Requests: 1',
            ],
            $body,
            $cookieFile
        );

        if (stripos($resp['body'], 'LoginForm:Usuario') !== false) {
            throw new Exception("{$contexto}: búsqueda de restauraciones redirigió al login");
        }

        return $resp['body'];
    }

    /**
     * Extrae del HTML del listado de GestionRestauraciones los ficheros con estado "Restaurada".
     *
     * @return array{nombre_fichero: string, fecha_inicio: string, fecha_fin: string, localizacion: string}[]
     */
    private function extraerFicherosRestauradosDesdeHtml(string $html): array
    {
        $files = [];

        $regex = '/<div\s+id="ArrayFilasTablaScroll"[^>]*>.*?<script[^>]*>.*?ficherosExtraccion\.push\(\'([^\']+)\'\).*?<\/script>(.*?)<\/div>(?:\s*<!--\s*Fila\s*-->)?/si';
        preg_match_all($regex, $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $filename = $m[1];
            $innerHtml = $m[2];

            preg_match_all('/<span[^>]*title=\'([^\']*)\'[^>]*>/', $innerHtml, $spans);
            $titles = $spans[1] ?? [];

            if (count($titles) >= 5) {
                $fechaInicio   = $titles[0];
                $fechaFin      = $titles[1];
                $localizacion  = $titles[2];
                $estado        = $titles[3];
                $nombreFichero = $titles[4];

                if (mb_strtolower(trim($estado)) === 'restaurada') {
                    $files[] = [
                        'nombre_fichero' => html_entity_decode($nombreFichero, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'fecha_inicio'   => html_entity_decode($fechaInicio, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'fecha_fin'      => html_entity_decode($fechaFin, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'localizacion'   => html_entity_decode($localizacion, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    ];
                }
            }
        }

        return $files;
    }

    private function obtenerPerfilLoginCecoco(string $baseUrl, string $usuario, string $password, string $cookieFile): ?string
    {
        $resp = $this->curlRequest(
            $baseUrl . '/ajax/perfil/AjaxServletPerfil',
            'POST',
            ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'],
            'LoginForm%3AUsuario=' . rawurlencode($usuario) . '&LoginForm%3APassword=' . rawurlencode($password),
            $cookieFile
        );

        if (!preg_match_all('/<PERFIL\s+perfil="([^"]+)"/i', $resp['body'], $m) || empty($m[1])) {
            return null;
        }

        foreach ($m[1] as $perfil) {
            if (strcasecmp($perfil, 'Coordinador Despacho') === 0) {
                return $perfil;
            }
        }

        return html_entity_decode($m[1][0], ENT_QUOTES, 'UTF-8');
    }

    private function resolverUrlAbsolutaConBase(string $src, string $baseUrl): string
    {
        if (preg_match('/^https?:\/\//i', $src)) {
            return $src;
        }

        $parts = parse_url(rtrim($baseUrl, '/'));
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (strpos($src, '/') === 0) {
            return $scheme . '://' . $host . $port . $src;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($src, '/');
    }

    private function baseUrlDesdeLoginUrl(string $loginUrl): string
    {
        $pos = stripos($loginUrl, '/app/login/');

        return $pos === false ? rtrim($loginUrl, '/') : substr($loginUrl, 0, $pos);
    }

    /**
     * Cierra la sesión de CECOCO usando el JSESSIONID del cookie jar.
     * Best-effort: si falla no hace nada (la sesión va a timeoutear en el server igual).
     */
    private function logoutCecoco(string $cookieFile, ?string $baseUrl = null): void
    {
        $baseUrl = $baseUrl ?: $this->baseUrl;
        try {
            $this->curlRequest(
                $baseUrl . '/app/login/Logout.jsp',
                'GET',
                ['Referer: ' . $baseUrl . '/app/inicio/Menu.faces'],
                null,
                $cookieFile
            );
        } catch (\Throwable $e) {
            Log::info('Logout CECOCO falló (best-effort)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper de cURL con cookie jar persistente y headers personalizados.
     * Necesario porque Laravel\Http no respeta el orden de campos del body
     * y MyFaces rechaza el POST de login si el orden o encoding cambian.
     */
    private function curlRequest(string $url, string $method, array $headers = [], ?string $body = null, ?string $cookieFile = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Dashboard CAR911 - CECOCO Monitor)',
        ]);
        if ($cookieFile !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL falló contra CECOCO: ' . $err);
        }
        $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status'  => $code,
            'headers' => substr($resp, 0, $hsize),
            'body'    => substr($resp, $hsize),
        ];
    }
}
