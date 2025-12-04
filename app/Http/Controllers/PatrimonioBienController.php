<?php
// app/Http/Controllers/PatrimonioBienController.php

namespace App\Http\Controllers;

use App\Models\PatrimonioBien;
use App\Models\PatrimonioTipoBien;
use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatrimonioBienController extends Controller
{
    public function index(Request $request)
    {
        $query = PatrimonioBien::with(['tipoBien', 'destino', 'ultimoMovimiento']);

        // Filtros
        if ($request->filled('tipo_bien_id')) {
            $query->where('tipo_bien_id', $request->tipo_bien_id);
        }

        if ($request->filled('destino_id')) {
            $query->where('destino_id', $request->destino_id);
        }

        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . $request->ubicacion . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('siaf')) {
            $query->where('siaf', 'like', '%' . $request->siaf . '%');
        }

        if ($request->filled('numero_serie')) {
            $query->where('numero_serie', 'like', '%' . $request->numero_serie . '%');
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('siaf', 'like', '%' . $busqueda . '%')
                    ->orWhere('numero_serie', 'like', '%' . $busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $busqueda . '%')
                    ->orWhere('ubicacion', 'like', '%' . $busqueda . '%');
            });
        }

        $bienes = $query->orderBy('created_at', 'desc')->paginate(15);
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.index', compact('bienes', 'tiposBien', 'destinos'));
    }

    public function create()
    {
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.create', compact('tiposBien', 'destinos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_bien_id' => 'required|exists:patrimonio_tipos_bien,id',
            'item_origen_id' => 'nullable|integer',
            'destino_id' => 'nullable|exists:destino,id',
            'ubicacion' => 'nullable|string|max:150',
            'siaf' => 'nullable|string|max:100',
            'descripcion' => 'required|string',
            'numero_serie' => 'nullable|string|max:255',
            'fecha_alta' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $tipoBien = PatrimonioTipoBien::findOrFail($validated['tipo_bien_id']);

            // Si tiene tabla propia, procesar vinculación
            if ($tipoBien->tiene_tabla_propia && $tipoBien->tabla_referencia) {

                // Validar que se seleccionó un item
                if (!$request->item_origen_id) {
                    return back()->with('error', 'Debe seleccionar un item para patrimoniar')->withInput();
                }

                // Verificar que el item no esté ya patrimoniado
                $yaPatrimoniado = PatrimonioBien::where('tabla_origen', $tipoBien->tabla_referencia)
                    ->where('id_origen', $request->item_origen_id)
                    ->exists();

                if ($yaPatrimoniado) {
                    return back()->with('error', 'Este item ya fue patrimoniado anteriormente')->withInput();
                }

                // Obtener datos del item origen
                $itemOrigen = DB::table($tipoBien->tabla_referencia)
                    ->where('id', $request->item_origen_id)
                    ->first();

                if (!$itemOrigen) {
                    return back()->with('error', 'El item seleccionado no existe')->withInput();
                }

                // Si la descripción está vacía, usar la del item origen
                if (empty($validated['descripcion']) && isset($itemOrigen->descripcion)) {
                    $validated['descripcion'] = $itemOrigen->descripcion;
                }

                // Asignar vinculación
                $validated['tabla_origen'] = $tipoBien->tabla_referencia;
                $validated['id_origen'] = $request->item_origen_id;
            }

            // Establecer estado inicial
            $validated['estado'] = 'activo';

            // Crear el bien patrimonial
            $bien = PatrimonioBien::create($validated);

            // Registrar movimiento de alta
            $bien->registrarMovimiento(
                'alta',
                null,
                null,
                $request->destino_id,
                $request->ubicacion,
                'Alta inicial del bien patrimonial'
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Bien patrimonial registrado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $bien = PatrimonioBien::with(['tipoBien', 'destino', 'movimientos.destinoDesde', 'movimientos.destinoHasta'])
            ->findOrFail($id);

        return view('patrimonio.bienes.show', compact('bien'));
    }

    public function edit($id)
    {
        $bien = PatrimonioBien::findOrFail($id);
        $tiposBien = PatrimonioTipoBien::orderBy('nombre')->get();
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.edit', compact('bien', 'tiposBien', 'destinos'));
    }

    public function update(Request $request, $id)
    {
        $bien = PatrimonioBien::findOrFail($id);

        $validated = $request->validate([
            'tipo_bien_id' => 'required|exists:patrimonio_tipos_bien,id',
            'destino_id' => 'nullable|exists:destino,id',
            'ubicacion' => 'nullable|string|max:150',
            'siaf' => 'nullable|string|max:100',
            'descripcion' => 'required|string',
            'numero_serie' => 'nullable|string|max:255',
            'fecha_alta' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verificar si cambió el destino o la ubicación
            $destinoAnterior = $bien->destino_id;
            $ubicacionAnterior = $bien->ubicacion;
            $destinoNuevo = $request->destino_id;
            $ubicacionNueva = $request->ubicacion;

            // Si cambió el destino o la ubicación, registrar traslado
            if ($destinoAnterior != $destinoNuevo || $ubicacionAnterior != $ubicacionNueva) {
                $bien->registrarMovimiento(
                    'traslado',
                    $destinoAnterior,
                    $ubicacionAnterior,
                    $destinoNuevo,
                    $ubicacionNueva,
                    'Traslado registrado desde edición'
                );
            }

            $bien->update($validated);

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Bien actualizado exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el bien: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $bien = PatrimonioBien::findOrFail($id);
            $bien->delete();

            return redirect()->route('patrimonio.bienes.index')
                ->with('success', 'Bien eliminado exitosamente');
        } catch (Exception $e) {
            return back()->with('error', 'Error al eliminar el bien: ' . $e->getMessage());
        }
    }

    public function darBaja($id)
    {
        $bien = PatrimonioBien::findOrFail($id);

        return view('patrimonio.bienes.baja', compact('bien'));
    }

    public function procesarBaja(Request $request, $id)
    {
        $validated = $request->validate([
            'tipo_baja' => 'required|in:baja_desuso,baja_transferencia,baja_rotura',
            'observaciones' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $bien = PatrimonioBien::findOrFail($id);

            $bien->update([
                'estado' => 'baja',
            ]);

            $bien->registrarMovimiento(
                $validated['tipo_baja'],
                $bien->destino_id,
                $bien->ubicacion,
                null,
                null,
                $validated['observaciones']
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Baja procesada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar la baja: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function traslado($id)
    {
        $bien = PatrimonioBien::findOrFail($id);
        $destinos = Destino::orderBy('nombre')->get();

        return view('patrimonio.bienes.traslado', compact('bien', 'destinos'));
    }

    public function procesarTraslado(Request $request, $id)
    {
        $validated = $request->validate([
            'destino_hasta_id' => 'required|exists:destino,id',
            'ubicacion_hasta' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $bien = PatrimonioBien::findOrFail($id);
            $destinoDesde = $bien->destino_id;
            $ubicacionDesde = $bien->ubicacion;
            $destinoHasta = $validated['destino_hasta_id'];
            $ubicacionHasta = $validated['ubicacion_hasta'] ?? null;

            if ($destinoDesde == $destinoHasta && $ubicacionDesde == $ubicacionHasta) {
                return back()->with('error', 'El destino y la ubicación son iguales a los actuales')
                    ->withInput();
            }

            $bien->update([
                'destino_id' => $destinoHasta,
                'ubicacion' => $ubicacionHasta,
            ]);

            $bien->registrarMovimiento(
                'traslado',
                $destinoDesde,
                $ubicacionDesde,
                $destinoHasta,
                $ubicacionHasta,
                $validated['observaciones']
            );

            DB::commit();

            return redirect()->route('patrimonio.bienes.show', $bien->id)
                ->with('success', 'Traslado procesado exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el traslado: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Obtener items disponibles para patrimoniar según el tipo de bien
     */
    /**
     * Obtener items disponibles para patrimoniar según el tipo de bien
     */
    public function getItemsDisponibles(Request $request)
    {
        try {
            $tipoId = $request->tipo_bien_id;

            if (!$tipoId) {
                return response()->json([]);
            }

            $tipoBien = PatrimonioTipoBien::find($tipoId);

            if (!$tipoBien || !$tipoBien->tiene_tabla_propia || !$tipoBien->tabla_referencia) {
                return response()->json([]);
            }

            if (!DB::getSchemaBuilder()->hasTable($tipoBien->tabla_referencia)) {
                return response()->json([]);
            }

            /* ==========================================================
             * CONFIGURACIÓN MODULAR
             * ==========================================================*/
            $config = [
                'equipos' => [
                    'prefix' => '- TEI: - ',
                    'column' => 'tei'
                ],
                'camaras' => [
                    'prefix' => ' - ',
                    'column' => 'nombre'
                ],
            ];

            $tabla = $tipoBien->tabla_referencia;

            // Definir prefijo y columna a concatenar
            $prefix = $config[$tabla]['prefix'] ?? '';
            $col = $config[$tabla]['column'] ?? 'id';

            // Construir select seguro
            $selectRaw = "id, CONCAT(" .
                DB::getPdo()->quote($prefix) . ", " .
                "COALESCE($col, '')) AS text";

            /* ==========================================================
             * CONSULTA
             * ==========================================================*/
            $items = DB::table($tabla)
                ->selectRaw($selectRaw)
                ->whereNotIn('id', function ($query) use ($tabla) {
                    $query->select('id_origen')
                        ->from('patrimonio_bienes')
                        ->where('tabla_origen', $tabla)
                        ->whereNull('deleted_at');
                })
                ->orderBy('id')
                ->get();

            return response()->json($items);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
