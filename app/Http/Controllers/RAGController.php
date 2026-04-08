<?php

namespace App\Http\Controllers;

use App\Services\IAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RAGController extends Controller
{
    public function __construct(private IAService $ia) {}

    public function index(): \Illuminate\View\View
    {
        return view('rag.index');
    }

    /**
     * Estado de los servicios del servidor IA.
     */
    public function estado(): JsonResponse
    {
        return response()->json($this->ia->estadoServicios());
    }

    /**
     * Sube un archivo al RAG y lo indexa con resumen opcional.
     */
    public function cargar(Request $request): JsonResponse
    {
        $request->validate([
            'documento' => 'required|file|mimes:txt,pdf,csv,md|max:20480',
            'resumir'   => 'boolean',
        ]);

        try {
            $resultado = $this->ia->cargarDocumento(
                $request->file('documento'),
                $request->boolean('resumir', true)
            );

            return response()->json([
                'success' => true,
                ...$resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Consulta al RAG.
     */
    public function preguntar(Request $request): JsonResponse
    {
        $request->validate(['pregunta' => 'required|string|max:500']);

        try {
            $respuesta = $this->ia->consultarRAG($request->input('pregunta'));
            return response()->json(['success' => true, 'respuesta' => $respuesta]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Re-indexa todos los documentos de DOCS_DIR.
     */
    public function reindexar(): JsonResponse
    {
        try {
            $total = $this->ia->reindexarRAG();
            return response()->json(['success' => true, 'documentos' => $total]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
