<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuscarLlamadaCentralTelefonicaRequest;
use App\Http\Requests\ImportarLlamadasCentralTelefonicaRequest;
use App\Http\Requests\ReporteLlamadasCentralTelefonicaRequest;
use App\Models\ImportacionLlamadaCentralTelefonica;
use App\Models\LlamadaCentralTelefonica;
use App\Services\CentralTelefonicaSincronizacionService;
use App\Services\LlamadaCentralTelefonicaImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LlamadaCentralTelefonicaController extends Controller
{
    public function index()
    {
        $periodos = LlamadaCentralTelefonica::query()
            ->select('periodo')
            ->distinct()
            ->orderByDesc('periodo')
            ->pluck('periodo');

        return view('cecoco.llamadas-central-telefonica.index', compact('periodos'));
    }

    public function buscar(BuscarLlamadaCentralTelefonicaRequest $request)
    {
        $validated = $request->validated();
        $numero = $validated['numero'] ?? null;

        $resultados = collect();

        if (!empty($numero)) {
            $query = LlamadaCentralTelefonica::query()->numero($numero);

            if (!empty($validated['desde']) || !empty($validated['hasta'])) {
                $desde = $validated['desde'] ?? $validated['hasta'];
                $hasta = $validated['hasta'] ?? $validated['desde'];
                $query->entreFechas($desde, $hasta);
            }

            if (!empty($validated['tipo'])) {
                $query->where('tipo_llamada', $validated['tipo']);
            }

            $resultados = $query->orderByDesc('calldate')->paginate(25)->withQueryString();
        }

        return view('cecoco.llamadas-central-telefonica.buscar', [
            'resultados' => $resultados,
            'numero' => $numero,
            'desde' => $validated['desde'] ?? null,
            'hasta' => $validated['hasta'] ?? null,
            'tipo' => $validated['tipo'] ?? null,
        ]);
    }

    public function importarForm()
    {
        $importaciones = ImportacionLlamadaCentralTelefonica::with('usuario')
            ->orderByDesc('created_at')
            ->simplePaginate(20);

        return view('cecoco.llamadas-central-telefonica.importar', compact('importaciones'));
    }

    public function importarProcesar(ImportarLlamadasCentralTelefonicaRequest $request, LlamadaCentralTelefonicaImportService $service): RedirectResponse
    {
        $archivos = $request->file('archivos');
        $resumen = [];

        foreach ($archivos as $archivo) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $inicio = microtime(true);

            try {
                $resultado = $service->importarArchivo($archivo->getRealPath(), $nombreOriginal);

                ImportacionLlamadaCentralTelefonica::create([
                    'nombre_archivo' => $nombreOriginal,
                    'total_registros' => $resultado['total'] + $resultado['omitidos'],
                    'registros_importados' => $resultado['total'],
                    'registros_omitidos' => $resultado['omitidos'],
                    'estado' => 'completado',
                    'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
                    'usuario_id' => $request->user()->id,
                ]);

                $resumen[] = "{$nombreOriginal}: {$resultado['total']} importadas" . ($resultado['omitidos'] > 0 ? ", {$resultado['omitidos']} omitidas" : '');
            } catch (\Throwable $e) {
                ImportacionLlamadaCentralTelefonica::create([
                    'nombre_archivo' => $nombreOriginal,
                    'estado' => 'error',
                    'error_mensaje' => $e->getMessage(),
                    'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
                    'usuario_id' => $request->user()->id,
                ]);

                $resumen[] = "{$nombreOriginal}: error - {$e->getMessage()}";
            }
        }

        return redirect()
            ->route('cecoco.llamadas-central-telefonica.importar')
            ->with('success', implode(' | ', $resumen));
    }

    public function importarHoy(CentralTelefonicaSincronizacionService $service): RedirectResponse
    {
        $desde = Carbon::today();
        $hasta = Carbon::now();
        $inicio = microtime(true);

        try {
            $resultado = $service->sincronizar($desde, $hasta);
        } catch (\Throwable $e) {
            ImportacionLlamadaCentralTelefonica::create([
                'nombre_archivo' => 'hoy_' . $desde->format('Y_m_d'),
                'estado' => 'error',
                'error_mensaje' => $e->getMessage(),
                'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
                'usuario_id' => auth()->id(),
            ]);

            return redirect()
                ->route('cecoco.llamadas-central-telefonica.importar')
                ->with('success', 'Error al traer las llamadas de hoy: ' . $e->getMessage());
        }

        ImportacionLlamadaCentralTelefonica::create([
            'nombre_archivo' => $resultado['archivo'],
            'total_registros' => $resultado['total'] + $resultado['omitidos'],
            'registros_importados' => $resultado['total'],
            'registros_omitidos' => $resultado['omitidos'],
            'estado' => 'completado',
            'tiempo_procesamiento' => (int) round((microtime(true) - $inicio) * 1000),
            'usuario_id' => auth()->id(),
        ]);

        return redirect()
            ->route('cecoco.llamadas-central-telefonica.importar')
            ->with('success', "Llamadas de hoy actualizadas: {$resultado['total']} procesadas" . ($resultado['omitidos'] > 0 ? ", {$resultado['omitidos']} omitidas" : '') . '.');
    }

    public function datos(ReporteLlamadasCentralTelefonicaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        [$desde, $hasta] = $this->resolverRango($validated);

        $base = LlamadaCentralTelefonica::query()->whereBetween('calldate', [$desde, $hasta]);

        $recibidas = (clone $base)->recibidas();
        $totalRecibidas = (clone $recibidas)->count();
        $atendidas = (clone $recibidas)->atendidas()->count();
        $descartadas = (clone $recibidas)->descartadas()->count();
        $salientes = (clone $base)->salientes()->count();
        $internas = (clone $base)->internas()->count();
        $otras = (clone $base)->where('tipo_llamada', LlamadaCentralTelefonica::TIPO_OTRA)->count();

        $tiempos = (clone $recibidas)->atendidas()
            ->select(
                DB::raw('AVG(bill_duration) as promedio_atencion'),
                DB::raw('MAX(bill_duration) as maximo_atencion'),
                DB::raw('AVG(GREATEST(duration - bill_duration, 0)) as promedio_espera')
            )
            ->first();

        $porDiaRaw = (clone $recibidas)
            ->select(
                DB::raw('DATE(calldate) as fecha'),
                DB::raw('COUNT(*) as recibidas'),
                DB::raw('SUM(CASE WHEN atendida = 1 THEN 1 ELSE 0 END) as atendidas'),
                DB::raw('SUM(CASE WHEN atendida = 0 THEN 1 ELSE 0 END) as descartadas')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $porDia = $porDiaRaw->map(fn ($fila) => [
            'fecha' => $fila->fecha,
            'recibidas' => (int) $fila->recibidas,
            'atendidas' => (int) $fila->atendidas,
            'descartadas' => (int) $fila->descartadas,
        ])->values();

        $porHoraRaw = (clone $recibidas)
            ->select(DB::raw('HOUR(calldate) as hora'), DB::raw('COUNT(*) as total'))
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->keyBy('hora');

        $porHora = [];
        for ($h = 0; $h < 24; $h++) {
            $porHora[$h] = $porHoraRaw->has($h) ? (int) $porHoraRaw[$h]->total : 0;
        }

        $topDestinosSalientes = (clone $base)->salientes()
            ->select('final_dnis', DB::raw('COUNT(*) as total'))
            ->whereNotNull('final_dnis')
            ->groupBy('final_dnis')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => ['numero' => $fila->final_dnis, 'total' => (int) $fila->total])
            ->values();

        return response()->json([
            'desde' => $desde,
            'hasta' => $hasta,
            'total_recibidas' => $totalRecibidas,
            'atendidas' => $atendidas,
            'descartadas' => $descartadas,
            'tasa_atencion' => $totalRecibidas > 0 ? round($atendidas / $totalRecibidas * 100, 1) : 0,
            'salientes' => $salientes,
            'internas' => $internas,
            'otras' => $otras,
            'tiempo_atencion_promedio' => round((float) $tiempos->promedio_atencion, 0),
            'tiempo_atencion_maximo' => (int) $tiempos->maximo_atencion,
            'tiempo_espera_promedio' => round((float) $tiempos->promedio_espera, 0),
            'por_dia' => $porDia,
            'por_hora' => $porHora,
            'top_destinos_salientes' => $topDestinosSalientes,
        ]);
    }

    public function exportarDocx(): BinaryFileResponse
    {
        $porMes = LlamadaCentralTelefonica::query()
            ->select(
                'periodo',
                DB::raw("SUM(CASE WHEN tipo_llamada = 'recibida' THEN 1 ELSE 0 END) as recibidas"),
                DB::raw("SUM(CASE WHEN tipo_llamada = 'recibida' AND atendida = 1 THEN 1 ELSE 0 END) as atendidas"),
                DB::raw("SUM(CASE WHEN tipo_llamada = 'recibida' AND atendida = 0 THEN 1 ELSE 0 END) as descartadas"),
                DB::raw("AVG(CASE WHEN tipo_llamada = 'recibida' AND atendida = 1 THEN bill_duration ELSE NULL END) as tiempo_atencion_promedio"),
                DB::raw("SUM(CASE WHEN tipo_llamada = 'saliente' THEN 1 ELSE 0 END) as salientes"),
                DB::raw("SUM(CASE WHEN tipo_llamada = 'interna' THEN 1 ELSE 0 END) as internas")
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 850, 'marginBottom' => 850,
            'marginLeft' => 850, 'marginRight' => 850,
            'orientation' => 'landscape',
        ]);

        $section->addText('Llamadas al 911 - CeCoCo', ['bold' => true, 'size' => 14]);
        $section->addText(
            'Discriminado por mes - Generado el ' . now()->locale('es')->isoFormat('DD/MM/YYYY HH:mm'),
            ['size' => 9, 'italic' => true]
        );
        $section->addTextBreak(1);

        $tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMarginLeft' => 80, 'cellMarginRight' => 80];
        $phpWord->addTableStyle('tablaLlamadas', $tableStyle);
        $table = $section->addTable('tablaLlamadas');

        $encabezados = [
            'Mes', 'Recibidas', 'Atendidas', 'Descartadas', '% Atención',
            'Tiempo prom. atención', 'Salientes', 'Internas',
        ];

        $table->addRow(400);
        foreach ($encabezados as $h) {
            $table->addCell(1500, ['bgColor' => 'D9D9D9'])
                ->addText($h, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        }

        $totales = ['recibidas' => 0, 'atendidas' => 0, 'descartadas' => 0, 'salientes' => 0, 'internas' => 0];
        $sumaTiempos = 0;
        $mesesConAtencion = 0;

        foreach ($porMes as $fila) {
            $recibidas = (int) $fila->recibidas;
            $atendidas = (int) $fila->atendidas;
            $descartadas = (int) $fila->descartadas;
            $tasa = $recibidas > 0 ? round($atendidas / $recibidas * 100, 1) : 0;
            $tiempoProm = $fila->tiempo_atencion_promedio !== null ? (float) $fila->tiempo_atencion_promedio : null;

            $mesLabel = Carbon::createFromFormat('Y-m', $fila->periodo)->translatedFormat('F Y');
            $mesLabel = ucfirst($mesLabel);

            $table->addRow(300);
            $table->addCell(1500)->addText($mesLabel, ['size' => 9], ['alignment' => 'left']);
            $table->addCell(1500)->addText(number_format($recibidas, 0, ',', '.'), ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText(number_format($atendidas, 0, ',', '.'), ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText(number_format($descartadas, 0, ',', '.'), ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText($tasa . '%', ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText($tiempoProm !== null ? $this->segundosATexto($tiempoProm) : 's/d', ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText(number_format((int) $fila->salientes, 0, ',', '.'), ['size' => 9], ['alignment' => 'center']);
            $table->addCell(1500)->addText(number_format((int) $fila->internas, 0, ',', '.'), ['size' => 9], ['alignment' => 'center']);

            $totales['recibidas'] += $recibidas;
            $totales['atendidas'] += $atendidas;
            $totales['descartadas'] += $descartadas;
            $totales['salientes'] += (int) $fila->salientes;
            $totales['internas'] += (int) $fila->internas;

            if ($tiempoProm !== null) {
                $sumaTiempos += $tiempoProm;
                $mesesConAtencion++;
            }
        }

        $tasaTotal = $totales['recibidas'] > 0 ? round($totales['atendidas'] / $totales['recibidas'] * 100, 1) : 0;
        $tiempoPromTotal = $mesesConAtencion > 0 ? $sumaTiempos / $mesesConAtencion : null;

        $table->addRow(300);
        $table->addCell(1500)->addText('TOTAL', ['bold' => true, 'size' => 9], ['alignment' => 'left']);
        $table->addCell(1500)->addText(number_format($totales['recibidas'], 0, ',', '.'), ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText(number_format($totales['atendidas'], 0, ',', '.'), ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText(number_format($totales['descartadas'], 0, ',', '.'), ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText($tasaTotal . '%', ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText($tiempoPromTotal !== null ? $this->segundosATexto($tiempoPromTotal) : 's/d', ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText(number_format($totales['salientes'], 0, ',', '.'), ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        $table->addCell(1500)->addText(number_format($totales['internas'], 0, ',', '.'), ['bold' => true, 'size' => 9], ['alignment' => 'center']);

        $section->addTextBreak(1);
        $section->addText(
            'Recibidas: llamadas del público que ingresaron a CeCoCo. Salientes: llamadas de CeCoCo hacia afuera (despacho, avisos). '
            . 'Internas: entre extensiones (rango 5000-5999). No incluye expedientes, tipificación ni tiempos de despacho/arribo/resolución.',
            ['size' => 8, 'italic' => true],
            ['alignment' => 'both']
        );

        $filename = 'Llamadas_911_CeCoCo_por_mes_' . now()->format('Ymd_His') . '.docx';
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    private function segundosATexto(float $segundos): string
    {
        $segundos = (int) round($segundos);
        $minutos = intdiv($segundos, 60);
        $resto = $segundos % 60;

        return $minutos . ':' . str_pad((string) $resto, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, string|null> $validated
     * @return array{0: string, 1: string}
     */
    private function resolverRango(array $validated): array
    {
        if (!empty($validated['periodo'])) {
            $inicio = Carbon::createFromFormat('Y-m', $validated['periodo'])->startOfMonth();

            return [$inicio->format('Y-m-d H:i:s'), $inicio->copy()->endOfMonth()->format('Y-m-d H:i:s')];
        }

        if (!empty($validated['desde']) || !empty($validated['hasta'])) {
            $desde = $validated['desde'] ?? $validated['hasta'];
            $hasta = $validated['hasta'] ?? $validated['desde'];

            return [$desde . ' 00:00:00', $hasta . ' 23:59:59'];
        }

        $ultimoPeriodo = LlamadaCentralTelefonica::query()->max('periodo');

        if ($ultimoPeriodo === null) {
            $inicio = now()->startOfMonth();

            return [$inicio->format('Y-m-d H:i:s'), now()->endOfMonth()->format('Y-m-d H:i:s')];
        }

        $inicio = Carbon::createFromFormat('Y-m', $ultimoPeriodo)->startOfMonth();

        return [$inicio->format('Y-m-d H:i:s'), $inicio->copy()->endOfMonth()->format('Y-m-d H:i:s')];
    }
}
