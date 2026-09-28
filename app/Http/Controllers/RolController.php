<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//agregamos
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Services\AuditoriaService;

class RolController extends Controller
{
    function __construct(){
        $this->middleware('permission:ver-rol|crear-rol|editar-rol|borrar-rol', ['only'=>['index']]);
        $this->middleware('permission:crear-rol', ['only'=>['create', 'store']]);
        $this->middleware('permission:editar-rol', ['only'=>['edit', 'update']]);
        $this->middleware('permission:borrar-rol', ['only'=>['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::paginate(100);
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $permission = Permission::get();
        return view('roles.crear', compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request);
        $this->validate($request, ['name' => 'required', 'permission' => 'required']);
        $role = Role::create(['name' => $request->input('name')]);
        $permisos = $this->permissionNamesFromRequest($request);
        $role->syncPermissions($permisos);

        AuditoriaService::registrar(
            'ASIGNAR PERMISOS',
            'role_has_permissions',
            sprintf('Rol: %s (id: %d) - Permisos asignados: %s', $role->name, $role->id, implode(', ', $permisos))
        );

        return redirect()->route('roles.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();

        return view('roles.editar',compact('role','permission','rolePermissions'));
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
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);

        $role = Role::find($id);
        $permisosAnteriores = $role->permissions()->pluck('name')->all();

        $role->name = $request->input('name');
        $role->save();

        $permisosNuevos = $this->permissionNamesFromRequest($request);
        $role->syncPermissions($permisosNuevos);

        $this->auditarCambioPermisos($role, $permisosAnteriores, $permisosNuevos);

        return redirect()->route('roles.index');
    }

    /**
     * Registra en la auditoría los permisos agregados/quitados de un rol.
     *
     * @param  array<int, string>  $permisosAnteriores
     * @param  array<int, string>  $permisosNuevos
     */
    private function auditarCambioPermisos(Role $role, array $permisosAnteriores, array $permisosNuevos): void
    {
        $agregados = array_values(array_diff($permisosNuevos, $permisosAnteriores));
        $quitados = array_values(array_diff($permisosAnteriores, $permisosNuevos));

        if (empty($agregados) && empty($quitados)) {
            return;
        }

        $detalle = sprintf('Rol: %s (id: %d)', $role->name, $role->id);

        if (!empty($agregados)) {
            $detalle .= ' - Permisos agregados: ' . implode(', ', $agregados);
        }

        if (!empty($quitados)) {
            $detalle .= ' - Permisos quitados: ' . implode(', ', $quitados);
        }

        AuditoriaService::registrar('ASIGNAR PERMISOS', 'role_has_permissions', $detalle);
    }

    /**
     * @return array<int, string>
     */
    private function permissionNamesFromRequest(Request $request): array
    {
        $permissionIds = collect($request->input('permission', []))
            ->filter(fn ($permissionId) => (int) $permissionId > 0)
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->values()
            ->all();

        return Permission::whereIn('id', $permissionIds)
            ->pluck('name')
            ->all();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        DB::table('roles')->where('id', $id)->delete();
        return redirect()->route('roles.index');
    }
}
