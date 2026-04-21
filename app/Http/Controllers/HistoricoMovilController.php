<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovilProcesado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;

class HistoricoMovilController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-historico-movil-cecoco');
    }

    public function index()
    {
        return view('cecoco.historico_movil.index');
    }

    public function buscarHistorial(Request $request)
    {
        $busqueda  = $request->input('q', '');
        $desde     = $request->input('desde', '');   // fecha procesamiento desde (Y-m-d)
        $hasta     = $request->input('hasta', '');   // fecha procesamiento hasta (Y-m-d)
        $porPagina = 12;

        $query = HistoricoMovilProcesado::with('user')
            ->orderByDesc('created_at');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('recurso', 'like', "%{$busqueda}%")
                  ->orWhere('nombre_archivo', 'like', "%{$busqueda}%");
            });
        }

        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }

        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        $paginado = $query->paginate($porPagina);

        return response()->json([
            'items'        => $paginado->map(fn($h) => [
                'id'               => $h->id,
                'nombre_archivo'   => $h->nombre_archivo,
                'recurso'          => $h->recurso,
                'fecha_inicio'     => $h->fecha_inicio,
                'fecha_fin'        => $h->fecha_fin,
                'posiciones'       => $h->posiciones,
                'velocidad_maxima' => $h->velocidad_maxima,
                'umbral_naranja'   => $h->umbral_naranja,
                'umbral_rojo'      => $h->umbral_rojo,
                'procesado_por'    => $h->user?->name ?? 'N/D',
                'procesado_el'     => $h->created_at->format('d/m/Y H:i'),
            ]),
            'total'        => $paginado->total(),
            'pagina_actual' => $paginado->currentPage(),
            'ultima_pagina' => $paginado->lastPage(),
        ]);
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'archivo'          => 'required|file',
            'velocidad_maxima' => 'nullable|numeric|min:0',
            'umbral_naranja'   => 'nullable|integer|min:1',
            'umbral_rojo'      => 'nullable|integer|min:1',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo.',
        ]);

        $extension = strtolower($request->file('archivo')->getClientOriginalExtension());
        if (!in_array($extension, ['xls', 'xlsx'])) {
            return response()->json([
                'message' => 'El archivo debe ser .xls o .xlsx.',
                'errors'  => ['archivo' => ['El archivo debe ser .xls o .xlsx.']],
            ], 422);
        }

        $velocidadMaxima = $request->filled('velocidad_maxima') ? (float) $request->velocidad_maxima : 45;
        $umbralNaranja   = $request->filled('umbral_naranja')   ? (int) $request->umbral_naranja * 60 : 1800;
        $umbralRojo      = $request->filled('umbral_rojo')      ? (int) $request->umbral_rojo * 60    : 2700;

        $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray();

        $metadata    = $this->extraerMetadata($rows);
        $filaInicio  = 10;
        $filas       = count($rows);
        $registros   = [];
        $estaDetenido          = false;
        $tiempoInicio          = null;
        $indiceInicioDetencion = null;
        $errores               = [];

        for ($i = $filaInicio; $i < $filas; $i++) {
            $fila          = $rows[$i];
            $id            = $fila[0] ?? null;
            $fechaRaw      = $fila[1] ?? '';
            $velocidadRaw  = trim(str_replace('Km/h', '', $fila[2] ?? ''));
            $direccion     = $fila[3] ?? '';
            $coordenadasRaw = $fila[4] ?? '';

            if (empty($coordenadasRaw) || !is_numeric($velocidadRaw)) {
                $errores[] = "Datos inválidos en fila " . ($i + 1);
                continue;
            }

            if (str_contains((string) $fechaRaw, '.0')) {
                $fechaRaw = substr($fechaRaw, 0, strpos($fechaRaw, '.0'));
            }

            try {
                $fecha = \Carbon\Carbon::parse($fechaRaw);
            } catch (\Exception $e) {
                $errores[] = "Error en la fecha de la fila " . ($i + 1);
                continue;
            }

            [$lat, $lng] = $this->parsearCoordenadas($coordenadasRaw);
            $velocidad   = (float) $velocidadRaw;
            $colorEstado = null;
            $excesoVelocidad = false;

            if ($velocidad == 0) {
                if (!$estaDetenido) {
                    $estaDetenido          = true;
                    $tiempoInicio          = $fecha;
                    $indiceInicioDetencion = count($registros);
                }
                $estado      = 'Detenido';
                $colorEstado = 'detenido';
            } else {
                if ($estaDetenido) {
                    $estaDetenido    = false;
                    $segundos        = $fecha->diffInSeconds($tiempoInicio);
                    $ultimoDetenido  = count($registros) - 1;
                    if (isset($registros[$indiceInicioDetencion])) {
                        $registros[$ultimoDetenido]['tiempo_detenido']   = $this->formatearTiempo($segundos);
                        $registros[$ultimoDetenido]['color_tiempo']      = $this->colorPorTiempo($segundos, $umbralNaranja, $umbralRojo);
                        $registros[$ultimoDetenido]['segundos_detenido'] = $segundos;
                    }
                }
                $estado      = 'En movimiento';
                $colorEstado = 'movimiento';
                if ($velocidadMaxima > 0 && $velocidad > $velocidadMaxima) {
                    $excesoVelocidad = true;
                }
            }

            $enlace = ($lat !== null && $lng !== null)
                ? "https://www.google.com/maps?q={$lat},{$lng}"
                : null;

            $registros[] = [
                'id'              => $id,
                'fecha'           => $fecha->format('d/m/Y H:i:s'),
                'velocidad'       => $velocidad,
                'direccion'       => $direccion,
                'lat'             => $lat,
                'lng'             => $lng,
                'enlace'          => $enlace,
                'estado'          => $estado,
                'color_estado'    => $colorEstado,
                'tiempo_detenido' => null,
                'color_tiempo'    => null,
                'segundos_detenido' => null,
                'exceso_velocidad'  => $excesoVelocidad,
            ];
        }

        if ($estaDetenido && !empty($registros)) {
            $ultimaFecha = \Carbon\Carbon::parse($rows[$filas - 1][1] ?? 'now');
            $segundos    = $ultimaFecha->diffInSeconds($tiempoInicio);
            $ultimo      = count($registros) - 1;
            $registros[$ultimo]['tiempo_detenido']   = $this->formatearTiempo($segundos);
            $registros[$ultimo]['color_tiempo']      = $this->colorPorTiempo($segundos, $umbralNaranja, $umbralRojo);
            $registros[$ultimo]['segundos_detenido'] = $segundos;
        }

        // Persistir en historial
        $historialRecord = HistoricoMovilProcesado::create([
            'user_id'          => Auth::id(),
            'nombre_archivo'   => $request->file('archivo')->getClientOriginalName(),
            'recurso'          => $metadata['recurso'] ?? null,
            'fecha_inicio'     => $metadata['fecha_inicio'] ?? null,
            'fecha_fin'        => $metadata['fecha_fin'] ?? null,
            'posiciones'       => count($registros),
            'velocidad_maxima' => $velocidadMaxima,
            'umbral_naranja'   => $umbralNaranja / 60,
            'umbral_rojo'      => $umbralRojo / 60,
            'metadata'         => $metadata,
            'registros_json'   => json_encode($registros),
        ]);

        return response()->json([
            'historial_id'     => $historialRecord->id,
            'metadata'         => $metadata,
            'registros'        => $registros,
            'velocidad_maxima' => $velocidadMaxima,
            'umbral_naranja'   => $umbralNaranja / 60,
            'umbral_rojo'      => $umbralRojo / 60,
            'errores'          => $errores,
        ]);
    }

    public function cargarHistorial(HistoricoMovilProcesado $historial)
    {
        return response()->json([
            'historial_id'     => $historial->id,
            'metadata'         => $historial->metadata,
            'registros'        => json_decode($historial->registros_json, true),
            'velocidad_maxima' => $historial->velocidad_maxima,
            'umbral_naranja'   => $historial->umbral_naranja,
            'umbral_rojo'      => $historial->umbral_rojo,
            'errores'          => [],
        ]);
    }

    public function eliminarHistorial(HistoricoMovilProcesado $historial)
    {
        $historial->delete();
        return response()->json(['ok' => true]);
    }

    public function exportarExcel(Request $request)
    {
        $data            = json_decode($request->input('data'), true);
        $registros       = $data['registros'] ?? [];
        $metadata        = $data['metadata'] ?? [];
        $velocidadMaxima = $data['velocidad_maxima'] ?? 0;
        $umbralNaranja   = $data['umbral_naranja'] ?? 30;
        $umbralRojo      = $data['umbral_rojo'] ?? 45;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Recurso:');
        $sheet->setCellValue('B1', $metadata['recurso'] ?? '');
        $sheet->setCellValue('A2', 'Período:');
        $sheet->setCellValue('B2', ($metadata['fecha_inicio'] ?? '') . ' - ' . ($metadata['fecha_fin'] ?? ''));
        $sheet->setCellValue('A3', 'Posiciones:');
        $sheet->setCellValue('B3', $metadata['posiciones'] ?? '');
        if ($velocidadMaxima > 0) {
            $sheet->setCellValue('A4', 'Vel. máx. configurada:');
            $sheet->setCellValue('B4', $velocidadMaxima . ' km/h');
        }

        $sheet->setCellValue('D2', "Detenido < {$umbralNaranja} min");
        $sheet->getStyle('D2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->setCellValue('D3', "Detenido {$umbralNaranja}-{$umbralRojo} min");
        $sheet->getStyle('D3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFA500');
        $sheet->setCellValue('D4', "Detenido > {$umbralRojo} min");
        $sheet->getStyle('D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');

        $headerRow = 6;
        $headers   = ['#', 'Fecha', 'Velocidad (km/h)', 'Dirección', 'Mapa', 'Estado', 'Tiempo Detenido', 'Exceso Velocidad'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . $headerRow;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $row = $headerRow + 1;
        foreach ($registros as $reg) {
            $sheet->setCellValue('A' . $row, $reg['id']);
            $sheet->setCellValue('B' . $row, $reg['fecha']);
            $sheet->setCellValue('C' . $row, $reg['velocidad']);
            $sheet->setCellValue('D' . $row, $reg['direccion']);

            if (!empty($reg['enlace'])) {
                $sheet->setCellValue('E' . $row, 'Ver en Google Maps');
                $sheet->getCell('E' . $row)->setHyperlink(new Hyperlink($reg['enlace']));
                $sheet->getStyle('E' . $row)->getFont()
                    ->setColor((new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('0563C1'))
                    ->setUnderline(true);
            }

            $sheet->setCellValue('F' . $row, $reg['estado']);
            if ($reg['color_estado'] === 'detenido') {
                $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0070C0');
                $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            } else {
                $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00FF00');
            }

            if (!empty($reg['tiempo_detenido'])) {
                $sheet->setCellValue('G' . $row, $reg['tiempo_detenido']);
                $colores = ['yellow' => 'FFFF00', 'orange' => 'FFA500', 'red' => 'FF0000'];
                $hex     = $colores[$reg['color_tiempo'] ?? 'yellow'] ?? 'FFFF00';
                $sheet->getStyle('G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($hex);
            }

            if ($reg['exceso_velocidad']) {
                $sheet->setCellValue('H' . $row, 'EXCESO');
                $sheet->getStyle('H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        foreach (['A' => 5, 'B' => 22, 'C' => 16, 'D' => 35, 'E' => 20, 'F' => 16, 'G' => 30, 'H' => 16] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lastRow = $row - 1;
        if ($lastRow >= $headerRow) {
            $sheet->getStyle("A{$headerRow}:H{$lastRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        $filename = 'historico_movil_' . now()->format('YmdHis') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function extraerMetadata(array $rows): array
    {
        return [
            'recurso'      => trim(str_replace("'", '', $rows[6][1] ?? '')),
            'fecha_inicio' => $rows[4][1] ?? '',
            'fecha_fin'    => $rows[5][1] ?? '',
            'posiciones'   => $rows[7][1] ?? '',
        ];
    }

    private function parsearCoordenadas(string $coordenadas): array
    {
        if (!str_contains($coordenadas, ',')) return [null, null];

        [$longitudDMS, $latitudDMS] = array_map('trim', explode(',', $coordenadas));

        return [
            $this->dmsADecimal($latitudDMS, 'S'),
            $this->dmsADecimal($longitudDMS, 'O'),
        ];
    }

    private function dmsADecimal(string $dms, string $direccion): ?float
    {
        preg_match('/(\d+)[°]\s*(\d+)[\'\']\s*([\d.]+)["\"]?\s*([NSEO])?/u', $dms, $matches);

        if (count($matches) < 4) return null;

        $decimal = (float) $matches[1] + (float) $matches[2] / 60 + (float) $matches[3] / 3600;
        $dir     = strtoupper(trim($matches[4] ?? $direccion));

        if (in_array($dir, ['S', 'O', 'W'])) $decimal = -$decimal;

        return round($decimal, 6);
    }

    private function formatearTiempo(int $segundos): string
    {
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;
        return "{$h} hs {$m} min {$s} seg";
    }

    private function colorPorTiempo(int $segundos, int $umbralNaranja = 1800, int $umbralRojo = 2700): string
    {
        if ($segundos >= $umbralRojo)    return 'red';
        if ($segundos >= $umbralNaranja) return 'orange';
        return 'yellow';
    }
}
