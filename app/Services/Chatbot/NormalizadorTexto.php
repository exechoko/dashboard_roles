<?php

namespace App\Services\Chatbot;

/**
 * Acerca lo que escribe una persona a los nombres guardados en la base.
 *
 * Nadie escribe "Comisaría Sexta (6ª)": escribe "Cria. 6ta". Acá se sacan
 * tildes y puntuación, se expanden las abreviaturas del ámbito policial y se
 * separan las palabras que aportan significado.
 */
class NormalizadorTexto
{
    /**
     * Abreviaturas de uso corriente y su forma larga.
     */
    private const ABREVIATURAS = [
        'cria' => 'comisaria',
        'crias' => 'comisaria',
        'com' => 'comisaria',
        'comis' => 'comisaria',
        'dptal' => 'departamental',
        'dptl' => 'departamental',
        'dpto' => 'departamental',
        'depto' => 'departamental',
        'dept' => 'departamental',
        'div' => 'division',
        'divs' => 'division',
        'secc' => 'seccion',
        'sec' => 'seccion',
        'dest' => 'destacamento',
        'dto' => 'destacamento',
        'dir' => 'direccion',
        'gral' => 'general',
        'mov' => 'movil',
        'movs' => 'movil',
        'moto' => 'motopatrulla',
        'jef' => 'jefatura',
    ];

    /**
     * Palabras que no aportan a la búsqueda.
     */
    private const VACIAS = [
        'de', 'del', 'la', 'el', 'los', 'las', 'y', 'en', 'a', 'al', 'un',
        'una', 'para', 'con', 'por', 'que', 'su', 'sus', 'nro', 'numero', 'no',
    ];

    /**
     * Sufijos con los que se escribe un ordinal en números.
     */
    private const SUFIJOS_ORDINALES = ['ra', 'ro', 'da', 'do', 'ta', 'to', 'ma', 'mo', 'va', 'vo', 'era', 'ero'];

    /**
     * Texto en minúsculas, sin tildes ni puntuación y con las abreviaturas
     * expandidas, listo para comparar contra otro texto normalizado.
     */
    public function normalizar(string $texto): string
    {
        $limpio = $this->sinTildes(mb_strtolower(trim($texto)));
        $limpio = preg_replace('/[^a-z0-9\(\)\s]+/u', ' ', $limpio) ?? $limpio;
        $limpio = preg_replace('/\s+/u', ' ', $limpio) ?? $limpio;

        return trim($limpio);
    }

    /**
     * Palabras significativas del texto, con las abreviaturas ya expandidas.
     *
     * @return array<int, string>
     */
    public function tokens(string $texto): array
    {
        $palabras = preg_split('/[\s\(\)]+/u', $this->normalizar($texto)) ?: [];

        $tokens = [];
        foreach ($palabras as $palabra) {
            if ($palabra === '' || in_array($palabra, self::VACIAS, true)) {
                continue;
            }

            $tokens[] = self::ABREVIATURAS[$palabra] ?? $palabra;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Indica si un token aparece en un nombre ya normalizado.
     *
     * Un token numérico también coincide con la forma "(6ª)" con la que se
     * numeran las comisarías, para que "Cria. 6ta" encuentre a la Sexta.
     */
    public function tokenCoincide(string $nombreNormalizado, string $token): bool
    {
        $numero = $this->numeroDeOrdinal($token);

        if ($numero !== null) {
            return $this->contienePalabra($nombreNormalizado, $numero)
                || str_contains($nombreNormalizado, '(' . $numero);
        }

        return str_contains($nombreNormalizado, $token);
    }

    /**
     * Devuelve el número de un token como "6", "6ta" o "13ª", o null si el
     * token no es numérico.
     */
    private function numeroDeOrdinal(string $token): ?string
    {
        if (ctype_digit($token)) {
            return $token;
        }

        foreach (self::SUFIJOS_ORDINALES as $sufijo) {
            if (str_ends_with($token, $sufijo)) {
                $numero = substr($token, 0, -strlen($sufijo));

                if ($numero !== '' && ctype_digit($numero)) {
                    return $numero;
                }
            }
        }

        return null;
    }

    private function contienePalabra(string $texto, string $palabra): bool
    {
        return preg_match('/(?<![a-z0-9])' . preg_quote($palabra, '/') . '(?![a-z0-9])/u', $texto) === 1;
    }

    private function sinTildes(string $texto): string
    {
        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ª' => 'a', 'º' => 'o', '°' => 'o',
        ]);
    }
}
