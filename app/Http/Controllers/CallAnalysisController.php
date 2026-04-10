<?php

namespace App\Http\Controllers;

use App\Models\CallAnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallAnalysisController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:102400',
            'mode'  => 'required|in:transcribe,analyze',
        ]);

        $file = $request->file('audio');
        $path = $file->store('callanalysis_temp');

        $job = CallAnalysisJob::create([
            'audio_path'    => $path,
            'original_name' => $file->getClientOriginalName(),
            'mode'          => $request->input('mode'),
            'status'        => 'pending',
        ]);

        Log::channel('transcripciones')->info('[CallAnalysis] Solicitud recibida.', [
            'job_id'  => $job->id,
            'modo'    => $job->mode,
            'archivo' => $file->getClientOriginalName(),
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'job_id'  => $job->id,
            'status'  => 'pending',
        ]);
    }

    public function estado($jobId)
    {
        $job = CallAnalysisJob::findOrFail($jobId);

        $payload = [
            'job_id' => $job->id,
            'status' => $job->status,
            'mode'   => $job->mode,
            'error'  => $job->error_message,
            'result' => null,
        ];

        if ($job->status === 'completed' && $job->result_json) {
            $payload['result'] = json_decode($job->result_json, true);
        }

        return response()->json($payload);
    }

    public function health()
    {
        try {
            $response = Http::timeout(5)->get('http://193.169.1.246:8082/health');
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['status' => 'error'], 503);
        } catch (\Exception $e) {
            return response()->json(['status' => 'unreachable'], 503);
        }
    }
}
