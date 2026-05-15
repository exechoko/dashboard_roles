<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadManualDocumentoRequest;
use App\Models\ManualDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManualesController extends Controller
{
    public function indexCecoco(): View
    {
        $this->authorize('ver-manuales-cecoco');

        $documentos = ManualDocumento::where('tipo', 'cecoco')
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('manuales.cecoco', compact('documentos'));
    }

    public function indexInstructivos(Request $request): View
    {
        $this->authorize('ver-instructivos');

        $busqueda = trim((string) $request->input('buscar', ''));
        $tematica = trim((string) $request->input('tematica', ''));

        $documentos = ManualDocumento::query()
            ->where('tipo', 'instructivo')
            ->when($busqueda !== '', function ($query) use ($busqueda): void {
                $query->where(function ($query) use ($busqueda): void {
                    $query->where('titulo', 'like', '%' . $busqueda . '%')
                        ->orWhere('tematica', 'like', '%' . $busqueda . '%')
                        ->orWhere('nombre_original', 'like', '%' . $busqueda . '%');
                });
            })
            ->when($tematica !== '', function ($query) use ($tematica): void {
                $query->where('tematica', $tematica);
            })
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        $tematicas = ManualDocumento::query()
            ->where('tipo', 'instructivo')
            ->whereNotNull('tematica')
            ->where('tematica', '!=', '')
            ->distinct()
            ->orderBy('tematica')
            ->pluck('tematica');

        return view('manuales.instructivos', compact('documentos', 'busqueda', 'tematica', 'tematicas'));
    }

    public function upload(UploadManualDocumentoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tipo = $validated['tipo'];

        $carpeta = 'manuales/' . $tipo;

        foreach ($request->file('archivos') as $archivo) {
            $ext         = strtolower($archivo->getClientOriginalExtension());
            $nombreOrig  = $archivo->getClientOriginalName();
            $nombreAlmac = Str::uuid() . '.' . $ext;
            $ruta        = $archivo->storeAs($carpeta, $nombreAlmac);

            ManualDocumento::create([
                'tipo'            => $tipo,
                'titulo'          => $validated['titulo'] ?? null,
                'tematica'        => $validated['tematica'] ?? null,
                'nombre_original' => $nombreOrig,
                'nombre_archivo'  => $nombreAlmac,
                'ruta_archivo'    => $ruta,
                'extension'       => $ext,
                'tamano'          => $archivo->getSize(),
                'subido_por'      => $request->user()?->id,
            ]);
        }

        return back()->with('success', 'Archivo(s) cargado(s) correctamente.');
    }

    public function download(int $id): StreamedResponse
    {
        $doc     = ManualDocumento::findOrFail($id);
        $permiso = $doc->tipo === 'cecoco' ? 'descargar-manuales-cecoco' : 'descargar-instructivos';
        $this->authorize($permiso);

        if (!Storage::exists($doc->ruta_archivo)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::download($doc->ruta_archivo, $doc->nombre_original);
    }

    public function view(int $id): BinaryFileResponse
    {
        $doc     = ManualDocumento::findOrFail($id);
        $permiso = $doc->tipo === 'cecoco' ? 'ver-manuales-cecoco' : 'ver-instructivos';
        $this->authorize($permiso);

        if (!Storage::exists($doc->ruta_archivo)) {
            abort(404, 'Archivo no encontrado.');
        }

        $mime = match ($doc->extension) {
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'html' => 'text/html',
            'md'   => 'text/plain',
            default => 'application/octet-stream',
        };

        return response()->file(Storage::path($doc->ruta_archivo), [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $doc->nombre_original . '"',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $doc     = ManualDocumento::findOrFail($id);
        $permiso = $doc->tipo === 'cecoco' ? 'borrar-manuales-cecoco' : 'borrar-instructivos';
        $this->authorize($permiso);

        Storage::delete($doc->ruta_archivo);
        $doc->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
