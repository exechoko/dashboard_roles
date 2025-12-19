<?php

namespace App\Http\Controllers;

use App\Models\DispositivoEdificio;
use App\Models\PasswordVault;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlanoEdificioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:plano-edificio-ver')->only(['index', 'show', 'getDevices', 'getDevice']);
        $this->middleware('permission:plano-edificio-crear')->only(['store', 'createPasswordVault']);
        $this->middleware('permission:plano-edificio-editar')->only(['update', 'updatePosition']);
        $this->middleware('permission:plano-edificio-borrar')->only(['destroy']);
        $this->middleware('permission:plano-edificio-credenciales')->only(['getCredentials']);
    }

    /**
     * Muestra la vista principal del plano del edificio
     */
    public function index()
    {
        // Obtener estadísticas
        $stats = [
            'total' => DispositivoEdificio::count(),
            'activos' => DispositivoEdificio::activos()->count(),
            'con_credenciales' => DispositivoEdificio::conCredenciales()->count(),
            'por_tipo' => DispositivoEdificio::selectRaw('tipo, COUNT(*) as count')
                ->groupBy('tipo')
                ->pluck('count', 'tipo')
                ->toArray(),
        ];

        return view('plano-edificio.index', compact('stats'));
    }

    /**
     * Obtiene todos los dispositivos para el mapa (API)
     */
    public function getDevices(Request $request): JsonResponse
    {
        $query = DispositivoEdificio::with(['passwordVault', 'createdBy', 'updatedBy']);

        // Filtros
        if ($request->has('tipos') && !empty($request->tipos)) {
            $query->whereIn('tipo', $request->tipos);
        }

        if ($request->has('oficina')) {
            $query->porOficina($request->oficina);
        }

        if ($request->has('piso')) {
            $query->porPiso($request->piso);
        }

        if ($request->has('activo')) {
            if ($request->activo === 'true') {
                $query->activos();
            } else {
                $query->where('activo', false);
            }
        }

        $dispositivos = $query->get();

        $data = $dispositivos->map(function ($dispositivo) {
            return [
                'id' => $dispositivo->id,
                'tipo' => $dispositivo->tipo,
                'nombre' => $dispositivo->nombre,
                'ip' => $dispositivo->ip,
                'mac' => $dispositivo->mac,
                'marca' => $dispositivo->marca,
                'modelo' => $dispositivo->modelo,
                'serie' => $dispositivo->serie,
                'oficina' => $dispositivo->oficina,
                'piso' => $dispositivo->piso,
                'posicion_x' => $dispositivo->posicion_x,
                'posicion_y' => $dispositivo->posicion_y,
                'sistema_operativo' => $dispositivo->sistema_operativo,
                'puertos' => $dispositivo->puertos,
                'observaciones' => $dispositivo->observaciones,
                'activo' => $dispositivo->activo,
                'tiene_credenciales' => $dispositivo->tieneCredenciales(),
                'icono' => $dispositivo->icono,
                'color' => $dispositivo->color,
                'tipo_label' => $dispositivo->tipo_label,
                'created_at' => $dispositivo->created_at->format('d/m/Y H:i'),
                'updated_at' => $dispositivo->updated_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    /**
     * Obtiene un dispositivo específico
     */
    public function getDevice($id): JsonResponse
    {
        $dispositivo = DispositivoEdificio::with(['passwordVault', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        $data = [
            'id' => $dispositivo->id,
            'tipo' => $dispositivo->tipo,
            'nombre' => $dispositivo->nombre,
            'ip' => $dispositivo->ip,
            'mac' => $dispositivo->mac,
            'marca' => $dispositivo->marca,
            'modelo' => $dispositivo->modelo,
            'serie' => $dispositivo->serie,
            'oficina' => $dispositivo->oficina,
            'piso' => $dispositivo->piso,
            'posicion_x' => $dispositivo->posicion_x,
            'posicion_y' => $dispositivo->posicion_y,
            'sistema_operativo' => $dispositivo->sistema_operativo,
            'puertos' => $dispositivo->puertos,
            'observaciones' => $dispositivo->observaciones,
            'activo' => $dispositivo->activo,
            'tiene_credenciales' => $dispositivo->tieneCredenciales(),
            'icono' => $dispositivo->icono,
            'color' => $dispositivo->color,
            'tipo_label' => $dispositivo->tipo_label,
            'password_vault_id' => $dispositivo->password_vault_id,
            'created_by' => $dispositivo->createdBy ? $dispositivo->createdBy->name : null,
            'updated_by' => $dispositivo->updatedBy ? $dispositivo->updatedBy->name : null,
            'created_at' => $dispositivo->created_at->format('d/m/Y H:i'),
            'updated_at' => $dispositivo->updated_at->format('d/m/Y H:i'),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Crea un nuevo dispositivo
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:pc,puesto_cecoco,puesto_video,router,switch,camara_interna',
            'nombre' => 'required|string|max:200',
            'ip' => 'nullable|ip',
            'mac' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serie' => 'nullable|string|max:100',
            'oficina' => 'required|string|max:200',
            'piso' => 'nullable|string|max:50',
            'posicion_x' => 'nullable|numeric|between:0,100',
            'posicion_y' => 'nullable|numeric|between:0,100',
            'sistema_operativo' => 'nullable|required_if:tipo,pc|string|max:100',
            'puertos' => 'nullable|required_if:tipo,router,switch|integer|min:1|max:48',
            'observaciones' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // Si se proporcionaron credenciales, crear PasswordVault
            if ($request->has('username') || $request->has('password')) {
                $passwordVault = $this->createPasswordVault($request);
                $data['password_vault_id'] = $passwordVault->id;
            }

            $dispositivo = DispositivoEdificio::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo creado correctamente',
                'data' => $dispositivo,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear dispositivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza un dispositivo existente
     */
    public function update(Request $request, $id): JsonResponse
    {
        $dispositivo = DispositivoEdificio::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:pc,puesto_cecoco,puesto_video,router,switch,camara_interna',
            'nombre' => 'required|string|max:200',
            'ip' => 'nullable|ip',
            'mac' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serie' => 'nullable|string|max:100',
            'oficina' => 'required|string|max:200',
            'piso' => 'nullable|string|max:50',
            'sistema_operativo' => 'nullable|required_if:tipo,pc|string|max:100',
            'puertos' => 'nullable|required_if:tipo,router,switch|integer|min:1|max:48',
            'observaciones' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['updated_by'] = Auth::id();

            // Actualizar credenciales si se proporcionaron
            if ($request->has('username') || $request->has('password')) {
                if ($dispositivo->password_vault_id) {
                    // Actualizar PasswordVault existente
                    $passwordVault = PasswordVault::find($dispositivo->password_vault_id);
                    if ($passwordVault) {
                        $passwordVault->update([
                            'username' => $request->username,
                            'password' => $request->password,
                            'system_name' => $dispositivo->nombre,
                            'system_type' => $dispositivo->tipo,
                        ]);
                    }
                } else {
                    // Crear nuevo PasswordVault
                    $passwordVault = $this->createPasswordVault($request, $dispositivo);
                    $data['password_vault_id'] = $passwordVault->id;
                }
            }

            $dispositivo->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo actualizado correctamente',
                'data' => $dispositivo,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar dispositivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza la posición de un dispositivo
     */
    public function updatePosition(Request $request, $id): JsonResponse
    {
        $dispositivo = DispositivoEdificio::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'posicion_x' => 'required|numeric|between:0,100',
            'posicion_y' => 'required|numeric|between:0,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $dispositivo->update([
            'posicion_x' => $request->posicion_x,
            'posicion_y' => $request->posicion_y,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posición actualizada correctamente',
            'data' => $dispositivo,
        ]);
    }

    /**
     * Elimina un dispositivo
     */
    public function destroy($id): JsonResponse
    {
        $dispositivo = DispositivoEdificio::findOrFail($id);

        DB::beginTransaction();
        try {
            // Eliminar PasswordVault asociado si existe
            if ($dispositivo->password_vault_id) {
                PasswordVault::find($dispositivo->password_vault_id)?->delete();
            }

            $dispositivo->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar dispositivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene las credenciales de un dispositivo
     */
    public function getCredentials($id): JsonResponse
    {
        $dispositivo = DispositivoEdificio::with('passwordVault')->findOrFail($id);

        if (!$dispositivo->tieneCredenciales()) {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo no tiene credenciales almacenadas',
            ], 404);
        }

        // Registrar acceso
        $dispositivo->passwordVault->recordAccess();

        return response()->json([
            'success' => true,
            'data' => [
                'username' => $dispositivo->passwordVault->username,
                'password' => $dispositivo->passwordVault->password,
            ],
        ]);
    }

    /**
     * Crea un PasswordVault para las credenciales
     */
    private function createPasswordVault(Request $request, ?DispositivoEdificio $dispositivo = null): PasswordVault
    {
        return PasswordVault::create([
            'user_id' => Auth::id(),
            'system_name' => $dispositivo?->nombre ?? $request->nombre,
            'system_type' => $dispositivo?->tipo ?? $request->tipo,
            'username' => $request->username,
            'password' => $request->password,
            'notes' => "Dispositivo en {$request->oficina}" . ($request->piso ? " - Piso {$request->piso}" : ""),
            'icon' => 'fas fa-network-wired',
        ]);
    }

    /**
     * Exporta dispositivos a Excel
     */
    public function export(Request $request)
    {
        // TODO: Implementar exportación a Excel
        return response()->json([
            'success' => false,
            'message' => 'Función de exportación en desarrollo',
        ]);
    }
}
