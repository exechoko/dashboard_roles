<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\TareaItem;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TareaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-tarea|crear-tarea|editar-tarea|borrar-tarea')->only(['index']);
        $this->middleware('permission:crear-tarea', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-tarea', ['only' => ['edit', 'update', 'updateItem']]);
        $this->middleware('permission:borrar-tarea', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $vista = $request->get('vista', 'proximas');
        if ($request->filled('realizado_por') || $request->filled('fecha_realizada_desde') || $request->filled('fecha_realizada_hasta')) {
            $vista = 'todas';
        }
        if ($request->filled('estado') && $request->estado === TareaItem::ESTADO_REALIZADA) {
            $vista = 'todas';
        }

        $query = TareaItem::with(['tarea', 'realizadoPor']);

        if ($request->filled('nombre')) {
            $nombre = $request->nombre;
            $query->whereHas('tarea', function ($q) use ($nombre) {
                $q->where('nombre', 'like', '%' . $nombre . '%');
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('realizado_por')) {
            $query->where('realizado_por', $request->realizado_por);
        }

        if ($request->filled('observaciones')) {
            $obs = $request->observaciones;
            $query->where('observaciones', 'like', '%' . $obs . '%');
        }

        if ($request->filled('fecha_programada_desde')) {
            $query->whereDate('fecha_programada', '>=', $request->fecha_programada_desde);
        }

        if ($request->filled('fecha_programada_hasta')) {
            $query->whereDate('fecha_programada', '<=', $request->fecha_programada_hasta);
        }

        if ($vista === 'proximas') {
            $estadosProximos = [TareaItem::ESTADO_PENDIENTE, TareaItem::ESTADO_EN_PROCESO];
            if ($request->filled('estado') && in_array($request->estado, $estadosProximos, true)) {
                $estadosProximos = [$request->estado];
            }

            $sub = TareaItem::selectRaw('MIN(fecha_programada) as fecha_programada, tarea_id')
                ->whereIn('estado', $estadosProximos);

            if ($request->filled('observaciones')) {
                $sub->where('observaciones', 'like', '%' . $request->observaciones . '%');
            }
            if ($request->filled('fecha_programada_desde')) {
                $sub->whereDate('fecha_programada', '>=', $request->fecha_programada_desde);
            }
            if ($request->filled('fecha_programada_hasta')) {
                $sub->whereDate('fecha_programada', '<=', $request->fecha_programada_hasta);
            }

            $sub->groupBy('tarea_id');

            $query
                ->select('tarea_items.*')
                ->joinSub($sub, 'next_items', function ($join) {
                    $join
                        ->on('tarea_items.tarea_id', '=', 'next_items.tarea_id')
                        ->on('tarea_items.fecha_programada', '=', 'next_items.fecha_programada');
                })
                ->orderBy('tarea_items.fecha_programada', 'asc')
                ->orderBy('tarea_items.id', 'asc');
        }

        if ($vista !== 'proximas') {
            if ($request->filled('fecha_realizada_desde')) {
                $query->whereDate('fecha_realizada', '>=', $request->fecha_realizada_desde);
            }

            if ($request->filled('fecha_realizada_hasta')) {
                $query->whereDate('fecha_realizada', '<=', $request->fecha_realizada_hasta);
            }

            $query
                ->orderBy('fecha_programada', 'desc')
                ->orderBy('created_at', 'desc');
        }

        $items = $query
            ->paginate(15)
            ->appends($request->query());

        $usuarios = User::orderBy('name')->get();
        $estados = TareaItem::ESTADOS;

        return view('tareas.index', compact('items', 'usuarios', 'estados', 'vista'));
    }

    public function create()
    {
        $recurrencias = Tarea::RECURRENCIAS;

        return view('tareas.create', compact('recurrencias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'recurrencia_tipo' => 'required|in:none,daily,weekly,monthly',
            'recurrencia_intervalo' => 'nullable|integer|min:1',
            'recurrencia_dia_semana' => 'nullable|integer|min:1|max:7',
            'recurrencia_dia_mes' => 'nullable|integer|min:1|max:31',
            'fecha_inicio' => 'nullable|date',
            'activa' => 'nullable|boolean',
        ]);

        $validated['recurrencia_intervalo'] = (int) ($validated['recurrencia_intervalo'] ?? 1);
        $validated['activa'] = (bool) ($request->has('activa') ? (int) $request->activa : true);
        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $tarea = Tarea::create($validated);

            $desde = Carbon::now();
            $hasta = Carbon::now()->addDays(60);
            $tarea->generarItems($desde, $hasta);

            DB::commit();

            return redirect()->route('tareas.index')
                ->with('success', 'Tarea creada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al crear la tarea: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $tarea = Tarea::findOrFail($id);
        $recurrencias = Tarea::RECURRENCIAS;

        return view('tareas.edit', compact('tarea', 'recurrencias'));
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'recurrencia_tipo' => 'required|in:none,daily,weekly,monthly',
            'recurrencia_intervalo' => 'nullable|integer|min:1',
            'recurrencia_dia_semana' => 'nullable|integer|min:1|max:7',
            'recurrencia_dia_mes' => 'nullable|integer|min:1|max:31',
            'fecha_inicio' => 'nullable|date',
            'activa' => 'nullable|boolean',

            'impacto_recurrencia' => 'nullable|in:solo_tarea,futuras_instancias',
            'fecha_corte' => 'nullable|date',
        ]);

        $validated['recurrencia_intervalo'] = (int) ($validated['recurrencia_intervalo'] ?? 1);
        $validated['activa'] = (bool) ($request->has('activa') ? (int) $request->activa : false);

        $impacto = $request->input('impacto_recurrencia', 'solo_tarea');
        $fechaCorte = $request->filled('fecha_corte') ? Carbon::parse($request->fecha_corte) : Carbon::now();

        unset($validated['impacto_recurrencia'], $validated['fecha_corte']);

        DB::beginTransaction();
        try {
            $tarea->update($validated);

            if ($impacto === 'futuras_instancias') {
                $tarea->items()
                    ->whereDate('fecha_programada', '>=', $fechaCorte->toDateString())
                    ->where('estado', TareaItem::ESTADO_PENDIENTE)
                    ->delete();

                $hasta = Carbon::now()->addDays(60);
                $tarea->generarItems($fechaCorte, $hasta);
            }

            DB::commit();

            return redirect()->route('tareas.index')
                ->with('success', 'Tarea actualizada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al actualizar la tarea: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $tarea = Tarea::findOrFail($id);

        DB::beginTransaction();
        try {
            $tarea->items()->delete();
            $tarea->delete();

            DB::commit();

            return redirect()->route('tareas.index')
                ->with('success', 'Tarea eliminada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al eliminar la tarea: ' . $e->getMessage());
        }
    }

    public function updateItem(Request $request, $id)
    {
        $item = TareaItem::with('tarea')->findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,realizada',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $item->observaciones = $validated['observaciones'] ?? null;

            if ($validated['estado'] === TareaItem::ESTADO_REALIZADA) {
                $item->estado = TareaItem::ESTADO_REALIZADA;
                $item->realizado_por = auth()->id();
                $item->fecha_realizada = now();
            } else {
                $item->estado = $validated['estado'];
                $item->realizado_por = null;
                $item->fecha_realizada = null;
            }

            $item->save();

            DB::commit();

            return redirect()->route('tareas.index')
                ->with('success', 'Tarea actualizada');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al actualizar la tarea: ' . $e->getMessage())
                ->withInput();
        }
    }
}
