<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CecocoExpedienteService
{
    private string $pythonPath;
    private string $scriptPath;
    private string $outputPath;

    public function __construct()
    {
        $this->pythonPath = config('cecoco.python_path', 'python');
        $this->scriptPath = config('cecoco.script_expediente_path', 'F:\Scripts_Eventos\scrapcoco_expediente.py');
        $this->outputPath = config('cecoco.output_path', 'F:\Scripts_Eventos');
    }

    public function obtenerDetalleExpediente(string $nroExpediente): array
    {
        $archivoExpediente = $this->outputPath . DIRECTORY_SEPARATOR . "expediente_{$nroExpediente}.xls";

        if (file_exists($archivoExpediente)) {
            @unlink($archivoExpediente);
        }

        $resultado = $this->ejecutarScriptPython($nroExpediente);

        if ($resultado['status'] === 'RESTORE_REQUIRED') {
            throw new Exception('El expediente requiere restauración desde backup. Contacte al administrador del sistema CECOCO.');
        }

        if ($resultado['status'] !== 'OK') {
            throw new Exception('Error al obtener el expediente: ' . ($resultado['error'] ?? 'Error desconocido'));
        }

        if (!file_exists($archivoExpediente)) {
            throw new Exception('El archivo del expediente no fue generado correctamente.');
        }

        $detalle = $this->parsearArchivoExpediente($archivoExpediente);

        @unlink($archivoExpediente);

        return $detalle;
    }

    private function ejecutarScriptPython(string $nroExpediente): array
    {
        $comando = sprintf(
            '%s %s %s 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->scriptPath),
            escapeshellarg($nroExpediente)
        );

        $output = [];
        $returnVar = 0;

        exec($comando, $output, $returnVar);

        $outputText = implode("\n", $output);

        Log::info('Ejecución script Python expediente', [
            'expediente' => $nroExpediente,
            'comando' => $comando,
            'return_code' => $returnVar,
            'output' => $outputText
        ]);

        if (str_contains($outputText, 'RESTORE_REQUIRED')) {
            return ['status' => 'RESTORE_REQUIRED'];
        }

        if (str_contains($outputText, 'OK')) {
            return ['status' => 'OK'];
        }

        return [
            'status' => 'ERROR',
            'error' => $outputText
        ];
    }

    private function parsearArchivoExpediente(string $rutaArchivo): array
    {
        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $sheet = $spreadsheet->getActiveSheet();
            $filas = $sheet->toArray();

            $expedienteInfo = [
                'nro_expediente' => null,
                'fecha_inicio' => null,
                'fecha_cierre' => null,
                'tipo_servicio' => null,
                'direccion' => null,
                'telefono' => null,
                'operador_inicial' => null,
                'descripcion_inicial' => null,
                'timeline' => []
            ];

            $indiceCabecera = null;
            $mapa = [];

            foreach ($filas as $indice => $fila) {
                $filaTexto = strtolower(implode(' ', array_filter($fila)));

                if (str_contains($filaTexto, 'fecha') && str_contains($filaTexto, 'hora')) {
                    $indiceCabecera = $indice;
                    $mapa = $this->mapearColumnasExpediente($fila);
                    break;
                }
            }

            if ($indiceCabecera === null) {
                throw new Exception('No se encontró la cabecera en el archivo del expediente');
            }

            for ($i = $indiceCabecera + 1; $i < count($filas); $i++) {
                $fila = $filas[$i];

                if (empty(array_filter($fila))) {
                    continue;
                }

                $evento = $this->extraerEventoTimeline($fila, $mapa);

                if ($evento) {
                    $expedienteInfo['timeline'][] = $evento;

                    if ($expedienteInfo['nro_expediente'] === null && !empty($evento['nro_expediente'])) {
                        $expedienteInfo['nro_expediente'] = $evento['nro_expediente'];
                    }

                    if ($expedienteInfo['fecha_inicio'] === null && !empty($evento['fecha_hora'])) {
                        $expedienteInfo['fecha_inicio'] = $evento['fecha_hora'];
                        $expedienteInfo['tipo_servicio'] = $evento['tipo_servicio'] ?? null;
                        $expedienteInfo['direccion'] = $evento['direccion'] ?? null;
                        $expedienteInfo['telefono'] = $evento['telefono'] ?? null;
                        $expedienteInfo['operador_inicial'] = $evento['operador'] ?? null;
                        $expedienteInfo['descripcion_inicial'] = $evento['descripcion'] ?? null;
                    }

                    if (!empty($evento['fecha_hora'])) {
                        $expedienteInfo['fecha_cierre'] = $evento['fecha_hora'];
                    }
                }
            }

            usort($expedienteInfo['timeline'], function ($a, $b) {
                return strcmp($a['fecha_hora'] ?? '', $b['fecha_hora'] ?? '');
            });

            return $expedienteInfo;

        } catch (Exception $e) {
            Log::error('Error parseando archivo expediente', [
                'archivo' => $rutaArchivo,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Error al procesar el archivo del expediente: ' . $e->getMessage());
        }
    }

    private function mapearColumnasExpediente(array $cabecera): array
    {
        $mapa = [];
        $cabeceraLower = array_map(fn($c) => strtolower(trim($c ?? '')), $cabecera);

        $mapeos = [
            'nro_expediente' => ['nº exp', 'n° exp', 'nro. exp', 'expediente', 'nro exp'],
            'fecha_hora' => ['fecha', 'fecha y hora', 'fecha/hora'],
            'hora' => ['hora'],
            'operador' => ['operador', 'usuario'],
            'descripcion' => ['descripción', 'descripcion', 'detalle', 'observaciones'],
            'tipo_servicio' => ['tipo servicio', 'tipo de servicio', 'servicio'],
            'direccion' => ['dirección', 'direccion', 'domicilio'],
            'telefono' => ['tel.', 'teléfono', 'telefono', 'tel'],
            'estado' => ['estado'],
            'recurso' => ['recurso', 'móvil', 'movil'],
        ];

        foreach ($mapeos as $campo => $variantes) {
            foreach ($cabeceraLower as $indice => $columna) {
                foreach ($variantes as $variante) {
                    if (str_contains($columna, $variante)) {
                        $mapa[$campo] = $indice;
                        break 2;
                    }
                }
            }
        }

        return $mapa;
    }

    private function extraerEventoTimeline(array $fila, array $mapa): ?array
    {
        $get = fn($campo) => isset($mapa[$campo]) ? trim($fila[$mapa[$campo]] ?? '') : '';

        $fechaHora = $get('fecha_hora');
        $hora = $get('hora');

        if (empty($fechaHora) && empty($hora)) {
            return null;
        }

        if (!empty($fechaHora) && !empty($hora) && !str_contains($fechaHora, ':')) {
            $fechaHora = $fechaHora . ' ' . $hora;
        }

        return [
            'nro_expediente' => $get('nro_expediente'),
            'fecha_hora' => $fechaHora,
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

        if (!file_exists($this->scriptPath)) {
            $errores[] = "Script Python no encontrado en: {$this->scriptPath}";
        }

        if (!is_dir($this->outputPath)) {
            $errores[] = "Directorio de salida no existe: {$this->outputPath}";
        }

        if (!is_writable($this->outputPath)) {
            $errores[] = "Directorio de salida no tiene permisos de escritura: {$this->outputPath}";
        }

        exec($this->pythonPath . ' --version 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $errores[] = "Python no está disponible o no se puede ejecutar: {$this->pythonPath}";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'configuracion' => [
                'python_path' => $this->pythonPath,
                'script_path' => $this->scriptPath,
                'output_path' => $this->outputPath,
            ]
        ];
    }
}
