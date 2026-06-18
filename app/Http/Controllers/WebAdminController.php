<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebContadoresRequest;
use App\Models\Auditoria;
use App\Models\WebConfigDato;
use App\Services\GeneradorConfigDatos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class WebAdminController extends Controller
{
    public function __construct(private GeneradorConfigDatos $generador)
    {
    }

    /**
     * Formulario de edición de los contadores de la web.
     */
    public function editContadores(): View
    {
        $datos = WebConfigDato::comoMapa();

        return view('web-admin.contadores', [
            'datos'  => $datos,
            'meses'  => $datos['meses2026'] ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
        ]);
    }

    /**
     * Guarda los contadores en BD y regenera config-datos.js.
     */
    public function updateContadores(WebContadoresRequest $request): RedirectResponse
    {
        $valores = $request->validated();

        DB::transaction(function () use ($valores): void {
            foreach ($valores as $clave => $valor) {
                WebConfigDato::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
            }
        });

        try {
            $this->generador->generar(WebConfigDato::comoMapa());
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Se guardaron los datos pero no se pudo actualizar la web: ' . $e->getMessage());
        }

        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_config_datos',
            'accion'       => 'editar',
            'cambios'      => json_encode($valores, JSON_UNESCAPED_UNICODE),
        ]);

        return back()->with('success', 'Contadores actualizados. La web ya muestra los nuevos valores.');
    }
}
