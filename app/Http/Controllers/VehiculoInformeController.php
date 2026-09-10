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
        $this->middleware('can:generar-parte-diario')->only(['parteDiario', 'generarParteDiario', 'preArmarParteDiario', 'parteDesdeUltimaGuardia', 'descargarParteDiario']);
        $this->middleware('can:ver-flota-911')->only('estadoFlota');
        $this->middleware('can:generar-estado-flota')->only('generarEstadoFlota');
    }

    public function parteDiario(Request $request)
    {
        $fecha = $request->get('fecha', today()->toDateString());
        $guardia = $request->get('guardia');
        $horario = $request->get('horario', '06_18');
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

    /**
     * Devuelve, por sección, los datos del último parte guardado de la guardia
     * indicada (recursos que circularon, zona/HT, dotación + chofer, guardia
     * interna, licencias, asignaciones y los 14 rubros de novedades) para
     * precargar el formulario de un parte nuevo. No guarda nada.
     */
    public function parteDesdeUltimaGuardia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'guardia' => ['required', 'in:guardia_1,guardia_2,guardia_3,guardia_4'],
        ]);
        $guardia = $datos['guardia'];

        $division = Destino::findOrFail(self::DIVISION_911_ID);

        $ultimosPorSeccion = ParteDiario::query()
            ->whereIn('destino_id', $division->getDestinosHijosRecursivo())
            ->where('guardia', $guardia)
            ->with(['estadosDiarios', 'dotaciones' => fn ($q) => $q->orderBy('orden'), 'asignaciones'])
            ->orderByDesc('fecha_inicio')
            ->get()
            ->groupBy('destino_id')
            ->map->first();

        if ($ultimosPorSeccion->isEmpty()) {
            return response()->json(['encontrado' => false]);
        }

        $secciones = [];

        foreach ($ultimosPorSeccion as $destinoId => $parte) {
            $dotacionPorRecurso = $parte->dotaciones->groupBy('recurso_id');
            $recursos = [];

            foreach ($parte->estadosDiarios as $estado) {
                $dot = $dotacionPorRecurso->get($estado->recurso_id, collect());
                $recursos[$estado->recurso_id] = [
                    'estado_dia' => $estado->estado_dia,
                    'zona'       => $estado->zona ? (string) $estado->zona : '',
                    'ht'         => (string) ($estado->ht ?? ''),
                    'motivo'     => (string) ($estado->motivo ?? ''),
                    'dotacion'   => $dot->sortBy('orden')->pluck('personal_id')->map(fn ($id) => (int) $id)->values(),
                    'chofer_id'  => optional($dot->firstWhere('es_chofer', true))->personal_id,
                ];
            }

            $secciones[$destinoId] = [
                'guardia_interna'    => (string) ($parte->guardia_interna ?? ''),
                'licencia_ordinaria' => (string) ($parte->licencia_ordinaria ?? ''),
                'novedades_pie'      => (string) ($parte->novedades_pie ?? ''),
                'recursos'           => $recursos,
                'asignaciones'       => $parte->asignaciones
                    ->map(fn ($a) => [
                        'grupo'            => $a->grupo,
                        'nombre'           => $a->nombre,
                        'asignacion_texto' => $a->asignacion_texto,
                    ])
                    ->values(),
            ];
        }

        $referencia    = $ultimosPorSeccion->sortByDesc('fecha_inicio')->first();
        $novedadesRow  = ParteDiarioNovedades::where('guardia', $guardia)
            ->orderByDesc('fecha_inicio')
            ->first();

        return response()->json([
            'encontrado' => true,
            'referencia' => [
                'fecha'         => optional($referencia->fecha)->toDateString(),
                'guardia_label' => $referencia->guardiaLabel(),
            ],
            'secciones'  => $secciones,
            'novedades'  => $novedadesRow?->contenido ?? [],
        ]);
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

        $q = trim((string) $request->get('q'));
        $vistas = \App\Models\RecursoBitacoraVista::where('user_id', auth()->id())
            ->pluck('visto_en', 'recurso_id');

        $lista = Recurso::query()
            ->whereIn('destino_id', $todosLosDestinoIds)
            ->activos()->whereNotNull('vehiculo_id')
            ->with(['vehiculo', 'estadoSeccion', 'ultimaBitacora.usuario', 'bitacoraAbiertas'])
            ->when($q !== '', fn($qq) => $qq->where(fn($w) => $w
                ->where('nombre', 'like', "%{$q}%")
                ->orWhereHas('vehiculo', fn($v) => $v
                    ->where('dominio', 'like', "%{$q}%")
                    ->orWhere('marca', 'like', "%{$q}%")
                    ->orWhere('modelo', 'like', "%{$q}%"))))
            ->get();

        $nuevasPorRecurso = \App\Models\RecursoBitacora::whereIn('recurso_id', $lista->pluck('id'))
            ->get(['recurso_id', 'created_at'])
            ->groupBy('recurso_id')
            ->map(fn($entradas, $rid) => $entradas
                ->filter(fn($e) => ! isset($vistas[$rid]) || $e->created_at->gt($vistas[$rid]))
                ->count());

        $lista = $lista
            ->each(fn($r) => $r->nuevas = $nuevasPorRecurso[$r->id] ?? 0)
            ->sortByDesc(fn($r) => sprintf(
                '%d|%011d',
                $r->nuevas > 0 ? 1 : 0,
                optional($r->ultimaBitacora)->fecha_hora?->timestamp ?? 0,
            ))
            ->values();

        return view('flota-911.informes.estado-flota', compact('secciones', 'division', 'lista', 'q'));
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

        if ($horario === '18_06') {
            return [$base->copy()->setTime(18, 15), $base->copy()->addDay()->setTime(6, 15)];
        }

        return [$base->copy()->setTime(6, 15), $base->copy()->setTime(18, 15)];
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
