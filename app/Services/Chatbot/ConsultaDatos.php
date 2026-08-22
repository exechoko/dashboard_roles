<?php

namespace App\Services\Chatbot;

use App\Models\User;

/**
 * Consulta de datos que el chatbot puede ejecutar sobre la base del sistema.
 *
 * El modelo remoto sólo recibe el nombre, la descripción y los parámetros de
 * cada consulta: nunca ve los resultados. La ejecución y el armado de la
 * respuesta ocurren siempre acá, dentro de la red interna.
 */
abstract class ConsultaDatos
{
    /**
     * Identificador que el modelo debe devolver para pedir esta consulta.
     */
    abstract public function nombre(): string;

    /**
     * Qué responde la consulta, en una línea, para el catálogo del prompt.
     */
    abstract public function descripcion(): string;

    /**
     * Permisos que habilitan la consulta: alcanza con tener uno.
     *
     * @return array<int, string>
     */
    abstract public function permisos(): array;

    /**
     * Parámetros aceptados, como nombre => explicación para el modelo.
     *
     * @return array<string, string>
     */
    public function parametros(): array
    {
        return [];
    }

    /**
     * Ejecuta la consulta y devuelve la respuesta ya redactada en Markdown.
     *
     * @param  array<string, mixed>  $parametros
     */
    abstract public function ejecutar(User $usuario, array $parametros): string;

    public function disponiblePara(User $usuario): bool
    {
        return $usuario->hasAnyPermission($this->permisos());
    }

    /**
     * Lee un parámetro de texto acotado, o null si vino vacío o no es escalar.
     *
     * @param  array<string, mixed>  $parametros
     */
    protected function texto(array $parametros, string $clave, int $largoMaximo = 120): ?string
    {
        $valor = $parametros[$clave] ?? null;

        if (!is_string($valor) && !is_int($valor) && !is_float($valor)) {
            return null;
        }

        $valor = trim(mb_substr((string) $valor, 0, $largoMaximo));

        return $valor !== '' ? $valor : null;
    }

    /**
     * Arma una lista Markdown de "etiqueta: cantidad" ordenada de mayor a menor.
     *
     * @param  array<string, int>  $conteos
     */
    protected function listaDeConteos(array $conteos): string
    {
        arsort($conteos);

        $lineas = [];
        foreach ($conteos as $etiqueta => $cantidad) {
            $lineas[] = '- ' . $etiqueta . ': ' . $this->numero($cantidad);
        }

        return implode("\n", $lineas);
    }

    /**
     * Cuántos ítems se listan como máximo antes de cortar.
     *
     * Un listado sin tope inunda el chat (hay consultas que devuelven cientos de
     * filas) y no se lee. Se muestran los primeros y se manda a la pantalla.
     */
    protected const MAXIMO_ITEMS_LISTADOS = 25;

    /**
     * ¿El usuario pidió el listado y no sólo el número?
     *
     * @param  array<string, mixed>  $parametros
     */
    protected function pidioListado(array $parametros, string $clave = 'listar'): bool
    {
        $valor = $parametros[$clave] ?? null;

        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(mb_strtolower(trim((string) $valor)), ['1', 'si', 'sí', 'true', 'yes'], true);
    }

    /**
     * Arma una lista Markdown de ítems, cortando en MAXIMO_ITEMS_LISTADOS.
     *
     * @param  array<int, string>  $items
     */
    protected function listaDeItems(array $items, string $dondeVerElResto = ''): string
    {
        $total = count($items);
        $mostrados = array_slice($items, 0, self::MAXIMO_ITEMS_LISTADOS);

        $texto = implode("\n", array_map(fn (string $item): string => '- ' . $item, $mostrados));

        if ($total > self::MAXIMO_ITEMS_LISTADOS) {
            $restantes = $total - self::MAXIMO_ITEMS_LISTADOS;
            $texto .= "\n\n" . 'Son ' . $this->numero($total) . ' en total; te muestro los primeros '
                . self::MAXIMO_ITEMS_LISTADOS . '. Los ' . $this->numero($restantes) . ' restantes'
                . ($dondeVerElResto !== '' ? ' los podés ver en ' . $dondeVerElResto . '.' : '.');
        }

        return $texto;
    }

    protected function numero(int $cantidad): string
    {
        return number_format($cantidad, 0, ',', '.');
    }

    protected function pluralizar(int $cantidad, string $singular, string $plural): string
    {
        return $this->numero($cantidad) . ' ' . ($cantidad === 1 ? $singular : $plural);
    }
}
