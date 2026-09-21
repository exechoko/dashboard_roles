<?php

namespace App\Http\Controllers;

use App\Exports\PersonalSeccionesExport;
use App\Models\Personal;
use App\Models\PersonalSeccion;
use App\Models\PersonalSeccionNota;
use App\Models\PersonalSeccionNotaComparticion;
use App\Models\User;
use App\Services\Personal911DetalleService;
use App\Services\Personal911ImportService;
use App\Services\PersonalSeccionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonalSeccionController extends Controller
{
    private const POR_PAGINA = 30;

    public function __construct()
    {
        $this->middleware('permission:ver-personal-secciones')->only(['index', 'show', 'export']);
        $this->middleware('permission:crear-personal-seccion-nota')->only(['storeNota', 'compartirNota', 'compartirTodasNotas']);
        $this->middleware('permission:sincronizar-personal-secciones')->only(['sincronizar']);
    }

    public function index(Request $request, Personal911DetalleService $detalleService): View
    {
        $usuarioActual = $request->user();
        $todos = $this->registrosFiltrados($request, $usuarioActual, $detalleService);

        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $registrosPagina = $todos->forPage($pagina, self::POR_PAGINA)->values();

        // Las notas se cargan recién acá, solo para la página que se va a
        // mostrar: cada una arma un modal completo en la vista y con el
        // padrón real (~450 funcionarios) cargarlas todas de una vuelve la
        // página inutilizable.
        $registrosPagina->load(['personal.notasSeccion' => function ($q) use ($usuarioActual) {
            $q->visiblesPara($usuarioActual)->with(['autor', 'compartidas.usuario']);
        }]);

        $registros = new LengthAwarePaginator(
            $registrosPagina,
            $todos->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $todasLasSecciones = PersonalSeccion::query()
            ->whereNotNull('seccion')
            ->distinct()
            ->orderBy('seccion')
            ->pluck('seccion');

        $usuariosParaCompartir = User::where('id', '!=', $usuarioActual->id)
            ->orderBy('name')
            ->get(['id', 'name', 'apellido']);

        return view('personal-secciones.index', [
            'registros' => $registros,
            'todasLasSecciones' => $todasLasSecciones,
            'seccionesSeleccionadas' => array_filter((array) $request->get('secciones', [])),
            'busqueda' => trim((string) $request->get('busqueda', '')),
            'estado' => $request->get('estado', 'todos'),
            'orden' => $request->get('orden', 'jerarquia'),
            'ultimaSincronizacion' => PersonalSeccionSyncService::ultimaSincronizacion(),
            'minutosParaProximaSync' => PersonalSeccionSyncService::minutosParaProximaSyncManual(),
            'usuariosParaCompartir' => $usuariosParaCompartir,
        ]);
    }

    public function export(Request $request, Personal911DetalleService $detalleService): BinaryFileResponse
    {
        $registros = $this->registrosFiltrados($request, $request->user(), $detalleService);

        return Excel::download(
            new PersonalSeccionesExport($registros),
            'PersonalPorSeccion_'.now()->format('Y-m-d_His').'.xlsx'
        );
    }

    /**
     * Aplica los filtros de la request (búsqueda, secciones, estado) y el
     * orden elegido sobre `personal_secciones`. La usan tanto index() como
     * export(), para que el Excel respete exactamente lo que se está viendo.
     */
    private function registrosFiltrados(Request $request, User $usuarioActual, Personal911DetalleService $detalleService): Collection
    {
        $busqueda = trim((string) $request->get('busqueda', ''));
        $secciones = array_filter((array) $request->get('secciones', []));
        $estado = $request->get('estado', 'todos'); // todos | activos | en_licencia | bajas
        $orden = $request->get('orden', 'jerarquia'); // jerarquia | novedades

        $todos = PersonalSeccion::query()
            ->with(['personal' => fn ($q) => $q->withTrashed()])
            ->enSecciones($secciones)
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true)->where('en_licencia', false))
            ->when($estado === 'en_licencia', fn ($q) => $q->where('activo', true)->where('en_licencia', true))
            ->when($estado === 'bajas', fn ($q) => $q->where('activo', false))
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->whereHas('personal', function ($qq) use ($busqueda) {
                    $qq->withTrashed()->where(function ($w) use ($busqueda) {
                        $w->where('apellido', 'like', "%{$busqueda}%")
                            ->orWhere('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('lp', 'like', "%{$busqueda}%")
                            ->orWhere('dni', 'like', "%{$busqueda}%");
                    });
                });
            })
            ->get()
            ->filter(fn (PersonalSeccion $r) => $r->personal !== null)
            ->values();

        return $orden === 'novedades'
            ? $this->ordenarPorNovedades($todos, $usuarioActual)
            : $this->ordenarPorJerarquia($todos, $detalleService);
    }

    private function ordenarPorJerarquia(Collection $registros, Personal911DetalleService $detalleService): Collection
    {
        $personal911Ids = $registros->map(fn (PersonalSeccion $r) => $r->personal->personal911_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fechasIngreso = $detalleService->obtenerFechasIngresoMasivo($personal911Ids);

        return $registros->sortBy([
            fn ($a, $b) => $b->activo <=> $a->activo,
            fn ($a, $b) => strcmp((string) $a->seccion, (string) $b->seccion),
            fn ($a, $b) => Personal::pesoJerarquia($a->personal->jerarquia) <=> Personal::pesoJerarquia($b->personal->jerarquia),
            function ($a, $b) use ($fechasIngreso) {
                $fechaA = $fechasIngreso[$a->personal->personal911_id ?? 0] ?? null;
                $fechaB = $fechasIngreso[$b->personal->personal911_id ?? 0] ?? null;

                // El más antiguo (fecha de ingreso más chica) va primero. Sin
                // fecha disponible (falla personal911, o dato no cargado) va
                // al final del grupo en vez de romper el orden.
                return match (true) {
                    $fechaA === $fechaB => 0,
                    $fechaA === null => 1,
                    $fechaB === null => -1,
                    default => $fechaA <=> $fechaB,
                };
            },
            fn ($a, $b) => strcmp((string) $a->personal->apellido, (string) $b->personal->apellido),
        ])->values();
    }

    private function ordenarPorNovedades(Collection $registros, User $usuarioActual): Collection
    {
        $personalIds = $registros->pluck('personal_id')->all();
        $ultimas = PersonalSeccionNota::ultimasPorPersonal($personalIds, $usuarioActual);

        return $registros->sortBy([
            fn ($a, $b) => $b->activo <=> $a->activo,
            function ($a, $b) use ($ultimas) {
                $fechaA = $ultimas[$a->personal_id] ?? null;
                $fechaB = $ultimas[$b->personal_id] ?? null;

                return match (true) {
                    $fechaA === null && $fechaB === null => 0,
                    $fechaA === null => 1,
                    $fechaB === null => -1,
                    default => $fechaB->timestamp <=> $fechaA->timestamp, // más reciente primero
                };
            },
            fn ($a, $b) => strcmp((string) $a->personal->apellido, (string) $b->personal->apellido),
        ])->values();
    }

    public function show(int $personalId, Personal911DetalleService $detalleService): View
    {
        $personal = Personal::withTrashed()->findOrFail($personalId);
        $seccion = PersonalSeccion::where('personal_id', $personal->id)->first();
        $usuarioActual = auth()->user();

        $detalle = $personal->personal911_id !== null
            ? $detalleService->obtener((int) $personal->personal911_id)
            : null;

        $notas = $personal->notasSeccion()
            ->visiblesPara($usuarioActual)
            ->with(['autor', 'compartidas.usuario'])
            ->get();

        $usuariosParaCompartir = User::where('id', '!=', $usuarioActual->id)
            ->orderBy('name')
            ->get(['id', 'name', 'apellido']);

        return view('personal-secciones.show', [
            'personal' => $personal,
            'seccion' => $seccion,
            'detalle' => $detalle,
            'notas' => $notas,
            'usuariosParaCompartir' => $usuariosParaCompartir,
        ]);
    }

    public function sincronizar(Personal911ImportService $importService, PersonalSeccionSyncService $seccionSyncService): RedirectResponse
    {
        $minutosRestantes = PersonalSeccionSyncService::minutosParaProximaSyncManual();

        if ($minutosRestantes > 0) {
            return redirect()
                ->route('personal-secciones.index')
                ->with('error', "Ya se sincronizó hace poco. Esperá {$minutosRestantes} min antes de volver a intentarlo.");
        }

        try {
            $resultado = $importService->importar();
            $resultadoSecciones = $seccionSyncService->sincronizar();
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('personal-secciones.index')
                ->with('error', 'No se pudo sincronizar con Personal 911: '.$e->getMessage());
        }

        return redirect()
            ->route('personal-secciones.index')
            ->with('success', "Sincronización completa: {$resultado['procesados']} funcionarios procesados, "
                ."{$resultadoSecciones['bajas']} dejaron su sección, {$resultadoSecciones['en_licencia']} en licencia.");
    }

    public function storeNota(Request $request, int $personalId): RedirectResponse
    {
        $personal = Personal::withTrashed()->findOrFail($personalId);

        $datos = $request->validate([
            'texto' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        // Privada por defecto: no se crea ninguna comparticion al crearla.
        PersonalSeccionNota::create([
            'personal_id' => $personal->id,
            'user_id' => $request->user()->id,
            'texto' => trim($datos['texto']),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Anotación agregada (privada, solo la ves vos salvo que la compartas).');
    }

    public function compartirNota(Request $request, int $notaId): RedirectResponse
    {
        $nota = PersonalSeccionNota::findOrFail($notaId);

        abort_unless($nota->esAutor($request->user()), 403, 'Solo el autor puede compartir su anotación.');

        $datos = $request->validate([
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'exists:users,id'],
        ]);

        $this->compartirConUsuarios($nota, $datos['usuarios'], $request->user()->id);

        return redirect()
            ->back()
            ->with('success', 'Anotación compartida.');
    }

    public function compartirTodasNotas(Request $request, int $personalId): RedirectResponse
    {
        $datos = $request->validate([
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'exists:users,id'],
        ]);

        $notas = PersonalSeccionNota::where('personal_id', $personalId)
            ->where('user_id', $request->user()->id)
            ->get();

        foreach ($notas as $nota) {
            $this->compartirConUsuarios($nota, $datos['usuarios'], $request->user()->id);
        }

        return redirect()
            ->back()
            ->with('success', "Se compartieron {$notas->count()} anotaciones tuyas de este funcionario.");
    }

    /**
     * @param  list<int>  $usuarioIds
     */
    private function compartirConUsuarios(PersonalSeccionNota $nota, array $usuarioIds, int $compartidoPorId): void
    {
        foreach ($usuarioIds as $usuarioId) {
            PersonalSeccionNotaComparticion::firstOrCreate(
                ['nota_id' => $nota->id, 'user_id' => $usuarioId],
                ['compartido_por' => $compartidoPorId]
            );
        }
    }
}
