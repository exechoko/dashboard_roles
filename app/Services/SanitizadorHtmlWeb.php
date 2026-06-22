<?php

namespace App\Services;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

class SanitizadorHtmlWeb
{
    /**
     * Tags permitidos en el contenido editable de la web.
     *
     * @var list<string>
     */
    private const TAGS_PERMITIDOS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li',
        'a', 'h3', 'h4', 'h5', 'h6', 'span', 'div', 'small', 'blockquote',
    ];

    /**
     * Atributos permitidos por tag.
     *
     * @var list<string>
     */
    private const ATRIBUTOS_PERMITIDOS = [
        'href', 'target', 'rel', 'style', 'class', 'aria-hidden',
    ];

    /**
     * Limpia un fragmento HTML dejando solo tags/atributos de la allowlist.
     * Elimina scripts, handlers on* y URLs javascript:.
     */
    public static function limpiar(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $documento = new DOMDocument('1.0', 'UTF-8');
        $previo = libxml_use_internal_errors(true);
        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="__raiz__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $raiz = $documento->getElementById('__raiz__');
        if (! $raiz instanceof DOMElement) {
            return '';
        }

        self::limpiarNodo($raiz);

        $salida = '';
        foreach (iterator_to_array($raiz->childNodes) as $hijo) {
            $salida .= $documento->saveHTML($hijo);
        }

        return trim($salida);
    }

    private static function limpiarNodo(DOMNode $nodo): void
    {
        foreach (iterator_to_array($nodo->childNodes) as $hijo) {
            if ($hijo instanceof DOMElement) {
                $tag = strtolower($hijo->nodeName);

                if (! in_array($tag, self::TAGS_PERMITIDOS, true)) {
                    self::desenvolver($hijo);
                    continue;
                }

                self::limpiarAtributos($hijo);
                self::limpiarNodo($hijo);
            }
        }
    }

    /**
     * Reemplaza un elemento no permitido por sus hijos (conservando el texto),
     * salvo que sea un tag peligroso, en cuyo caso se descarta por completo.
     */
    private static function desenvolver(DOMElement $elemento): void
    {
        $padre = $elemento->parentNode;
        if ($padre === null) {
            return;
        }

        $tag = strtolower($elemento->nodeName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'form'], true)) {
            $padre->removeChild($elemento);

            return;
        }

        self::limpiarNodo($elemento);
        while ($elemento->firstChild !== null) {
            $padre->insertBefore($elemento->firstChild, $elemento);
        }
        $padre->removeChild($elemento);
    }

    private static function limpiarAtributos(DOMElement $elemento): void
    {
        foreach (iterator_to_array($elemento->attributes) as $atributo) {
            /** @var DOMAttr $atributo */
            $nombre = strtolower($atributo->nodeName);

            if (! in_array($nombre, self::ATRIBUTOS_PERMITIDOS, true)) {
                $elemento->removeAttribute($atributo->nodeName);
                continue;
            }

            if ($nombre === 'href' && self::esUrlPeligrosa($atributo->nodeValue ?? '')) {
                $elemento->removeAttribute($atributo->nodeName);
            }
        }
    }

    private static function esUrlPeligrosa(string $url): bool
    {
        $normalizada = strtolower(preg_replace('/\s+/', '', $url) ?? '');

        return str_starts_with($normalizada, 'javascript:')
            || str_starts_with($normalizada, 'data:')
            || str_starts_with($normalizada, 'vbscript:');
    }
}
