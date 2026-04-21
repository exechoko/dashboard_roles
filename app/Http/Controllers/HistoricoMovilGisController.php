<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovilProcesado;
use App\Services\CecocoGisService;
use App\Services\GeocodificacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Consulta el GIS viewer de CECOCO para extraer el histórico de posiciones
 * de un móvil en un rango de fechas, sin necesidad de importar un Excel.
 *
 * La tabla resultante replica la del módulo "Procesar Histórico Móvil"
 * (carga de Excel). Se guarda en la misma tabla historico_movil_procesados
 * marcando el nombre_archivo con prefijo "[GIS]" para diferenciar el origen.
 */
class HistoricoMovilGisController extends Controller
{
    private const ORIGEN_PREFIJO = '[GIS] ';

    public function __construct()
    {
        $this->middleware('permission:ver-historico-movil-gis-cecoco');
    }

    public function index()
    {
        return view('cecoco.historico_movil_gis.index');
    }

    /**
     * Busca recursos en el GIS que matcheen (parcialmente) el texto ingresado.
     * Usado por el autocompletado del form antes de consultar el histórico.
     */
    public function buscarRecurso(Request $request, CecocoGisService $gis)
    {
        $validated = $request->validate([
            'q'            => 'required|string|min:1|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        try {
            $items = $gis->buscarRecursos(
                $validated['q'],
                Carbon::parse($validated['fecha_inicio']),
                Carbon::parse($validated['fecha_fin'])
            );
        } catch (\Exception $e) {
            Log::error('Error buscando recursos GIS', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['items' => $items]);
    }

    public function buscarHistorial(Request $request)
    {
        $busqueda  = $request->input('q', '');
        $desde     = $request->input('desde', '');
        $hasta     = $request->input('hasta', '');
        $porPagina = 12;

        $query = HistoricoMovilProcesado::with('user')
            ->where('nombre_archivo', 'like', self::ORIGEN_PREFIJO . '%')
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
            'items'         => $paginado->map(fn($h) => [
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
            'total'         => $paginado->total(),
            'pagina_actual' => $paginado->currentPage(),
            'ultima_pagina' => $paginado->lastPage(),
        ]);
    }

    /**
     * Consulta GIS, geocodifica coordenadas y construye la tabla de registros.
     */
    public function consultar(Request $request, CecocoGisService $gis, GeocodificacionService $geo)
    {
        // La consulta puede demorar: auth GIS + POST histórico + N reverse-geocoding.
        // Un día completo con buena muestra puede tener 500-1000 puntos; subimos el límite.
        @set_time_limit(300);

        $validated = $request->validate([
            'recurso'          => 'required|string|max:50',
            'fecha_inicio'     => 'required|date',
            'fecha_fin'        => 'required|date|after_or_equal:fecha_inicio',
            'velocidad_maxima' => 'nullable|numeric|min:0',
            'umbral_naranja'   => 'nullable|integer|min:1',
            'umbral_rojo'      => 'nullable|integer|min:1',
        ]);

        $velocidadMaxima = $request->filled('velocidad_maxima') ? (float) $request->velocidad_maxima : 45;
        $umbralNaranja   = $request->filled('umbral_naranja')   ? (int) $request->umbral_naranja * 60 : 1800;
        $umbralRojo      = $request->filled('umbral_rojo')      ? (int) $request->umbral_rojo * 60    : 2700;

        try {
            $fechaInicio = Carbon::parse($validated['fecha_inicio']);
            $fechaFin    = Carbon::parse($validated['fecha_fin']);

            $resultadoGis = $gis->obtenerHistoricoMovil(
                trim($validated['recurso']),
                $fechaInicio,
                $fechaFin
            );
        } catch (\Exception $e) {
            Log::error('Error consultando GIS histórico móvil', [
                'error'   => $e->getMessage(),
                'recurso' => $request->recurso,
            ]);
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        $posiciones = $resultadoGis['posiciones'] ?? [];
        $registros  = $this->construirRegistros($posiciones, $geo, $velocidadMaxima, $umbralNaranja, $umbralRojo);

        $metadata = [
            'recurso'      => $resultadoGis['recurso'] ?? trim($validated['recurso']),
            'fecha_inicio' => $fechaInicio->format('d/m/Y H:i:s'),
            'fecha_fin'    => $fechaFin->format('d/m/Y H:i:s'),
            'posiciones'   => count($registros),
        ];

        $historial = HistoricoMovilProcesado::create([
            'user_id'          => Auth::id(),
            'nombre_archivo'   => self::ORIGEN_PREFIJO . $metadata['recurso'] . ' ' . $fechaInicio->format('YmdHi') . '-' . $fechaFin->format('YmdHi'),
            'recurso'          => $metadata['recurso'],
            'fecha_inicio'     => $metadata['fecha_inicio'],
            'fecha_fin'        => $metadata['fecha_fin'],
            'posiciones'       => count($registros),
            'velocidad_maxima' => $velocidadMaxima,
            'umbral_naranja'   => $umbralNaranja / 60,
            'umbral_rojo'      => $umbralRojo / 60,
            'metadata'         => $metadata,
            'registros_json'   => json_encode($registros),
        ]);

        return response()->json([
            'historial_id'     => $historial->id,
            'metadata'         => $metadata,
            'registros'        => $registros,
            'velocidad_maxima' => $velocidadMaxima,
            'umbral_naranja'   => $umbralNaranja / 60,
            'umbral_rojo'      => $umbralRojo / 60,
            'errores'          => [],
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
        $sheet->setCellValue('A4', 'Origen:');
        $sheet->setCellValue('B4', 'GIS viewer CECOCO');
        if ($velocidadMaxima > 0) {
            $sheet->setCellValue('A5', 'Vel. máx. configurada:');
            $sheet->setCellValue('B5', $velocidadMaxima . ' km/h');
        }

        $sheet->setCellValue('D2', "Detenido < {$umbralNaranja} min");
        $sheet->getStyle('D2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->setCellValue('D3', "Detenido {$umbralNaranja}-{$umbralRojo} min");
        $sheet->getStyle('D3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFA500');
        $sheet->setCellValue('D4', "Detenido > {$umbralRojo} min");
        $sheet->getStyle('D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');

        $headerRow = 7;
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

        $filename = 'historico_movil_gis_' . now()->format('YmdHis') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Construye los registros normalizados (con geocodificación inversa, estado
     * detenido/movimiento, tiempo detenido acumulado y exceso de velocidad)
     * a partir de las posiciones crudas del GIS.
     *
     * @param array<int,array{fecha:\Carbon\Carbon,lat:float,lng:float,velocidad:float,direccion:?string}> $posiciones
     */
    private function construirRegistros(
        array $posiciones,
        GeocodificacionService $geo,
        float $velocidadMaxima,
        int $umbralNaranja,
        int $umbralRojo
    ): array {
        $registros             = [];
        $estaDetenido          = false;
        $tiempoInicio          = null;
        $indiceInicioDetencion = null;

        // ── Batch geocoding: deduplica coordenadas y resuelve en paralelo ──
        // Evita N requests secuenciales a Google Maps (crítico bajo túneles
        // Cloudflare con timeout de 100s).
        $paresCoords = [];
        foreach ($posiciones as $pos) {
            $lat = isset($pos['lat']) ? (float) $pos['lat'] : null;
            $lng = isset($pos['lng']) ? (float) $pos['lng'] : null;
            if ($lat !== null && $lng !== null && empty($pos['direccion'])) {
                $paresCoords[] = [$lat, $lng];
            }
        }
        $direcciones = !empty($paresCoords) ? $geo->reverseGeocodeBatch($paresCoords) : [];
        Log::info('CECOCO GIS: batch reverse-geocode', [
            'pares_input'   => count($paresCoords),
            'pares_unicos'  => count($direcciones),
        ]);

        foreach ($posiciones as $i => $pos) {
            $fecha     = $pos['fecha'] instanceof Carbon ? $pos['fecha'] : Carbon::parse($pos['fecha']);
            $lat       = isset($pos['lat']) ? (float) $pos['lat'] : null;
            $lng       = isset($pos['lng']) ? (float) $pos['lng'] : null;
            $velocidad = (float) ($pos['velocidad'] ?? 0);

            $direccion = $pos['direccion'] ?? null;
            if (!$direccion && $lat !== null && $lng !== null) {
                $clave = sprintf('%.5f,%.5f', $lat, $lng);
                $direccion = $direcciones[$clave] ?? '';
            }

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
                    $estaDetenido   = false;
                    $segundos       = $fecha->diffInSeconds($tiempoInicio);
                    $ultimoDetenido = count($registros) - 1;
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
                'id'                => $i + 1,
                'fecha'             => $fecha->format('d/m/Y H:i:s'),
                'velocidad'         => $velocidad,
                'direccion'         => $direccion,
                'lat'               => $lat,
                'lng'               => $lng,
                'enlace'            => $enlace,
                'estado'            => $estado,
                'color_estado'      => $colorEstado,
                'tiempo_detenido'   => null,
                'color_tiempo'      => null,
                'segundos_detenido' => null,
                'exceso_velocidad'  => $excesoVelocidad,
            ];
        }

        // Si quedó detenido al final, calcular hasta la última posición registrada
        if ($estaDetenido && !empty($registros) && !empty($posiciones)) {
            $ultimaPos   = end($posiciones);
            $ultimaFecha = $ultimaPos['fecha'] instanceof Carbon ? $ultimaPos['fecha'] : Carbon::parse($ultimaPos['fecha']);
            $segundos    = $ultimaFecha->diffInSeconds($tiempoInicio);
            $ultimo      = count($registros) - 1;
            $registros[$ultimo]['tiempo_detenido']   = $this->formatearTiempo($segundos);
            $registros[$ultimo]['color_tiempo']      = $this->colorPorTiempo($segundos, $umbralNaranja, $umbralRojo);
            $registros[$ultimo]['segundos_detenido'] = $segundos;
        }

        return $registros;
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
