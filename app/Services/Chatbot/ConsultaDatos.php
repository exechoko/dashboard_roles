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

    protected function numero(int $cantidad): string
    {
        return number_format($cantidad, 0, ',', '.');
    }

    protected function pluralizar(int $cantidad, string $singular, string $plural): string
    {
        return $this->numero($cantidad) . ' ' . ($cantidad === 1 ? $singular : $plural);
    }
}
