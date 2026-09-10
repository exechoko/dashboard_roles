<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarEntradaBitacoraRequest;
use App\Models\Recurso;
use App\Models\RecursoBitacora;
use App\Models\RecursoBitacoraAdjunto;
use App\Models\RecursoBitacoraSeguimiento;
use App\Models\RecursoBitacoraSolicitud;
use App\Models\RecursoBitacoraVista;
use App\Models\RecursoEstadoSeccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RecursoBitacoraController extends Controller
{
    /** Campos de una entrada que una solicitud de edición puede tocar. */
    private const CAMPOS_EDITABLES = ['fecha_hora', 'categoria', 'descripcion', 'km', 'taller', 'costo'];

    public function __construct()
    {
        $this->middleware('can:ver-flota-911')->only(['show', 'descargarAdjunto']);
        $this->middleware('can:gestionar-flota-911')->only(['store', 'cerrar', 'storeSeguimiento', 'solicitarCambio']);
        $this->middleware('can:moderar-bitacora-flota-911')->only(['destroyAdjunto']);
    }

    public function show(Recurso $recurso): View
    {
        $recurso->load([
            'vehiculo',
            'estadoSeccion',
            'bitacora' => fn ($q) => $q->with([
                'usuario',
                'cerradaPor',
                'adjuntos',
                'seguimientos.usuario',
                'seguimientos.adjuntos',
                'solicitudPendiente.usuario',
            ]),
        ]);

        RecursoBitacoraVista::updateOrCreate(
            ['recurso_id' => $recurso->id, 'user_id' => auth()->id()],
            ['visto_en' => now()],
        );

        $abierta = $recurso->bitacora->firstWhere('estado', RecursoBitacora::ESTADO_ABIERTO);
        $recordarDevolucion = $abierta
            && $recurso->bitacora->contains(fn ($e) => $e->id !== $abierta->id && $e->fecha_hora->gt($abierta->fecha_hora));

        return view('flota-911.bitacora.show', compact('recurso', 'abierta', 'recordarDevolucion'));
    }

    public function store(RegistrarEntradaBitacoraRequest $request, Recurso $recurso): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($request, $recurso, $datos): void {
            $entrada = RecursoBitacora::create([
                'recurso_id'  => $recurso->id,
                'fecha_hora'  => $datos['fecha_hora'],
                'categoria'   => $datos['categoria'],
                'descripcion' => $datos['descripcion'],
                'estado'      => $datos['estado'] ?? null,
                'km'          => $datos['km'] ?? null,
                'taller'      => $datos['taller'] ?? null,
                'costo'       => $datos['costo'] ?? null,
                'user_id'     => auth()->id(),
            ]);

            $this->guardarAdjuntos($request, $entrada->id, null);

            if ($request->boolean('poner_en_taller') && $entrada->estado === RecursoBitacora::ESTADO_ABIERTO) {
                $this->cambiarEstadoSeccion($recurso, 'en_taller');
            }
        });

        return back()->with('success', 'Novedad registrada en la bitácora.');
    }

    public function cerrar(Request $request, RecursoBitacora $entrada): RedirectResponse
    {
        if (! $entrada->estaAbierta()) {
            return back()->with('error', 'La entrada no está abierta.');
        }

        $datos = $request->validate([
            'fecha_cierre'       => ['nullable', 'date'],
            'nota_cierre'        => ['nullable', 'string', 'max:2000'],
            'volver_en_servicio' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $entrada, $datos): void {
            $entrada->update([
                'estado'      => RecursoBitacora::ESTADO_CERRADO,
                'cerrada_en'  => $datos['fecha_cierre'] ?? now(),
                'cerrada_por' => auth()->id(),
            ]);

            if (! empty($datos['nota_cierre'])) {
                $entrada->seguimientos()->create([
                    'user_id'     => auth()->id(),
                    'descripcion' => 'Devolución registrada: ' . $datos['nota_cierre'],
                ]);
            }

            if ($request->boolean('volver_en_servicio')) {
                $this->cambiarEstadoSeccion($entrada->recurso, 'en_servicio');
            }
        });

        return back()->with('success', 'Devolución registrada.');
    }

    public function storeSeguimiento(Request $request, RecursoBitacora $entrada): RedirectResponse
    {
        $request->validate([
            'descripcion' => ['required', 'string', 'max:5000'],
            'adjuntos'    => ['nullable', 'array', 'max:15'],
            'adjuntos.*'  => ['file', 'max:10240'],
        ], [
            'descripcion.required' => 'El seguimiento no puede estar vacío.',
        ]);

        DB::transaction(function () use ($request, $entrada): void {
            $seguimiento = $entrada->seguimientos()->create([
                'user_id'     => auth()->id(),
                'descripcion' => $request->input('descripcion'),
            ]);

            $this->guardarAdjuntos($request, null, $seguimiento->id);
        });

        return back()->with('success', 'Seguimiento agregado.');
    }

    public function solicitarCambio(Request $request, RecursoBitacora $entrada): RedirectResponse
    {
        if ($entrada->solicitudPendiente()->exists()) {
            return back()->with('error', 'Ya hay una solicitud pendiente para esta entrada.');
        }

        $datos = $request->validate([
            'tipo'               => ['required', 'in:edicion,eliminacion'],
            'motivo'             => ['nullable', 'string', 'max:500'],
            'cambios'            => ['nullable', 'array'],
            'cambios.fecha_hora' => ['nullable', 'date'],
            'cambios.categoria'  => ['nullable', 'in:' . implode(',', array_keys(RecursoBitacora::CATEGORIAS))],
            'cambios.descripcion' => ['nullable', 'string', 'max:5000'],
            'cambios.km'         => ['nullable', 'integer', 'min:0'],
            'cambios.taller'     => ['nullable', 'string', 'max:191'],
            'cambios.costo'      => ['nullable', 'numeric', 'min:0'],
        ]);

        $cambios = null;
        if ($datos['tipo'] === RecursoBitacoraSolicitud::TIPO_EDICION) {
            $cambios = collect($datos['cambios'] ?? [])
                ->only(self::CAMPOS_EDITABLES)
                ->reject(fn ($v) => $v === null || $v === '')
                ->all();

            if ($cambios === []) {
                return back()->with('error', 'La solicitud de edición no propone ningún cambio.');
            }
        }

        RecursoBitacoraSolicitud::create([
            'bitacora_id' => $entrada->id,
            'tipo'        => $datos['tipo'],
            'cambios'     => $cambios,
            'motivo'      => $datos['motivo'] ?? null,
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Solicitud enviada. Un administrador la va a revisar.');
    }

    public function destroyAdjunto(RecursoBitacoraAdjunto $adjunto): RedirectResponse
    {
        Storage::disk('public')->delete($adjunto->ruta);
        $adjunto->delete();

        return back()->with('success', 'Adjunto eliminado.');
    }

    public function descargarAdjunto(RecursoBitacoraAdjunto $adjunto)
    {
        abort_unless(Storage::disk('public')->exists($adjunto->ruta), 404);

        return Storage::disk('public')->download($adjunto->ruta, $adjunto->nombre_original ?: basename($adjunto->ruta));
    }

    private function guardarAdjuntos(Request $request, ?int $bitacoraId, ?int $seguimientoId): void
    {
        foreach ($request->file('adjuntos', []) as $archivo) {
            $carpeta = 'flota-911/bitacora/' . ($bitacoraId ?? 'seg-' . $seguimientoId);
            $ruta = $archivo->store($carpeta, 'public');

            RecursoBitacoraAdjunto::create([
                'bitacora_id'     => $bitacoraId,
                'seguimiento_id'  => $seguimientoId,
                'user_id'         => auth()->id(),
                'ruta'            => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'mime_type'       => $archivo->getMimeType(),
                'tamano'          => $archivo->getSize(),
            ]);
        }
    }

    private function cambiarEstadoSeccion(Recurso $recurso, string $estado): void
    {
        RecursoEstadoSeccion::updateOrCreate(
            ['recurso_id' => $recurso->id],
            ['estado' => $estado, 'user_id' => auth()->id()],
        );
    }
}
