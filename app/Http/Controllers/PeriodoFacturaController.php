<?php

namespace App\Http\Controllers;

use App\Imports\Incidencias911Import;
use App\Models\Incidencia911;
use App\Models\PeriodoFactura;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class PeriodoFacturaController extends Controller
{
    // ─── Períodos ─────────────────────────────────────────────────────────────

    public function index()
    {
        $periodos = PeriodoFactura::orderByDesc('numero')->paginate(20);
        return view('incidencias.index', compact('periodos'));
    }

    public function create()
    {
        $siguienteNumero = (PeriodoFactura::max('numero') ?? 0) + 1;
        return view('incidencias.create', compact('siguienteNumero'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero'               => 'required|integer|min:1|unique:periodos_factura,numero',
            'fecha_inicio'         => 'required|date',
            'fecha_fin'            => 'required|date|after_or_equal:fecha_inicio',
            'n_total_tetra'        => 'nullable|integer|min:0',
            'n_total_camaras'      => 'nullable|integer|min:0',
            'n_total_puestos_cecoco'=> 'nullable|integer|min:0',
            'factura_numero'       => 'nullable|string|max:50',
            'factura_monto'        => 'nullable|numeric|min:0',
            'expediente_numero'    => 'nullable|string|max:50',
            'ru_numero'            => 'nullable|string|max:50',
            'observaciones'        => 'nullable|string',
        ]);

        [$validated['dias'], $validated['minutos_totales']] = $this->calcularTiempos(
            $validated['fecha_inicio'], $validated['fecha_fin']
        );

        $periodo = PeriodoFactura::create($validated);

        return redirect()->route('incidencias.periodos.show', $periodo->id)
            ->with('success', "Período {$periodo->label} creado exitosamente.");
    }

    public function show($id)
    {
        $periodo   = PeriodoFactura::with('incidencias')->findOrFail($id);
        $analisis  = $periodo->analisis();

        // Incidencias separadas por tipo para la vista
        $persistentes = $periodo->incidencias->where('tipo_incidencia', 'persistente');
        $transitorias = $periodo->incidencias->whereIn('tipo_incidencia', ['transitoria', 'manual']);

        // Detalle de cálculo por sistema (para verificar datos)
        $T = $periodo->minutos_totales;
        $detalleCalculo = [];
        foreach ($periodo->incidencias as $inc) {
            $sis = $inc->sistema ?: 'Sin sistema';
            if (!isset($detalleCalculo[$sis])) {
                $detalleCalculo[$sis] = ['aplica' => [], 'excluidas' => []];
            }
            $indisp = ($T > 0 && $inc->n_total_unidades > 0)
                ? ($inc->n_unidades_afectadas / $inc->n_total_unidades) * ($inc->minutos_fallo / $T) * 100
                : 0;
            $entry = [
                'code'        => $inc->incidencia_code,
                'tipo'        => $inc->tipo_incidencia,
                'modulo_n2'   => $inc->modulo_n2,
                'pond_n2'     => $inc->ponderacion_n2,
                'n_afect'     => $inc->n_unidades_afectadas,
                'n_total'     => $inc->n_total_unidades,
                'min_fallo'   => $inc->minutos_fallo,
                'indisp'      => round($indisp, 5),
                'deficiencia' => round($indisp * 2, 5),
            ];
            if ($inc->aplica_calculo) {
                $detalleCalculo[$sis]['aplica'][] = $entry;
            } else {
                $detalleCalculo[$sis]['excluidas'][] = $entry;
            }
        }

        // Datos para el gráfico
        $chartLabels = [];
        $chartData   = [];
        foreach ($analisis['por_sistema'] as $sis => $datos) {
            $chartLabels[] = $sis;
            $chartData[]   = round($datos['deficiencia_n1'], 5);
        }

        return view('incidencias.show', compact(
            'periodo', 'analisis', 'persistentes', 'transitorias',
            'chartLabels', 'chartData', 'detalleCalculo'
        ));
    }

    public function edit($id)
    {
        $periodo = PeriodoFactura::findOrFail($id);
        return view('incidencias.edit', compact('periodo'));
    }

    public function update(Request $request, $id)
    {
        $periodo   = PeriodoFactura::findOrFail($id);
        $validated = $request->validate([
            'numero'               => "required|integer|min:1|unique:periodos_factura,numero,{$id}",
            'fecha_inicio'         => 'required|date',
            'fecha_fin'            => 'required|date|after_or_equal:fecha_inicio',
            'n_total_tetra'        => 'nullable|integer|min:0',
            'n_total_camaras'      => 'nullable|integer|min:0',
            'n_total_puestos_cecoco'=> 'nullable|integer|min:0',
            'factura_numero'       => 'nullable|string|max:50',
            'factura_monto'        => 'nullable|numeric|min:0',
            'expediente_numero'    => 'nullable|string|max:50',
            'ru_numero'            => 'nullable|string|max:50',
            'observaciones'        => 'nullable|string',
        ]);

        [$validated['dias'], $validated['minutos_totales']] = $this->calcularTiempos(
            $validated['fecha_inicio'], $validated['fecha_fin']
        );

        $periodo->update($validated);

        return redirect()->route('incidencias.periodos.show', $periodo->id)
            ->with('success', "Período {$periodo->label} actualizado.");
    }

    public function destroy($id)
    {
        PeriodoFactura::findOrFail($id)->delete();
        return redirect()->route('incidencias.periodos.index')
            ->with('success', 'Período eliminado.');
    }

    // ─── Importar Excel ───────────────────────────────────────────────────────

    public function importarForm($periodoId)
    {
        $periodo = PeriodoFactura::findOrFail($periodoId);
        return view('incidencias.importar', compact('periodo'));
    }

    public function importar(Request $request, $periodoId)
    {
        $periodo = PeriodoFactura::findOrFail($periodoId);
        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xlsm,xls|max:20480',
            'hoja'     => 'required|in:patagonia,preventivos,telecom,ute,persistentes_tabla',
            'limpiar'  => 'nullable|boolean',
        ]);

        if ($request->boolean('limpiar')) {
            if ($request->hoja === 'persistentes_tabla') {
                // Solo eliminar las persistentes arrastradas, no todas
                $periodo->incidencias()->where('tipo_incidencia', 'persistente')->delete();
            } else {
                $periodo->incidencias()->delete();
            }
        }

        // Palabras clave para encontrar la hoja correcta (búsqueda parcial, sin importar mayúsculas)
        $hojaKeywords = [
            'patagonia'          => 'patagonia',
            'preventivos'        => 'preventivos',
            'telecom'            => 'telecom',
            'ute'                => 'u.t.e',
            'persistentes_tabla' => 'persistentes',
        ];
        $keyword = $hojaKeywords[$request->hoja] ?? $request->hoja;

        try {
            // Leer solo los nombres de hojas del archivo (sin cargar celdas)
            $filePath   = $request->file('archivo')->getPathname();
            $reader     = SpreadsheetIOFactory::createReaderForFile($filePath);
            $sheetNames = $reader->listWorksheetNames($filePath);

            // Buscar coincidencia parcial case-insensitive
            $nombreHoja = collect($sheetNames)->first(
                fn($n) => str_contains(strtolower($n), explode('.', $keyword)[0])
            );

            if (!$nombreHoja) {
                $disponibles = implode(', ', $sheetNames);
                return back()->with('error',
                    "No se encontró la hoja \"{$request->hoja}\" en el archivo. "
                    . "Hojas disponibles: {$disponibles}"
                );
            }

            $import = new Incidencias911Import(
                $periodo->id, $periodo->numero,
                $request->hoja,
                $periodo->n_total_tetra, $periodo->n_total_camaras,
                $periodo->fecha_fin,
                $nombreHoja,
                $periodo->minutos_totales
            );
            Excel::import($import, $request->file('archivo'));

            $sufijo = $import->omitidosNoAplica > 0
                ? " ({$import->omitidosNoAplica} sin aplica multa)"
                : '';

            $msg = "Importación completa ({$nombreHoja}): {$import->importados} importadas "
                 . "({$import->transitorias} transitorias, {$import->persistentes} persistentes)"
                 . $sufijo . '.';

            return redirect()->route('incidencias.periodos.show', $periodo->id)
                ->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    // ─── Arrastrar persistentes del período anterior ─────────────────────────

    public function arrastarPersistentes(Request $request, $periodoId)
    {
        $periodo = PeriodoFactura::findOrFail($periodoId);

        $anterior = PeriodoFactura::where('numero', $periodo->numero - 1)->first();
        if (!$anterior) {
            return back()->with('error', "No existe el período anterior (P" . ($periodo->numero - 1) . ").");
        }

        // Persistentes del período anterior que aplican al cálculo
        $fuente = $anterior->incidencias()
            ->where('tipo_incidencia', 'persistente')
            ->where('aplica_calculo', true)
            ->get();

        if ($fuente->isEmpty()) {
            return back()->with('error', "El período {$anterior->label} no tiene incidencias persistentes registradas.");
        }

        $creados  = 0;
        $omitidos = 0;

        foreach ($fuente as $inc) {
            // No duplicar si ya existe en este período
            if ($periodo->incidencias()->where('incidencia_code', $inc->incidencia_code)->exists()) {
                $omitidos++;
                continue;
            }

            Incidencia911::create([
                'periodo_id'           => $periodo->id,
                'tipo_incidencia'      => 'persistente',
                'hoja_origen'          => 'arrastrado',
                'incidencia_code'      => $inc->incidencia_code,
                'tickets'              => $inc->tickets,
                // Fecha inicio = comienzo del nuevo período (el equipo sigue caído)
                'fecha_inicio_falla'   => $periodo->fecha_inicio,
                // Minutos = período completo
                'minutos_fallo'        => $periodo->minutos_totales,
                'n_unidades_afectadas' => $inc->n_unidades_afectadas,
                'n_total_unidades'     => $inc->n_total_unidades,
                'sistema'              => $inc->sistema,
                'modulo_n2'            => $inc->modulo_n2,
                'ponderacion_n2'       => $inc->ponderacion_n2,
                'ponderacion_n1'       => $inc->ponderacion_n1,
                'prioridad'            => $inc->prioridad,
                'aplica_calculo'       => true,
                'estado'               => $inc->estado,
                'observaciones'        => $inc->observaciones,
            ]);
            $creados++;
        }

        $msg = "Se arrastraron {$creados} incidencia(s) persistente(s) del {$anterior->label}.";
        if ($omitidos) {
            $msg .= " {$omitidos} ya existían en este período.";
        }

        return back()->with('success', $msg);
    }

    // ─── Incidencias (CRUD dentro de un período) ──────────────────────────────

    public function incidenciaCreate($periodoId)
    {
        $periodo = PeriodoFactura::findOrFail($periodoId);
        return view('incidencias.incidencia_form', compact('periodo'));
    }

    public function incidenciaStore(Request $request, $periodoId)
    {
        $periodo             = PeriodoFactura::findOrFail($periodoId);
        $validated           = $this->validarIncidencia($request);
        $validated['periodo_id'] = $periodo->id;
        Incidencia911::create($validated);

        return redirect()->route('incidencias.periodos.show', $periodo->id)
            ->with('success', 'Incidencia agregada.');
    }

    public function incidenciaEdit($periodoId, $incidenciaId)
    {
        $periodo    = PeriodoFactura::findOrFail($periodoId);
        $incidencia = Incidencia911::where('periodo_id', $periodo->id)->findOrFail($incidenciaId);
        return view('incidencias.incidencia_form', compact('periodo', 'incidencia'));
    }

    public function incidenciaUpdate(Request $request, $periodoId, $incidenciaId)
    {
        $periodo    = PeriodoFactura::findOrFail($periodoId);
        $incidencia = Incidencia911::where('periodo_id', $periodo->id)->findOrFail($incidenciaId);
        $incidencia->update($this->validarIncidencia($request));

        return redirect()->route('incidencias.periodos.show', $periodo->id)
            ->with('success', 'Incidencia actualizada.');
    }

    public function incidenciaDestroy($periodoId, $incidenciaId)
    {
        $periodo    = PeriodoFactura::findOrFail($periodoId);
        Incidencia911::where('periodo_id', $periodo->id)->findOrFail($incidenciaId)->delete();

        return redirect()->route('incidencias.periodos.show', $periodo->id)
            ->with('success', 'Incidencia eliminada.');
    }

    // ─── API helpers ──────────────────────────────────────────────────────────

    /** Devuelve los pesos N1/N2 para un sistema+módulo dados */
    public function apiPonderacion(Request $request)
    {
        $sistema  = $request->get('sistema', '');
        $moduloN2 = $request->get('modulo_n2', '');
        return response()->json(Incidencia911::ponderacionPara($sistema, $moduloN2));
    }

    // ─── Generación de documentos Word ───────────────────────────────────────

    public function generarInforme($id)
    {
        $periodo  = PeriodoFactura::with('incidencias')->findOrFail($id);
        $analisis = $periodo->analisis();

        $phpWord  = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $section  = $phpWord->addSection([
            'marginTop' => 1134, 'marginBottom' => 1134,
            'marginLeft' => 1701, 'marginRight' => 1701,
        ]);

        // ── Encabezado ──
        $section->addText(
            'Paraná,   ' . now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            ['bold' => false, 'size' => 11],
            ['alignment' => 'right']
        );
        $section->addTextBreak(1);
        $section->addText('SEÑOR DIRECTOR GENERAL', ['bold' => true, 'size' => 11]);
        $section->addText('OPERACIONES Y SEGURIDAD PÚBLICA', ['bold' => true]);
        $section->addTextBreak(1);
        $section->addText('S / D', ['bold' => true]);
        $section->addTextBreak(1);

        // ── Contexto ──
        $fechaIni     = $periodo->fecha_inicio->locale('es')->isoFormat('DD/MM/YYYY');
        $fechaFin     = $periodo->fecha_fin->locale('es')->isoFormat('DD/MM/YYYY');
        $dias         = $periodo->dias;
        $pNumero      = $periodo->numero;
        $pLabel       = $periodo->label;
        $facturaNum   = $periodo->factura_numero ?? '00002-00000000';
        $facturaMonto = $periodo->factura_monto
            ? number_format((float)$periodo->factura_monto, 2, ',', '.') : '0,00';
        $expediente   = $periodo->expediente_numero ?? '';
        $ru           = $periodo->ru_numero ?? '';

        $textSection = $phpWord->addParagraphStyle('normal', ['spaceAfter' => 120]);

        $contextoParagrafo = "En el marco de la Licitación Pública Nº 067/2020 – \"SISTEMA INTEGRAL DE SEGURIDAD PUBLICA 911\", Expediente Nº 2426804-, los Pliegos de Condiciones Generales y Particulares, Anexos y Contrato de Prestación de Servicio suscripto el 1 de Octubre de 2021; el Acta de Inicio de Actividades suscripta entre Patagonia Green S.A. y el señor Jefe de Policía de la Provincia, de fecha 18 de Febrero de 2022; el Decreto Nº 820 MGJ de fecha 13 de Abril de 2.022, por el cual se aprueba la misma; y lo dispuesto por la Resolución DAG Nº 02 del 07 de Marzo de 2023; los integrantes de esta Comisión Técnica Especial, cumplimos en dirigirnos a usted, y por su intermedio a quien corresponda, conforme a lo previsto en la cláusula Quinta del ya citado Contrato de Prestación de Servicios, remitiéndole el presente informe, conforme se detalla, certificando la prestación de los servicios por parte de LA ADJUDICATARIA; particularmente, en relación al periodo Nº {$pNumero} comprendido entre el día {$fechaIni} y el {$fechaFin} ({$dias} días), Factura B Nº {$facturaNum} por la suma de (\${$facturaMonto}), expediente Nº {$expediente}, R.U. Nº {$ru}; a saber: Equipamientos de Comunicación Tetra para Móviles policiales – HT de mano – Bases.";

        $section->addText($contextoParagrafo, ['size' => 11], ['alignment' => 'both', 'spaceAfter' => 200]);
        $section->addTextBreak(1);

        // ── Equipos persistentes ──
        $persistentes = $periodo->incidencias->where('tipo_incidencia', 'persistente')->where('aplica_calculo', true);
        if ($persistentes->count() > 0) {
            $section->addText(
                'Detalle de Equipos/Componentes con fallas persistentes en su funcionamiento, que no fueron reparados, ni reemplazados durante todos los días del periodo analizado, '
                . $dias . ' días (' . $fechaIni . ' y el ' . $fechaFin . '):',
                ['bold' => true, 'size' => 11],
                ['alignment' => 'both', 'spaceAfter' => 120]
            );
            foreach ($persistentes as $inc) {
                $section->addText(
                    '• ' . ($inc->observaciones ?? $inc->incidencia_code ?? '(sin descripción)'),
                    ['size' => 11],
                    ['alignment' => 'both', 'spaceAfter' => 80]
                );
            }
            $section->addTextBreak(1);
        }

        // ── Tabla resumen por módulos N1 ──
        $section->addText(
            'De acuerdo con los parámetros definidos en la "Tabla de Ponderación para cálculos porcentuales de la indisponibilidad y de la deficiencia funcional del Sistema", apartado 7 del ANEXO V - ESPECIFICACIONES GENERALES del pliego de la Licitación Pública Nº 67/2020, se verifica lo siguiente:',
            ['size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 200]
        );

        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMarginLeft' => 60, 'cellMarginRight' => 60];
        $phpWord->addTableStyle('tablaResumen', $tableStyle);
        $table = $section->addTable('tablaResumen');

        // Encabezado tabla
        $table->addRow(500);
        foreach (['Módulos de Primer Nivel', '% Def. Primer Nivel', 'Ponderación', 'Def. Total Sistema'] as $h) {
            $cell = $table->addCell(2268, ['bgColor' => 'D9D9D9']);
            $cell->addText($h, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        }

        $allSystems = array_keys(Incidencia911::MODULOS);
        foreach ($allSystems as $sis) {
            $datos    = $analisis['por_sistema'][$sis] ?? ['deficiencia_n1' => 0, 'ponderacion_n1' => Incidencia911::MODULOS[$sis]['n1_peso'], 'contrib_total' => 0];
            $defN1    = number_format($datos['deficiencia_n1'], 5) . '%';
            $pond     = $datos['ponderacion_n1'] . '%';
            $contrib  = number_format($datos['contrib_total'], 5) . '%';

            $table->addRow(360);
            $table->addCell(2268)->addText($sis, ['size' => 9], ['alignment' => 'left']);
            $table->addCell(2268)->addText($defN1, ['size' => 9], ['alignment' => 'center']);
            $table->addCell(2268)->addText($pond, ['size' => 9], ['alignment' => 'center']);
            $table->addCell(2268)->addText($contrib, ['size' => 9], ['alignment' => 'center']);
        }

        // Fila total
        $table->addRow(360);
        $table->addCell(6804, ['gridSpan' => 3])->addText('Deficiencia de funcionamiento del Sistema Completo', ['bold' => true, 'size' => 9], ['alignment' => 'right']);
        $table->addCell(2268)->addText(number_format($analisis['total_ponderado'], 5) . '%', ['bold' => true, 'size' => 9], ['alignment' => 'center']);

        $section->addTextBreak(1);

        // ── Conclusión ──
        if (!$analisis['aplica_multa']) {
            $section->addText(
                'De acuerdo al resultado del análisis expresado ut-supra, esta comisión llega a la conclusión, salvo más elevado criterio y consideración, que durante el periodo analizado, no corresponde la aplicación de la cláusula OCTAVA: MULTA, en lo referido a indisponibilidad y deficiencia funcional de los Sistemas de la División 911 y Video Vigilancia, esto se fundamenta en el hecho de que ningún subsistema analizado (Módulo de Primer Nivel) supera el umbral máximo del (2%) y la sumatoria ponderada del Sistema Completo se encuentra por debajo del límite del (1,5%), criterios estipulados en el apartado 8, "Indisponibilidades del Sistema por Módulos y Sub Módulos" del ANEXO V - ESPECIFICACIONES GENERALES del Pliego de Especificaciones Técnicas.',
                ['size' => 11],
                ['alignment' => 'both', 'spaceAfter' => 200]
            );
        } else {
            $montoMulta = $periodo->montoMulta();
            $section->addText(
                'De acuerdo al resultado del análisis expresado ut-supra, esta comisión concluye que CORRESPONDE la aplicación de la cláusula OCTAVA: MULTA sobre la factura Nº ' . $facturaNum . ' por la suma de $' . number_format($montoMulta, 2, ',', '.') . '. Motivo: ' . $analisis['motivo_multa'],
                ['size' => 11, 'bold' => true],
                ['alignment' => 'both', 'spaceAfter' => 200]
            );
        }

        $section->addText(
            'En virtud de lo previsto en la cláusula Quinta del Contrato de Prestación de Servicios, y conforme a los parámetros establecidos en el ANEXO V – ESPECIFICACIONES GENERALES del Pliego de Especificaciones Técnicas, esta Comisión certifica que la prestación de los servicios por parte de LA ADJUDICATARIA, durante el período analizado, ha sido conforme y satisfactoria para la Policía de Entre Ríos. Se adjunta la tabla de cálculos utilizada para el periodo de incumbencia.',
            ['size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 200]
        );

        $section->addTextBreak(2);

        // ── Firmas ──
        $section->addText('Asesor Jurídico         Asesor Tecnológico         Asesor Operativo', ['size' => 11], ['alignment' => 'center']);

        // ── Guardar y descargar ──
        $filename = "Informe_ComEspecial_{$pLabel}.docx";
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    public function generarRecibo($id)
    {
        $periodo  = PeriodoFactura::findOrFail($id);
        $analisis = $periodo->analisis();

        $phpWord  = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);
        $section  = $phpWord->addSection([
            'marginTop' => 1134, 'marginBottom' => 1134,
            'marginLeft' => 1701, 'marginRight' => 1701,
        ]);

        $pLabel       = $periodo->label;
        $pNumero      = $periodo->numero;
        $fechaIni     = $periodo->fecha_inicio->locale('es')->isoFormat('DD/MM/YYYY');
        $fechaFin     = $periodo->fecha_fin->locale('es')->isoFormat('DD/MM/YYYY');
        $dias         = $periodo->dias;
        $facturaNum   = $periodo->factura_numero ?? '';
        $facturaMonto = $periodo->factura_monto
            ? number_format((float)$periodo->factura_monto, 2, ',', '.') : '0,00';
        $expediente   = $periodo->expediente_numero ?? '';
        $ru           = $periodo->ru_numero ?? '';
        $defTotal     = number_format($analisis['total_ponderado'], 5);
        $montoMulta   = number_format($periodo->montoMulta(), 2, ',', '.');
        $anio         = now()->year;

        // ── MENSAJES PARA JEFE ──
        $section->addText('Mensajes para Jefe:', ['bold' => true, 'size' => 12]);
        $section->addTextBreak(1);

        if ($analisis['aplica_multa']) {
            $section->addText('Con Multa', ['bold' => true, 'underline' => 'single']);
            $section->addText(
                "En virtud del análisis efectuado sobre la Prestación de Servicios brindada por la Empresa Patagonia S.A. durante al periodo Nº {$pNumero} comprendido entre los días {$fechaIni} y el {$fechaFin} ({$dias} días), se ha establecido una sanción de multa del {$defTotal}%, que asciende a (\${$montoMulta}). sobre el precio de la Factura B Nº {$facturaNum} por la suma de (\${$facturaMonto}), expediente Nº {$expediente}, R.U. Nº {$ru}.-",
                ['size' => 11],
                ['alignment' => 'both', 'spaceAfter' => 200]
            );
        } else {
            $section->addText('Sin Multa:', ['bold' => true, 'underline' => 'single']);
            $section->addText(
                "En virtud del análisis efectuado sobre la Prestación de Servicios brindada por la Empresa Patagonia Green S.A. durante al periodo Nº {$pNumero} comprendido entre los días {$fechaIni} y el {$fechaFin} ({$dias} días), se determina que no corresponde la aplicación de la cláusula OCTAVA: MULTA, en el marco de la Factura B Nº {$facturaNum} por la suma de (\${$facturaMonto}), vinculada al expediente Nº {$expediente}, R.U. Nº {$ru}.-",
                ['size' => 11],
                ['alignment' => 'both', 'spaceAfter' => 200]
            );
        }

        $section->addTextBreak(1);
        $section->addText('Mensaje para Crio. Inspector Leandro Juárez:', ['bold' => true]);
        $section->addText("Buen día, Leandro. ¿Cómo estás? Ya tenemos firmado el informe del período {$pNumero}. Avísame si hoy estás en tu oficina y me acerco para que lo firmes.", ['size' => 11]);
        $section->addTextBreak(1);
        $section->addText('Hola Leandro, ya se hizo el pase del expediente a través del Sistema Web', ['size' => 11]);
        $section->addTextBreak(1);

        $section->addText('Mensaje para Secretaría:', ['bold' => true]);
        $section->addText('Buen día, avísenme por favor cuando salgan a llevar correspondencia, tengo que llevar el informe de facturación de Patagonia Green', ['size' => 11]);
        $section->addTextBreak(1);
        $section->addText(
            "Buen día, Podrían por favor hacer el pase del expediente Nº {$expediente}, R.U. Nº {$ru} (facturación de Patagonia Green S.A. Periodo {$pNumero}), a la División de Asuntos Jurídicos dependiente de la Jefatura de Policía de Provincia. El mismo fue entregado al Crio. Inspector Juárez Leandro Javier, adjuntando informe de la Comisión Técnica Especial, en un cómputo de 13 fojas útiles.",
            ['size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 200]
        );
        $section->addTextBreak(2);

        // ── RECIBO ──
        $section->addText('RECIBO DE ENTREGA', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $section->addText('----------', ['size' => 11], ['alignment' => 'center']);
        $section->addTextBreak(1);
        $section->addText(
            "En la ciudad de Paraná, capital de la provincia de Entre Ríos, a los ____ días del mes de ____________ del año {$anio}, siendo las ______ horas, se hace entrega al Crio. Inspector Juárez Leandro Javier, numerario de la División de Asuntos Jurídicos dependiente de la Jefatura De Policía de Provincia, Expediente Nº {$expediente}, R.U. Nº {$ru}, en ref: \"MANTENIMIENTO Y SOPORTE INTEGRAL DEL SERVICIO DE EMERGENCIAS 911 Y CONECTIVIDAD POR FIBRA OPTICA correspondiente al periodo Nº {$pNumero} comprendido entre el {$fechaIni} al {$fechaFin} Factura B Nº {$facturaNum}, por la suma de (\${$facturaMonto}) emitida por la empresa PATAGONIA GREEN S.A.\", adjuntando informe realizado por la Comisión Técnica Especial respecto a la prestación del Servicio de la adjudicataria, de acuerdo a la cláusula Quinta del Contrato en vigencia en la Licitación Pública Nº67/2020, en un cómputo de (       ) Fojas.",
            ['size' => 11],
            ['alignment' => 'both']
        );
        $section->addText('----------', ['size' => 11], ['alignment' => 'center']);
        $section->addText('Firmando al pie para constancia y de conformidad.', ['size' => 11], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $section->addText("-----------------------------          -----------------------------", ['size' => 11], ['alignment' => 'center']);
        $section->addText("        RECIBE                                          ENTREGA", ['size' => 11], ['alignment' => 'center']);

        $filename = "Mensajes_Recibo_{$pLabel}.docx";
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    // ─── Privados ─────────────────────────────────────────────────────────────

    private function calcularTiempos(string $inicio, string $fin): array
    {
        $i    = Carbon::parse($inicio);
        $f    = Carbon::parse($fin);
        $dias = $i->diffInDays($f) + 1;
        return [$dias, $dias * 24 * 60];
    }

    private function validarIncidencia(Request $request): array
    {
        $data = $request->validate([
            'tipo_incidencia'      => 'required|in:persistente,transitoria,manual',
            'hoja_origen'          => 'required|in:preventivos,patagonia,telecom,ute,manual',
            'incidencia_code'      => 'nullable|string|max:60',
            'tickets'              => 'nullable|string',
            'fecha_inicio_falla'   => 'nullable|date',
            'minutos_fallo'        => 'required|numeric|min:0',
            'n_unidades_afectadas' => 'required|integer|min:1',
            'n_total_unidades'     => 'required|integer|min:1',
            'sistema'              => 'required|string|max:100',
            'modulo_n2'            => 'required|string|max:100',
            'ponderacion_n2'       => 'required|numeric|min:0|max:100',
            'ponderacion_n1'       => 'required|numeric|min:0|max:100',
            'prioridad'            => 'required|in:critico,alto,medio,bajo',
            'aplica_calculo'       => 'nullable',
            'estado'               => 'nullable|string|max:50',
            'observaciones'        => 'nullable|string',
        ]);
        $data['aplica_calculo'] = $request->boolean('aplica_calculo', true);
        return $data;
    }
}
