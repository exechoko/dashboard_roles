<?php

namespace App\Services\Mbox;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Recorre un .mbox en streaming (sin cargarlo en memoria) y guarda en
 * mail_mensajes el índice de cada mensaje: cabeceras, texto y el byte_offset
 * dentro del archivo original para poder releerlo bajo demanda con
 * MboxLector, sin duplicar el contenido del backup en la base.
 */
class MboxIndexador
{
    private const CADA_N_MENSAJES_GUARDA_PROGRESO = 200;

    private MailMimeParser $parser;

    public function __construct()
    {
        $this->parser = new MailMimeParser();
    }

    /**
     * @return array{mensajes_total: int, mensajes_nuevos: int}
     */
    public function indexar(MailArchivo $archivo, bool $reiniciar = false): array
    {
        $buzon = $archivo->buzon;
        $ruta = $archivo->ruta_absoluta;

        $fh = fopen($ruta, 'rb');
        if ($fh === false) {
            throw new \RuntimeException("No se pudo abrir el archivo mbox: {$ruta}");
        }

        $offsetInicio = $reiniciar ? 0 : $archivo->offset_reanudar;
        $totalMensajes = $reiniciar ? 0 : $archivo->mensajes_total;
        $nuevosMensajes = $reiniciar ? 0 : $archivo->mensajes_nuevos;

        if ($offsetInicio > 0) {
            fseek($fh, $offsetInicio);
        }

        $maxBytesMensaje = config('mbox.max_mensaje_mb') * 1024 * 1024;
        $tamanoLote = config('mbox.lote_insert');

        $inicioMensaje = null;
        $buffer = '';
        $desbordado = false;
        $lote = [];

        try {
            while (($line = fgets($fh)) !== false) {
                $offsetLinea = ftell($fh) - strlen($line);

                if (str_starts_with($line, 'From ')) {
                    if ($inicioMensaje !== null) {
                        $fila = $this->parsearMensaje($buffer, $desbordado, $inicioMensaje, $offsetLinea - $inicioMensaje, $buzon, $archivo);
                        if ($fila !== null) {
                            $lote[] = $fila;
                            $totalMensajes++;
                        }

                        if (count($lote) >= $tamanoLote) {
                            $nuevosMensajes += $this->insertarLote($lote, $buzon);
                            $lote = [];
                        }

                        if ($totalMensajes % self::CADA_N_MENSAJES_GUARDA_PROGRESO === 0) {
                            // $offsetLinea es el arranque del mensaje que recién empieza: es el
                            // único punto seguro para reanudar, porque coincide con un límite
                            // real entre mensajes (dentro del cuerpo nunca aparece un "From "
                            // sin escapar).
                            $this->guardarProgreso($archivo, $offsetLinea, $totalMensajes, $nuevosMensajes);
                        }
                    }
                    $inicioMensaje = $offsetLinea;
                    $buffer = '';
                    $desbordado = false;
                }

                if ($inicioMensaje !== null && !$desbordado) {
                    if (strlen($buffer) + strlen($line) > $maxBytesMensaje) {
                        $desbordado = true;
                    } else {
                        $buffer .= $line;
                    }
                }
            }

            $finArchivo = ftell($fh);
            if ($inicioMensaje !== null) {
                $fila = $this->parsearMensaje($buffer, $desbordado, $inicioMensaje, $finArchivo - $inicioMensaje, $buzon, $archivo);
                if ($fila !== null) {
                    $lote[] = $fila;
                    $totalMensajes++;
                }
            }

            if (!empty($lote)) {
                $nuevosMensajes += $this->insertarLote($lote, $buzon);
            }

            $this->guardarProgreso($archivo, $finArchivo, $totalMensajes, $nuevosMensajes);
        } finally {
            fclose($fh);
        }

        return [
            'mensajes_total' => $totalMensajes,
            'mensajes_nuevos' => $nuevosMensajes,
        ];
    }

    private function guardarProgreso(MailArchivo $archivo, int $bytesProcesados, int $totalMensajes, int $nuevosMensajes): void
    {
        $archivo->forceFill([
            'bytes_procesados' => $bytesProcesados,
            'offset_reanudar' => $bytesProcesados,
            'mensajes_total' => $totalMensajes,
            'mensajes_nuevos' => $nuevosMensajes,
        ])->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parsearMensaje(string $crudo, bool $truncadoPorTamano, int $offset, int $length, MailBuzon $buzon, MailArchivo $archivo): ?array
    {
        if (trim($crudo) === '') {
            return null;
        }

        $desescapado = $this->desescaparMboxrd($crudo);

        try {
            $mensaje = $this->parser->parse($desescapado, false);

            $from = $mensaje->getHeader('From');
            $deNombre = $from instanceof AddressHeader ? $from->getPersonName() : null;
            $deEmail = $from instanceof AddressHeader ? $from->getEmail() : null;

            $to = $mensaje->getHeader('To');
            $cc = $mensaje->getHeader('Cc');
            $replyTo = $mensaje->getHeader('Reply-To');

            $fechaHeader = $mensaje->getHeader('Date');
            $fecha = $fechaHeader instanceof DateHeader ? $fechaHeader->getDateTime() : null;

            $labelsRaw = $mensaje->getHeaderValue('X-Gmail-Labels');

            [$adjuntos, $nombresAdjuntos] = $this->extraerAdjuntos($mensaje);

            $tieneHtml = $mensaje->getHtmlPartCount() > 0;
            $texto = $mensaje->getTextContent();
            if ($texto === null && $tieneHtml) {
                $texto = html_entity_decode(strip_tags((string) $mensaje->getHtmlContent()));
            }

            [$texto, $truncadoPorCuerpo] = $this->limitarCuerpo($texto);

            return [
                'buzon_id' => $buzon->id,
                'archivo_id' => $archivo->id,
                'byte_offset' => $offset,
                'byte_length' => $length,
                'message_id' => $this->recortar($mensaje->getMessageId(), 255),
                'gm_thread_id' => $this->recortar($mensaje->getHeaderValue('X-GM-THRID'), 64),
                'de_nombre' => $this->recortar($deNombre, 255),
                'de_email' => $this->recortar($deEmail, 255),
                'para' => $to instanceof AddressHeader ? $to->getDecodedValue() : null,
                'cc' => $cc instanceof AddressHeader ? $cc->getDecodedValue() : null,
                'responder_a' => $this->recortar($replyTo instanceof AddressHeader ? $replyTo->getEmail() : null, 255),
                'asunto' => $this->recortar($mensaje->getSubject(), 998),
                'fecha' => $fecha,
                'etiquetas' => $this->recortar($labelsRaw, 500),
                'carpeta' => $this->derivarCarpeta($labelsRaw),
                'tiene_adjuntos' => count($adjuntos) > 0,
                'cantidad_adjuntos' => count($adjuntos),
                'adjuntos_json' => json_encode($adjuntos),
                'adjuntos_nombres' => $nombresAdjuntos !== [] ? implode(' ', $nombresAdjuntos) : null,
                'tamano_bytes' => $length,
                'tiene_html' => $tieneHtml,
                'cuerpo_truncado' => $truncadoPorTamano || $truncadoPorCuerpo,
                'snippet' => $this->armarSnippet($texto),
                'cuerpo_texto' => $texto,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } catch (Throwable $e) {
            Log::channel('mbox')->warning('No se pudo parsear un mensaje, se usa el modo de cabeceras crudas.', [
                'archivo_id' => $archivo->id,
                'offset' => $offset,
                'error' => $e->getMessage(),
            ]);

            return $this->filaDeRespaldo($desescapado, $truncadoPorTamano, $offset, $length, $buzon, $archivo);
        }
    }

    /**
     * Extracción muy básica de cabeceras por si el parser MIME falla con un
     * mensaje malformado: mejor un registro con lo esencial que perderlo.
     *
     * @return array<string, mixed>
     */
    private function filaDeRespaldo(string $crudo, bool $truncado, int $offset, int $length, MailBuzon $buzon, MailArchivo $archivo): array
    {
        $bloqueCabeceras = explode("\n\n", str_replace("\r\n", "\n", $crudo), 2)[0] ?? '';

        $extraer = function (string $nombre) use ($bloqueCabeceras): ?string {
            if (preg_match('/^'.preg_quote($nombre, '/').':\s*(.+)$/mi', $bloqueCabeceras, $m)) {
                return trim($m[1]);
            }

            return null;
        };

        $de = $extraer('From');
        $asunto = $extraer('Subject');
        $fechaTexto = $extraer('Date');

        return [
            'buzon_id' => $buzon->id,
            'archivo_id' => $archivo->id,
            'byte_offset' => $offset,
            'byte_length' => $length,
            'message_id' => $this->recortar($extraer('Message-ID'), 255),
            'gm_thread_id' => $this->recortar($extraer('X-GM-THRID'), 64),
            'de_nombre' => null,
            'de_email' => $this->recortar($de, 255),
            'para' => $extraer('To'),
            'cc' => $extraer('Cc'),
            'responder_a' => null,
            'asunto' => $this->recortar($asunto, 998),
            'fecha' => $fechaTexto && strtotime($fechaTexto) ? date('Y-m-d H:i:s', strtotime($fechaTexto)) : null,
            'etiquetas' => $this->recortar($extraer('X-Gmail-Labels'), 500),
            'carpeta' => $this->derivarCarpeta($extraer('X-Gmail-Labels')),
            'tiene_adjuntos' => false,
            'cantidad_adjuntos' => 0,
            'adjuntos_json' => null,
            'adjuntos_nombres' => null,
            'tamano_bytes' => $length,
            'tiene_html' => false,
            'cuerpo_truncado' => true,
            'snippet' => '[No se pudo interpretar el contenido de este mensaje]',
            'cuerpo_texto' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function extraerAdjuntos($mensaje): array
    {
        $adjuntos = [];
        $nombres = [];

        foreach ($mensaje->getAllAttachmentParts() as $indice => $parte) {
            $nombre = $parte->getFilename();
            $tamano = 0;

            try {
                $stream = $parte->getBinaryContentStream();
                $tamano = $stream?->getSize() ?? 0;
                if ($tamano === 0 && $stream !== null) {
                    $tamano = strlen($stream->getContents());
                }
            } catch (Throwable) {
                $tamano = 0;
            }

            $adjuntos[] = [
                'parte' => $indice,
                'nombre' => $nombre,
                'mime' => $parte->getContentType(),
                'tamano' => $tamano,
                'inline' => $parte->getContentDisposition('attachment') === 'inline',
                'cid' => $parte->getContentId(),
            ];

            if ($nombre) {
                $nombres[] = $nombre;
            }
        }

        return [$adjuntos, $nombres];
    }

    /**
     * @return array{0: ?string, 1: bool}
     */
    private function limitarCuerpo(?string $texto): array
    {
        if ($texto === null) {
            return [null, false];
        }

        $maxBytes = config('mbox.max_cuerpo_kb') * 1024;
        if (strlen($texto) <= $maxBytes) {
            return [$texto, false];
        }

        return [substr($texto, 0, $maxBytes), true];
    }

    private function armarSnippet(?string $texto): ?string
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        $plano = trim(preg_replace('/\s+/', ' ', $texto));

        return mb_substr($plano, 0, 500);
    }

    private function derivarCarpeta(?string $labels): string
    {
        if (!$labels) {
            return 'recibidos';
        }

        $labels = strtolower($labels);

        return match (true) {
            str_contains($labels, 'trash') => 'papelera',
            str_contains($labels, 'spam') => 'spam',
            str_contains($labels, 'draft') => 'borradores',
            str_contains($labels, 'sent') => 'enviados',
            str_contains($labels, 'inbox') => 'recibidos',
            default => 'archivados',
        };
    }

    /**
     * Deshace el escape mboxrd (líneas de cuerpo que empiezan con "From "
     * se guardan como ">From " para no confundirlas con un separador de
     * mensaje) y devuelve el mensaje RFC822 original.
     */
    private function desescaparMboxrd(string $crudo): string
    {
        return preg_replace_callback('/^(>+)From /m', function (array $m): string {
            return substr($m[1], 1).'From ';
        }, $crudo);
    }

    private function recortar(?string $valor, int $longitud): ?string
    {
        if ($valor === null) {
            return null;
        }

        return mb_substr($valor, 0, $longitud);
    }

    /**
     * Inserta el lote con ON DUPLICATE KEY UPDATE (buzon_id, message_id) y
     * devuelve cuántas filas eran realmente nuevas, calculado por consulta
     * previa en vez de confiar en el conteo de filas afectadas de MySQL
     * (que duplica el valor en los UPDATE y lo vuelve ambiguo).
     */
    private function insertarLote(array $lote, MailBuzon $buzon): int
    {
        if (empty($lote)) {
            return 0;
        }

        $idsConMessageId = array_values(array_filter(array_column($lote, 'message_id')));

        $existentes = empty($idsConMessageId) ? [] : DB::table('mail_mensajes')
            ->where('buzon_id', $buzon->id)
            ->whereIn('message_id', $idsConMessageId)
            ->pluck('message_id')
            ->all();

        $nuevos = 0;
        foreach ($lote as $fila) {
            if (empty($fila['message_id']) || !in_array($fila['message_id'], $existentes, true)) {
                $nuevos++;
            }
        }

        DB::table('mail_mensajes')->upsert(
            $lote,
            ['buzon_id', 'message_id'],
            [
                'archivo_id', 'byte_offset', 'byte_length', 'gm_thread_id',
                'de_nombre', 'de_email', 'para', 'cc', 'responder_a',
                'asunto', 'fecha', 'etiquetas', 'carpeta',
                'tiene_adjuntos', 'cantidad_adjuntos', 'adjuntos_json', 'adjuntos_nombres',
                'tamano_bytes', 'tiene_html', 'cuerpo_truncado', 'snippet', 'cuerpo_texto',
                'updated_at',
            ]
        );

        return $nuevos;
    }
}
