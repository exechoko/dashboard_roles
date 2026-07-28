<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalcularHashArchivoRequest;
use App\Http\Requests\RegistrarHistorialHashRequest;
use App\Models\HistorialHashArchivo;
use App\Models\User;
use App\Services\ArchivoHashService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
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
        return view('herramientas.hash-archivo', [
            'historial' => $this->historial(),
        ]);
    }

    public function calcular(CalcularHashArchivoRequest $request, ArchivoHashService $archivoHashService): View
    {
        $archivo = $request->file('archivo');

        if (!$archivo instanceof UploadedFile) {
            abort(422, 'No se recibió un archivo válido.');
        }

        $hash = $archivoHashService->calcularSha256($archivo);
        $usuario = $request->user();

        if (!$usuario instanceof User) {
            abort(403);
        }

        $this->crearRegistro($usuario, $archivo->getClientOriginalName(), $hash);

        return view('herramientas.hash-archivo', [
            'resultado' => [
                'nombre' => $archivo->getClientOriginalName(),
                'tamano' => (int) $archivo->getSize(),
                'hash' => $hash,
            ],
            'historial' => $this->historial(),
        ]);
    }

    public function registrar(RegistrarHistorialHashRequest $request): JsonResponse
    {
        $usuario = $request->user();

        if (!$usuario instanceof User) {
            abort(403);
        }

        $validated = $request->validated();
        $registro = $this->crearRegistro(
            $usuario,
            $validated['nombre_archivo'],
            strtolower($validated['hash']),
            $validated['cifrado_aplicado']
        );

        return response()->json([
            'success' => true,
            'item' => $this->formatearRegistro($registro->load('user')),
        ], 201);
    }

    private function crearRegistro(
        User $usuario,
        string $nombreArchivo,
        string $hash,
        string $cifradoAplicado = 'SHA-256'
    ): HistorialHashArchivo
    {
        return HistorialHashArchivo::create([
            'user_id' => $usuario->id,
            'nombre_archivo' => $nombreArchivo,
            'cifrado_aplicado' => $cifradoAplicado,
            'hash' => strtolower($hash),
        ]);
    }

    private function historial(): LengthAwarePaginator
    {
        return HistorialHashArchivo::query()
            ->with('user')
            ->latest()
            ->paginate(20, ['*'], 'hash_page');
    }

    /**
     * @return array<string, int|string|null>
     */
    private function formatearRegistro(HistorialHashArchivo $registro): array
    {
        $nombreUsuario = trim(implode(' ', array_filter([
            $registro->user?->name,
            $registro->user?->apellido,
        ])));

        return [
            'id' => $registro->id,
            'fecha_hora' => $registro->created_at?->format('d/m/Y H:i:s'),
            'nombre_archivo' => $registro->nombre_archivo,
            'cifrado_aplicado' => $registro->cifrado_aplicado,
            'hash' => $registro->hash,
            'usuario' => $nombreUsuario !== '' ? $nombreUsuario : 'Usuario eliminado',
        ];
    }
}
