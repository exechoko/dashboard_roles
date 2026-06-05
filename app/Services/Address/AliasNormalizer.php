<?php

namespace App\Services\Address;

class AliasNormalizer
{
    private const ABREVIATURAS = [
        '/\bav(da)?\.?\b/i'    => 'avenida',
        '/\bpje\.?\b/i'        => 'pasaje',
        '/\bbv\.?\b/i'         => 'boulevard',
        '/\bblvd\.?\b/i'       => 'boulevard',
        '/\bdiag\.?\b/i'       => 'diagonal',
        '/\bgral\.?\b/i'       => 'general',
        '/\bcnel\.?\b/i'       => 'coronel',
        '/\balte\.?\b/i'       => 'almirante',
        '/\bcap\.?\b/i'        => 'capitan',
        '/\btte\.?\b/i'        => 'teniente',
        '/\bsgt\.?\b/i'        => 'sargento',
        '/\bdr\.?\b/i'         => 'doctor',
        '/\bdra\.?\b/i'        => 'doctora',
        '/\bpte\.?\b/i'        => 'presidente',
        '/\brn\.?\b/i'         => 'ruta',
        '/\brp\.?\b/i'         => 'ruta provincial',
        '/\bsn\.?\b/i'         => 'san',
        '/\bsta\.?\b/i'        => 'santa',
    ];

    private const PREFIJOS_TIPO = [
        '/^avenida\s+/i',
        '/^avda\s+/i',
        '/^av\.?\s+/i',
        '/^pasaje\s+/i',
        '/^pje\.?\s+/i',
        '/^boulevard\s+/i',
        '/^bv\.?\s+/i',
        '/^diagonal\s+/i',
        '/^diag\.?\s+/i',
        '/^calle\s+(?!\d)/i',
    ];

    /**
     * Normaliza una cadena a alias de búsqueda:
     * minúsculas, sin acentos, expande abreviaturas comunes.
     */
    public static function toAlias(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e',
        ]);

        foreach (self::ABREVIATURAS as $patron => $expansion) {
            $s = preg_replace($patron, $expansion, $s);
        }

        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    /**
     * Como toAlias pero elimina además el prefijo de tipo de vía.
     * "Av. San Martín" → "san martin"
     */
    public static function toAliasSinTipo(string $s): string
    {
        $s = self::toAlias($s);

        foreach (self::PREFIJOS_TIPO as $patron) {
            $s = trim(preg_replace($patron, '', $s));
        }

        return $s;
    }
}
