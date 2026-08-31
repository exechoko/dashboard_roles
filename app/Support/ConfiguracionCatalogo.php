<?php

namespace App\Support;

/**
 * Envoltorio de solo lectura sobre config('configuracion_sistema'): centraliza
 * las reglas de qué claves del .env existen, a qué grupo pertenecen, cuáles
 * son sensibles (se enmascaran / nunca se pisan en blanco) y cuáles requieren
 * el permiso extra de "críticas".
 */
class ConfiguracionCatalogo
{
    /**
     * @return array<string, array{titulo: string, icono: string, claves: array<string, array<string, mixed>>}>
     */
    public static function grupos(): array
    {
        return config('configuracion_sistema.grupos', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function metaGrupo(string $grupo): array
    {
        return self::grupos()[$grupo]['claves'] ?? [];
    }

    /**
     * Todas las claves catalogadas (de todos los grupos), clave => meta.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function todasLasClaves(): array
    {
        $todas = [];
        foreach (self::grupos() as $grupo) {
            foreach ($grupo['claves'] as $clave => $meta) {
                $todas[$clave] = $meta;
            }
        }

        return $todas;
    }

    /**
     * @return array<int, string>
     */
    public static function clavesDeGrupo(string $grupo): array
    {
        return array_keys(self::metaGrupo($grupo));
    }

    public static function existeEnCatalogo(string $clave): bool
    {
        return array_key_exists($clave, self::todasLasClaves());
    }

    public static function grupoCritico(): string
    {
        return config('configuracion_sistema.grupo_critico', 'criticas');
    }

    public static function esCritica(string $clave): bool
    {
        return in_array($clave, self::clavesDeGrupo(self::grupoCritico()), true);
    }

    /**
     * @return array<int, string>
     */
    public static function clavesBloqueadas(): array
    {
        return config('configuracion_sistema.claves_bloqueadas', []);
    }

    public static function estaBloqueada(string $clave): bool
    {
        return in_array($clave, self::clavesBloqueadas(), true);
    }

    /**
     * Sensible = se enmascara en la UI y una entrega en blanco no pisa el
     * valor actual. Para claves catalogadas, lo decide el tipo 'password'; para
     * claves libres (pestaña Avanzado), el patrón configurado.
     */
    public static function esSensible(string $clave): bool
    {
        $meta = self::todasLasClaves()[$clave] ?? null;
        if ($meta !== null) {
            return ($meta['tipo'] ?? 'text') === 'password';
        }

        $patron = config('configuracion_sistema.patron_sensible');

        return $patron && preg_match($patron, $clave) === 1;
    }
}
