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

        $fh = is_file($ruta) ? @fopen($ruta, 'rb') : false;
        if ($fh === false) {
            throw new MboxNoDisponibleException($ruta);
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
        $html = $this->decodificarEmailsCloudflare($html);
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

    /**
     * Cloudflare (Email Address Obfuscation, en el túnel de producción) reescribe
     * los emails de la respuesta como "[email protected]" y el script que los
     * revierte no corre dentro del iframe con sandbox. Escribir la "@" como
     * entidad HTML evita que Cloudflare reconozca el email; el navegador la
     * muestra igual. No se toca el contenido de <style>, donde las entidades
     * no se interpretan.
     */
    public function evitarOfuscacionDeCloudflare(string $html): string
    {
        $partes = preg_split('/(<style\b.*?<\/style>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($partes === false) {
            return $html;
        }

        foreach ($partes as $indice => $parte) {
            if ($indice % 2 === 1) {
                continue;
            }

            $partes[$indice] = preg_replace('/(?<=[\w.+\-])@(?=[\w\-]+\.)/', '&#64;', $parte) ?? $parte;
        }

        return implode('', $partes);
    }

    /**
     * Revierte la ofuscación de Cloudflare ("Email Address Obfuscation"):
     * elementos con data-cfemail (span, a, etc.) y enlaces con href
     * /cdn-cgi/l/email-protection#HEX, incluidos los que llevan ?subject=...
     */
    private function decodificarEmailsCloudflare(string $html): string
    {
        $html = preg_replace_callback('/<([a-z][a-z0-9]*)\b([^>]*\bdata-cfemail\s*=\s*(["\'])([0-9a-f]+)\3[^>]*)>(.*?)<\/\1>/is', function (array $m): string {
            if (strtolower($m[1]) === 'a') {
                return $m[0];
            }

            $email = $this->decodificarHexCloudflare($m[4]);

            return $email === null ? $m[0] : e(explode('?', $email)[0]);
        }, $html) ?? $html;

        return preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', function (array $anchor): string {
            $atributos = $anchor[1];
            $hex = null;

            if (preg_match('/\sdata-cfemail\s*=\s*(["\'])([0-9a-f]+)\1/i', $atributos, $dataMatch)) {
                $hex = $dataMatch[2];
            } elseif (preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $atributos, $hrefMatch)) {
                $href = html_entity_decode($hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fragment = parse_url($href, PHP_URL_FRAGMENT);

                if (str_ends_with((string) parse_url($href, PHP_URL_PATH), '/cdn-cgi/l/email-protection') && is_string($fragment)) {
                    $hex = $fragment;
                }
            }

            $email = $hex === null ? null : $this->decodificarHexCloudflare($hex);

            if ($email === null) {
                return $anchor[0];
            }

            $resto = preg_replace('/\s(?:data-cfemail|href)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $atributos) ?? $atributos;
            $resto = rtrim($resto);
            $direccion = e(explode('?', $email)[0]);
            $contenido = preg_replace('/\[email(?:&#160;|&nbsp;|\s|\xC2\xA0)protected\]/i', $direccion, $anchor[2]) ?? $anchor[2];

            return '<a href="mailto:'.e($email).'"'.$resto.'>'.$contenido.'</a>';
        }, $html) ?? $html;
    }

    /**
     * El primer byte es la clave XOR; el resto, el email (con query opcional).
     */
    private function decodificarHexCloudflare(string $hex): ?string
    {
        if (strlen($hex) < 4 || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return null;
        }

        $key = hexdec(substr($hex, 0, 2));
        $email = '';

        for ($index = 2; $index < strlen($hex); $index += 2) {
            $email .= chr(hexdec(substr($hex, $index, 2)) ^ $key);
        }

        $direccion = explode('?', $email)[0];

        return filter_var($direccion, FILTER_VALIDATE_EMAIL) === false ? null : $email;
    }

    private function desescaparMboxrd(string $crudo): string
    {
        return preg_replace_callback('/^(>+)From /m', function (array $m): string {
            return substr($m[1], 1).'From ';
        }, $crudo);
    }
}
