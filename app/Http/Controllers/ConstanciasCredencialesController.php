<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConstanciaCredencialRequest;
use App\Http\Requests\UpdateConstanciaCredencialRequest;
use App\Http\Requests\UploadActaFirmadaRequest;
use App\Mail\CredencialesAccesoMail;
use App\Models\ConstanciaCredencial;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConstanciasCredencialesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-constancias-credenciales')->only(['index', 'show', 'descargarDocumento']);
        $this->middleware('permission:crear-constancias-credenciales')->only(['create', 'store', 'generarDocumento', 'enviarEmail']);
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

        $totalConstancias = ConstanciaCredencial::count();
        $totalFirmadas = ConstanciaCredencial::firmadas()->count();
        $totalPendientes = ConstanciaCredencial::pendientes()->count();
        $totalEmailsEnviados = ConstanciaCredencial::emailEnviado()->count();

        return view('constancias-credenciales.index', compact(
            'constancias',
            'totalConstancias',
            'totalFirmadas',
            'totalPendientes',
            'totalEmailsEnviados'
        ));
    }

    public function create(): View
    {
        return view('constancias-credenciales.crear');
    }

    public function store(StoreConstanciaCredencialRequest $request): RedirectResponse
    {
        $user = $request->input('user_id') ? User::find($request->input('user_id')) : null;

        $constancia = ConstanciaCredencial::create([
            'user_id' => $user?->id,
            'nombre_apellido' => $request->input('nombre_apellido'),
            'dni' => $request->input('dni'),
            'email' => $request->input('email'),
            'contrasena' => $request->input('contrasena'),
            'lugar' => $request->input('lugar', 'Paraná, Entre Ríos'),
            'fecha_entrega' => $request->input('fecha_entrega'),
            'observaciones' => $request->input('observaciones'),
            'usuario_creador_id' => auth()->id(),
            'usuario_creador_nombre' => auth()->user()->name . ' ' . auth()->user()->apellido,
        ]);

        $this->enviarEmailNotificacion($constancia);

        return redirect()->route('constancias-credenciales.show', $constancia->id)
            ->with('success', 'Constancia de credenciales creada exitosamente.');
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

        $data = [
            'nombre_apellido' => $request->input('nombre_apellido'),
            'dni' => $request->input('dni'),
            'email' => $request->input('email'),
            'lugar' => $request->input('lugar', 'Paraná, Entre Ríos'),
            'fecha_entrega' => $request->input('fecha_entrega'),
            'observaciones' => $request->input('observaciones'),
        ];

        if ($request->filled('contrasena')) {
            $data['contrasena'] = $request->input('contrasena');
        }

        if ($request->has('firmada')) {
            $data['firmada'] = $request->input('firmada');
            if ($request->input('firmada') && !$constancia->fecha_firma) {
                $data['fecha_firma'] = now();
            }
        }

        $constancia->update($data);

        return redirect()->route('constancias-credenciales.show', $constancia->id)
            ->with('success', 'Constancia actualizada exitosamente.');
    }

    public function destroy($id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);
        $constancia->delete();

        return redirect()->route('constancias-credenciales.index')
            ->with('success', 'Constancia eliminada exitosamente.');
    }

    public function generarDocumento($id): BinaryFileResponse|RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        $templatePath = storage_path('app/templates/template_constancia_credenciales.docx');

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template de constancia no encontrado: ' . $templatePath);
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
            $tp->setValue('CONTRASENA', $constancia->contrasena);

            $fileName = 'constancia_credenciales_' . $constancia->id . '_' . date('Ymd_His') . '.docx';
            $tempFolder = storage_path('app/temp');
            if (!file_exists($tempFolder)) {
                mkdir($tempFolder, 0755, true);
            }
            $tempPath = $tempFolder . DIRECTORY_SEPARATOR . $fileName;

            $tp->saveAs($tempPath);

            $constancia->update(['ruta_archivo' => $fileName]);

            return response()->download($tempPath, $fileName)->deleteFileAfterSend();
        } catch (Exception $e) {
            Log::error('Error generando documento de constancia: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    public function descargarDocumento($id): BinaryFileResponse|RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        if (!$constancia->ruta_archivo) {
            return redirect()->back()->with('error', 'No hay documento generado para esta constancia.');
        }

        $filePath = storage_path('app/temp/' . $constancia->ruta_archivo);

        if (!file_exists($filePath)) {
            return $this->generarDocumento($id);
        }

        return response()->download($filePath, $constancia->ruta_archivo);
    }

    public function uploadActaFirmada(UploadActaFirmadaRequest $request, $id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);
        $rutaArchivo = $request->file('acta_firmada')->store('constancias_credenciales', 'anexos');

        $constancia->update([
            'ruta_archivo_firmado' => 'anexos/' . $rutaArchivo,
            'firmada' => true,
            'fecha_firma' => $constancia->fecha_firma ?? now(),
        ]);

        return redirect()->route('constancias-credenciales.show', $constancia->id)
            ->with('success', 'Acta firmada cargada exitosamente.');
    }

    public function enviarEmail($id): RedirectResponse
    {
        $constancia = ConstanciaCredencial::findOrFail($id);

        if (!$this->enviarEmailNotificacion($constancia)) {
            return redirect()->back()->with('error', 'No se pudo enviar el email. Verifique la dirección de correo.');
        }

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

    private function enviarEmailNotificacion(ConstanciaCredencial $constancia): bool
    {
        if (!$constancia->email || !filter_var($constancia->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($constancia->email)->send(new CredencialesAccesoMail(
                $constancia->nombre_apellido
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
}
