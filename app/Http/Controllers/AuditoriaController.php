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
        $accion = $request->get('accion');
        $usuario = $request->get('usuario');
        $fecha_desde = $request->get('fecha_desde');
        $fecha_hasta = $request->get('fecha_hasta');

        // Query base
        $query = Auditoria::query();

        // Filtro por texto general (usa el índice FULLTEXT de "cambios" en vez de LIKE '%...%',
        // que forzaba un escaneo completo de la tabla en cada búsqueda).
        if ($texto) {
            $booleanQuery = self::construirConsultaBooleana($texto);

            if ($booleanQuery !== null) {
                $query->whereRaw('MATCH(cambios) AGAINST (? IN BOOLEAN MODE)', [$booleanQuery]);
            }
        }

        // Filtro por tabla
        if ($tabla) {
            $query->where('nombre_tabla', $tabla);
        }

        // Filtro por acción
        if ($accion) {
            $query->where('accion', $accion);
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

        $acciones = Auditoria::select('accion')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion');

        return view('auditoria.index', compact(
            'auditorias',
            'texto',
            'tabla',
            'accion',
            'usuario',
            'fecha_desde',
            'fecha_hasta',
            'tablas',
            'acciones',
            'usuarios'
        ));
    }

    /**
     * Convierte el texto ingresado por el usuario en una consulta para
     * MATCH...AGAINST en modo booleano, agregando '*' a cada palabra para
     * que funcione como búsqueda por prefijo (similar a LIKE 'palabra%').
     *
     * Las palabras de menos de 3 caracteres se descartan porque
     * innodb_ft_min_token_size no las indexa. Devuelve null si no queda
     * ninguna palabra utilizable.
     */
    private static function construirConsultaBooleana(string $texto): ?string
    {
        $palabras = preg_split('/[^\p{L}\p{N}_]+/u', $texto, -1, PREG_SPLIT_NO_EMPTY);

        $terminos = collect($palabras)
            ->filter(fn (string $palabra) => mb_strlen($palabra) >= 3)
            ->map(fn (string $palabra) => '+' . $palabra . '*')
            ->implode(' ');

        return $terminos === '' ? null : $terminos;
    }
}
