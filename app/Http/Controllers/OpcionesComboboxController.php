<?php

namespace App\Http\Controllers;

use App\Services\OpcionesComboboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpcionesComboboxController extends Controller
{
    public function __construct(private OpcionesComboboxService $opciones)
    {
    }

    /**
     * Endpoint AJAX (paginado) de los catálogos del componente combobox-ajax.
     * La autorización y los catálogos permitidos se definen por ruta, según el módulo que los consume.
     */
    public function buscar(Request $request, string $catalogo): JsonResponse
    {
        $termino = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return response()->json($this->opciones->buscar($catalogo, $termino, $perPage, $request->query()));
    }
}
