<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\RecursoInformePreferencia;
use App\Services\FlotaInformeService;
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
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = $this->getSecciones($todosLosDestinoIds, $fecha);
        $personal = Personal::orderBy('apellido')->get(['id', 'jerarquia', 'apellido', 'nombre', 'lp']);

        return view('flota-911.informes.parte-diario', compact('fecha', 'secciones', 'personal', 'division'));
    }

    public function generarParteDiario(Request $request)
    {
        $request->validate([
            'fecha'               => 'required|date',
            'novedades_generales' => 'nullable|string|max:3000',
            'recursos'            => 'nullable|array',
            'recursos.*.id'       => 'required|exists:recursos,id',
            'recursos.*.estado_dia' => 'required|in:circula,reserva,fuera_de_servicio,otro',
            'recursos.*.motivo'   => 'nullable|string|max:500',
            'recursos.*.dotacion' => 'nullable|array',
        ]);

        $fecha = $request->fecha;
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

        DB::transaction(function () use ($request, $fecha, $userId) {
            foreach ($request->input('recursos', []) as $datos) {
                RecursoEstadoDiario::updateOrCreate(
                    ['recurso_id' => $datos['id'], 'fecha' => $fecha],
                    ['estado_dia' => $datos['estado_dia'], 'motivo' => $datos['motivo'] ?? null, 'user_id' => $userId]
                );

                RecursoDotacion::where('recurso_id', $datos['id'])->whereDate('fecha', $fecha)->delete();

                foreach ($datos['dotacion'] ?? [] as $personalId) {
                    RecursoDotacion::create([
                        'recurso_id' => $datos['id'],
                        'personal_id' => $personalId,
                        'fecha'       => $fecha,
                        'user_id'     => $userId,
                    ]);
                }
            }
        });

        $this->guardarPreferencias($request, $userId);

        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $secciones = $this->getSecciones($division->getDestinosHijosRecursivo(), $fecha);

        return $this->informeService->generarParteDiario($secciones, $fecha, $request->novedades_generales);
    }

    public function estadoFlota(Request $request)
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = Destino::whereIn('id', $todosLosDestinoIds)
            ->with([
                'recursos' => fn($q) => $q->whereNotNull('vehiculo_id')->with([
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

    private function getSecciones($destinoIds, string $fecha)
    {
        return Destino::whereIn('id', $destinoIds)
            ->with([
                'recursos' => function ($q) use ($fecha) {
                    $q->whereNotNull('vehiculo_id')->with([
                        'asignacionActual.vehiculo',
                        'vehiculo',
                        'estadoSeccion',
                        'novedadesPendientes',
                        'prestamoActivo.destinoDestino',
                        'estadoDiario' => fn($q2) => $q2->whereDate('fecha', $fecha),
                        'dotaciones'   => fn($q2) => $q2->whereDate('fecha', $fecha)->with('personal'),
                    ]);
                },
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());
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
