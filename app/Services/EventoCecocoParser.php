<?php

namespace App\Services;

use App\Models\EventoCecoco;
use App\Models\Importacion;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class EventoCecocoParser
{
    public function procesar(UploadedFile $archivo): array
    {
        $inicio = microtime(true);

        $importacion = Importacion::create([
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'estado' => 'procesando',
        ]);

        try {
            $filas = $this->leerFilas($archivo);
            $resultado = $this->persistir($filas, $importacion);

            $tiempoProcesamiento = (int)(microtime(true) - $inicio);

            $importacion->update([
                'periodo' => $resultado['periodo'],
                'anio' => $resultado['anio'],
                'mes' => $resultado['mes'],
                'total_registros' => $resultado['total'],
                'registros_importados' => $resultado['importados'],
                'registros_duplicados' => $resultado['duplicados'],
                'registros_omitidos' => $resultado['omitidos'],
                'errores' => !empty($resultado['errores']) ? implode("\n", $resultado['errores']) : null,
                'tiempo_procesamiento' => $tiempoProcesamiento,
                'estado' => 'completado',
            ]);

            return [
                'importacion' => $importacion->fresh(),
                'errores' => $resultado['errores'],
            ];
        } catch (\Exception $e) {
            $importacion->update([
                'estado' => 'error',
                'errores' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function leerFilas(UploadedFile $archivo): array
    {
        $path = $archivo->getRealPath();
        $muestra = file_get_contents($path, false, null, 0, 200);

        if (str_starts_with($muestra, '<?xml') && str_contains($muestra, 'schemas-microsoft-com:office:spreadsheet')) {
            $xml = file_get_contents($path);
            return $this->parsearSpreadsheetML($xml);
        }

        return $this->parsearConPhpSpreadsheet($path);
    }

    private function parsearSpreadsheetML(string $xml): array
    {
        $filas = [];
        $reader = new \XMLReader();
        
        libxml_use_internal_errors(true);
        
        if (!$reader->XML($xml)) {
            throw new RuntimeException('Error al parsear XML: ' . implode(', ', libxml_get_errors()));
        }

        $namespace = 'urn:schemas-microsoft-com:office:spreadsheet';
        
        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && 
                $reader->localName === 'Row' && 
                $reader->namespaceURI === $namespace) {
                
                $rowXml = $reader->readOuterXML();
                $rowNode = new \SimpleXMLElement($rowXml);
                $rowNode->registerXPathNamespace('ss', $namespace);
                
                $fila = [];
                $cells = $rowNode->xpath('ss:Cell');
                
                foreach ($cells as $cell) {
                    $data = $cell->xpath('ss:Data');
                    $fila[] = $data ? (string)$data[0] : '';
                }
                
                $filas[] = $fila;
                unset($rowNode, $cells);
            }
        }
        
        $reader->close();
        
        return $filas;
    }

    private function parsearConPhpSpreadsheet(string $ruta): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new RuntimeException('PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($ruta);
        $sheet = $spreadsheet->getActiveSheet();
        $filas = [];

        foreach ($sheet->getRowIterator() as $row) {
            $fila = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $fila[] = $cell->getFormattedValue();
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    private function persistir(array $filas, Importacion $importacion): array
    {
        $periodo = null;
        $mes = null;
        $anio = null;
        $indiceCabecera = null;

        foreach ($filas as $indice => $fila) {
            $filaTexto = strtolower(implode(' ', $fila));

            if (str_contains($filaTexto, 'periodo') && preg_match('/(\d{2})\/(\d{4})/', implode(' ', $fila), $matches)) {
                $periodo = $matches[1] . '/' . $matches[2];
                $mes = (int)$matches[1];
                $anio = (int)$matches[2];
            }

            $markers = ['nº exp', 'n° exp', 'expediente', 'fecha'];
            $tieneMarkers = false;
            foreach ($markers as $marker) {
                if (str_contains($filaTexto, $marker)) {
                    $tieneMarkers = true;
                    break;
                }
            }

            if ($tieneMarkers) {
                $indiceCabecera = $indice;
                break;
            }
        }

        if ($indiceCabecera === null) {
            throw new RuntimeException('No se encontró la fila de cabecera en el archivo');
        }

        $mapa = $this->mapearColumnas($filas[$indiceCabecera]);

        if (!isset($mapa['nro_expediente']) || !isset($mapa['fecha_hora'])) {
            throw new RuntimeException('No se encontraron las columnas obligatorias (nro_expediente, fecha_hora)');
        }

        $expedientesArchivo = [];
        for ($i = $indiceCabecera + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            if (empty(array_filter($fila))) {
                continue;
            }

            $nroExp = trim($fila[$mapa['nro_expediente']] ?? '');
            if (!empty($nroExp)) {
                $expedientesArchivo[] = $nroExp;
            }
        }

        $expedientesArchivo = array_unique($expedientesArchivo);

        if (count($expedientesArchivo) === 0) {
            throw new RuntimeException('El archivo no contiene registros válidos');
        }

        $yaExistentes = collect();
        foreach (array_chunk($expedientesArchivo, 1000) as $chunk) {
            $yaExistentes = $yaExistentes->merge(
                EventoCecoco::whereIn('nro_expediente', $chunk)->pluck('nro_expediente')
            );
        }
        $mapaExistentes = $yaExistentes->flip()->all();

        $lote = [];
        $importados = 0;
        $duplicados = 0;
        $omitidos = 0;
        $errores = [];

        for ($i = $indiceCabecera + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];

            if (empty(array_filter($fila))) {
                continue;
            }

            $dato = $this->extraerDato($fila, $mapa, $periodo, $mes, $anio, $importacion->id);

            if (empty($dato['nro_expediente']) || $dato['fecha_hora'] === null) {
                $omitidos++;
                continue;
            }

            if (isset($mapaExistentes[$dato['nro_expediente']])) {
                $duplicados++;
                continue;
            }

            $lote[] = $dato;

            if (count($lote) >= 500) {
                EventoCecoco::insert($lote);
                $importados += count($lote);
                $lote = [];
            }
        }

        if (!empty($lote)) {
            EventoCecoco::insert($lote);
            $importados += count($lote);
        }

        return [
            'periodo' => $periodo,
            'anio' => $anio,
            'mes' => $mes,
            'total' => count($expedientesArchivo),
            'importados' => $importados,
            'duplicados' => $duplicados,
            'omitidos' => $omitidos,
            'errores' => $errores,
        ];
    }

    private function mapearColumnas(array $cabecera): array
    {
        $mapa = [];
        $cabeceraLower = array_map(fn($c) => strtolower(trim($c)), $cabecera);

        $mapeos = [
            'nro_expediente' => ['nº exp', 'n° exp', 'nro. exp', 'expediente', 'nro exp', 'numero exp'],
            'fecha_hora' => ['fecha'],
            'box' => ['box'],
            'operador' => ['operador'],
            'descripcion' => ['descripción', 'descripcion'],
            'direccion' => ['dirección', 'direccion'],
            'telefono' => ['tel.', 'teléfono', 'telefono', 'tel llamante', 'tel. llamante'],
            'fecha_cierre' => ['f. cierre', 'f.cierre', 'fecha cierre', 'cierre'],
            'tipo_servicio' => ['tipo servicio', 'tipo de servicio', 'servicio'],
        ];

        foreach ($mapeos as $campo => $variantes) {
            foreach ($cabeceraLower as $indice => $columna) {
                if ($campo === 'fecha_hora' && str_contains($columna, 'cierre')) {
                    continue;
                }

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

    private function extraerDato(array $fila, array $mapa, ?string $periodo, ?int $mes, ?int $anio, int $importacionId): array
    {
        $get = fn($campo) => isset($mapa[$campo]) ? trim($fila[$mapa[$campo]] ?? '') : '';

        return [
            'nro_expediente' => $get('nro_expediente'),
            'fecha_hora' => $this->parsearFecha($get('fecha_hora')),
            'box' => $get('box') ?: null,
            'operador' => $get('operador') ?: null,
            'descripcion' => $get('descripcion') ?: null,
            'direccion' => $get('direccion') ?: null,
            'telefono' => $get('telefono') ?: null,
            'fecha_cierre' => $this->parsearFecha($get('fecha_cierre')),
            'tipo_servicio' => $get('tipo_servicio') ?: null,
            'periodo' => $periodo,
            'mes' => $mes,
            'anio' => $anio,
            'importacion_id' => $importacionId,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    private function parsearFecha(string $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $formatos = [
            'd/m/Y H:i',
            'd/m/Y H:i:s',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formatos as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $valor);
                if ($fecha !== false) {
                    return $fecha->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($valor)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
