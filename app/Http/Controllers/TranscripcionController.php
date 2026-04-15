<?php

namespace App\Http\Controllers;

use App\Models\AudioTranscripcion;
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
            'audio'    => 'required|file|mimes:mp3,wav,m4a,ogg|max:51200',
            'telefono' => 'nullable|string|max:50',
        ]);

        $log  = Log::channel('transcripciones');
        $file = $request->file('audio');

        // Guardar el audio en disco (storage/app/transcripciones_temp/)
        $path = $file->store('transcripciones_temp');

        // Crear registro del job con nombre original y teléfono
        $job = TranscripcionJob::create([
            'audio_path'        => $path,
            'original_filename' => $file->getClientOriginalName(),
            'telefono'          => $request->input('telefono'),
            'status'            => 'pending',
        ]);

        $log->info('Solicitud de transcripción recibida.', [
            'job_id'         => $job->id,
            'archivo'        => $file->getClientOriginalName(),
            'telefono'       => $request->input('telefono'),
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
            'json'     => $job->result_json ? json_decode($job->result_json, true) : null,
            'duracion' => $job->duration_seconds,
            'error'    => $job->error_message,
        ]);
    }

    /**
     * Listar todas las transcripciones guardadas.
     */
    public function historial(Request $request)
    {
        $query = AudioTranscripcion::query();

        if ($request->filled('nombre_archivo')) {
            $query->where('nombre_archivo', 'like', '%' . $request->nombre_archivo . '%');
        }

        if ($request->filled('telefono')) {
            $query->where('telefono', 'like', '%' . $request->telefono . '%');
        }

        $transcripciones = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($transcripciones);
    }

    /**
     * Buscar transcripción por nombre exacto de archivo.
     */
    public function buscarPorNombre(Request $request)
    {
        $request->validate([
            'nombre_archivo' => 'required|string',
        ]);

        $transcripcion = AudioTranscripcion::where('nombre_archivo', $request->nombre_archivo)->first();

        if (!$transcripcion) {
            return response()->json(['error' => 'Transcripción no encontrada'], 404);
        }

        return response()->json([
            'id'                 => $transcripcion->id,
            'nombre_archivo'     => $transcripcion->nombre_archivo,
            'telefono'           => $transcripcion->telefono,
            'tipo_emergencia'    => $transcripcion->tipo_emergencia,
            'resumen'            => $transcripcion->resumen,
            'transcripcion_json' => json_decode($transcripcion->transcripcion_json, true),
            'created_at'         => $transcripcion->created_at,
        ]);
    }

    /**
     * Buscar transcripciones por número de teléfono.
     */
    public function buscarPorTelefono(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string',
        ]);

        $transcripciones = AudioTranscripcion::where('telefono', 'like', '%' . $request->telefono . '%')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($transcripciones->map(function ($t) {
            return [
                'id'                 => $t->id,
                'nombre_archivo'     => $t->nombre_archivo,
                'telefono'           => $t->telefono,
                'tipo_emergencia'    => $t->tipo_emergencia,
                'resumen'            => $t->resumen,
                'transcripcion_json' => json_decode($t->transcripcion_json, true),
                'created_at'         => $t->created_at,
            ];
        }));
    }

    /**
     * Obtener una transcripción específica por ID.
     */
    public function show($id)
    {
        $transcripcion = AudioTranscripcion::findOrFail($id);

        return response()->json([
            'id'                 => $transcripcion->id,
            'nombre_archivo'     => $transcripcion->nombre_archivo,
            'telefono'           => $transcripcion->telefono,
            'tipo_emergencia'    => $transcripcion->tipo_emergencia,
            'resumen'            => $transcripcion->resumen,
            'transcripcion_json' => json_decode($transcripcion->transcripcion_json, true),
            'created_at'         => $transcripcion->created_at,
        ]);
    }
}
