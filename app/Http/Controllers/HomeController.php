<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\DashboardController;
use App\Models\Camara;
use App\Models\Equipo;
use App\Models\Estado;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //ID estados
        $idEstadoNuevo = Estado::where('nombre', 'Nuevo')->value('id');
        $idEstadoUsado = Estado::where('nombre', 'Usado')->value('id');
        $idEstadoReparado = Estado::where('nombre', 'Reparado')->value('id');
        $idEstadoBaja = Estado::where('nombre', 'Baja')->value('id');
        $idEstadoNoFunciona = Estado::where('nombre', 'No funciona')->value('id');
        $idEstadoPerdido = Estado::where('nombre', 'Perdido')->value('id');
        $idEstadoRecambio = Estado::where('nombre', 'Recambio')->value('id');

        $dashboardController = new DashboardController();

        $cant_usuarios = User::count();
        $cant_roles = Role::count();
        $cant_camaras = Camara::all()->count();
        $cant_equipos_sin_funcionar = Equipo::where('estado_id', $idEstadoNoFunciona)->count();
        $cant_equipos_baja = Equipo::where('estado_id', $idEstadoBaja)->count();
        $cant_equipos_funcionales = Equipo::whereIn('estado_id', [$idEstadoNuevo, $idEstadoUsado, $idEstadoReparado])->count();
        $cant_equipos_provisto_por_pg = Equipo::where('provisto', 'Patagonia Green')->count();
        $cant_equipos_provisto_por_telecom = Equipo::where('provisto', 'Telecom')->count();

        $responseStock = $dashboardController->getCantidadEquiposEnStockJSON(request());
        $responseDepa = $dashboardController->getCantidadEquiposEnDepartamentalJSON(request());
        $responsePG = $dashboardController->getCantidadEquiposEnPGJSON(request());
        $responseDesinstalacionesParciales = $dashboardController->getCantidadDesinstalacionesParcialesJSON(request());
        $responseDiv911 = $dashboardController->getCantidadEquiposEnDivision911JSON(request());
        $responseDivBancaria = $dashboardController->getCantidadEquiposEnDivisionBancariaJSON(request());

        $cant_equipos_en_stock = $responseStock->original['cantidad_equipos_en_stock'];
        $cant_equipos_en_departamental = $responseDepa->original['cantidad_equipos_en_departamental'];
        $cant_equipos_en_div_911 = $responseDiv911->original['cantidad_equipos_en_division_911'];
        $cant_equipos_en_div_bancaria = $responseDivBancaria->original['cantidad_equipos_en_division_bancaria'];
        $cant_equipos_en_pg = $responsePG->original['cantidad_equipos_en_pg'];
        $cant_desinstalaciones = $responseDesinstalacionesParciales->original['cantidad_desinstalaciones_parciales'];
        return view('home', compact(
            'cant_usuarios',
            'cant_roles',
            'cant_equipos_en_stock',
            'cant_equipos_en_departamental',
            'cant_equipos_en_pg',
            'cant_equipos_provisto_por_pg',
            'cant_equipos_provisto_por_telecom',
            'cant_camaras',
            'cant_desinstalaciones',
            'cant_equipos_en_div_911',
            'cant_equipos_sin_funcionar',
            'cant_equipos_funcionales',
            'cant_equipos_baja',
            'cant_equipos_en_div_bancaria'
        ));
    }
}
