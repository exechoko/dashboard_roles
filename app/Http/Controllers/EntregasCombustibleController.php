<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntregaCombustibleRequest;
use App\Http\Requests\UpdateEntregaCombustibleRequest;
use App\Http\Requests\UploadActaEntregaCombustibleRequest;
use App\Models\EntregaCombustible;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class EntregasCombustibleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:ver-entrega-combustible')->only(['index', 'show', 'descargarArchivo']);
        $this->middleware('can:crear-entrega-combustible')->only(['create', 'store', 'generarDocumento']);
        $this->middleware('can:editar-entrega-combustible')->only(['edit', 'update', 'subirActaFirmada']);
        $this->middleware('can:borrar-entrega-combustible')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $query = EntregaCombustible::query()
            ->buscarPorTicket($request->input('ticket'))
            ->buscarPorEmpresa($request->input('empresa_soporte'));

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_entrega', $request->input('fecha'));
        }

        $entregas = $query->orderByDesc('fecha_entrega')
            ->orderByDesc('hora_entrega')
            ->paginate(15);

        $totales = EntregaCombustible::query()
            ->whereYear('fecha_entrega', $anio)
            ->whereMonth('fecha_entrega', $mes)
            ->selectRaw('COALESCE(SUM(cantidad_litros), 0) as litros, COALESCE(SUM(cantidad_bidones), 0) as bidones, COUNT(*) as entregas')
            ->first();

        return view('entregas.entregas-combustible.index', compact('entregas', 'totales', 'mes', 'anio'));
    }

    public function create(): View
    {
        return view('entregas.entregas-combustible.crear');
    }

    public function store(StoreEntregaCombustibleRequest $request): RedirectResponse
    {
        $entrega = EntregaCombustible::create(array_merge(
            $this->datosEntrega($request->validated()),
            ['usuario_creador' => auth()->user()->name]
        ));

        return redirect()->route('entrega-combustible.show', $entrega)
            ->with('success', 'Entrega de combustible creada exitosamente.');
    }

    public function show(EntregaCombustible $entregaCombustible): View
    {
        return view('entregas.entregas-combustible.show', ['entrega' => $entregaCombustible]);
    }

    public function edit(EntregaCombustible $entregaCombustible): View
    {
        return view('entregas.entregas-combustible.editar', ['entrega' => $entregaCombustible]);
    }

    public function update(UpdateEntregaCombustibleRequest $request, EntregaCombustible $entregaCombustible): RedirectResponse
    {
        $entregaCombustible->update($this->datosEntrega($request->validated()));

        return redirect()->route('entrega-combustible.show', $entregaCombustible)
            ->with('success', 'Entrega de combustible actualizada exitosamente.');
    }

    public function destroy(EntregaCombustible $entregaCombustible): RedirectResponse
    {
        $entregaCombustible->delete();

        return redirect()->route('entrega-combustible.index')
            ->with('success', 'Entrega de combustible eliminada exitosamente.');
    }

    public function generarDocumento(EntregaCombustible $entregaCombustible): BinaryFileResponse|RedirectResponse
    {
        $templatePath = storage_path('app/templates/template_entrega_combustible.docx');

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template de entrega de combustible no encontrado.');
        }

        try {
            $templateTemporal = $this->crearTemplateTemporalNormalizado($templatePath);
            $templateProcessor = new TemplateProcessor($templateTemporal);

            $templateProcessor->setValue('DIA', $entregaCombustible->fecha_entrega->format('d'));
            $templateProcessor->setValue('MES', $this->mesEnEspanol($entregaCombustible->fecha_entrega));
            $templateProcessor->setValue('ANIO', $entregaCombustible->fecha_entrega->format('Y'));
            $templateProcessor->setValue('HORA', Carbon::parse($entregaCombustible->hora_entrega)->format('H:i'));
            $templateProcessor->setValue('TICKET', $entregaCombustible->ticket);
            $templateProcessor->setValue('PERSONAL_RECEPTOR', $entregaCombustible->personal_receptor);
            $templateProcessor->setValue('CANTIDAD_BIDONES', (string) $entregaCombustible->cantidad_bidones);
            $templateProcessor->setValue('CANTIDAD_BIDONES_LETRAS', strtoupper($this->numeroALetras($entregaCombustible->cantidad_bidones)));

            $fileName = 'entrega_combustible_' . $entregaCombustible->id . '_' . now()->format('Ymd_His') . '.docx';
            $relativePath = 'entregas_combustible/documentos/' . $fileName;
            $destinationPath = storage_path('app/' . $relativePath);

            if (!file_exists(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
            }

            $templateProcessor->saveAs($destinationPath);

            @unlink($templateTemporal);

            $entregaCombustible->update(['ruta_archivo' => $relativePath]);

            return response()->download($destinationPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }
    }

    public function descargarArchivo(EntregaCombustible $entregaCombustible): BinaryFileResponse|RedirectResponse
    {
        if (!$entregaCombustible->ruta_archivo) {
            abort(404, 'Archivo no encontrado');
        }

        $path = storage_path('app/' . $entregaCombustible->ruta_archivo);

        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'El archivo generado no está disponible.');
        }

        return response()->download($path, basename($path));
    }

    public function subirActaFirmada(UploadActaEntregaCombustibleRequest $request, EntregaCombustible $entregaCombustible): RedirectResponse
    {
        $rutaArchivo = $request->file('acta_firmada')->store('combustible', 'anexos');

        $entregaCombustible->update([
            'ruta_acta_firmada' => 'anexos/' . $rutaArchivo,
        ]);

        return redirect()->route('entrega-combustible.show', $entregaCombustible)
            ->with('success', 'Acta firmada cargada exitosamente.');
    }

    private function crearTemplateTemporalNormalizado(string $templatePath): string
    {
        $tempPath = storage_path('app/temp/template_entrega_combustible_' . uniqid('', true) . '.docx');

        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        copy($templatePath, $tempPath);

        $zip = new ZipArchive();
        if ($zip->open($tempPath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml !== false) {
                $xml = preg_replace('/\$\{<\/w:t>.*?<w:t>CANTIDAD_<\/w:t>.*?<w:t>BIDONES<\/w:t>.*?<w:t>_LETRAS<\/w:t>.*?<w:t>\}/s', '${CANTIDAD_BIDONES_LETRAS}', $xml);
                $xml = preg_replace('/\$\{CANTIDAD_<\/w:t>.*?<w:t>BIDONES<\/w:t>.*?<w:t>\)/s', '${CANTIDAD_BIDONES})', $xml);
                $zip->addFromString('word/document.xml', $xml);
            }

            $zip->close();
        }

        return $tempPath;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function datosEntrega(array $data): array
    {
        $data['cantidad_litros'] = 40;
        $data['cantidad_bidones'] = 2;
        $data['litros_por_bidon'] = 20;

        return $data;
    }

    private function mesEnEspanol(Carbon $fecha): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $meses[(int) $fecha->format('n')];
    }

    private function numeroALetras(int $numero): string
    {
        $numeros = [
            0 => 'cero',
            1 => 'un',
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
            7 => 'siete',
            8 => 'ocho',
            9 => 'nueve',
            10 => 'diez',
            11 => 'once',
            12 => 'doce',
            13 => 'trece',
            14 => 'catorce',
            15 => 'quince',
            16 => 'dieciséis',
            17 => 'diecisiete',
            18 => 'dieciocho',
            19 => 'diecinueve',
            20 => 'veinte',
        ];

        return $numeros[$numero] ?? (string) $numero;
    }
}
