<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\Vehiculo;
use App\Models\VehiculoDotacion;
use App\Models\VehiculoEstadoDiario;
use App\Models\VehiculoInformePreferencia;
use App\Services\FlotaInformeService;
use Illuminate\Http\Request;

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

        // Secciones con sus vehículos
        $secciones = $this->getSecciones($todosLosDestinoIds, $fecha);

        // Personal disponible para dotaciones
        $personal = Personal::orderBy('apellido')->get(['id', 'jerarquia', 'apellido', 'nombre', 'lp']);

        return view('flota-911.informes.parte-diario', compact('fecha', 'secciones', 'personal', 'division'));
    }

    public function generarParteDiario(Request $request)
    {
        $request->validate([
            'fecha'            => 'required|date',
            'novedades_generales' => 'nullable|string|max:3000',
            'vehiculos'        => 'nullable|array',
            'vehiculos.*.id'   => 'required|exists:vehiculos,id',
            'vehiculos.*.estado_dia' => 'required|in:circula,reserva,fuera_de_servicio,otro',
            'vehiculos.*.motivo'  => 'nullable|string|max:500',
            'vehiculos.*.dotacion' => 'nullable|array',
        ]);

        $fecha = $request->fecha;
        $userId = auth()->id();

        // Persistir estados diarios y dotaciones
        foreach ($request->input('vehiculos', []) as $datos) {
            VehiculoEstadoDiario::updateOrCreate(
                ['vehiculo_id' => $datos['id'], 'fecha' => $fecha],
                ['estado_dia' => $datos['estado_dia'], 'motivo' => $datos['motivo'] ?? null, 'user_id' => $userId]
            );

            VehiculoDotacion::where('vehiculo_id', $datos['id'])->whereDate('fecha', $fecha)->delete();

            foreach ($datos['dotacion'] ?? [] as $personalId) {
                VehiculoDotacion::create([
                    'vehiculo_id' => $datos['id'],
                    'personal_id' => $personalId,
                    'fecha'       => $fecha,
                    'user_id'     => $userId,
                ]);
            }
        }

        // Guardar preferencias de selección por sección
        $this->guardarPreferencias($request, $userId);

        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();
        $secciones = $this->getSecciones($todosLosDestinoIds, $fecha);

        return $this->informeService->generarParteDiario($secciones, $fecha, $request->novedades_generales);
    }

    public function estadoFlota(Request $request)
    {
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $todosLosDestinoIds = $division->getDestinosHijosRecursivo();

        $secciones = Destino::whereIn('id', $todosLosDestinoIds)
            ->with([
                'recursos' => fn($q) => $q->whereNotNull('vehiculo_id')
                    ->with(['vehiculo', 'vehiculo.estadoSeccion', 'vehiculo.novedadesPendientes']),
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());

        return view('flota-911.informes.estado-flota', compact('secciones', 'division'));
    }

    public function generarEstadoFlota(Request $request)
    {
        $request->validate([
            'destino_id'  => 'required|exists:destino,id',
            'vehiculo_ids' => 'required|array|min:1',
            'vehiculo_ids.*' => 'exists:vehiculos,id',
        ], [
            'destino_id.required'   => 'Seleccione una sección.',
            'vehiculo_ids.required' => 'Seleccione al menos un vehículo.',
        ]);

        // Guardar preferencia
        VehiculoInformePreferencia::updateOrCreate(
            ['user_id' => auth()->id(), 'destino_id' => $request->destino_id],
            ['vehiculo_ids' => $request->vehiculo_ids]
        );

        $destino = Destino::findOrFail($request->destino_id);
        $vehiculos = Vehiculo::whereIn('id', $request->vehiculo_ids)
            ->with(['estadoSeccion', 'novedadesPendientes'])
            ->get();

        return $this->informeService->generarEstadoFlota($vehiculos, $destino);
    }

    /**
     * @param \Illuminate\Support\Collection<int> $destinoIds
     */
    private function getSecciones($destinoIds, string $fecha)
    {
        return Destino::whereIn('id', $destinoIds)
            ->with([
                'recursos' => function ($q) use ($fecha) {
                    $q->whereNotNull('vehiculo_id')->with([
                        'vehiculo',
                        'vehiculo.estadoSeccion',
                        'vehiculo.novedadesPendientes',
                        'vehiculo.prestamoActivo.destinoDestino',
                        'vehiculo.estadoDiario' => fn($q2) => $q2->whereDate('fecha', $fecha),
                        'vehiculo.dotaciones'   => fn($q2) => $q2->whereDate('fecha', $fecha)->with('personal'),
                    ]);
                },
            ])
            ->get()
            ->filter(fn($d) => $d->recursos->isNotEmpty());
    }

    private function guardarPreferencias(Request $request, int $userId): void
    {
        $porSeccion = [];

        foreach ($request->input('vehiculos', []) as $datos) {
            $recurso = Recurso::where('vehiculo_id', $datos['id'])->first();
            if ($recurso) {
                $porSeccion[$recurso->destino_id][] = $datos['id'];
            }
        }

        foreach ($porSeccion as $destinoId => $vehiculoIds) {
            VehiculoInformePreferencia::updateOrCreate(
                ['user_id' => $userId, 'destino_id' => $destinoId],
                ['vehiculo_ids' => $vehiculoIds]
            );
        }
    }
}
