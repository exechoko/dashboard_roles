<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class TranscripcionController extends Controller
{
    public function index()
    {
        return view('transcribir.index');
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:10240' // 10MB max
        ]);

        try {
            $audio = $request->file('audio');

            // Enviar el archivo al microservicio Whisper
            $response = Http::attach(
                'file',
                fopen($audio->getRealPath(), 'r'),
                $audio->getClientOriginalName()
            )->post('http://localhost:8010/transcribe'); // Asumiendo que el contenedor expone el puerto 8010

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'text' => $response->json('text')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en el microservicio: ' . $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el audio: ' . $e->getMessage()
            ], 500);
        }
    }

}
