<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebTechCardRequest;
use App\Http\Requests\UpdateWebTechCardRequest;
use App\Models\Auditoria;
use App\Models\WebTechCard;
use App\Services\GeneradorTecnologiaJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class WebTechCardController extends Controller
{
    public function __construct(private GeneradorTecnologiaJs $generador)
    {
        $this->middleware('permission:editar-web-textos');
    }

    public function imagen(string $archivo): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $ruta = $this->directorioImagenes() . DIRECTORY_SEPARATOR . basename($archivo);
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta);
    }

    public function index(): View
    {
        $cards = WebTechCard::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(30);

        return view('web-tecnologia.index', [
            'cards'    => $cards,
            'colores'  => config('landing.tecnologia_colores', []),
        ]);
    }

    public function create(): View
    {
        return view('web-tecnologia.crear', ['colores' => config('landing.tecnologia_colores', [])]);
    }

    public function store(StoreWebTechCardRequest $request): RedirectResponse
    {
        $datos = $this->datos($request->validated());

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->guardarComoWebp($request->file('imagen'));
        }

        $card = WebTechCard::create($datos);

        $this->regenerar();
        $this->auditar($card, 'crear');

        return redirect()->route('web-tecnologia.index')->with('success', 'Card de tecnología creada y publicada en la web.');
    }

    public function edit(WebTechCard $card): View
    {
        return view('web-tecnologia.editar', [
            'card'    => $card,
            'colores' => config('landing.tecnologia_colores', []),
        ]);
    }

    public function update(UpdateWebTechCardRequest $request, WebTechCard $card): RedirectResponse
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

        return redirect()->route('web-tecnologia.index')->with('success', 'Card de tecnología actualizada.');
    }

    public function destroy(WebTechCard $card): RedirectResponse
    {
        if ($card->imagen) {
            $this->eliminarArchivo($card->imagen);
        }

        $this->auditar($card, 'eliminar');
        $card->delete();
        $this->regenerar();

        return redirect()->route('web-tecnologia.index')->with('success', 'Card de tecnología eliminada.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datos(array $validated): array
    {
        return [
            'titulo' => $validated['titulo'],
            'texto'  => $validated['texto'],
            'color'  => $validated['color'],
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
            . str_replace('/', DIRECTORY_SEPARATOR, config('landing.tecnologia_img_dir'));
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

    private function auditar(WebTechCard $card, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'web_tech_cards',
            'accion'       => $accion,
            'cambios'      => json_encode(['id' => $card->id, 'titulo' => $card->titulo], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
