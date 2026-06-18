<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoticiaRequest;
use App\Http\Requests\UpdateNoticiaRequest;
use App\Models\Auditoria;
use App\Models\Noticia;
use App\Models\NoticiaImagen;
use App\Services\GeneradorNoticiasJson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NoticiaController extends Controller
{
    public function __construct(private GeneradorNoticiasJson $generador)
    {
        $this->middleware('permission:crear-noticia|editar-noticia|eliminar-noticia', ['only' => ['index']]);
        $this->middleware('permission:crear-noticia', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-noticia', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-noticia', ['only' => ['destroy']]);
    }

    /**
     * Sirve una imagen de noticia desde la carpeta de la web (para previsualizar en el admin).
     */
    public function imagen(string $archivo): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $ruta = $this->directorioImagenes() . DIRECTORY_SEPARATOR . basename($archivo);
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta);
    }

    public function index(): View
    {
        $noticias = Noticia::query()
            ->with('miniatura')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(10);

        return view('noticias.index', compact('noticias'));
    }

    public function create(): View
    {
        return view('noticias.crear');
    }

    public function store(StoreNoticiaRequest $request): RedirectResponse
    {
        $noticia = Noticia::create($this->datosNoticia($request->validated(), $request->boolean('publicada')));

        $nuevas = $this->guardarImagenes($noticia, $request->file('imagenes', []));
        $this->aplicarMiniatura($noticia, $request->input('miniatura'), $nuevas);
        $this->asegurarMiniatura($noticia);

        $this->regenerar();
        $this->auditar($noticia, 'crear');

        return redirect()->route('noticias.index')->with('success', 'Noticia creada y publicada en la web.');
    }

    public function edit(Noticia $noticia): View
    {
        $noticia->load('imagenes');

        return view('noticias.editar', compact('noticia'));
    }

    public function update(UpdateNoticiaRequest $request, Noticia $noticia): RedirectResponse
    {
        $noticia->update($this->datosNoticia($request->validated(), $request->boolean('publicada')));

        foreach ((array) $request->input('eliminar', []) as $imagenId) {
            $imagen = $noticia->imagenes()->find($imagenId);
            if ($imagen) {
                $this->eliminarArchivo($imagen->archivo);
                $imagen->delete();
            }
        }

        $nuevas = $this->guardarImagenes($noticia, $request->file('imagenes', []));
        $this->aplicarMiniatura($noticia, $request->input('miniatura'), $nuevas);
        $this->asegurarMiniatura($noticia);

        $this->regenerar();
        $this->auditar($noticia, 'editar');

        return redirect()->route('noticias.index')->with('success', 'Noticia actualizada.');
    }

    public function destroy(Noticia $noticia): RedirectResponse
    {
        foreach ($noticia->imagenes as $imagen) {
            $this->eliminarArchivo($imagen->archivo);
        }

        $this->auditar($noticia, 'eliminar');
        $noticia->delete();
        $this->regenerar();

        return redirect()->route('noticias.index')->with('success', 'Noticia eliminada.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datosNoticia(array $validated, bool $publicada): array
    {
        return [
            'titulo'            => $validated['titulo'],
            'bajada'            => $validated['bajada'] ?? null,
            'cuerpo'            => $validated['cuerpo'],
            'fecha_publicacion' => $validated['fecha_publicacion'],
            'publicada'         => $publicada,
        ];
    }

    /**
     * Mueve los archivos subidos a la carpeta de la web y crea los registros.
     *
     * @param  array<int, UploadedFile>  $archivos
     * @return array<int, NoticiaImagen>
     */
    private function guardarImagenes(Noticia $noticia, array $archivos): array
    {
        if (empty($archivos)) {
            return [];
        }

        $directorio = $this->directorioImagenes();
        if (! is_dir($directorio)) {
            @mkdir($directorio, 0775, true);
        }

        $orden = (int) $noticia->imagenes()->max('orden');
        $creadas = [];

        foreach ($archivos as $archivo) {
            $nombre = $this->guardarComoWebp($archivo, $directorio);

            $creadas[] = $noticia->imagenes()->create([
                'archivo'      => $nombre,
                'es_miniatura' => false,
                'orden'        => ++$orden,
            ]);
        }

        return $creadas;
    }

    /**
     * Convierte la imagen subida a WebP (más liviana), redimensionándola si
     * supera el ancho máximo. Si la conversión falla, guarda el original.
     */
    private function guardarComoWebp(UploadedFile $archivo, string $directorio): string
    {
        $imagen = $this->crearImagenGd($archivo);

        if ($imagen === false) {
            $nombre = Str::uuid()->toString() . '.' . $archivo->getClientOriginalExtension();
            $archivo->move($directorio, $nombre);

            return $nombre;
        }

        $maxAncho = (int) config('landing.noticias_img_max_ancho');
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
        imagewebp($imagen, $directorio . DIRECTORY_SEPARATOR . $nombre, (int) config('landing.noticias_img_calidad'));
        imagedestroy($imagen);

        return $nombre;
    }

    /**
     * @return \GdImage|false
     */
    private function crearImagenGd(UploadedFile $archivo): \GdImage|false
    {
        $ruta = $archivo->getPathname();

        return match ($archivo->getClientMimeType()) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($ruta),
            'image/png'               => @imagecreatefrompng($ruta),
            'image/webp'              => @imagecreatefromwebp($ruta),
            default                   => false,
        };
    }

    /**
     * Marca como miniatura la imagen seleccionada (token "e{id}" o "n{indice}").
     *
     * @param  array<int, NoticiaImagen>  $nuevas
     */
    private function aplicarMiniatura(Noticia $noticia, ?string $token, array $nuevas): void
    {
        if (! $token) {
            return;
        }

        $tipo = substr($token, 0, 1);
        $valor = substr($token, 1);

        $objetivo = match ($tipo) {
            'e'     => $noticia->imagenes()->find($valor),
            'n'     => $nuevas[(int) $valor] ?? null,
            default => null,
        };

        if (! $objetivo) {
            return;
        }

        $noticia->imagenes()->update(['es_miniatura' => false]);
        $objetivo->update(['es_miniatura' => true]);
    }

    /**
     * Garantiza que, si hay imágenes, al menos una sea la miniatura.
     */
    private function asegurarMiniatura(Noticia $noticia): void
    {
        if ($noticia->imagenes()->where('es_miniatura', true)->exists()) {
            return;
        }

        $primera = $noticia->imagenes()->orderBy('orden')->first();
        $primera?->update(['es_miniatura' => true]);
    }

    private function directorioImagenes(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.noticias_img_dir'));
    }

    private function eliminarArchivo(string $nombre): void
    {
        $ruta = $this->directorioImagenes() . DIRECTORY_SEPARATOR . $nombre;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function regenerar(): void
    {
        try {
            $this->generador->generar();
        } catch (Throwable $e) {
            session()->flash('error', 'Se guardó la noticia pero no se pudo actualizar la web: ' . $e->getMessage());
        }
    }

    private function auditar(Noticia $noticia, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'noticias',
            'accion'       => $accion,
            'cambios'      => json_encode(['id' => $noticia->id, 'titulo' => $noticia->titulo], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
