<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebContadoresRequest;
use App\Http\Requests\WebTextosRequest;
use App\Models\Auditoria;
use App\Models\WebConfigDato;
use App\Models\WebTexto;
use App\Services\GeneradorConfigDatos;
use App\Services\GeneradorConfigTextos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class WebAdminController extends Controller
{
    public function __construct(
        private GeneradorConfigDatos $generador,
        private GeneradorConfigTextos $generadorTextos,
    ) {
    }

    /**
     * Formulario de edición de los contadores de la web.
     */
    public function editContadores(): View
    {
        $datos = WebConfigDato::comoMapa();
        $ahora = now();

        return view('web-admin.contadores', [
            'datos'      => $datos,
            'meses'      => $datos['meses2026'] ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            'anioActual' => $ahora->year,
            'mesActual'  => ucfirst($ahora->locale('es')->translatedFormat('F')) . ' ' . $ahora->year,
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

    /**
     * Formulario de edición de los textos de la web.
     */
    public function editTextos(): View
    {
        return view('web-admin.textos', [
            'catalogoPorGrupo' => collect(config('textos_web', []))
                ->map(fn ($grupo) => $grupo)
                ->all(),
            'valores' => GeneradorConfigTextos::valores(),
        ]);
    }

    /**
     * Guarda los textos en BD y regenera config-textos.js.
     */
    public function updateTextos(WebTextosRequest $request): RedirectResponse
    {
        $textos = $request->validated()['textos'];

        DB::transaction(function () use ($textos): void {
            foreach ($textos as $clave => $valor) {
                WebTexto::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
            }
        });

        try {
            $this->generadorTextos->generar();
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Se guardaron los textos pero no se pudo actualizar la web: ' . $e->getMessage());
        }

        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_textos',
            'accion'       => 'editar',
            'cambios'      => json_encode(array_keys($textos), JSON_UNESCAPED_UNICODE),
        ]);

        return back()->with('success', 'Textos actualizados. La web ya muestra los cambios.');
    }
}
