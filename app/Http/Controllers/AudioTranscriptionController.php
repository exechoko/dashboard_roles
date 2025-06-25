<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
     * Generar URL de carga para el archivo de audio usando cURL
     */
    public function generateUploadUrl(Request $request)
    {
        try {
            $request->validate([
                'fileName' => 'required|string',
                'contentType' => 'required|string'
            ]);

            $url = $this->apiBaseUrl . '/generate-upload-url';
            $data = json_encode([
                'fileName' => $request->fileName,
                'contentType' => $request->contentType
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            // Dentro de cada función que use cURL, después de curl_init()
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            $decodedResponse = json_decode($response, true);
            if (!$decodedResponse) {
                throw new \Exception("Invalid JSON response: " . $response);
            }

            return response()->json($decodedResponse, $httpCode);

        } catch (\Exception $e) {
            Log::error('Error generando URL de carga: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Subir archivo a S3 usando la URL prefirmada con cURL
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
            $filePath = $file->getRealPath();

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_INFILE, fopen($filePath, 'r'));
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: ' . $file->getMimeType()
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            // Dentro de cada función que use cURL, después de curl_init()
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            // Para S3, una respuesta exitosa suele ser un código 200 sin cuerpo
            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json(['success' => true, 'message' => 'Archivo subido exitosamente']);
            } else {
                throw new \Exception("Error al subir el archivo. Código HTTP: $httpCode");
            }

        } catch (\Exception $e) {
            Log::error('Error subiendo archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener resultados de transcripción usando cURL
     */
    public function getResults(Request $request)
    {
        try {
            $query = $request->get('getAll') ? ['getAll' => 'true'] : [];
            $url = $this->apiBaseUrl . '/results?' . http_build_query($query);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            // Dentro de cada función que use cURL, después de curl_init()
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            $decodedResponse = json_decode($response, true);
            if (!$decodedResponse) {
                throw new \Exception("Invalid JSON response: " . $response);
            }

            return response()->json($decodedResponse, $httpCode);

        } catch (\Exception $e) {
            Log::error('Error obteniendo resultados: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener resultados específicos por nombre de archivo usando cURL
     */
    public function getResultsByFileName(Request $request)
    {
        try {
            $request->validate([
                'fileName' => 'required|string'
            ]);

            $url = $this->apiBaseUrl . '/results?' . http_build_query([
                'fileName' => $request->fileName
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            // Dentro de cada función que use cURL, después de curl_init()
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            $decodedResponse = json_decode($response, true);
            if (!$decodedResponse) {
                throw new \Exception("Invalid JSON response: " . $response);
            }

            return response()->json($decodedResponse, $httpCode);

        } catch (\Exception $e) {
            Log::error('Error obteniendo resultados específicos: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /*
     * Obtener el historial de archivos procesados con
     * sus respectivas transcripciones.
     */
    public function getHistorial(Request $request)
    {
        try {
            $url = $this->apiBaseUrl . '/results?' . http_build_query([
                'getAll' => 'true'
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            $decodedResponse = json_decode($response, true);
            if (!$decodedResponse) {
                throw new \Exception("Invalid JSON response: " . $response);
            }

            return response()->json($decodedResponse, $httpCode);

        } catch (\Exception $e) {
            Log::error('Error obteniendo historial completo: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }
}
