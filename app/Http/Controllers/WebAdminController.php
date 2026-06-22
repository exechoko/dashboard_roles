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

        return $this->renderPreview($datos['pagina'], $datos['textos'] ?? []);
    }

    /**
     * Vista previa genérica (GET) de cualquier página de la web, reflejando los
     * datos locales ya guardados (contadores, cards, dependencias, galería, etc.).
     */
    public function previewWeb(Request $request): Response
    {
        $datos = $request->validate(['pagina' => 'required|string']);

        return $this->renderPreview($datos['pagina'], []);
    }

    /**
     * Lee la página local, hace que los assets carguen del sitio publicado y
     * reemplaza los datos publicados por los locales (más, opcionalmente, los
     * textos enviados sin guardar). Así la previa coincide con lo que se publicará.
     *
     * @param  array<string, string|null>  $textosOverride
     */
    private function renderPreview(string $pagina, array $textosOverride): Response
    {
        if (! preg_match('/^[a-z0-9-]+\.html$/i', $pagina)) {
            abort(404);
        }

        $archivo = rtrim((string) config('landing.path'), '/\\') . DIRECTORY_SEPARATOR . $pagina;
        if (! is_file($archivo)) {
            abort(404, 'No se encontró el archivo de la página en el sitio.');
        }

        $html = (string) file_get_contents($archivo);
        $html = $this->inyectarBase($html);

        // Reemplazar cada archivo de datos publicado por su versión local (la BD).
        foreach ([
            'js/config-datos.js'      => 'config_datos_js',
            'js/historia-data.js'     => 'historia_js',
            'js/tecnologia-data.js'   => 'tecnologia_js',
            'js/dependencias-data.js' => 'dependencias_js',
            'js/galeria-data.js'      => 'galeria_js',
        ] as $src => $configKey) {
            $contenido = $this->contenidoLocal($configKey);
            if ($contenido !== null) {
                $html = $this->reemplazarScript($html, $src, "<script>\n{$contenido}\n</script>");
            }
        }

        // Textos: reemplazar config-textos.js por los valores actuales (con override).
        $html = $this->reemplazarScript($html, 'js/config-textos.js', $this->scriptTextos($textosOverride));

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Inserta el <base> al sitio publicado y un parche de fetch para que las
     * noticias también se lean del archivo local (evita CORS y refleja la BD).
     */
    private function inyectarBase(string $html): string
    {
        if (stripos($html, '<base') !== false) {
            return $html;
        }

        $base = rtrim((string) config('landing.url'), '/') . '/';
        $inyeccion = "\n    <base href=\"{$base}\">";

        $noticias = config('landing.noticias_json');
        $rutaNoticias = $noticias
            ? rtrim((string) config('landing.path'), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $noticias)
            : null;

        if ($rutaNoticias && is_file($rutaNoticias)) {
            $jsonLiteral = json_encode((string) file_get_contents($rutaNoticias), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
            $inyeccion .= "\n    <script>(function(){var d={$jsonLiteral};var of=window.fetch;window.fetch=function(u){try{if(typeof u==='string'&&u.indexOf('noticias.json')>-1){return Promise.resolve(new Response(d,{status:200,headers:{'Content-Type':'application/json'}}));}}catch(e){}return of.apply(this,arguments);};})();</script>";
        }

        return preg_replace('/<head(\s[^>]*)?>/i', '$0' . $inyeccion, $html, 1) ?? $html;
    }

    /**
     * Construye el script que aplica los textos (guardados + override) por
     * encima de los publicados.
     *
     * @param  array<string, string|null>  $override
     */
    private function scriptTextos(array $override): string
    {
        $json = json_encode(
            $this->valoresPreview($override),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return <<<HTML
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
     * Devuelve el contenido del archivo JS local apuntado por config('landing.<key>').
     */
    private function contenidoLocal(string $configKey): ?string
    {
        $relativo = config("landing.{$configKey}");
        if (! $relativo) {
            return null;
        }

        $ruta = rtrim((string) config('landing.path'), '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativo);

        return is_file($ruta) ? (string) file_get_contents($ruta) : null;
    }

    /**
     * Reemplaza el <script src="..."> indicado por contenido inline. Si no está
     * presente, inyecta el contenido antes de </body> como respaldo.
     */
    private function reemplazarScript(string $html, string $src, string $reemplazo): string
    {
        $patron = '#<script\s+src=(["\'])' . preg_quote($src, '#') . '\1\s*></script>#i';

        if (preg_match($patron, $html)) {
            return (string) preg_replace($patron, addcslashes($reemplazo, '\\$'), $html, 1);
        }

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $reemplazo . "\n</body>", $html);
        }

        return $html . $reemplazo;
    }
}
