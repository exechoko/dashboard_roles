<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
        usort($nodos, function($a, $b) {
            $score = function($n) {
                $name = strtolower($n->getAttribute('name') . ' ' . $n->getAttribute('id'));
                if (strpos($name, 'report') !== false) return 0;
                if (strpos($name, 'birt') !== false) return 1;
                return 2;
            };
            return $score($a) <=> $score($b);
        });

        foreach ($nodos as $node) {
            $src = trim($node->getAttribute('src'));
            if ($src === '') continue;

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
            if ($src === '') continue;
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

            // Intentar primero seleccionar la tabla 'Acciones'
            $tabla = $this->seleccionarTablaAcciones($xpath);
            if ($tabla === null) {
                // Seleccionar la mejor tabla de datos (más filas/columnas)
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

            // Detectar fila de encabezados (thead o primera con th)
            $headerTr = $xpath->query('.//thead/tr[1]', $tabla)->item(0);
            if (!$headerTr) {
                foreach ($xpath->query('.//tr', $tabla) as $trPosible) {
                    if ($xpath->query('.//th', $trPosible)->length > 0) {
                        $headerTr = $trPosible;
                        break;
                    }
                }
            }
            $encabezados = [];
            if ($headerTr) {
                foreach ($xpath->query('.//th|.//td', $headerTr) as $celda) {
                    $encabezados[] = $this->normalizarClaveColumna(trim($celda->textContent));
                }
                Log::info('Encabezados tabla seleccionada CECOCO', ['encabezados' => $encabezados]);
            }

            // Recorrer filas de datos (excluir la cabecera detectada)
            foreach ($xpath->query('.//tr', $tabla) as $fila) {
                if ($headerTr && $fila->isSameNode($headerTr)) {
                    continue;
                }
                $celdas = $xpath->query('.//td', $fila);
                if ($celdas->length === 0) {
                    continue;
                }
                $datos = [];
                $i = 0;
                foreach ($celdas as $celda) {
                    $valor = trim($celda->textContent);
                    if ($i < count($encabezados)) {
                        $clave = $encabezados[$i];
                        if ($clave !== '') {
                            $datos[$clave] = $valor;
                        }
                    }
                    $i++;
                }
                if (!empty($datos)) {
                    $eventoNorm = $this->normalizarDatosEvento($datos, $nroExpediente);
                    $timeline[] = $eventoNorm;
                }
            }
            
            if (empty($timeline)) {
                $dump = $this->tempPath . '/cecoco_reporte_' . $nroExpediente . '_' . time() . '.html';
                @file_put_contents($dump, $html);
                Log::warning('No se pudieron extraer filas de eventos de la tabla seleccionada', [
                    'dump' => $dump,
                ]);
                throw new Exception("No se encontraron eventos en el expediente");
            }
            
            // Ordenar por fecha con soporte d/m/Y
            usort($timeline, function($a, $b) {
                $ta = $this->parseFecha($a['fecha_hora'] ?? '');
                $tb = $this->parseFecha($b['fecha_hora'] ?? '');
                return $ta <=> $tb;
            });
            
            $primerEvento = $this->primerEventoConDatos($timeline);

            // Extraer resumen general (encabezados) del reporte
            $resumen = $this->extraerResumenKeyValue($xpath);
            
            Log::info('HTML parseado correctamente', ['eventos' => count($timeline)]);
            
            return [
                'nro_expediente' => $resumen['expediente'] ?? $nroExpediente,
                'fecha_hora_inicial' => $primerEvento['fecha_hora'] ?? ($resumen['fecha_inicio'] ?? ''),
                'operador_inicial' => $primerEvento['operador'] ?? ($resumen['operador'] ?? ''),
                'tipo_servicio' => $resumen['tipo'] ?? ($primerEvento['tipo_servicio'] ?? ''),
                'direccion' => $resumen['direccion'] ?? ($primerEvento['direccion'] ?? ''),
                'telefono' => $resumen['telefono'] ?? ($primerEvento['telefono'] ?? ''),
                'descripcion_inicial' => $primerEvento['descripcion'] ?? ($resumen['descripcion'] ?? ''),
                'timeline' => $timeline,
                'total_eventos' => count($timeline),
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
            $hasFecha = false; $hasHora = false;
            foreach ($enc as $h) {
                if (strpos($h, 'fecha') !== false) { $hasFecha = true; $score += 2; }
                if (strpos($h, 'hora') !== false) { $hasHora = true; $score += 1; }
                if (strpos($h, 'operador') !== false) { $score += 2; }
                if (strpos($h, 'accion') !== false) { $score += 2; }
                if (strpos($h, 'caracteristicas') !== false) { $score += 2; }
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
        }
        
        // Si no se encontró el número de expediente en los datos, usar el parámetro
        if (empty($evento['nro_expediente'])) {
            $evento['nro_expediente'] = $nroExpediente;
        }
        
        return $evento;
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
        $texto = trim((string)$texto, '_');

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

    /**
     * Extrae pares clave:valor del encabezado del reporte BIRT para completar
     * expediente, tipo, operador, fecha_inicio, direccion, telefono, descripcion.
     */
    private function extraerResumenKeyValue(\DOMXPath $xpath): array
    {
        $resumen = [];

        // Etiquetas objetivo vistas en el reporte BIRT
        $objetivos = [
            'expediente'   => ['expediente:','expediente'],
            'tipo'         => ['tipo:','tipo','tipo_servicio:','tipo_servicio'],
            'operador'     => ['operador:','operador','usuario:','usuario'],
            'fecha_inicio' => ['fecha_inicio:','fecha de creacion:','fecha de creación:','fecha de ejecucion:','fecha de ejecución:','fecha:'],
            'direccion'    => ['direccion:','dirección:','domicilio:','ubicacion:','ubicación:'],
            'telefono'     => ['telefono:','teléfono:','tel:'],
            'descripcion'  => ['descripcion:','descripción:','observaciones:','detalle:'],
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
                if ($n < 2) continue;

                for ($i = 0; $i < $n - 1; $i++) {
                    $labelText = trim($cells[$i]->textContent);
                    $labelNorm = $this->normalizarClaveColumna($labelText);
                    if ($labelNorm === '') continue;

                    foreach ($objetivos as $clave => $variantes) {
                        foreach ($variantes as $token) {
                            $tokenNorm = $this->normalizarClaveColumna($token);
                            if ($tokenNorm !== '' && strpos($labelNorm, $tokenNorm) !== false) {
                                // Tomar el valor de la próxima celda de datos si existe
                                $j = $i + 1;
                                if ($j < $n) {
                                    $valor = trim($cells[$j]->textContent);
                                    if ($valor !== '') {
                                        if (!isset($resumen[$clave]) || $resumen[$clave] === '') {
                                            $resumen[$clave] = $valor;
                                        }
                                    }
                                }
                                break 2;
                            }
                        }
                    }
                }
            }

            if (count($resumen) >= 5) break;
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
}
