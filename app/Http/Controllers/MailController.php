<?php

namespace App\Http\Controllers;

use App\Exports\MailsExport;
use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Services\Mbox\MboxLector;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-visor-mails');
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $buzones = MailBuzon::query()->accesiblesPor($usuario)->where('activo', true)->orderBy('nombre')->get();

        if ($buzones->isEmpty()) {
            abort(403, 'No tenés acceso a ningún buzón de correo.');
        }

        $buzon = $buzones->firstWhere('id', (int) $request->input('buzon_id', $buzones->first()->id));
        abort_unless($buzon, 403, 'No tenés acceso a ese buzón.');

        $query = $this->filtrar(MailMensaje::query()->where('buzon_id', $buzon->id), $request);

        $ordenables = ['fecha', 'de_email', 'asunto', 'tamano_bytes'];
        $orden = in_array($request->input('orden'), $ordenables, true) ? $request->input('orden') : 'fecha';
        $direccion = $request->input('direccion') === 'asc' ? 'asc' : 'desc';

        $mensajes = $query->orderBy($orden, $direccion)->paginate(25)->withQueryString();

        return view('herramientas.mails.index', [
            'mensajes' => $mensajes,
            'buzones' => $buzones,
            'buzon' => $buzon,
            'carpetas' => MailMensaje::CARPETAS,
        ]);
    }

    public function show(MailMensaje $mensaje): View
    {
        $this->autorizarBuzon($mensaje);

        return view('herramientas.mails.show', [
            'mensaje' => $mensaje,
            'adjuntos' => $mensaje->adjuntos_json ?? [],
        ]);
    }

    public function cuerpo(MailMensaje $mensaje, MboxLector $lector): Response
    {
        $this->autorizarBuzon($mensaje);

        $mimeMensaje = $lector->parsear($mensaje);
        $html = $mimeMensaje->getHtmlContent();

        if ($html === null) {
            $texto = $mimeMensaje->getTextContent() ?? '(mensaje sin contenido)';
            $html = '<pre style="white-space:pre-wrap;font-family:inherit;margin:0;">'.e($texto).'</pre>';
        } else {
            $mapaCid = [];
            foreach ($mensaje->adjuntos_json ?? [] as $adjunto) {
                if (!empty($adjunto['cid'])) {
                    $mapaCid[trim($adjunto['cid'], '<>')] = route('herramientas.mails.adjunto', [$mensaje, $adjunto['parte']]);
                }
            }
            $html = $lector->sanitizarHtml($html, $mapaCid);
        }

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; frame-ancestors 'self'");
    }

    public function adjunto(MailMensaje $mensaje, int $parte, MboxLector $lector): StreamedResponse
    {
        $this->autorizarBuzon($mensaje);

        $adjuntoParte = $lector->obtenerAdjunto($mensaje, $parte);
        $nombre = $adjuntoParte->getFilename() ?: "adjunto-{$parte}";

        return response()->streamDownload(function () use ($adjuntoParte) {
            $stream = $adjuntoParte->getBinaryContentStream();
            while ($stream !== null && !$stream->eof()) {
                echo $stream->read(8192);
            }
        }, $nombre, ['Content-Type' => $adjuntoParte->getContentType()]);
    }

    public function eml(MailMensaje $mensaje, MboxLector $lector): Response
    {
        $this->autorizarBuzon($mensaje);

        $crudo = $lector->leerCrudo($mensaje);
        $nombre = ($mensaje->asunto ? Str::slug(mb_substr($mensaje->asunto, 0, 60)) : 'mensaje').'.eml';

        return response($crudo, 200)
            ->header('Content-Type', 'message/rfc822')
            ->header('Content-Disposition', "attachment; filename=\"{$nombre}\"");
    }

    public function exportar(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $usuario = $request->user();
        $buzones = MailBuzon::query()->accesiblesPor($usuario)->where('activo', true)->pluck('id');

        $buzonId = (int) $request->input('buzon_id');
        abort_unless($buzones->contains($buzonId), 403, 'No tenés acceso a ese buzón.');

        return Excel::download(new MailsExport($buzonId, $request->query()), 'mails.xlsx');
    }

    private function autorizarBuzon(MailMensaje $mensaje): void
    {
        $tieneAcceso = MailBuzon::query()
            ->accesiblesPor(request()->user())
            ->whereKey($mensaje->buzon_id)
            ->exists();

        abort_unless($tieneAcceso, 403, 'No tenés acceso a este buzón de correo.');
    }

    private function filtrar(Builder $query, Request $request): Builder
    {
        if ($request->filled('texto')) {
            $query->whereRaw(
                'MATCH(asunto, cuerpo_texto, adjuntos_nombres) AGAINST (? IN BOOLEAN MODE)',
                [$this->prepararModoBooleano($request->string('texto')->toString())]
            );
        }

        if ($request->filled('de')) {
            $de = $request->de;
            $query->where(fn (Builder $q) => $q->where('de_email', 'like', "%{$de}%")->orWhere('de_nombre', 'like', "%{$de}%"));
        }

        if ($request->filled('para')) {
            $para = $request->para;
            $query->where(fn (Builder $q) => $q->where('para', 'like', "%{$para}%")->orWhere('cc', 'like', "%{$para}%"));
        }

        if ($request->filled('asunto')) {
            $query->where('asunto', 'like', "%{$request->asunto}%");
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('adjuntos')) {
            $query->where('tiene_adjuntos', $request->adjuntos === 'con');
        }

        if ($request->filled('adjunto_nombre')) {
            $query->where('adjuntos_nombres', 'like', "%{$request->adjunto_nombre}%");
        }

        if ($request->filled('carpeta')) {
            $query->where('carpeta', $request->carpeta);
        }

        if ($request->filled('etiqueta')) {
            $query->where('etiquetas', 'like', "%{$request->etiqueta}%");
        }

        if ($request->filled('tamano_min_kb')) {
            $query->where('tamano_bytes', '>=', (int) $request->tamano_min_kb * 1024);
        }

        if ($request->filled('tamano_max_kb')) {
            $query->where('tamano_bytes', '<=', (int) $request->tamano_max_kb * 1024);
        }

        return $query;
    }

    private function prepararModoBooleano(string $texto): string
    {
        $palabras = array_filter(preg_split('/\s+/', trim($texto)) ?: []);

        $terminos = array_map(
            fn (string $palabra) => '+'.preg_replace('/[+\-><()~*"@]+/', '', $palabra).'*',
            $palabras
        );

        return implode(' ', array_filter($terminos)) ?: $texto;
    }
}
