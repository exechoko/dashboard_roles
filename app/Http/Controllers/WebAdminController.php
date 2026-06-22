<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebContadoresRequest;
use App\Http\Requests\WebTextosRequest;
use App\Models\Auditoria;
use App\Models\WebConfigDato;
use App\Models\WebTexto;
use App\Services\GeneradorConfigDatos;
use App\Services\GeneradorConfigTextos;
use App\Services\SanitizadorHtmlWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $catalogo = GeneradorConfigTextos::catalogo();

        foreach ($textos as $clave => $valor) {
            if (($catalogo[$clave]['tipo'] ?? 'text') === 'html') {
                $textos[$clave] = SanitizadorHtmlWeb::limpiar($valor);
            }
        }

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

    /**
     * Renderiza una página de la web aplicando los textos del formulario aún
     * SIN guardar, para previsualizar cómo quedará antes de publicar.
     */
    public function previewTextos(Request $request): Response
    {
        $datos = $request->validate([
            'pagina'   => 'required|string',
            'textos'   => 'array',
            'textos.*' => 'nullable|string',
        ]);

        $pagina = $datos['pagina'];
        if (! in_array($pagina, $this->paginasDisponibles(), true)) {
            abort(404);
        }

        $archivo = rtrim((string) config('landing.path'), '/\\') . DIRECTORY_SEPARATOR . $pagina;
        if (! is_file($archivo)) {
            abort(404, 'No se encontró el archivo de la página en el sitio.');
        }

        $valores = $this->valoresPreview($datos['textos'] ?? []);
        $html = $this->inyectarPreview((string) file_get_contents($archivo), $valores);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @return list<string>
     */
    private function paginasDisponibles(): array
    {
        return collect(config('textos_web', []))
            ->pluck('pagina')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mezcla los valores guardados/por defecto con los enviados en el formulario
     * (saneando los bloques HTML igual que al publicar).
     *
     * @param  array<string, string|null>  $enviados
     * @return array<string, string>
     */
    private function valoresPreview(array $enviados): array
    {
        $catalogo = GeneradorConfigTextos::catalogo();
        $valores = GeneradorConfigTextos::valores();

        foreach ($enviados as $clave => $valor) {
            if (! isset($catalogo[$clave]) || $valor === null) {
                continue;
            }
            $valores[$clave] = ($catalogo[$clave]['tipo'] ?? 'text') === 'html'
                ? SanitizadorHtmlWeb::limpiar($valor)
                : $valor;
        }

        return $valores;
    }

    /**
     * Inserta un <base> al sitio (para que carguen CSS/JS/imágenes) y un script
     * final que aplica los textos de la vista previa por encima de los publicados.
     *
     * @param  array<string, string>  $valores
     */
    private function inyectarPreview(string $html, array $valores): string
    {
        $base = rtrim((string) config('landing.url'), '/') . '/';
        $json = json_encode(
            $valores,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if (stripos($html, '<base') === false) {
            $html = preg_replace('/<head(\s[^>]*)?>/i', '$0' . "\n    <base href=\"{$base}\">", $html, 1);
        }

        $script = <<<HTML
<script>
(function () {
    var TEXTOS = {$json};
    document.querySelectorAll('[data-edit]').forEach(function (el) {
        var v = TEXTOS[el.dataset.edit];
        if (v !== undefined && v !== null) { el.textContent = v; }
    });
    document.querySelectorAll('[data-edit-html]').forEach(function (el) {
        var v = TEXTOS[el.dataset.editHtml];
        if (v !== undefined && v !== null) { el.innerHTML = v; }
    });
})();
</script>
HTML;

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $script . "\n</body>", $html);
        }

        return $html . $script;
    }
}
