<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-auditoria')->only('index');
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto'));
        $tabla = $request->get('tabla');
        $usuario = $request->get('usuario');
        $fecha_desde = $request->get('fecha_desde');
        $fecha_hasta = $request->get('fecha_hasta');

        // Query base
        $query = Auditoria::query();

        // Filtro por texto general
        if ($texto) {
            $query->where(function ($q) use ($texto) {
                $q->where('accion', 'LIKE', '%' . $texto . '%')
                    ->orWhere('cambios', 'LIKE', '%' . $texto . '%');
            });
        }

        // Filtro por tabla
        if ($tabla) {
            $query->where('nombre_tabla', $tabla);
        }

        // Filtro por usuario
        if ($usuario) {
            $query->where('user_id', $usuario);
        }

        // Filtro por rango de fechas
        if ($fecha_desde) {
            $query->whereDate('created_at', '>=', $fecha_desde);
        }
        if ($fecha_hasta) {
            $query->whereDate('created_at', '<=', $fecha_hasta);
        }

        // Obtener resultados paginados
        $auditorias = $query->orderBy('id', 'desc')->paginate(20);

        // Obtener listas para los filtros
        $tablas = Auditoria::select('nombre_tabla')
            ->distinct()
            ->orderBy('nombre_tabla')
            ->pluck('nombre_tabla');

        $usuarios = User::select('id', 'name', 'apellido')
            ->orderBy('apellido')
            ->get();

        return view('auditoria.index', compact(
            'auditorias',
            'texto',
            'tabla',
            'usuario',
            'fecha_desde',
            'fecha_hasta',
            'tablas',
            'usuarios'
        ));
    }
}
