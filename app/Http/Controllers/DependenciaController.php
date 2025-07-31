<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DependenciaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-dependencia|crear-dependencia|editar-dependencia|borrar-dependencia')->only('index');
        $this->middleware('permission:crear-dependencia', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-dependencia', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-dependencia', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Obtener todas las dependencias ordenadas por jerarquía y nombre
        $todasDependencias = Destino::with(['padre', 'hijos'])
            ->orderByRaw("
            CASE tipo
                WHEN 'jefatura' THEN 1
                WHEN 'subjefatura' THEN 2
                WHEN 'direccion' THEN 3
                WHEN 'departamental' THEN 4
                WHEN 'division' THEN 5
                WHEN 'comisaria' THEN 6
                WHEN 'seccion' THEN 7
                WHEN 'destacamento' THEN 8
                ELSE 9
            END
        ")
            ->orderBy('nombre')
            ->get();

        // Obtener estadísticas
        $estadisticas = Destino::getEstadisticas();

        // Si prefieres usar paginación:
        // $todasDependencias = Destino::with(['padre', 'hijos'])
        //     ->orderByRaw("...")
        //     ->orderBy('nombre')
        //     ->paginate(50);

        return view('dependencias.index', compact('todasDependencias', 'estadisticas'));
    }

    /**
     * Método helper para obtener la clase CSS del badge según el tipo
     * Puedes agregar esto como método en el modelo Destino o como helper
     */
    public function getTipoBadgeClass($tipo)
    {
        $clases = [
            'jefatura' => 'dark',
            'subjefatura' => 'secondary',
            'direccion' => 'light',
            'departamental' => 'primary',
            'division' => 'success',
            'comisaria' => 'warning',
            'seccion' => 'info',
            'destacamento' => 'danger'
        ];

        return $clases[$tipo] ?? 'light';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $direcciones = Destino::where('tipo', 'direccion')->get();
        $departamentales = Destino::where('tipo', 'departamental')->get();

        return view('dependencias.crear', compact('direcciones', 'departamentales'));
    }

    public function getDepartamentales(Request $request)
    {
        $departamentales = Destino::where('tipo', 'departamental')
            ->where('parent_id', $request->direccion_id)
            ->get();
        return response()->json($departamentales);
    }

    public function getDivisiones(Request $request)
    {
        if (!is_null($request->departamental_id)) {
            $divisiones = Destino::where('tipo', 'division')
                ->where('parent_id', $request->departamental_id)
                ->get();
        } else {
            $divisiones = Destino::where('tipo', 'division')
                ->where('parent_id', $request->direccion_id)
                ->get();
        }
        return response()->json($divisiones);
    }

    public function getComisarias(Request $request)
    {
        $comisarias = Destino::where('tipo', 'comisaria')
            ->where('parent_id', $request->departamental_id)
            ->get();
        return response()->json($comisarias);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'nombre' => 'required',
            'tipoDependencia' => 'required|in:seccion,destacamento',
        ], [
            'required' => 'El campo :attribute es necesario completar.',
            'in' => 'El tipo de dependencia debe ser sección o destacamento.'
        ]);

        // Validar que se haya seleccionado al menos una dependencia padre
        if (
            is_null($request->direccion) && is_null($request->departamental) &&
            is_null($request->division) && is_null($request->comisaria)
        ) {
            return back()->with('error', 'Debe elegir al menos una dependencia padre (Dirección, Departamental, División o Comisaría)')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Determinar el parent_id basado en la jerarquía
            $parent_id = $this->determinarParentId($request);

            // Crear la nueva dependencia
            $dependencia = new Destino();
            $dependencia->nombre = $this->formatearNombre($request->nombre, $request->tipoDependencia);
            $dependencia->tipo = $request->tipoDependencia;
            $dependencia->parent_id = $parent_id;
            $dependencia->telefono = $request->telefono;
            $dependencia->ubicacion = $request->ubicacion;
            $dependencia->observaciones = $request->observaciones;

            $dependencia->save();

            DB::commit();

            return redirect()->route('dependencias.index')
                ->with('success', 'Dependencia creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error al crear la dependencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Determina el parent_id basado en la jerarquía seleccionada
     */
    private function determinarParentId(Request $request)
    {
        // Priorizar la dependencia más específica (de menor a mayor jerarquía)
        if (!is_null($request->comisaria)) {
            return $request->comisaria;
        }

        if (!is_null($request->division)) {
            return $request->division;
        }

        if (!is_null($request->departamental)) {
            return $request->departamental;
        }

        if (!is_null($request->direccion)) {
            return $request->direccion;
        }

        // Si no se seleccionó ninguna, buscar la jefatura como parent por defecto
        $jefatura = Destino::where('tipo', 'jefatura')->first();
        return $jefatura ? $jefatura->id : null;
    }

    /**
     * Formatea el nombre según el tipo de dependencia
     */
    private function formatearNombre($nombre, $tipo)
    {
        $prefijo = ($tipo === 'seccion') ? 'Sección' : 'Destacamento';

        // Si el nombre ya tiene el prefijo, no lo duplicamos
        if (stripos($nombre, $prefijo) === 0) {
            return $nombre;
        }

        return $prefijo . ' ' . $nombre;
    }

    public function show($id)
    {
        $dependencia = Destino::with(['padre', 'hijos'])->findOrFail($id);

        return view('dependencias.show', compact('dependencia'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $dependencia = Destino::findOrFail($id);

        // Obtener todas las posibles dependencias padre según el tipo
        $tiposPadresValidos = [
            'subjefatura' => ['jefatura'],
            'direccion' => ['jefatura', 'subjefatura'],
            'departamental' => ['jefatura', 'direccion'],
            'division' => ['jefatura', 'direccion', 'departamental'],
            'comisaria' => ['departamental'],
            'seccion' => ['direccion', 'departamental', 'division', 'comisaria'],
            'destacamento' => ['departamental', 'division', 'comisaria']
        ];

        $tiposValidos = $tiposPadresValidos[$dependencia->tipo] ?? [];
        $posiblesPadres = collect();

        if (!empty($tiposValidos)) {
            $posiblesPadres = Destino::whereIn('tipo', $tiposValidos)
                ->where('id', '!=', $id) // Excluir la propia dependencia
                ->get();
        }

        return view('dependencias.editar', compact('dependencia', 'posiblesPadres'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $dependencia = Destino::findOrFail($id);

        // Validaciones dinámicas según el tipo
        $rules = [
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'ubicacion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string'
        ];

        // Si se está cambiando el parent_id, validar que exista
        if ($request->has('parent_id') && !is_null($request->parent_id)) {
            $rules['parent_id'] = 'exists:destino,id';
        }

        $request->validate($rules, [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'La dependencia padre seleccionada no existe.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.'
        ]);

        try {
            DB::beginTransaction();

            // Validar jerarquía si se está cambiando el parent_id
            if ($request->has('parent_id') && $request->parent_id != $dependencia->parent_id) {
                if (!$this->validarJerarquia($dependencia->tipo, $request->parent_id)) {
                    return back()->with('error', 'La nueva jerarquía seleccionada no es válida para este tipo de dependencia.')
                        ->withInput();
                }

                // Verificar que no se esté creando una referencia circular
                if ($this->validarReferenciaCircular($id, $request->parent_id)) {
                    return back()->with('error', 'No se puede establecer esta dependencia como padre porque crearía una referencia circular.')
                        ->withInput();
                }

                $dependencia->parent_id = $request->parent_id;
            }

            // Actualizar el nombre usando el formateador adecuado
            $dependencia->nombre = $this->formatearNombrePorTipo($request->nombre, $dependencia->tipo);
            $dependencia->telefono = $request->telefono;
            $dependencia->ubicacion = $request->ubicacion;
            $dependencia->observaciones = $request->observaciones;

            $dependencia->save();

            DB::commit();

            return redirect()->route('dependencias.index')
                ->with('success', 'Dependencia actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error al actualizar la dependencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Validar que no se cree una referencia circular
     */
    private function validarReferenciaCircular($dependenciaId, $nuevoParentId)
    {
        if (is_null($nuevoParentId)) {
            return false; // No hay referencia circular si no hay padre
        }

        // Verificar si el nuevo padre es la misma dependencia
        if ($dependenciaId == $nuevoParentId) {
            return true;
        }

        // Recorrer hacia arriba en la jerarquía del nuevo padre
        $currentParent = Destino::find($nuevoParentId);

        while ($currentParent && $currentParent->parent_id) {
            if ($currentParent->parent_id == $dependenciaId) {
                return true; // Se encontró una referencia circular
            }
            $currentParent = $currentParent->padre;
        }

        return false; // No hay referencia circular
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $dependencia = Destino::findOrFail($id);

            // Solo permitir eliminar secciones y destacamentos
            if (!in_array($dependencia->tipo, ['seccion', 'destacamento'])) {
                return redirect()->route('dependencias.index')
                    ->with('error', 'Solo se pueden eliminar secciones y destacamentos.');
            }

            // Verificar si tiene dependencias hijas
            if ($dependencia->hijos()->count() > 0) {
                return redirect()->route('dependencias.index')
                    ->with('error', 'No se puede eliminar la dependencia porque tiene dependencias asociadas.');
            }

            $dependencia->delete();

            DB::commit();

            return redirect()->route('dependencias.index')
                ->with('success', 'Dependencia eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('dependencias.index')
                ->with('error', 'Error al eliminar la dependencia: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para crear cualquier tipo de dependencia
     */
    public function createGeneral()
    {
        // Obtener todas las dependencias que pueden ser padres
        $jefatura = Destino::where('tipo', 'jefatura')->first();
        $subjefatura = Destino::where('tipo', 'subjefatura')->first();
        $direcciones = Destino::where('tipo', 'direccion')->get();
        $departamentales = Destino::where('tipo', 'departamental')->get();
        $divisiones = Destino::where('tipo', 'division')->get();
        $comisarias = Destino::where('tipo', 'comisaria')->get();

        return view(
            'dependencias.crear-general',
            compact(
                'jefatura',
                'subjefatura',
                'direcciones',
                'departamentales',
                'divisiones',
                'comisarias'
            )
        );
    }

    /**
     * Almacenar cualquier tipo de dependencia
     */
    public function storeGeneral(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:subjefatura,direccion,departamental,division,comisaria,seccion,destacamento',
            'parent_id' => 'nullable|exists:destino,id',
            'telefono' => 'nullable|string|max:50',
            'ubicacion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string'
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'in' => 'El tipo seleccionado no es válido.',
            'exists' => 'La dependencia padre seleccionada no existe.'
        ]);

        try {
            DB::beginTransaction();

            // Validar la jerarquía
            if (!$this->validarJerarquia($request->tipo, $request->parent_id)) {
                return back()->with('error', 'La jerarquía seleccionada no es válida.')
                    ->withInput();
            }

            // Crear la nueva dependencia
            $dependencia = new Destino();
            $dependencia->nombre = $this->formatearNombrePorTipo($request->nombre, $request->tipo);
            $dependencia->tipo = $request->tipo;
            $dependencia->parent_id = $request->parent_id;
            $dependencia->telefono = $request->telefono;
            $dependencia->ubicacion = $request->ubicacion;
            $dependencia->observaciones = $request->observaciones;

            $dependencia->save();

            DB::commit();

            return redirect()->route('dependencias.index')
                ->with('success', 'Dependencia creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error al crear la dependencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Validar que la jerarquía sea correcta
     */
    private function validarJerarquia($tipo, $parentId)
    {

        if (is_null($parentId)) {
            // Solo la jefatura puede no tener padre
            return $tipo === 'jefatura';
        }

        $padre = Destino::find($parentId);
        if (!$padre) {
            return false;
        }

        // Definir las relaciones jerárquicas válidas
        $jerarquiaValida = [
            'jefatura' => [], // La jefatura no puede tener padre
            'subjefatura' => ['jefatura'],
            'direccion' => ['jefatura', 'subjefatura'],
            'departamental' => ['jefatura', 'direccion'],
            'division' => ['jefatura', 'direccion', 'departamental'],
            'comisaria' => ['departamental'],
            'seccion' => ['direccion', 'departamental', 'division', 'comisaria'],
            'destacamento' => ['departamental', 'division', 'comisaria']
        ];

        return in_array($padre->tipo, $jerarquiaValida[$tipo] ?? []);
    }

    /**
     * Formatear nombre según el tipo
     */
    private function formatearNombrePorTipo($nombre, $tipo)
    {
        $prefijos = [
            'subjefatura' => 'Sub Jefatura',
            'direccion' => 'Dirección',
            'departamental' => 'Departamental',
            'division' => 'División',
            'comisaria' => 'Comisaría',
            'seccion' => 'Sección',
            'destacamento' => 'Destacamento'
        ];

        $prefijo = $prefijos[$tipo] ?? '';

        // Si el nombre ya tiene el prefijo, no lo duplicamos
        if ($prefijo && stripos($nombre, $prefijo) === 0) {
            return $nombre;
        }

        return $prefijo ? $prefijo . ' ' . $nombre : $nombre;
    }

    /**
     * Obtener dependencias por tipo para AJAX
     */
    public function getDependenciasPorTipo(Request $request)
    {
        $tipo = $request->tipo;
        $parentId = $request->parent_id;

        $query = Destino::where('tipo', $tipo);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        $dependencias = $query->get();

        return response()->json($dependencias);
    }

    /**
     * Obtener posibles padres según el tipo seleccionado
     */
    public function getPosiblesPadres(Request $request)
    {
        $tipo = $request->tipo;

        $tiposPadresValidos = [
            'subjefatura' => ['jefatura'],
            'direccion' => ['jefatura', 'subjefatura'],
            'departamental' => ['jefatura', 'direccion'],
            'division' => ['jefatura', 'direccion', 'departamental'],
            'comisaria' => ['departamental'],
            'seccion' => ['direccion', 'departamental', 'division', 'comisaria'],
            'destacamento' => ['departamental', 'division', 'comisaria']
        ];

        $tiposValidos = $tiposPadresValidos[$tipo] ?? [];

        if (empty($tiposValidos)) {
            return response()->json([]);
        }

        $posiblesPadres = Destino::whereIn('tipo', $tiposValidos)->get();

        return response()->json($posiblesPadres);
    }
}
