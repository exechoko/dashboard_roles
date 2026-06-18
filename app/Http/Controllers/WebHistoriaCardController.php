<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebHistoriaCardRequest;
use App\Http\Requests\UpdateWebHistoriaCardRequest;
use App\Models\Auditoria;
use App\Models\WebHistoriaCard;
use App\Services\GeneradorHistoriaJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class WebHistoriaCardController extends Controller
{
    public function __construct(private GeneradorHistoriaJs $generador)
    {
        $this->middleware('permission:editar-web-historia');
    }

    public function imagen(string $archivo): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $ruta = $this->directorioImagenes() . DIRECTORY_SEPARATOR . basename($archivo);
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta);
    }

    public function index(): View
    {
        $cards = WebHistoriaCard::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(30);

        return view('web-historia.index', compact('cards'));
    }

    public function create(): View
    {
        return view('web-historia.crear');
    }

    public function store(StoreWebHistoriaCardRequest $request): RedirectResponse
    {
        $datos = $this->datos($request->validated());

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->guardarComoWebp($request->file('imagen'));
        }

        $card = WebHistoriaCard::create($datos);

        $this->regenerar();
        $this->auditar($card, 'crear');

        return redirect()->route('web-historia.index')->with('success', 'Tarjeta de historia creada y publicada en la web.');
    }

    public function edit(WebHistoriaCard $card): View
    {
        return view('web-historia.editar', compact('card'));
    }

    public function update(UpdateWebHistoriaCardRequest $request, WebHistoriaCard $card): RedirectResponse
    {
        $datos = $this->datos($request->validated());

        if ($request->boolean('quitar_imagen') && $card->imagen) {
            $this->eliminarArchivo($card->imagen);
            $datos['imagen'] = null;
        }

        if ($request->hasFile('imagen')) {
            if ($card->imagen) {
                $this->eliminarArchivo($card->imagen);
            }
            $datos['imagen'] = $this->guardarComoWebp($request->file('imagen'));
        }

        $card->update($datos);

        $this->regenerar();
        $this->auditar($card, 'editar');

        return redirect()->route('web-historia.index')->with('success', 'Tarjeta de historia actualizada.');
    }

    public function destroy(WebHistoriaCard $card): RedirectResponse
    {
        if ($card->imagen) {
            $this->eliminarArchivo($card->imagen);
        }

        $this->auditar($card, 'eliminar');
        $card->delete();
        $this->regenerar();

        return redirect()->route('web-historia.index')->with('success', 'Tarjeta de historia eliminada.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datos(array $validated): array
    {
        return [
            'anio'   => $validated['anio'],
            'titulo' => $validated['titulo'],
            'texto'  => $validated['texto'],
            'tag'    => $validated['tag'] ?? null,
            'orden'  => (int) ($validated['orden'] ?? 0),
        ];
    }

    /**
     * Convierte la imagen subida a WebP (redimensionada). Devuelve el nombre del archivo.
     */
    private function guardarComoWebp(UploadedFile $archivo): string
    {
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

            return $nombre;
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

        return $nombre;
    }

    private function directorioImagenes(): string
    {
        return rtrim(config('landing.path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.historia_img_dir'));
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
            session()->flash('error', 'Se guardó pero no se pudo actualizar la web: ' . $e->getMessage());
        }
    }

    private function auditar(WebHistoriaCard $card, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_historia_cards',
            'accion'       => $accion,
            'cambios'      => json_encode(['id' => $card->id, 'anio' => $card->anio, 'titulo' => $card->titulo], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
