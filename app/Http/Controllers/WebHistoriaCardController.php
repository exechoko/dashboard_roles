<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebHistoriaCardRequest;
use App\Http\Requests\UpdateWebHistoriaCardRequest;
use App\Models\Auditoria;
use App\Models\WebHistoriaCard;
use App\Services\GeneradorHistoriaJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class WebHistoriaCardController extends Controller
{
    public function __construct(private GeneradorHistoriaJs $generador)
    {
        $this->middleware('permission:editar-web-historia');
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
        $card = WebHistoriaCard::create($this->datos($request->validated()));

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
        $card->update($this->datos($request->validated()));

        $this->regenerar();
        $this->auditar($card, 'editar');

        return redirect()->route('web-historia.index')->with('success', 'Tarjeta de historia actualizada.');
    }

    public function destroy(WebHistoriaCard $card): RedirectResponse
    {
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
