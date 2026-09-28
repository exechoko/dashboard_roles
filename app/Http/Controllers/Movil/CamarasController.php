<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\Camara;
use App\Services\CamaraReinicioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CamarasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-camara');
        $this->middleware('permission:reiniciar-camara')->only('reiniciar');
    }

    public function index(Request $request)
    {
        $texto = trim((string) $request->get('texto'));

        $camaras = Camara::with(['tipoCamara', 'sitio.destino'])
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($q) use ($texto) {
                    $q->where('nombre', 'like', "%{$texto}%")
                        ->orWhereHas('sitio', function ($sitio) use ($texto) {
                            $sitio->where('nombre', 'like', "%{$texto}%");
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        $resumenPorTipo = $this->resumenPorTipo();

        return view('movil.camaras.index', compact('camaras', 'texto', 'resumenPorTipo'));
    }

    public function show(Camara $camara)
    {
        $camara->load(['tipoCamara', 'sitio.destino', 'camaraFisica']);

        // Vuelve a la búsqueda tal cual quedó (texto, página), no al listado
        // vacío: solo se confía en la URL anterior si de verdad viene del
        // listado de cámaras.
        $volver = str_starts_with(url()->previous(), route('movil.camaras.index'))
            ? url()->previous()
            : route('movil.camaras.index');

        return view('movil.camaras.show', compact('camara', 'volver'));
    }

    /**
     * Reinicia la cámara vía el cgi de reboot del fabricante (Dahua), pegándole
     * al equipo desde el servidor (no desde el navegador del celular) para no
     * depender de que el dispositivo móvil tenga alcance de red a la cámara.
     */
    public function reiniciar(Camara $camara, CamaraReinicioService $camaraReinicioService): JsonResponse
    {
        $camara->loadMissing('tipoCamara');

        $resultado = $camaraReinicioService->reiniciar($camara);

        if ($resultado['ok']) {
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => $resultado['mensaje']], $resultado['status'] === null ? 422 : 502);
    }

    /**
     * Cantidades de cámaras activas por tipo, mismo criterio que la pantalla
     * de escritorio (Cámaras - Administración): sólo sitios activos, y el
     * total/canales excluyen los tótems BDE.
     *
     * @return array{fijas: int, fijas_fr: int, fijas_lpr: int, domos: int, domos_duales: int, bde: int, total: int, canales: int}
     */
    private function resumenPorTipo(): array
    {
        $contarPorTipo = fn (string ...$tipos): int => Camara::whereHas('tipoCamara', function ($query) use ($tipos) {
            $query->whereIn('tipo', $tipos);
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();

        $camarasActivas = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', '!=', 'BDE (Totem)');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->with('tipoCamara')
            ->get();

        return [
            'fijas'        => $contarPorTipo('Fija'),
            'fijas_fr'     => $contarPorTipo('Fija - FR'),
            'fijas_lpr'    => $contarPorTipo('Fija - LPR', 'Fija - LPR NV', 'Fija - LPR AV'),
            'domos'        => $contarPorTipo('Domo'),
            'domos_duales' => $contarPorTipo('Domo Dual'),
            'bde'          => $contarPorTipo('BDE (Totem)'),
            'total'        => $camarasActivas->count(),
            'canales'      => (int) $camarasActivas->sum(fn (Camara $camara) => $camara->tipoCamara->canales ?? 0),
        ];
    }
}
