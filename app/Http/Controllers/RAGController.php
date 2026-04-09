<?php

namespace App\Http\Controllers;

use App\Models\RagCargaJob;
use App\Models\RagTematica;
use App\Services\IAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RAGController extends Controller
{
    public function __construct(private IAService $ia) {}

    public function index(): \Illuminate\View\View
    {
        $tematicas = RagTematica::orderBy('nombre')->get();
        return view('rag.index', compact('tematicas'));
    }

    public function estado(): JsonResponse
    {
        return response()->json($this->ia->estadoServicios());
    }

    // ── Temáticas ────────────────────────────────────────────────────────────

    /**
     * Crea una nueva temática localmente y la registra en ChromaDB al primer uso.
     */
    public function crearTematica(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'      => 'required|string|max:80',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $coleccion = RagTematica::slugDesdeNombre($request->input('nombre'));

        if (RagTematica::where('coleccion', $coleccion)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ya existe una temática con ese nombre.'], 422);
        }

        $tematica = RagTematica::create([
            'nombre'      => trim($request->input('nombre')),
            'coleccion'   => $coleccion,
            'descripcion' => $request->input('descripcion'),
        ]);

        return response()->json(['success' => true, 'tematica' => $tematica]);
    }

    /**
     * Elimina una temática localmente (no borra ChromaDB — los documentos quedan en el servidor).
     */
    public function eliminarTematica(string $coleccion): JsonResponse
    {
        $tematica = RagTematica::where('coleccion', $coleccion)->firstOrFail();
        $tematica->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Lista las colecciones del servidor ChromaDB junto con los datos locales.
     */
    public function colecciones(): JsonResponse
    {
        $remotas  = collect($this->ia->listarColecciones())->keyBy('nombre');
        $locales  = RagTematica::orderBy('nombre')->get();

        $resultado = $locales->map(function (RagTematica $t) use ($remotas) {
            $remota = $remotas->get($t->coleccion);
            return [
                'id'          => $t->id,
                'nombre'      => $t->nombre,
                'coleccion'   => $t->coleccion,
                'descripcion' => $t->descripcion,
                'documentos'  => $remota['documentos'] ?? 0,
            ];
        });

        return response()->json(['tematicas' => $resultado]);
    }

    // ── Documentos ───────────────────────────────────────────────────────────

    public function cargar(Request $request): JsonResponse
    {
        $request->validate([
            'documentos'   => 'required|array|min:1|max:5',
            'documentos.*' => 'file|mimes:txt,pdf,csv,md|max:51200',
            'coleccion'    => 'required|string|max:100',
            'resumir'      => 'boolean',
        ]);

        $tematica = RagTematica::where('coleccion', $request->input('coleccion'))->first();
        if (!$tematica) {
            return response()->json(['success' => false, 'message' => 'Temática no encontrada.'], 404);
        }

        $resumir   = $request->boolean('resumir', true);
        $archivos  = $request->file('documentos');
        $resultados = [];

        foreach ($archivos as $file) {
            if (!$resumir) {
                // Sin resumen: síncrono (rápido)
                try {
                    $res = $this->ia->cargarDocumento($file, false, $tematica->coleccion);
                    $resultados[] = [
                        'async'           => false,
                        'archivo'         => $res['archivo'],
                        'documentos_total'=> $res['documentos_total'],
                        'status'          => 'completed',
                    ];
                } catch (\Exception $e) {
                    $resultados[] = [
                        'async'   => false,
                        'archivo' => $file->getClientOriginalName(),
                        'status'  => 'failed',
                        'error'   => $e->getMessage(),
                    ];
                }
            } else {
                // Con resumen: async vía scheduler
                $path = $file->store('rag_temp');
                $job  = RagCargaJob::create([
                    'archivo_path'   => $path,
                    'archivo_nombre' => $file->getClientOriginalName(),
                    'coleccion'      => $tematica->coleccion,
                    'resumir'        => true,
                    'status'         => 'pending',
                ]);
                $resultados[] = [
                    'async'   => true,
                    'job_id'  => $job->id,
                    'archivo' => $file->getClientOriginalName(),
                    'status'  => 'pending',
                ];
            }
        }

        return response()->json(['success' => true, 'archivos' => $resultados]);
    }

    public function estadoCarga(int $jobId): JsonResponse
    {
        $job = RagCargaJob::findOrFail($jobId);

        return response()->json([
            'job_id'          => $job->id,
            'status'          => $job->status,
            'resumen'         => $job->resumen,
            'documentos_total'=> $job->documentos_total,
            'error'           => $job->error_message,
            'archivo'         => $job->archivo_nombre,
            'coleccion'       => $job->coleccion,
        ]);
    }

    public function preguntar(Request $request): JsonResponse
    {
        $request->validate([
            'pregunta'  => 'required|string|max:500',
            'coleccion' => 'required|string|max:100',
        ]);

        try {
            $respuesta = $this->ia->consultarRAG(
                $request->input('pregunta'),
                $request->input('coleccion'),
            );
            return response()->json(['success' => true, 'respuesta' => $respuesta]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reintentarCarga(int $jobId): JsonResponse
    {
        $job = RagCargaJob::findOrFail($jobId);

        if ($job->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'El job no está en estado fallido.'], 422);
        }

        $job->update(['status' => 'pending', 'error_message' => null]);

        return response()->json(['success' => true]);
    }

    public function reindexar(Request $request): JsonResponse
    {
        $coleccion = $request->input('coleccion');
        try {
            $total = $this->ia->reindexarRAG($coleccion ?: null);
            return response()->json(['success' => true, 'documentos' => $total]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
