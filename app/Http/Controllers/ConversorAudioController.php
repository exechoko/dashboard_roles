<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertirAudioRequest;
use App\Models\HistorialConversionAudio;
use App\Models\User;
use App\Services\ConversorAudioService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ConversorAudioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-conversor-audio');
    }

    public function index(ConversorAudioService $service): View
    {
        return view('herramientas.conversor-audio', [
            'maxArchivos' => (int) config('conversor_audio.max_archivos', 20),
            'formatosSoportados' => $service->formatosSoportados(),
            'historial' => $this->historial(),
        ]);
    }

    /**
     * Convierte UN archivo del lote {token} y lo deja guardado en el disco
     * 'local' hasta que el frontend pida el ZIP final. Se sube de a uno (en
     * vez de mandar todo el lote en un solo request) para poder mostrar
     * progreso real "N de M" en la vista.
     */
    public function convertirEnLote(string $token, ConvertirAudioRequest $request, ConversorAudioService $service): JsonResponse
    {
        $directorio = $this->directorioLote($token);
        $maxArchivos = (int) config('conversor_audio.max_archivos', 20);

        if (count(Storage::disk('local')->files($directorio)) >= $maxArchivos) {
            return response()->json([
                'success' => false,
                'message' => "Se alcanzó el máximo de {$maxArchivos} archivos por lote.",
            ], 422);
        }

        $archivo = $request->file('archivo');
        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        $usuario = $request->user();
        $tmpMp3 = $service->convertirAMp3($archivo);

        if ($tmpMp3 === null) {
            $mensajeError = in_array($extension, $service->formatosSoportados(), true)
                ? 'No se pudo convertir "' . $archivo->getClientOriginalName() . '".'
                : 'Formato ' . strtoupper($extension) . ' no soportado en este servidor (el conversor instalado no lo decodifica).';
            $item = $usuario instanceof User
                ? $this->registrarHistorial($usuario, $archivo->getClientOriginalName(), $extension, false, $mensajeError)
                : null;

            return response()->json([
                'success' => false,
                'nombre' => $archivo->getClientOriginalName(),
                'message' => $mensajeError,
                'item' => $item,
            ]);
        }

        $nombreMp3 = $this->nombreDisponible($directorio, pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME) . '.mp3');

        $directorioAbsoluto = Storage::disk('local')->path($directorio);
        if (!is_dir($directorioAbsoluto)) {
            mkdir($directorioAbsoluto, 0755, true);
        }

        rename($tmpMp3, $directorioAbsoluto . DIRECTORY_SEPARATOR . $nombreMp3);

        $item = $usuario instanceof User
            ? $this->registrarHistorial($usuario, $archivo->getClientOriginalName(), $extension, true)
            : null;

        return response()->json([
            'success' => true,
            'nombre' => $nombreMp3,
            'item' => $item,
        ]);
    }

    /**
     * Empaqueta lo convertido del lote {token} (un único MP3 si hay uno solo,
     * ZIP si hay varios) y borra el lote del disco al terminar de enviarlo.
     */
    public function descargarLote(string $token): Response
    {
        $directorio = $this->directorioLote($token);
        $archivos = Storage::disk('local')->files($directorio);

        if (empty($archivos)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay archivos convertidos para descargar en este lote.',
            ], 404);
        }

        if (count($archivos) === 1) {
            $ruta = Storage::disk('local')->path($archivos[0]);
            $nombre = basename($archivos[0]);

            // Solo se borra el archivo (deleteFileAfterSend no borra directorios);
            // la carpeta del usuario/token, ya vacía, la limpia
            // conversor-audio:limpiar-lotes-huerfanos.
            return response()->download($ruta, $nombre)->deleteFileAfterSend(true);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'audiomp3') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($archivos as $archivo) {
            $zip->addFile(Storage::disk('local')->path($archivo), basename($archivo));
        }

        $zip->close();

        Storage::disk('local')->deleteDirectory($directorio);

        return response()->download($zipPath, 'audios-convertidos-' . now()->format('Ymd-His') . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function registrarHistorial(
        User $usuario,
        string $nombreArchivo,
        string $extensionOriginal,
        bool $exito,
        ?string $mensajeError = null
    ): array {
        $registro = HistorialConversionAudio::create([
            'user_id' => $usuario->id,
            'nombre_archivo' => $nombreArchivo,
            'extension_original' => $extensionOriginal,
            'exito' => $exito,
            'mensaje_error' => $mensajeError,
        ]);

        return $this->formatearRegistro($registro->setRelation('user', $usuario));
    }

    private function historial(): LengthAwarePaginator
    {
        return HistorialConversionAudio::query()
            ->with('user')
            ->latest()
            ->paginate(20, ['*'], 'conversion_page');
    }

    /**
     * @return array<string, int|string|null>
     */
    private function formatearRegistro(HistorialConversionAudio $registro): array
    {
        $nombreUsuario = trim(implode(' ', array_filter([
            $registro->user?->name,
            $registro->user?->apellido,
        ])));

        return [
            'id' => $registro->id,
            'fecha_hora' => $registro->created_at?->format('d/m/Y H:i:s'),
            'nombre_archivo' => $registro->nombre_archivo,
            'extension_original' => strtoupper($registro->extension_original),
            'exito' => $registro->exito,
            'mensaje_error' => $registro->mensaje_error,
            'usuario' => $nombreUsuario !== '' ? $nombreUsuario : 'Usuario eliminado',
        ];
    }

    private function directorioLote(string $token): string
    {
        $usuario = auth()->id();

        return "conversor_audio_temp/{$usuario}/{$token}";
    }

    private function nombreDisponible(string $directorio, string $nombre): string
    {
        if (!Storage::disk('local')->exists($directorio . '/' . $nombre)) {
            return $nombre;
        }

        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $contador = 2;

        do {
            $candidato = "{$base} ({$contador}).mp3";
            $contador++;
        } while (Storage::disk('local')->exists($directorio . '/' . $candidato));

        return $candidato;
    }
}
