<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarEquipoPatrimonioCargoRequest;
use App\Http\Requests\FirmarPatrimonioCargoRequest;
use App\Models\Destino;
use App\Models\FlotaGeneral;
use App\Models\PatrimonioCargo;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class PatrimonioCargoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-patrimonio-cargos')->only(['index', 'show', 'acta', 'generarActa']);
        $this->middleware('permission:firmar-patrimonio-cargos')->only(['firmar', 'rechazar', 'agruparPendientes', 'agregarEquipo', 'quitarEquipo']);
        $this->middleware('permission:gestionar-patrimonio')->only(['dashboard']);
    }

    /**
     * Listado de cargos patrimoniales con filtros
     */
    public function index(Request $request)
    {
        $query = PatrimonioCargo::with([
            'equipo:id,tei,issi,tipo_terminal_id',
            'equipo.tipo_terminal:id,marca,modelo',
            'flotas.equipo:id,tei,issi,tipo_terminal_id',
            'flotas.equipo.tipo_terminal:id,marca,modelo',
            'destino:id,nombre,parent_id',
            'destino.padre:id,nombre',
        ])->withCount('flotas');

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por dependencia (incluye hijos recursivos)
        if ($request->filled('destino_id')) {
            $destinoIds = Destino::obtenerTodosLosHijos($request->destino_id);
            $query->whereIn('destino_id', $destinoIds);
        }

        // Filtro por texto (TEI, ISSI, firmante)
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('firmante_nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('firmante_legajo', 'like', '%' . $busqueda . '%')
                    ->orWhereHas('equipo', function ($sub) use ($busqueda) {
                        $sub->where('tei', 'like', '%' . $busqueda . '%')
                            ->orWhere('issi', 'like', '%' . $busqueda . '%');
                    })
                    ->orWhereHas('flotas.equipo', function ($sub) use ($busqueda) {
                        $sub->where('tei', 'like', '%' . $busqueda . '%')
                            ->orWhere('issi', 'like', '%' . $busqueda . '%');
                    });
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        $cargos = $query->orderBy('created_at', 'desc')->paginate(20);
        $destinos = Destino::orderBy('nombre')->get();
        $pendientesAgrupables = PatrimonioCargo::query()
            ->where('estado', 'pendiente')
            ->select('destino_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('destino_id')
            ->having('total', '>', 1)
            ->pluck('total', 'destino_id');

        // Contadores por estado
        $contadores = [
            'total'      => PatrimonioCargo::count(),
            'pendientes' => PatrimonioCargo::where('estado', 'pendiente')->count(),
            'firmados'   => PatrimonioCargo::where('estado', 'firmado')->count(),
            'rechazados' => PatrimonioCargo::where('estado', 'rechazado')->count(),
        ];

        return view('patrimonio.cargos.index', compact('cargos', 'destinos', 'contadores', 'pendientesAgrupables'));
    }

    public function agruparPendientes($id)
    {
        $cargoBase = PatrimonioCargo::findOrFail($id);

        if (!$cargoBase->estaPendiente()) {
            return back()->with('error', 'Solo se pueden agrupar cargos pendientes');
        }

        $cargos = PatrimonioCargo::where('destino_id', $cargoBase->destino_id)
            ->where('estado', 'pendiente')
            ->orderBy('created_at')
            ->get();

        if ($cargos->count() < 2) {
            return back()->with('error', 'No hay otros cargos pendientes para agrupar en esta dependencia');
        }

        DB::transaction(function () use ($cargoBase, $cargos) {
            $cargoIds = $cargos->pluck('id');

            FlotaGeneral::whereIn('cargo_id', $cargoIds)->update([
                'cargo_id' => $cargoBase->id,
            ]);

            PatrimonioCargo::whereIn('id', $cargoIds->reject(fn($cargoId) => $cargoId === $cargoBase->id))->delete();
        });

        return redirect()->route('patrimonio.cargos.show', $cargoBase->id)
            ->with('success', 'Cargos pendientes agrupados correctamente');
    }

    /**
     * Detalle de un cargo patrimonial
     */
    public function show($id)
    {
        $cargo = PatrimonioCargo::with([
            'equipo',
            'equipo.tipo_terminal',
            'equipo.estado',
            'flotas.equipo',
            'flotas.equipo.tipo_terminal',
            'flotas.equipo.estado',
            'destino',
            'destino.padre',
            'firmanteDestino',
            'historico',
            'historico.tipoMovimiento',
        ])->findOrFail($id);

        $destinos = Destino::orderBy('nombre')->get();
        $equiposDisponibles = collect();

        if ($cargo->estaPendiente()) {
            $equiposDisponibles = FlotaGeneral::with([
                'equipo:id,tei,issi,tipo_terminal_id',
                'equipo.tipo_terminal:id,marca,modelo',
                'cargo:id,estado,destino_id',
            ])
                ->where('id', '<>', 0)
                ->where(function ($query) use ($cargo) {
                    $query->where(function ($sub) use ($cargo) {
                        $sub->where('patrimoniado', false)
                            ->where('destino_id', $cargo->destino_id);
                    })->orWhere(function ($sub) use ($cargo) {
                        $sub->where('patrimoniado', true)
                            ->where('destino_patrimonial_id', $cargo->destino_id)
                            ->where('cargo_id', '<>', $cargo->id)
                            ->whereHas('cargo', function ($cargoQuery) {
                                $cargoQuery->where('estado', 'pendiente');
                            });
                    });
                })
                ->orderBy('equipo_id')
                ->get();
        }

        return view('patrimonio.cargos.show', compact('cargo', 'destinos', 'equiposDisponibles'));
    }

    public function agregarEquipo(AgregarEquipoPatrimonioCargoRequest $request, $id)
    {
        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Solo se pueden modificar cargos pendientes');
        }

        $flota = FlotaGeneral::with('cargo')->findOrFail($request->flota_id);

        if ($flota->cargo_id === $cargo->id) {
            return back()->with('error', 'El equipo ya pertenece a este cargo');
        }

        $puedeAgregar = (!$flota->patrimoniado && (int) $flota->destino_id === (int) $cargo->destino_id)
            || ($flota->patrimoniado
                && (int) $flota->destino_patrimonial_id === (int) $cargo->destino_id
                && $flota->cargo
                && $flota->cargo->estado === 'pendiente');

        if (!$puedeAgregar) {
            return back()->with('error', 'El equipo seleccionado no está disponible para este cargo');
        }

        DB::transaction(function () use ($cargo, $flota) {
            $cargoAnteriorId = $flota->cargo_id;
            $flota->patrimoniar($cargo->destino_id, $cargo->id);

            if ($cargoAnteriorId && $cargoAnteriorId !== $cargo->id && !FlotaGeneral::where('cargo_id', $cargoAnteriorId)->exists()) {
                PatrimonioCargo::where('id', $cargoAnteriorId)
                    ->where('estado', 'pendiente')
                    ->delete();
            }
        });

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Equipo agregado al cargo patrimonial');
    }

    public function quitarEquipo($id, $flotaId)
    {
        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Solo se pueden modificar cargos pendientes');
        }

        $flotasDelCargo = FlotaGeneral::where('cargo_id', $cargo->id)->count();

        if ($flotasDelCargo <= 1) {
            return back()->with('error', 'No se puede quitar el último equipo del cargo');
        }

        $flota = FlotaGeneral::where('cargo_id', $cargo->id)->findOrFail($flotaId);
        $flota->despatrimoniar();

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Equipo quitado del cargo patrimonial');
    }

    public function acta($id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->ruta_documento) {
            abort(404);
        }

        $ruta = str_replace('anexos/', '', $cargo->ruta_documento);

        if (!Storage::disk('anexos')->exists($ruta)) {
            abort(404);
        }

        return response()->file(Storage::disk('anexos')->path($ruta), [
            'Content-Type' => $cargo->acta_mime ?? Storage::disk('anexos')->mimeType($ruta),
        ]);
    }

    /**
     * Genera el acta patrimonial en formato .docx a partir del template
     * y los datos del cargo. Si el cargo está firmado, completa los datos
     * del firmante; si está pendiente, los deja en blanco para llenar a mano.
     */
    public function generarActa($id)
    {
        $cargo = PatrimonioCargo::with([
            'destino.padre',
            'flotas.equipo.tipo_terminal',
            'flotas.equipo.estado',
            'firmanteDestino',
        ])->findOrFail($id);

        $templatePath = resource_path('templates/template_acta_patrimonial.docx');

        if (!file_exists($templatePath)) {
            return back()->with('error', 'Template de acta no encontrado: ' . $templatePath);
        }

        try {
            $tp = new TemplateProcessor($templatePath);

            $fecha = $cargo->estaFirmado() && $cargo->fecha_firma
                ? $cargo->fecha_firma
                : Carbon::now();

            $meses = [
                'January'   => 'Enero',
                'February'  => 'Febrero',
                'March'     => 'Marzo',
                'April'     => 'Abril',
                'May'       => 'Mayo',
                'June'      => 'Junio',
                'July'      => 'Julio',
                'August'    => 'Agosto',
                'September' => 'Septiembre',
                'October'   => 'Octubre',
                'November'  => 'Noviembre',
                'December'  => 'Diciembre',
            ];

            $tp->setValue('DIA', $fecha->format('d'));
            $tp->setValue('MES', $meses[$fecha->format('F')] ?? $fecha->format('F'));
            $tp->setValue('ANIO', $fecha->format('Y'));

            $cantidadEquipos = $cargo->flotas->count();
            $tp->setValue('CANTIDAD_EQUIPOS', $cantidadEquipos);
            $tp->setValue('CANTIDAD_EQUIPOS_LETRAS', $this->numeroALetras($cantidadEquipos));

            $dependenciaNombre = $cargo->destino->nombre ?? 'SIN DEPENDENCIA';
            $dependenciaPadre  = $cargo->destino && $cargo->destino->padre
                ? '(dependiente de ' . $cargo->destino->padre->nombre . ')'
                : '';

            $tp->setValue('DEPENDENCIA', $dependenciaNombre);
            $tp->setValue('DEPENDENCIA_PADRE_LINEA', $dependenciaPadre);

            $firmanteNombre      = $cargo->estaFirmado() ? ($cargo->firmante_nombre ?? '') : '';
            $firmanteCargo       = $cargo->estaFirmado() ? ($cargo->firmante_cargo ?? '') : '';
            $firmanteLegajo      = $cargo->estaFirmado() ? ($cargo->firmante_legajo ?? '') : '';
            $firmanteDependencia = $cargo->estaFirmado() ? ($cargo->firmanteDestino->nombre ?? '') : '';

            $tp->setValue('FIRMANTE', $firmanteNombre);
            $tp->setValue('CARGO_FIRMANTE', $firmanteCargo);
            $tp->setValue('LEGAJO_FIRMANTE', $firmanteLegajo);
            $tp->setValue('DEPENDENCIA_FIRMANTE', $firmanteDependencia);

            $tp->setValue('OBSERVACIONES', $cargo->observaciones ?? '');

            $filas = [];
            $numero = 1;
            foreach ($cargo->flotas as $flota) {
                if (!$flota->equipo) {
                    continue;
                }

                $tt = $flota->equipo->tipo_terminal;
                $marcaModelo = $tt
                    ? trim(($tt->marca ?? '') . ' ' . ($tt->modelo ?? ''))
                    : 'N/A';

                $filas[] = [
                    'NUMERO'       => $numero++,
                    'TEI'          => $flota->equipo->tei ?? 'N/A',
                    'ISSI'         => $flota->equipo->issi ?? '-',
                    'MARCA_MODELO' => $marcaModelo !== '' ? $marcaModelo : 'N/A',
                    'ESTADO'       => $flota->equipo->estado->nombre ?? '-',
                ];
            }

            if (!empty($filas)) {
                $tp->cloneRowAndSetValues('NUMERO', $filas);
            }

            $fileName = 'acta_patrimonial_cargo_' . $cargo->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/temp/' . $fileName);

            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $tp->saveAs($tempPath);

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Error al generar acta patrimonial: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }
    }

    /**
     * Firmar un cargo patrimonial
     */
    public function firmar(FirmarPatrimonioCargoRequest $request, $id)
    {
        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Este cargo ya fue procesado');
        }

        $rutaDocumento = null;
        $actaNombreOriginal = null;
        $actaMime = null;

        if ($request->hasFile('acta_firmada')) {
            $archivo = $request->file('acta_firmada');
            $rutaDocumento = 'anexos/' . $archivo->store('actas_patrimoniales', 'anexos');
            $actaNombreOriginal = $archivo->getClientOriginalName();
            $actaMime = $archivo->getClientMimeType();
        }

        $cargo->firmar(
            $request->firmante_nombre,
            $request->firmante_cargo,
            $request->firmante_legajo,
            $request->observaciones,
            $request->firmante_destino_id,
            $rutaDocumento,
            $actaNombreOriginal,
            $actaMime
        );

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Cargo patrimonial firmado exitosamente');
    }

    /**
     * Rechazar un cargo patrimonial
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'observaciones' => 'required|string',
        ]);

        $cargo = PatrimonioCargo::findOrFail($id);

        if (!$cargo->estaPendiente()) {
            return back()->with('error', 'Este cargo ya fue procesado');
        }

        $cargo->rechazar($request->observaciones);

        foreach (FlotaGeneral::where('cargo_id', $cargo->id)->get() as $flota) {
            $flota->despatrimoniar();
        }

        return redirect()->route('patrimonio.cargos.show', $cargo->id)
            ->with('success', 'Cargo patrimonial rechazado');
    }

    /**
     * Dashboard patrimonial por dependencia
     */
    public function dashboard(Request $request)
    {
        // Obtener departamentales como nivel principal
        $departamentales = Destino::where('tipo', 'departamental')
            ->with(['hijos' => function ($q) {
                $q->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();

        // Calcular estadísticas por cada departamental (incluyendo hijos)
        foreach ($departamentales as $departamental) {
            $departamental->stats = $departamental->getEstadisticasPatrimoniales(true);

            // Stats de cada hijo directo
            foreach ($departamental->hijos as $hijo) {
                $hijo->stats = $hijo->getEstadisticasPatrimoniales(true);
            }
        }

        // Totales generales
        $totales = [
            'patrimoniados'    => FlotaGeneral::where('patrimoniado', true)->count(),
            'pendientes_firma' => FlotaGeneral::where('patrimoniado', true)
                ->whereHas('cargo', fn($q) => $q->where('estado', 'pendiente'))
                ->count(),
            'sin_patrimoniar'  => FlotaGeneral::where('patrimoniado', false)->count(),
            'total_flota'      => FlotaGeneral::count(),
        ];

        // También incluir direcciones y otras dependencias de primer nivel
        $direcciones = Destino::where('tipo', 'direccion')
            ->with(['hijos' => function ($q) {
                $q->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();

        foreach ($direcciones as $direccion) {
            $direccion->stats = $direccion->getEstadisticasPatrimoniales(true);

            foreach ($direccion->hijos as $hijo) {
                $hijo->stats = $hijo->getEstadisticasPatrimoniales(true);
            }
        }

        return view('patrimonio.dashboard', compact('departamentales', 'direcciones', 'totales'));
    }

    /**
     * Convierte un número entero (1–99) a su representación en letras (mayúsculas).
     */
    private function numeroALetras(int $numero): string
    {
        $unidades = [
            0 => 'CERO', 1 => 'UNO', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO',
            5 => 'CINCO', 6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE',
            10 => 'DIEZ', 11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE',
            14 => 'CATORCE', 15 => 'QUINCE', 16 => 'DIECISÉIS',
            17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
            20 => 'VEINTE', 21 => 'VEINTIUNO', 22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS', 24 => 'VEINTICUATRO', 25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE',
        ];

        if (isset($unidades[$numero])) {
            return $unidades[$numero];
        }

        $decenas = [
            30 => 'TREINTA', 40 => 'CUARENTA', 50 => 'CINCUENTA',
            60 => 'SESENTA', 70 => 'SETENTA', 80 => 'OCHENTA', 90 => 'NOVENTA',
        ];

        foreach ($decenas as $base => $texto) {
            if ($numero >= $base && $numero < $base + 10) {
                $resto = $numero - $base;
                return $resto === 0 ? $texto : $texto . ' Y ' . $unidades[$resto];
            }
        }

        return (string) $numero;
    }
}
