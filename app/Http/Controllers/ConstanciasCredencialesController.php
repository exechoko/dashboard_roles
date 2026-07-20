<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConstanciaCredencialRequest;
use App\Http\Requests\UpdateConstanciaCredencialRequest;
use App\Http\Requests\UploadActaFirmadaRequest;
use App\Mail\CredencialesAccesoMail;
use App\Models\Auditoria;
use App\Models\ConstanciaCredencial;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConstanciasCredencialesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-constancias-credenciales')->only(['index', 'show']);
        $this->middleware('permission:crear-constancias-credenciales')->only(['create', 'store', 'enviarEmail', 'buscarUsuarios']);
        $this->middleware('permission:editar-constancias-credenciales')->only(['edit', 'update', 'uploadActaFirmada']);
        $this->middleware('permission:borrar-constancias-credenciales')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = ConstanciaCredencial::query()
            ->buscar($request->input('buscar'));

        if ($request->filled('estado')) {
            if ($request->input('estado') === 'firmada') {
                $query->firmadas();
            } elseif ($request->input('estado') === 'pendiente') {
                $query->pendientes();
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_entrega', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_entrega', '<=', $request->input('fecha_hasta'));
        }

        $constancias = $query->with(['usuario', 'creador'])
            ->orderByDesc('fecha_entrega')
            ->orderByDesc('created_at')
            ->paginate(15);

        $statsQuery = ConstanciaCredencial::query();

        if ($request->filled('buscar')) {
            $statsQuery->buscar($request->input('buscar'));
        }

        if ($request->filled('fecha_desde')) {
            $statsQuery->where('fecha_entrega', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $statsQuery->where('fecha_entrega', '<=', $request->input('fecha_hasta'));
        }

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(firmada = 1), 0) as firmadas,
            COALESCE(SUM(firmada = 0), 0) as pendientes,
            COALESCE(SUM(email_enviado = 1), 0) as emails_enviados
        ')->first();

        return view('constancias-credenciales.index', compact(
            'constancias',
            'stats'
        ));
    }

    public function create(Request $request): View
    {
        $usuarioPreseleccionado = $request->filled('user_id')
            ? User::query()->findOrFail($request->integer('user_id'))
            : null;

        return view('constancias-credenciales.crear', compact('usuarioPreseleccionado'));
    }

    public function store(StoreConstanciaCredencialRequest $request): RedirectResponse|BinaryFileResponse
    {
        $validated = $request->validated();
        $contrasena = $validated['contrasena'];
        unset($validated['contrasena']);

        $validated['usuario_creador_id'] = Auth::id();
        $validated['usuario_creador_nombre'] = Auth::user()->name . ' ' . Auth::user()->apellido;
        $validated['lugar'] = $validated['lugar'] ?? 'Paraná, Entre Ríos';

        $constancia = ConstanciaCredencial::create($validated);

        $this->auditar($constancia, 'crear');

        $this->enviarEmailNotificacion($constancia);

        if (!$this->generarYDescargarDocumento($constancia, $contrasena)) {
            return redirect()->route('constancias-credenciales.show', $constancia->id)
                ->with('error', 'El acta se creó pero no se pudo generar el documento. Verifique el template.');
        }

        return redirect()->route('constancias-credenciales.index')
            ->with('success', 'Acta de credenciales creada y documento generado exitosamente.');
    }

    public function show($id): View
    {
        $constancia = ConstanciaCredencial::with(['usuario', 'creador'])->findOrFail($id);

        return view('constancias-credenciales.show', compact('constancia'));
    }

    public function edit($id): View
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        return view('constancias-credenciales.editar', compact('constancia'));
    }

    public function update(UpdateConstanciaCredencialRequest $request, $id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        $constancia->update($request->validated());

        $this->auditar($constancia, 'actualizar');

        return redirect()->route('constancias-credenciales.show', $constancia->id)
            ->with('success', 'Constancia actualizada exitosamente.');
    }

    public function destroy($id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);
        $constancia->delete();

        $this->auditar($constancia, 'eliminar');

        return redirect()->route('constancias-credenciales.index')
            ->with('success', 'Constancia eliminada exitosamente.');
    }

    public function descargarDocumento($id): BinaryFileResponse|RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        if (!$constancia->ruta_archivo) {
            return redirect()->back()->with('error', 'No hay documento generado para esta constancia.');
        }

        $filePath = storage_path('app/constancias_credenciales/documentos/' . $constancia->ruta_archivo);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'El archivo del documento no se encuentra disponible.');
        }

        return response()->download($filePath, $constancia->ruta_archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function uploadActaFirmada(UploadActaFirmadaRequest $request, $id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);
        $rutaArchivo = $request->file('acta_firmada')->store('constancias_credenciales', 'anexos');

        $constancia->update([
            'ruta_archivo_firmado' => 'anexos/' . $rutaArchivo,
            'firmada' => true,
            'fecha_firma' => now(),
        ]);

        $this->auditar($constancia, 'subir acta firmada');

        return redirect()->route('constancias-credenciales.show', $constancia->id)
            ->with('success', 'Acta firmada cargada exitosamente.');
    }

    public function enviarEmail($id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        if (!$this->enviarEmailNotificacion($constancia)) {
            return redirect()->back()->with('error', 'No se pudo enviar el email. Verifique la dirección de correo.');
        }

        $this->auditar($constancia, 'enviar email');

        return redirect()->back()->with('success', 'Email de notificación enviado exitosamente.');
    }

    public function buscarUsuarios(Request $request)
    {
        $term = $request->get('term', '');

        $usuarios = User::where('name', 'LIKE', "%{$term}%")
            ->orWhere('apellido', 'LIKE', "%{$term}%")
            ->orWhere('dni', 'LIKE', "%{$term}%")
            ->orWhere('email', 'LIKE', "%{$term}%")
            ->limit(10)
            ->get()
            ->map(function ($user) {
                $nombreCompleto = $user->name;
                if ($user->apellido) {
                    $nombreCompleto .= ' ' . $user->apellido;
                }

                return [
                    'id' => $user->id,
                    'text' => $nombreCompleto . ' - DNI: ' . ($user->dni ?? 'N/A'),
                    'nombre' => $nombreCompleto,
                    'dni' => $user->dni,
                    'email' => $user->email,
                ];
            });

        return response()->json($usuarios);
    }

    private function generarYDescargarDocumento(ConstanciaCredencial $constancia, string $contrasena): bool
    {
        $templatePath = storage_path('app/templates/template_constancia_credenciales.docx');

        if (!file_exists($templatePath)) {
            Log::error('Template de constancia de credenciales no encontrado.');

            return false;
        }

        try {
            $tp = new TemplateProcessor($templatePath);

            $fecha = $constancia->fecha_entrega ?? Carbon::now();

            $meses = [
                'January'   => 'Enero',
                'February'  => 'Febrero',
                'March'     => 'Marzo',
                'April'     => 'Abril',
                'May'       => 'Mayo',
                'June'      => 'Junio',
                'July'      => 'Julio',
                'August'    => 'Agosto',
                'September' => 'Septiembre',
                'October'   => 'Octubre',
                'November'  => 'Noviembre',
                'December'  => 'Diciembre',
            ];

            $tp->setValue('LUGAR', $constancia->lugar);
            $tp->setValue('DIA', $fecha->format('d'));
            $tp->setValue('MES', $meses[$fecha->format('F')] ?? $fecha->format('F'));
            $tp->setValue('ANIO', $fecha->format('Y'));
            $tp->setValue('NOMBRE_APELLIDO', $constancia->nombre_apellido);
            $tp->setValue('DNI_USUARIO', $constancia->dni);
            $tp->setValue('EMAIL', $constancia->email);
            $tp->setValue('CONTRASENA', $contrasena);

            $fileName = 'constancia_credenciales_' . $constancia->id . '_' . date('Ymd_His') . '.docx';
            $storageFolder = storage_path('app/constancias_credenciales/documentos');
            if (!file_exists($storageFolder)) {
                mkdir($storageFolder, 0755, true);
            }
            $filePath = $storageFolder . DIRECTORY_SEPARATOR . $fileName;

            $tp->saveAs($filePath);

            $constancia->update(['ruta_archivo' => $fileName]);

            return true;
        } catch (Exception $e) {
            Log::error('Error generando documento de constancia: ' . $e->getMessage());

            return false;
        }
    }

    private function enviarEmailNotificacion(ConstanciaCredencial $constancia): bool
    {
        if (!$constancia->email || !filter_var($constancia->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($constancia->email)->send(new CredencialesAccesoMail(
                $constancia->nombre_apellido,
                $constancia->email
            ));

            $constancia->update([
                'email_enviado' => true,
                'fecha_envio_email' => now(),
            ]);

            Log::info('Email de credenciales enviado a: ' . $constancia->email);

            return true;
        } catch (Exception $e) {
            Log::error('Error enviando email de credenciales: ' . $e->getMessage());

            return false;
        }
    }

    private function auditar(ConstanciaCredencial $constancia, string $accion): void
    {
        Auditoria::create([
            'user_id'      => Auth::id(),
            'nombre_tabla' => 'constancias_credenciales',
            'accion'       => $accion,
            'cambios'      => json_encode([
                'constancia_id' => $constancia->id,
                'nombre_apellido' => $constancia->nombre_apellido,
                'dni' => $constancia->dni,
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
