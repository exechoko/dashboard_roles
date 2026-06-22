<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebGaleriaImagenRequest;
use App\Http\Requests\UpdateWebGaleriaImagenRequest;
use App\Models\Auditoria;
use App\Models\WebGaleriaImagen;
use App\Services\GeneradorGaleriaJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class WebGaleriaImagenController extends Controller
{
    public function __construct(private GeneradorGaleriaJs $generador)
    {
        $this->middleware('permission:editar-web-galeria');
    }

    /**
     * Sirve al panel una imagen del sitio (cualquier ruta dentro de la web),
     * validando que no escape del directorio del sitio.
     */
    public function imagen(): BinaryFileResponse
    {
        $ruta = (string) request('ruta', '');
        $base = realpath(rtrim(config('landing.path'), '/\\'));
        $archivo = realpath($base . DIRECTORY_SEPARATOR . str_replace(['\\', '..'], ['/', ''], $ruta));

        abort_if($base === false || $archivo === false, 404);
        abort_unless(str_starts_with($archivo, $base) && is_file($archivo), 404);

        return response()->file($archivo);
    }

    public function index(): View
    {
        $imagenes = WebGaleriaImagen::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(30);

        return view('web-galeria.index', ['imagenes' => $imagenes]);
    }

    public function create(): View
    {
        return view('web-galeria.crear');
    }

    public function store(StoreWebGaleriaImagenRequest $request): RedirectResponse
    {
        $datos = $this->datos($request->validated());
        $datos['imagen'] = $this->guardarComoWebp($request->file('imagen'));

        $imagen = WebGaleriaImagen::create($datos);

        $this->regenerar();
        $this->auditar($imagen, 'crear');

        return redirect()->route('web-galeria.index')->with('success', 'Imagen agregada y publicada en la galería de la web.');
    }

    public function edit(WebGaleriaImagen $imagen): View
    {
        return view('web-galeria.editar', ['imagen' => $imagen]);
    }

    public function update(UpdateWebGaleriaImagenRequest $request, WebGaleriaImagen $imagen): RedirectResponse
    {
        $datos = $this->datos($request->validated());

        if ($request->hasFile('imagen')) {
            $this->eliminarArchivo($imagen->imagen);
            $datos['imagen'] = $this->guardarComoWebp($request->file('imagen'));
        }

        $imagen->update($datos);

        $this->regenerar();
        $this->auditar($imagen, 'editar');

        return redirect()->route('web-galeria.index')->with('success', 'Imagen de galería actualizada.');
    }

    public function destroy(WebGaleriaImagen $imagen): RedirectResponse
    {
        $this->eliminarArchivo($imagen->imagen);
        $this->auditar($imagen, 'eliminar');
        $imagen->delete();
        $this->regenerar();

        return redirect()->route('web-galeria.index')->with('success', 'Imagen quitada de la galería.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datos(array $validated): array
    {
        return [
            'titulo'    => $validated['titulo'],
            'categoria' => $validated['categoria'] ?? null,
            'orden'     => (int) ($validated['orden'] ?? 0),
        ];
    }

    /**
     * Convierte la imagen subida a WebP (redimensionada) dentro de images/galeria/.
     * Devuelve la ruta relativa al sitio (p. ej. images/galeria/uuid.webp).
     */
    private function guardarComoWebp(UploadedFile $archivo): string
    {
        $imgDir = trim(config('landing.galeria_img_dir'), '/');
        $directorio = $this->directorioImagenes();
        if (! is_dir($directorio)) {
            @mkdir($directorio, 0775, true);
        }

        $imagen = match ($archivo->getClientMimeType()) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($archivo->getPathname()),
            'image/png'               => @imagecreatefrompng($archivo->getPathname()),
            'image/webp'              => @imagecreatefromwebp($archivo->getPathname()),
            default                   => false,
        };

        if ($imagen === false) {
            $nombre = Str::uuid()->toString() . '.' . $archivo->getClientOriginalExtension();
            $archivo->move($directorio, $nombre);

            return $imgDir . '/' . $nombre;
        }

        $maxAncho = (int) config('landing.noticias_img_max_ancho', 1920);
        if ($maxAncho > 0 && imagesx($imagen) > $maxAncho) {
            $escalada = imagescale($imagen, $maxAncho);
            if ($escalada !== false) {
                imagedestroy($imagen);
                $imagen = $escalada;
            }
        }

        imagepalettetotruecolor($imagen);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        $nombre = Str::uuid()->toString() . '.webp';
        imagewebp($imagen, $directorio . DIRECTORY_SEPARATOR . $nombre, (int) config('landing.noticias_img_calidad', 82));
        imagedestroy($imagen);

        return $imgDir . '/' . $nombre;
    }

    private function directorioImagenes(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.galeria_img_dir'));
    }

    /**
     * Borra el archivo físico solo si está dentro de images/galeria/ (no toca
     * imágenes preexistentes del sitio que viven en otras carpetas).
     */
    private function eliminarArchivo(?string $rutaRelativa): void
    {
        if ($rutaRelativa === null) {
            return;
        }

        $imgDir = trim(config('landing.galeria_img_dir'), '/') . '/';
        if (! str_starts_with($rutaRelativa, $imgDir)) {
            return;
        }

        $ruta = rtrim(config('landing.path'), '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function regenerar(): void
    {
        try {
            $this->generador->generar();
        } catch (Throwable $e) {
            session()->flash('error', 'Se guardó pero no se pudo actualizar la web: ' . $e->getMessage());
        }
    }

    private function auditar(WebGaleriaImagen $imagen, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_galeria_imagenes',
            'accion'       => $accion,
            'cambios'      => json_encode(['id' => $imagen->id, 'titulo' => $imagen->titulo], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
