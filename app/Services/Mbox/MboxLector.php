<?php

namespace App\Services\Mbox;

use App\Models\MailMensaje;
use RuntimeException;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\IMessagePart;

/**
 * Relee un mensaje ya indexado directamente desde el .mbox original usando
 * el byte_offset/byte_length guardados en MailMensaje, sin volver a escanear
 * el archivo completo.
 */
class MboxLector
{
    private MailMimeParser $parser;

    public function __construct()
    {
        $this->parser = new MailMimeParser();
    }

    /**
     * Devuelve el mensaje RFC822 original (des-escapado), tal como estaría
     * en un .eml suelto.
     */
    public function leerCrudo(MailMensaje $mensaje): string
    {
        $ruta = $mensaje->archivo->ruta_absoluta;

        $fh = fopen($ruta, 'rb');
        if ($fh === false) {
            throw new RuntimeException("No se pudo abrir el archivo mbox: {$ruta}");
        }

        try {
            fseek($fh, $mensaje->byte_offset);
            $crudo = fread($fh, $mensaje->byte_length);
            if ($crudo === false) {
                throw new RuntimeException('No se pudo leer el mensaje del archivo mbox.');
            }
        } finally {
            fclose($fh);
        }

        return $this->desescaparMboxrd($crudo);
    }

    public function parsear(MailMensaje $mensaje): IMessage
    {
        return $this->parser->parse($this->leerCrudo($mensaje), false);
    }

    public function obtenerAdjunto(MailMensaje $mensaje, int $parte): IMessagePart
    {
        $mimeMensaje = $this->parsear($mensaje);
        $adjunto = $mimeMensaje->getAttachmentPart($parte);

        if ($adjunto === null) {
            throw new RuntimeException("El mensaje no tiene un adjunto en la parte {$parte}.");
        }

        return $adjunto;
    }

    /**
     * Sanitiza el HTML del mensaje para mostrarlo embebido en un iframe:
     * quita scripts, iframes, handlers on*, javascript: y bloquea imágenes
     * remotas por defecto (privacidad: evita "web bugs" de tracking).
     * Las imágenes inline (cid:) sí se resuelven, contra $mapaCid.
     *
     * @param array<string, string> $mapaCid cid => URL interna de la imagen inline
     */
    public function sanitizarHtml(string $html, array $mapaCid = []): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html) ?? $html;
        $html = preg_replace('/<(script|iframe|object|embed|link)\b[^>]*\/?>/is', '', $html) ?? $html;

        // Handlers on* (onclick=, onerror=, ...), con comillas simples, dobles o sin comillas.
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        // href/src con javascript:
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $html) ?? $html;

        // Imágenes inline por Content-ID.
        $html = preg_replace_callback('/src\s*=\s*(["\'])cid:([^"\']+)\1/i', function (array $m) use ($mapaCid): string {
            $cid = trim($m[2], '<>');
            $url = $mapaCid[$cid] ?? null;

            return $url ? 'src="'.$url.'"' : 'src=""';
        }, $html) ?? $html;

        // Cualquier otra imagen remota queda bloqueada por defecto.
        $html = preg_replace('/src\s*=\s*(["\'])(?!cid:|data:image\/)https?:\/\/[^"\']*\1/i', 'src=""', $html) ?? $html;

        return $html;
    }

    private function desescaparMboxrd(string $crudo): string
    {
        return preg_replace_callback('/^(>+)From /m', function (array $m): string {
            return substr($m[1], 1).'From ';
        }, $crudo);
    }
}
