<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalcularHashArchivoRequest;
use App\Services\ArchivoHashService;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ArchivoHashController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-hash-archivo');
    }

    public function index(): View
    {
        return view('herramientas.hash-archivo');
    }

    public function calcular(CalcularHashArchivoRequest $request, ArchivoHashService $archivoHashService): View
    {
        $archivo = $request->file('archivo');

        if (!$archivo instanceof UploadedFile) {
            abort(422, 'No se recibió un archivo válido.');
        }

        return view('herramientas.hash-archivo', [
            'resultado' => [
                'nombre' => $archivo->getClientOriginalName(),
                'tamano' => (int) $archivo->getSize(),
                'hash' => $archivoHashService->calcularSha256($archivo),
            ],
        ]);
    }
}
