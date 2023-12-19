<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\DashboardController;
use App\Models\Camara;
use App\Models\Equipo;

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
        $dashboardController = new DashboardController();
        
        $cant_usuarios = User::count();
        $cant_roles = Role::count();
        $cant_camaras = Camara::all()->count();
        $cant_equipos_sin_funcionar = Equipo::where('estado_id', 3)->count();
        $cant_equipos_funcionales = Equipo::where('estado_id', '<>', 3)->count();

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
            'cant_camaras',
            'cant_desinstalaciones',
            'cant_equipos_en_div_911',
            'cant_equipos_sin_funcionar',
            'cant_equipos_funcionales',
            'cant_equipos_en_div_bancaria'
        ));
    }
}
