<?php

namespace App\Http\Controllers;

use App\Models\TranscripcionJob;
use Illuminate\Http\Request;

class TranscripcionController extends Controller
{
    public function index()
    {
        return view('transcribir.index');
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:51200',
        ]);

        // Guardar el audio en disco (storage/app/transcripciones_temp/)
        $path = $request->file('audio')->store('transcripciones_temp');

        // Crear registro del job
        $job = TranscripcionJob::create([
            'audio_path' => $path,
            'status'     => 'pending',
        ]);

        // Lanzar el proceso en background (sin bloquear la respuesta HTTP)
        $this->lanzarEnBackground($job->id);

        // Responder inmediatamente con el job_id para que el frontend haga polling
        return response()->json([
            'success' => true,
            'job_id'  => $job->id,
            'status'  => 'pending',
        ]);
    }

    public function estado($jobId)
    {
        $job = TranscripcionJob::findOrFail($jobId);

        return response()->json([
            'job_id'   => $job->id,
            'status'   => $job->status,
            'text'     => $job->result_text,
            'duracion' => $job->duration_seconds,
            'error'    => $job->error_message,
        ]);
    }

    private function lanzarEnBackground(int $jobId): void
    {
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "start /B \"\" \"{$php}\" \"{$artisan}\" transcribir:proceso {$jobId}";
            popen($cmd, 'r');
        } else {
            $cmd = "\"{$php}\" \"{$artisan}\" transcribir:proceso {$jobId} > /dev/null 2>&1 &";
            exec($cmd);
        }
    }
}
