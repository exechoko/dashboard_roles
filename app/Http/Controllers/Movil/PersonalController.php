<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-personal');
    }

    public function index(Request $request): View
    {
        $texto = trim((string) $request->get('texto'));
        $soloLicencia = $request->boolean('licencia');

        $personales = Personal::query()
            ->when($texto !== '', function (Builder $query) use ($texto): void {
                $query->where(function (Builder $q) use ($texto): void {
                    $q->where('apellido', 'like', "%{$texto}%")
                        ->orWhere('nombre', 'like', "%{$texto}%")
                        ->orWhere('lp', 'like', "%{$texto}%")
                        ->orWhere('dni', 'like', "%{$texto}%");
                });
            })
            ->when($soloLicencia, function (Builder $query): void {
                $query->whereHas('licencias', function (Builder $licencias): void {
                    $licencias->vigentes();
                });
            })
            ->with('licencias')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        $totalActivos = Personal::count();
        $totalDeLicencia = Personal::whereHas('licencias', function (Builder $licencias): void {
            $licencias->vigentes();
        })->count();

        return view('movil.personal.index', compact('personales', 'texto', 'soloLicencia', 'totalActivos', 'totalDeLicencia'));
    }

    public function show(Personal $personal): View
    {
        $personal->load([
            'tipoArma',
            'licencias' => fn ($query) => $query->orderByDesc('fecha_inicio')->orderByDesc('id'),
        ]);

        return view('movil.personal.show', compact('personal'));
    }
}
