<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\RecursoInformePreferencia;
use App\Services\FlotaInformeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoInformeController extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct(private readonly FlotaInformeService $informeService)
    {
        $this->middleware('can:generar-parte-diario')->only(['parteDiario', 'generarParteDiario']);
        $this->middleware('can:generar-estado-flota')->only(['estadoFlota', 'generarEstadoFlota']);
    }

    public function parteDiario(Request $request)
    {
        $fecha = $request->get('fecha', today()->toDateString());
        $guardia = $request->get('guardia');
        $horario = $request->get('horario', '07_19');
        [$fechaInicio, $fechaFin] = $this->calcularRangoTurno(
            $fecha, $horario, $request->get('fecha_inicio'), $request->get('fecha_fin')
        );

        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = $this->getSecciones($todosLosDestinoIds, $fechaInicio);
        $personal = Personal::orderBy('apellido')->get(['id', 'jerarquia', 'apellido', 'nombre', 'lp']);

        return view('flota-911.informes.parte-diario', compact(
            'fecha', 'guardia', 'horario', 'fechaInicio', 'fechaFin', 'secciones', 'personal', 'division'
        ));
    }

    public function generarParteDiario(Request $request)
    {
        $request->validate([
            'fecha'               => 'required|date',
            'guardia'             => 'required|in:guardia_1,guardia_2,guardia_3,guardia_4',
            'horario'             => 'required|in:07_19,19_07',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'           => 'required|date|after:fecha_inicio',
            'novedades_generales' => 'nullable|string|max:3000',
            'recursos'            => 'nullable|array',
            'recursos.*.id'       => 'required|exists:recursos,id',
            'recursos.*.estado_dia' => 'required|in:circula,reserva,fuera_de_servicio,otro',
            'recursos.*.motivo'   => 'nullable|string|max:500',
            'recursos.*.dotacion' => 'nullable|array',
        ]);

        $guardia = $request->guardia;
        $horario = $request->horario;
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $userId = auth()->id();

        // Validar que ningún funcionario aparezca en más de un recurso.
        $todosLosPersonalIds = collect($request->input('recursos', []))
            ->flatMap(fn($d) => $d['dotacion'] ?? [])
            ->map(fn($id) => (int) $id);

        $duplicados = $todosLosPersonalIds->duplicates()->unique()->values();
        if ($duplicados->isNotEmpty()) {
            $nombres = Personal::whereIn('id', $duplicados)
                ->get()
                ->map(fn($p) => $p->getNombreCompletoAttribute())
                ->join(', ');
            return back()
                ->withErrors(['dotacion' => "El siguiente personal está asignado a más de un recurso: {$nombres}. Cada funcionario puede figurar en la dotación de un solo recurso por parte."])
                ->withInput();
        }

        DB::transaction(function () use ($request, $guardia, $horario, $fechaInicio, $fechaFin, $userId) {
            foreach ($request->input('recursos', []) as $datos) {
                RecursoEstadoDiario::updateOrCreate(
                    ['recurso_id' => $datos['id'], 'fecha_inicio' => $fechaInicio],
                    [
                        'guardia'    => $guardia,
                        'horario'    => $horario,
                        'fecha_fin'  => $fechaFin,
                        'estado_dia' => $datos['estado_dia'],
                        'motivo'     => $datos['motivo'] ?? null,
                        'user_id'    => $userId,
                    ]
                );

                RecursoDotacion::where('recurso_id', $datos['id'])
                    ->where('fecha_inicio', $fechaInicio)
                    ->delete();

                foreach ($datos['dotacion'] ?? [] as $personalId) {
                    RecursoDotacion::create([
                        'recurso_id'   => $datos['id'],
                        'personal_id'  => $personalId,
                        'guardia'      => $guardia,
                        'horario'      => $horario,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin'    => $fechaFin,
                        'user_id'      => $userId,
                    ]);
                }
            }
        });

        $this->guardarPreferencias($request, $userId);

        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $secciones = $this->getSecciones($division->getDestinosHijosRecursivo(), $fechaInicio);

        return $this->informeService->generarParteDiario(
            $secciones, $guardia, $horario, $fechaInicio, $fechaFin, $request->novedades_generales
        );
    }

    public function estadoFlota(Request $request)
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = Destino::whereIn('id', $todosLosDestinoIds)
            ->with([
                'recursos' => fn($q) => $q->activos()->whereNotNull('vehiculo_id')->with([
                    'asignacionActual.vehiculo',
                    'vehiculo',
                    'estadoSeccion',
                    'novedadesPendientes',
                ]),
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());

        return view('flota-911.informes.estado-flota', compact('secciones', 'division'));
    }

    public function generarEstadoFlota(Request $request)
    {
        $request->validate([
            'destino_id'  => 'required|exists:destino,id',
            'recurso_ids' => 'required|array|min:1',
            'recurso_ids.*' => 'exists:recursos,id',
        ], [
            'destino_id.required'   => 'Seleccione una sección.',
            'recurso_ids.required'  => 'Seleccione al menos un recurso.',
        ]);

        RecursoInformePreferencia::updateOrCreate(
            ['user_id' => auth()->id(), 'destino_id' => $request->destino_id],
            ['recurso_ids' => $request->recurso_ids]
        );

        $destino = Destino::findOrFail($request->destino_id);
        $recursos = Recurso::whereIn('id', $request->recurso_ids)
            ->with(['asignacionActual.vehiculo', 'vehiculo', 'estadoSeccion', 'novedadesPendientes'])
            ->get();

        return $this->informeService->generarEstadoFlota($recursos, $destino);
    }

    private function getSecciones($destinoIds, Carbon $fechaInicio)
    {
        return Destino::whereIn('id', $destinoIds)
            ->with([
                'recursos' => function ($q) use ($fechaInicio) {
                    $q->activos()->whereNotNull('vehiculo_id')->with([
                        'asignacionActual.vehiculo',
                        'vehiculo',
                        'estadoSeccion',
                        'novedadesPendientes',
                        'prestamoActivo.destinoDestino',
                        'estadoDiario' => fn($q2) => $q2->where('fecha_inicio', $fechaInicio),
                        'dotaciones'   => fn($q2) => $q2->where('fecha_inicio', $fechaInicio)->with('personal'),
                    ]);
                },
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());
    }

    /**
     * Resuelve el rango [inicio, fin] de un turno de guardia.
     *
     * Si el request ya trae `fecha_inicio`/`fecha_fin` (el usuario ajustó el horario),
     * se respetan; si no, se derivan de la fecha base y el horario del turno.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function calcularRangoTurno(string $fecha, string $horario, ?string $inicio, ?string $fin): array
    {
        if ($inicio && $fin) {
            return [Carbon::parse($inicio), Carbon::parse($fin)];
        }

        $base = Carbon::parse($fecha)->startOfDay();

        if ($horario === '19_07') {
            return [$base->copy()->setTime(19, 0), $base->copy()->addDay()->setTime(7, 0)];
        }

        return [$base->copy()->setTime(7, 0), $base->copy()->setTime(19, 0)];
    }

    private function guardarPreferencias(Request $request, int $userId): void
    {
        $porSeccion = [];

        foreach ($request->input('recursos', []) as $datos) {
            $recurso = Recurso::find($datos['id']);
            if ($recurso) {
                $porSeccion[$recurso->destino_id][] = $datos['id'];
            }
        }

        foreach ($porSeccion as $destinoId => $recursoIds) {
            RecursoInformePreferencia::updateOrCreate(
                ['user_id' => $userId, 'destino_id' => $destinoId],
                ['recurso_ids' => $recursoIds]
            );
        }
    }
}
