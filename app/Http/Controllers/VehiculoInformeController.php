<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarParteDiarioRequest;
use App\Models\Destino;
use App\Models\ParteDiario;
use App\Models\ParteDiarioAsignacion;
use App\Models\ParteDiarioConsigna;
use App\Models\ParteDiarioNovedades;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\RecursoInformePreferencia;
use App\Services\FlotaInformeService;
use App\Services\ParteDiarioBorradorService;
use App\Services\ParteDiarioDocxService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehiculoInformeController extends Controller
{
    private const DIVISION_911_ID = 42;

    public function __construct(private readonly FlotaInformeService $informeService)
    {
        $this->middleware('can:generar-parte-diario')->only(['parteDiario', 'generarParteDiario', 'preArmarParteDiario', 'descargarParteDiario']);
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
        $consignas = ParteDiarioConsigna::activas()->ordenadas()->get();

        $partesPorSeccion = ParteDiario::with('asignaciones')
            ->where('fecha_inicio', $fechaInicio)
            ->get()
            ->keyBy('destino_id');

        $novedades = $guardia
            ? ParteDiarioNovedades::firstWhere(['fecha' => $fecha, 'guardia' => $guardia])
            : null;

        $tiposPorSeccion = $secciones->mapWithKeys(
            fn (Destino $s) => [$s->id => $this->tipoDeSeccion($s)]
        );

        return view('flota-911.informes.parte-diario', compact(
            'fecha', 'guardia', 'horario', 'fechaInicio', 'fechaFin', 'secciones', 'personal',
            'division', 'consignas', 'partesPorSeccion', 'novedades', 'tiposPorSeccion'
        ));
    }

    public function preArmarParteDiario(Request $request, ParteDiarioBorradorService $borradorService): JsonResponse
    {
        $datos = $request->validate([
            'guardia'      => ['required', 'in:guardia_1,guardia_2,guardia_3,guardia_4'],
            'fecha_inicio' => ['required', 'date'],
        ]);

        $fechaInicio = Carbon::parse($datos['fecha_inicio']);
        $division = Destino::findOrFail(self::DIVISION_911_ID);
        $secciones = $this->getSecciones($division->getDestinosHijosRecursivo(), $fechaInicio);

        $novedades = [];
        $porSeccion = [];

        foreach ($secciones as $seccion) {
            $tipo = $this->tipoDeSeccion($seccion);
            $borrador = $borradorService->armar($tipo, $datos['guardia'], $fechaInicio);

            $porSeccion[$seccion->id] = [
                'guardia_interna'    => $borrador['guardia_interna']
                    ->map(fn (Personal $p) => trim("{$p->jerarquia} {$p->apellido} {$p->nombre}"))
                    ->join('; '),
                'licencia_ordinaria' => $borrador['novedades']['licencia_ordinaria'] ?? '',
                'calle_disponibles'  => $borrador['personal_calle']->count(),
            ];

            $novedades = array_merge($novedades, array_filter($borrador['novedades']));
        }

        return response()->json([
            'novedades' => $novedades,
            'secciones' => $porSeccion,
        ]);
    }

    public function generarParteDiario(GuardarParteDiarioRequest $request)
    {
        $guardia = $request->input('guardia');
        $horario = $request->input('horario');
        $fecha = $request->input('fecha');
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'));
        $fechaFin = Carbon::parse($request->input('fecha_fin'));
        $userId = auth()->id();

        DB::transaction(function () use ($request, $guardia, $horario, $fecha, $fechaInicio, $fechaFin, $userId) {
            $recursosPorSeccion = collect($request->input('recursos', []))
                ->groupBy(fn ($datos) => Recurso::find($datos['id'])?->destino_id)
                ->filter(fn ($_, $destinoId) => $destinoId !== null && $destinoId !== '');

            foreach ($recursosPorSeccion as $destinoId => $recursos) {
                $seccion = Destino::find($destinoId);
                $datosSeccion = $request->input("secciones.{$destinoId}", []);

                $parte = ParteDiario::updateOrCreate(
                    ['destino_id' => $destinoId, 'fecha_inicio' => $fechaInicio],
                    [
                        'tipo'               => $this->tipoDeSeccion($seccion),
                        'fecha'              => $fecha,
                        'guardia'            => $guardia,
                        'horario'            => $horario,
                        'fecha_fin'          => $fechaFin,
                        'guardia_interna'    => $datosSeccion['guardia_interna'] ?? null,
                        'licencia_ordinaria' => $datosSeccion['licencia_ordinaria'] ?? null,
                        'novedades_pie'      => $datosSeccion['novedades_pie'] ?? null,
                        'user_id'            => $userId,
                    ]
                );

                foreach ($recursos as $datos) {
                    $this->guardarRecursoDelParte($parte, $datos, $guardia, $horario, $fechaInicio, $fechaFin, $userId);
                }

                $this->guardarAsignaciones($parte, $datosSeccion['asignaciones'] ?? []);
            }

            $this->guardarNovedades($request, $fecha, $guardia, $horario, $fechaInicio, $fechaFin, $userId);
        });

        $this->guardarPreferencias($request, $userId);

        return redirect()
            ->route('flota-911.informes.parte-diario', [
                'fecha'        => $fecha,
                'guardia'      => $guardia,
                'horario'      => $horario,
                'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i'),
                'fecha_fin'    => $fechaFin->format('Y-m-d\TH:i'),
            ])
            ->with('success', 'Parte guardado. Descargá el .docx de cada sección desde los botones de abajo.');
    }

    public function descargarParteDiario(Request $request, Destino $seccion, ParteDiarioDocxService $docxService)
    {
        $datos = $request->validate([
            'fecha_inicio' => ['required', 'date'],
        ]);

        $parte = ParteDiario::with([
            'seccion',
            'estadosDiarios.recurso.vehiculo',
            'dotaciones.personal',
            'asignaciones',
        ])
            ->where('destino_id', $seccion->id)
            ->where('fecha_inicio', Carbon::parse($datos['fecha_inicio']))
            ->firstOrFail();

        $novedades = ParteDiarioNovedades::firstWhere([
            'fecha'   => $parte->fecha->toDateString(),
            'guardia' => $parte->guardia,
        ]);

        return $docxService->generar($parte, $novedades);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardarRecursoDelParte(
        ParteDiario $parte,
        array $datos,
        string $guardia,
        string $horario,
        Carbon $fechaInicio,
        Carbon $fechaFin,
        int $userId
    ): void {
        RecursoEstadoDiario::updateOrCreate(
            ['recurso_id' => $datos['id'], 'fecha_inicio' => $fechaInicio],
            [
                'parte_diario_id' => $parte->id,
                'guardia'         => $guardia,
                'horario'         => $horario,
                'zona'            => $datos['zona'] ?? null,
                'ht'             => $datos['ht'] ?? null,
                'fecha_fin'       => $fechaFin,
                'estado_dia'      => $datos['estado_dia'],
                'motivo'          => $datos['motivo'] ?? null,
                'user_id'         => $userId,
            ]
        );

        RecursoDotacion::where('recurso_id', $datos['id'])
            ->where('fecha_inicio', $fechaInicio)
            ->delete();

        $choferId = isset($datos['chofer_id']) ? (int) $datos['chofer_id'] : null;

        foreach (array_values($datos['dotacion'] ?? []) as $orden => $personalId) {
            RecursoDotacion::create([
                'parte_diario_id' => $parte->id,
                'recurso_id'      => $datos['id'],
                'personal_id'     => $personalId,
                'es_chofer'       => (int) $personalId === $choferId,
                'orden'           => $orden,
                'guardia'         => $guardia,
                'horario'         => $horario,
                'fecha_inicio'    => $fechaInicio,
                'fecha_fin'       => $fechaFin,
                'user_id'         => $userId,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $asignaciones
     */
    private function guardarAsignaciones(ParteDiario $parte, array $asignaciones): void
    {
        ParteDiarioAsignacion::where('parte_diario_id', $parte->id)->delete();

        foreach (array_values($asignaciones) as $orden => $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            ParteDiarioAsignacion::create([
                'parte_diario_id'  => $parte->id,
                'grupo'            => $fila['grupo'] ?? null,
                'nombre'          => $nombre,
                'asignacion_texto' => $fila['asignacion_texto'] ?? null,
                'orden'           => $orden,
            ]);
        }
    }

    private function guardarNovedades(
        GuardarParteDiarioRequest $request,
        string $fecha,
        string $guardia,
        string $horario,
        Carbon $fechaInicio,
        Carbon $fechaFin,
        int $userId
    ): void {
        $contenido = collect($request->input('novedades', []))
            ->map(fn ($valor) => trim((string) $valor))
            ->filter()
            ->all();

        if ($contenido === [] && ! ParteDiarioNovedades::where(['fecha' => $fecha, 'guardia' => $guardia])->exists()) {
            return;
        }

        ParteDiarioNovedades::updateOrCreate(
            ['fecha' => $fecha, 'guardia' => $guardia],
            [
                'horario'      => $horario,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'contenido'    => $contenido,
                'user_id'      => $userId,
            ]
        );
    }

    private function tipoDeSeccion(?Destino $seccion): string
    {
        return $seccion && Str::contains(Str::lower($seccion->nombre), 'motor')
            ? ParteDiario::TIPO_MOTOS
            : ParteDiario::TIPO_MOVILES;
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
                    'ultimaBitacora',
                    'bitacoraAbiertas',
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
            ->with(['asignacionActual.vehiculo', 'vehiculo', 'estadoSeccion', 'ultimaBitacora', 'bitacoraAbiertas'])
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
                        'prestamoActivo.destinoDestino',
                        'estadoDiario' => fn($q2) => $q2->where('fecha_inicio', $fechaInicio),
                        'dotaciones'   => fn($q2) => $q2->where('fecha_inicio', $fechaInicio)
                            ->orderBy('orden')->with('personal'),
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
