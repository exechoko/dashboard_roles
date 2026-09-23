<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalSeccion;

class PersonalController extends Controller
{
    public const SECCION_TECNICA = 'Sección Técnica y Desarrollo';

    public function index()
    {
        $funcionarios = PersonalSeccion::activos()
            ->enSecciones([self::SECCION_TECNICA])
            ->with('personal')
            ->get()
            ->sortBy(fn (PersonalSeccion $ps) => Personal::pesoJerarquia($ps->personal?->jerarquia))
            ->values();

        if (request()->expectsJson()) {
            return $funcionarios->map(fn (PersonalSeccion $ps) => [
                'id' => $ps->personal_id,
                'nombre' => $ps->personal?->nombre,
                'apellido' => $ps->personal?->apellido,
                'lp' => $ps->personal?->lp,
                'jerarquia' => $ps->personal?->jerarquia,
                'en_licencia' => $ps->en_licencia,
            ]);
        }

        return view('tareas.personal-efectivo.index');
    }
}
