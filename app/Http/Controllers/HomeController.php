<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Camara;
use App\Models\Equipo;
use App\Models\Estado;
use App\Models\FlotaGeneral;
use App\Models\Destino;
use App\Models\Recurso;
use App\Models\Historico;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Obtener IDs de estados una sola vez
        $estados = Estado::whereIn('nombre', [
            'Nuevo', 'Usado', 'Reparado', 'Baja',
            'No funciona', 'Perdido', 'Recambio', 'Temporal'
        ])->pluck('id', 'nombre');

        // Contadores básicos
        $cant_usuarios = User::count();
        $cant_roles = Role::count();
        $cant_camaras = Camara::count();

        // Equipos por estado
        $cant_equipos_sin_funcionar = Equipo::where('estado_id', $estados['No funciona'])->count();
        $cant_equipos_temporales = Equipo::where('estado_id', $estados['Temporal'])->count();
        $cant_equipos_baja = Equipo::whereIn('estado_id', [
            $estados['Baja'],
            $estados['Recambio'],
            $estados['Perdido']
        ])->count();
        $cant_equipos_funcionales = Equipo::whereIn('estado_id', [
            $estados['Nuevo'],
            $estados['Usado'],
            $estados['Reparado']
        ])->count();

        // Equipos por proveedor
        $cant_equipos_provisto_por_pg = Equipo::where('provisto', 'Patagonia Green')->count();
        $cant_equipos_provisto_por_telecom = Equipo::where('provisto', 'Telecom')->count();

        // Equipos en Stock 911
        $stock911 = Recurso::where('nombre', 'Stock 911')->first();
        $cant_equipos_en_stock = $stock911
            ? FlotaGeneral::where('recurso_id', $stock911->id)
                ->where('destino_id', $stock911->destino_id)
                ->where('fecha_desasignacion', null)
                ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
                ->count()
            : 0;

        // Equipos en Departamental Paraná
        $departamentalParana = Destino::where('nombre', 'Departamental Paraná (JDP)')->first();
        $cant_equipos_en_departamental = $departamentalParana
            ? FlotaGeneral::whereHas('destino', fn($q) =>
                $q->where('departamental_id', $departamentalParana->departamental_id)
              )
              ->whereExists(fn($q) =>
                $q->select(DB::raw(1))
                  ->from('historico')
                  ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                  ->where('historico.fecha_desasignacion', null)
              )
              ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
              ->count()
            : 0;

        // Equipos en División 911
        $division911 = Destino::where('nombre', 'División 911 y Videovigilancia')->first();
        $cant_equipos_en_div_911 = $division911
            ? FlotaGeneral::whereHas('destino', fn($q) =>
                $q->where('division_id', $division911->division_id)
              )
              ->whereExists(fn($q) =>
                $q->select(DB::raw(1))
                  ->from('historico')
                  ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                  ->where('historico.fecha_desasignacion', null)
              )
              ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
              ->count()
            : 0;

        // Equipos en División Bancaria
        $divisionBancaria = Destino::where('nombre', 'División Seguridad Urbana y Bancaria')->first();
        $cant_equipos_en_div_bancaria = $divisionBancaria
            ? FlotaGeneral::whereHas('destino', fn($q) =>
                $q->where('division_id', $divisionBancaria->division_id)
              )
              ->whereExists(fn($q) =>
                $q->select(DB::raw(1))
                  ->from('historico')
                  ->whereRaw('flota_general.equipo_id = historico.equipo_id')
                  ->where('historico.fecha_desasignacion', null)
              )
              ->whereHas('equipo', fn($q) => $q->where('estado_id', '!=', $estados['No funciona']))
              ->count()
            : 0;

        // Equipos en Patagonia Green
        $destPG = Destino::where('nombre', 'Patagonia Green')->first();
        $cant_equipos_en_pg = $destPG
            ? Historico::where('destino_id', $destPG->id)
                ->where('fecha_desasignacion', null)
                ->count()
            : 0;

        // Desinstalaciones parciales
        $cant_desinstalaciones = Historico::whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('historico')
                    ->groupBy('equipo_id');
            })
            ->where('tipo_movimiento_id', function ($subquery) {
                $subquery->select('id')
                    ->from('tipo_movimiento')
                    ->where('nombre', 'Desinstalación Parcial');
            })
            ->count();

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
            'cant_equipos_temporales',
            'cant_equipos_baja',
            'cant_equipos_en_div_bancaria'
        ));
    }
}
