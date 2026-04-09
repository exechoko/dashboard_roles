<?php

namespace App\Http\Controllers;

use App\Models\TranscripcionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $log  = Log::channel('transcripciones');
        $file = $request->file('audio');

        // Guardar el audio en disco (storage/app/transcripciones_temp/)
        $path = $file->store('transcripciones_temp');

        // Crear registro del job
        $job = TranscripcionJob::create([
            'audio_path' => $path,
            'status'     => 'pending',
        ]);

        $log->info('Solicitud de transcripción recibida.', [
            'job_id'         => $job->id,
            'archivo'        => $file->getClientOriginalName(),
            'mime'           => $file->getClientMimeType(),
            'tamano_bytes'   => $file->getSize(),
            'ip'             => $request->ip(),
        ]);

        // El scheduler procesa los jobs pendientes cada minuto (transcribir:pendientes)
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

}
