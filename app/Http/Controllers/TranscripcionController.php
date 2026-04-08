<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:51200', // 50MB
        ]);

        $audio = $request->file('audio');
        $url   = config('services.ia.whisper_url') . '/inference';

        try {
            $response = Http::timeout(300)
                ->attach('file', fopen($audio->getRealPath(), 'r'), $audio->getClientOriginalName())
                ->post($url, ['language' => 'es']);

            if ($response->successful()) {
                return response()->json([
                    'success'  => true,
                    'text'     => $response->json('text'),
                    'duracion' => $response->json('duration'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en Whisper (' . $response->status() . '): ' . $response->body(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar al servidor IA: ' . $e->getMessage(),
            ], 500);
        }
    }

}
