<?php

namespace App\Http\Controllers;

use App\Models\ManualDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManualesController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'docx', 'md', 'html'];
    private const MAX_SIZE_KB = 51200; // 50 MB

    public function indexCecoco()
    {
        $this->authorize('ver-manuales-cecoco');
        $documentos = ManualDocumento::where('tipo', 'cecoco')
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('manuales.cecoco', compact('documentos'));
    }

    public function indexInstructivos()
    {
        $this->authorize('ver-instructivos');
        $documentos = ManualDocumento::where('tipo', 'instructivo')
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('manuales.instructivos', compact('documentos'));
    }

    public function upload(Request $request)
    {
        $tipo = $request->input('tipo');
        $permiso = $tipo === 'cecoco' ? 'cargar-manuales-cecoco' : 'cargar-instructivos';
        $this->authorize($permiso);

        $request->validate([
            'tipo'       => 'required|in:cecoco,instructivo',
            'archivos'   => 'required|array|min:1',
            'archivos.*' => [
                'required',
                'file',
                'max:' . self::MAX_SIZE_KB,
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
                        $fail('Solo se permiten archivos: ' . implode(', ', self::ALLOWED_EXTENSIONS));
                    }
                },
            ],
        ]);

        $carpeta = 'manuales/' . $tipo;

        foreach ($request->file('archivos') as $archivo) {
            $ext         = strtolower($archivo->getClientOriginalExtension());
            $nombreOrig  = $archivo->getClientOriginalName();
            $nombreAlmac = Str::uuid() . '.' . $ext;
            $ruta        = $archivo->storeAs($carpeta, $nombreAlmac);

            ManualDocumento::create([
                'tipo'            => $tipo,
                'nombre_original' => $nombreOrig,
                'nombre_archivo'  => $nombreAlmac,
                'ruta_archivo'    => $ruta,
                'extension'       => $ext,
                'tamano'          => $archivo->getSize(),
                'subido_por'      => Auth::id(),
            ]);
        }

        return back()->with('success', 'Archivo(s) cargado(s) correctamente.');
    }

    public function download($id)
    {
        $doc     = ManualDocumento::findOrFail($id);
        $permiso = $doc->tipo === 'cecoco' ? 'descargar-manuales-cecoco' : 'descargar-instructivos';
        $this->authorize($permiso);

        if (!Storage::exists($doc->ruta_archivo)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::download($doc->ruta_archivo, $doc->nombre_original);
    }

    public function view($id)
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

    public function destroy($id)
    {
        $doc     = ManualDocumento::findOrFail($id);
        $permiso = $doc->tipo === 'cecoco' ? 'borrar-manuales-cecoco' : 'borrar-instructivos';
        $this->authorize($permiso);

        Storage::delete($doc->ruta_archivo);
        $doc->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
