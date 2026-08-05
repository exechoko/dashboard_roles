<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarActivacionTotemRequest;
use App\Http\Requests\SubirVideoActivacionTotemRequest;
use App\Models\ActivacionTotem;
use App\Models\Camara;
use App\Services\DetectorActivacionesTotem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class ActivacionTotemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-activacion-totem')->only(['index', 'descargarVideo', 'descargarCertificado']);
        $this->middleware('permission:editar-activacion-totem')->only([
            'update', 'descartar', 'escanear', 'eliminar', 'subirVideo', 'totems', 'actualizarCarpetaTotem',
        ]);
    }

    public function index(Request $request): View
    {
        $query = ActivacionTotem::with(['evento', 'camara', 'descargadoPor', 'eliminadoPor']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->boolean('vencidas')) {
            $query->vencidas();
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_evento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_evento', '<=', $request->fecha_hasta);
        }

        $activaciones = $query->orderBy('fecha_evento', 'desc')->paginate(15)->withQueryString();

        $estados = ActivacionTotem::ESTADOS;
        $totems = Camara::whereHas('tipoCamara', function ($q) {
            $q->where('tipo', 'BDE (Totem)');
        })->orderBy('nombre')->get();

        return view('activaciones-totem.index', compact('activaciones', 'estados', 'totems'));
    }

    public function update(ActualizarActivacionTotemRequest $request, ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'camara_id' => $request->validated('camara_id'),
            'observaciones' => $request->validated('observaciones'),
            'estado' => ActivacionTotem::ESTADO_DESCARGADO,
            'descargado_por' => auth()->id(),
            'fecha_descarga' => now(),
            // Es un registro manual (sin archivo): si esta activación ya tenía un
            // video subido por el sistema en un ciclo anterior (subido y luego
            // marcado como eliminado), esos datos ya no corresponden.
            'nombre_archivo_original' => null,
            'ruta_archivo' => null,
            'hash_sha256' => null,
            'subida_estado' => null,
            'subida_error' => null,
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Descarga registrada correctamente.');
    }

    public function subirVideo(SubirVideoActivacionTotemRequest $request, ActivacionTotem $activacionTotem): RedirectResponse|JsonResponse
    {
        if ($activacionTotem->estado === ActivacionTotem::ESTADO_DESCARGADO) {
            $mensaje = 'Ya hay un video cargado para esta activación. Marcala como eliminada antes de subir uno nuevo.';

            return $request->expectsJson()
                ? response()->json(['message' => $mensaje], 422)
                : redirect()->route('activaciones-totem.index')->with('error', $mensaje);
        }

        if (in_array($activacionTotem->subida_estado, [ActivacionTotem::SUBIDA_PENDIENTE, ActivacionTotem::SUBIDA_PROCESANDO], true)) {
            $mensaje = 'Ya hay un video en proceso para esta activación. Esperá a que termine.';

            return $request->expectsJson()
                ? response()->json(['message' => $mensaje], 422)
                : redirect()->route('activaciones-totem.index')->with('error', $mensaje);
        }

        $archivo = $request->file('video');
        $nombreOriginal = $archivo->getClientOriginalName();

        $carpetaTemporal = storage_path('app/totem-uploads-temp');
        File::ensureDirectoryExists($carpetaTemporal);
        $archivo->move($carpetaTemporal, $activacionTotem->id . '_' . $nombreOriginal);

        $activacionTotem->update([
            'camara_id' => $request->validated('camara_id'),
            'observaciones' => $request->validated('observaciones'),
            'descargado_por' => auth()->id(),
            'nombre_archivo_original' => $nombreOriginal,
            'ruta_archivo' => null,
            'hash_sha256' => null,
            'subida_estado' => ActivacionTotem::SUBIDA_PENDIENTE,
            'subida_error' => null,
        ]);

        $mensaje = 'Video recibido. Se está procesando en segundo plano (puede tardar hasta 1 minuto).';

        return $request->expectsJson()
            ? response()->json(['message' => $mensaje], 200)
            : redirect()->route('activaciones-totem.index')->with('success', $mensaje);
    }

    public function descartar(ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'estado' => ActivacionTotem::ESTADO_DESCARTADO,
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Activación descartada.');
    }

    public function escanear(DetectorActivacionesTotem $detector): RedirectResponse
    {
        $creadas = $detector->detectar();

        return redirect()->route('activaciones-totem.index')
            ->with('success', "Escaneo completado: {$creadas} activación(es) nueva(s) detectada(s).");
    }

    public function eliminar(ActivacionTotem $activacionTotem): RedirectResponse
    {
        $activacionTotem->update([
            'estado' => ActivacionTotem::ESTADO_ELIMINADO,
            'eliminado_por' => auth()->id(),
            'fecha_eliminado' => now(),
        ]);

        return redirect()->route('activaciones-totem.index')
            ->with('success', 'Video marcado como eliminado.');
    }

    public function totems(): View
    {
        $totems = Camara::whereHas('tipoCamara', function ($q) {
            $q->where('tipo', 'BDE (Totem)');
        })->orderBy('nombre')->get();

        return view('activaciones-totem.totems', compact('totems'));
    }

    public function actualizarCarpetaTotem(Request $request, Camara $camara): RedirectResponse
    {
        if (!$camara->tipoCamara || $camara->tipoCamara->tipo !== 'BDE (Totem)') {
            abort(404);
        }

        $request->validate([
            'carpeta_red' => 'nullable|string|max:255',
        ]);

        $camara->update([
            'carpeta_red' => $request->input('carpeta_red'),
        ]);

        return redirect()->route('activaciones-totem.totems')
            ->with('success', "Carpeta de red actualizada para {$camara->nombre}.");
    }

    public function descargarVideo(ActivacionTotem $activacionTotem): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if (empty($activacionTotem->ruta_archivo) || !file_exists($activacionTotem->ruta_archivo)) {
            return redirect()->route('activaciones-totem.index')
                ->with('error', 'El video no está disponible en la carpeta de red.');
        }

        $nombreDescarga = $activacionTotem->nombre_archivo_original
            ?: basename($activacionTotem->ruta_archivo);

        return response()->download($activacionTotem->ruta_archivo, $nombreDescarga);
    }

    public function descargarCertificado(ActivacionTotem $activacionTotem): \Illuminate\Http\Response|RedirectResponse
    {
        if (empty($activacionTotem->hash_sha256)) {
            return redirect()->route('activaciones-totem.index')
                ->with('error', 'No hay hash calculado para esta activación.');
        }

        $activacionTotem->loadMissing(['camara', 'descargadoPor', 'evento']);

        $contenido = implode("\n", [
            'CERTIFICADO DE INTEGRIDAD — VIDEO TÓTEM',
            '========================================',
            '',
            'Expediente CECOCO: ' . $activacionTotem->nro_expediente,
            'Tótem: ' . ($activacionTotem->camara->nombre ?? '-'),
            'Fecha del evento: ' . $activacionTotem->fecha_evento->format('d/m/Y H:i'),
            'Descripción del evento: ' . ($activacionTotem->evento->descripcion ?? '-'),
            'Video subido por: ' . ($activacionTotem->descargadoPor->name ?? '-'),
            'Fecha de subida: ' . optional($activacionTotem->fecha_descarga)->format('d/m/Y H:i'),
            'Archivo original: ' . $activacionTotem->nombre_archivo_original,
            'Ruta guardada: ' . $activacionTotem->ruta_archivo,
            '',
            'Hash SHA-256:',
            $activacionTotem->hash_sha256,
            '',
            'Certificado generado el ' . now()->format('d/m/Y H:i') . ' por el sistema.',
        ]);

        $nombreArchivo = $activacionTotem->nro_expediente . '_certificado.txt';

        return response($contenido, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        ]);
    }
}
