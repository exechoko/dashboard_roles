<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AudioTranscriptionController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('TRANSCRIPTION_API_URL', 'https://gvs9j8cd3a.execute-api.us-east-1.amazonaws.com/prod');
    }

    /**
     * Mostrar la página principal de transcripción
     */
    public function index()
    {
        return view('transcription.index');
    }

    /**
     * Generar URL de carga para el archivo de audio
     */
    public function generateUploadUrl(Request $request)
    {
        try {
            $request->validate([
                'fileName' => 'required|string',
                'contentType' => 'required|string'
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->apiBaseUrl . '/generate-upload-url', [
                        'fileName' => $request->fileName,
                        'contentType' => $request->contentType
                    ]);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('Error generando URL de carga: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Subir archivo a S3 usando la URL prefirmada
     */
    public function uploadFile(Request $request)
    {
        try {
            $request->validate([
                'uploadUrl' => 'required|url',
                'file' => 'required|file|mimes:mp3,wav,m4a,ogg|max:51200'
            ]);

            $file = $request->file('file');
            $uploadUrl = $request->uploadUrl;

            // Leer archivo como binario
            $fileContents = file_get_contents($file->getRealPath());

            // Subir el archivo con PUT, como binario
            $response = Http::withHeaders([
                'Content-Type' => $file->getMimeType()
            ])->withBody($fileContents, $file->getMimeType())
                ->put($uploadUrl);

            return $response->successful()
                ? response()->json(['success' => true, 'message' => 'Archivo subido exitosamente'])
                : response()->json(['error' => 'Error al subir el archivo'], $response->status());

        } catch (\Exception $e) {
            Log::error('Error subiendo archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener resultados de transcripción
     */
    public function getResults(Request $request)
    {
        try {
            $query = $request->get('getAll') ? ['getAll' => 'true'] : [];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->get($this->apiBaseUrl . '/results', $query);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('Error obteniendo resultados: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener resultados específicos por nombre de archivo
     */
    public function getResultsByFileName(Request $request)
    {
        try {
            $request->validate([
                'fileName' => 'required|string'
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->get($this->apiBaseUrl . '/results', [
                        'fileName' => $request->fileName
                    ]);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('Error obteniendo resultados específicos: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
