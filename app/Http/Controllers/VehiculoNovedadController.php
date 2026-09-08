<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\VehiculoNovedad;
use App\Models\VehiculoNovedadAdjunto;
use App\Models\VehiculoNovedadSeguimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehiculoNovedadController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:ver-flota-911')->only('index');
        $this->middleware('can:gestionar-flota-911')->only(['store', 'resolver', 'storeSeguimiento', 'storeAdjunto', 'destroyAdjunto']);
    }

    public function index(Vehiculo $vehiculo)
    {
        $novedades = $vehiculo->novedades()
            ->with(['usuario', 'seguimientos.usuario', 'adjuntos'])
            ->paginate(15);

        return view('flota-911.novedades.index', compact('vehiculo', 'novedades'));
    }

    public function store(Request $request, Vehiculo $vehiculo)
    {
        $request->validate([
            'descripcion'  => 'required|string|max:2000',
            'fecha_novedad' => 'required|date',
            'km_actuales'  => 'nullable|integer|min:0',
        ], [
            'descripcion.required'   => 'La descripción es obligatoria.',
            'fecha_novedad.required' => 'La fecha es obligatoria.',
        ]);

        $novedad = VehiculoNovedad::create([
            'vehiculo_id'   => $vehiculo->id,
            'user_id'       => auth()->id(),
            'descripcion'   => $request->descripcion,
            'fecha_novedad' => $request->fecha_novedad,
            'km_actuales'   => $request->km_actuales,
            'resuelta'      => false,
        ]);

        if ($request->hasFile('adjuntos')) {
            foreach ($request->file('adjuntos') as $archivo) {
                $ruta = $archivo->store("flota-911/novedades/{$novedad->id}", 'public');
                VehiculoNovedadAdjunto::create([
                    'novedad_id'      => $novedad->id,
                    'user_id'         => auth()->id(),
                    'ruta'            => $ruta,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'mime_type'       => $archivo->getMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Novedad registrada correctamente.');
    }

    public function resolver(VehiculoNovedad $novedad)
    {
        $novedad->update(['resuelta' => true]);

        return back()->with('success', 'Novedad marcada como resuelta.');
    }

    public function storeSeguimiento(Request $request, VehiculoNovedad $novedad)
    {
        $request->validate([
            'descripcion' => 'required|string|max:2000',
        ], [
            'descripcion.required' => 'El seguimiento no puede estar vacío.',
        ]);

        VehiculoNovedadSeguimiento::create([
            'novedad_id'  => $novedad->id,
            'user_id'     => auth()->id(),
            'descripcion' => $request->descripcion,
        ]);

        return back()->with('success', 'Seguimiento agregado.');
    }

    public function storeAdjunto(Request $request, VehiculoNovedad $novedad)
    {
        $request->validate([
            'adjunto' => 'required|file|max:10240',
        ], [
            'adjunto.required' => 'Debe seleccionar un archivo.',
            'adjunto.max'      => 'El archivo no puede superar 10 MB.',
        ]);

        $archivo = $request->file('adjunto');
        $ruta = $archivo->store("flota-911/novedades/{$novedad->id}", 'public');

        VehiculoNovedadAdjunto::create([
            'novedad_id'      => $novedad->id,
            'user_id'         => auth()->id(),
            'ruta'            => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime_type'       => $archivo->getMimeType(),
        ]);

        return back()->with('success', 'Archivo adjunto guardado.');
    }

    public function destroyAdjunto(VehiculoNovedadAdjunto $adjunto)
    {
        Storage::disk('public')->delete($adjunto->ruta);
        $adjunto->delete();

        return back()->with('success', 'Adjunto eliminado.');
    }
}
