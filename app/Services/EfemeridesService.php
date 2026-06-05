<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Consulta la API pública de Wikipedia ES ("On this day") y arma una lista
 * de efemérides relevantes para Argentina y la provincia de Entre Ríos.
 *
 * API:  https://es.wikipedia.org/api/rest_v1/feed/onthisday/events/MM/DD
 * Sin API key, sin autenticación, sólo pide un User-Agent identificable.
 */
class EfemeridesService
{
    private const ENDPOINT = 'https://es.wikipedia.org/api/rest_v1/feed/onthisday/events/%s/%s';

    private const ENDPOINT_HOLIDAYS = 'https://es.wikipedia.org/api/rest_v1/feed/onthisday/holidays/%s/%s';

    private const CACHE_TTL_HORAS = 26;

    /**
     * Términos sin ambigüedad para Argentina: se buscan en minúsculas, en cualquier parte.
     */
    private const ARGENTINA_FUERTES = [
        'argentina', 'argentino', 'argentinos', 'argentinas',
        'rioplatense', 'virreinato del río de la plata',
        'provincias unidas del río de la plata',
    ];

    /**
     * Localidades / referentes argentinos. Se exigen como palabra completa
     * y con mayúscula inicial (proper noun) para evitar matches con palabras comunes.
     */
    private const ARGENTINA_LOCALIDADES = [
        'Buenos Aires', 'Rosario', 'Mendoza', 'Tucumán', 'La Plata',
        'Mar del Plata', 'Salta', 'Jujuy', 'San Juan', 'Patagonia',
    ];

    /**
     * Términos sin ambigüedad para Entre Ríos: se buscan en minúsculas, en cualquier parte.
     */
    private const ENTRE_RIOS_FUERTES = [
        'entre ríos', 'entre rios', 'entrerriano', 'entrerriana',
        'entrerrianos', 'entrerrianas', 'gualeguaychú', 'gualeguay',
        'concepción del uruguay', 'villaguay', 'francisco ramírez',
    ];

    /**
     * Localidades / referentes entrerrianos ambiguos (también son palabras comunes
     * o nombres de otros lugares). Se exigen como palabra completa, con mayúscula,
     * y además requerimos que el contexto sea argentino (alguna marca AR detectada).
     */
    private const ENTRE_RIOS_LOCALIDADES = [
        'Paraná', 'Concordia', 'Victoria', 'Colón',
        'Feliciano', 'Federación', 'Urquiza',
    ];

    public function __construct(private Client $http = new Client(['timeout' => 15]))
    {
    }

    /**
     * Devuelve las efemérides del día desde caché (las refresca si están ausentes).
     *
     * @return array{fecha:string, generado_en:string, fuente_url:string, argentina:array<int,array>, entre_rios:array<int,array>}
     */
    public function obtener(?Carbon $fecha = null): array
    {
        $fecha = $fecha ?: Carbon::today();
        $cacheKey = $this->cacheKey($fecha);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        return $this->refrescar($fecha);
    }

    /**
     * Devuelve el día conmemorativo del día si existe (ej: "Día del Médico"),
     * y si no hay, cae en la efeméride histórica de Argentina/Entre Ríos.
     *
     * @return array{tipo:string, texto:string, url:?string, anio:?int, alcance:?string}|null
     */
    public function obtenerDestacada(?Carbon $fecha = null): ?array
    {
        $dias = $this->obtenerDiasConmemorativos($fecha);
        if (! empty($dias)) {
            usort($dias, fn ($a, $b) => $this->prioridadHoliday($a) <=> $this->prioridadHoliday($b));
            return array_merge($dias[0], ['tipo' => 'holiday', 'anio' => null, 'alcance' => null]);
        }

        $datos = $this->obtener($fecha);

        $candidata = $this->mejorCandidata($datos['entre_rios']);
        if ($candidata !== null) {
            return array_merge($candidata, ['tipo' => 'evento', 'alcance' => 'Entre Ríos']);
        }

        $candidata = $this->mejorCandidata($datos['argentina']);
        if ($candidata !== null) {
            return array_merge($candidata, ['tipo' => 'evento', 'alcance' => 'Argentina']);
        }

        return null;
    }

    /**
     * Devuelve los días conmemorativos de la fecha (ej: "Día del Médico").
     *
     * @return array<int, array{texto:string, url:?string}>
     */
    public function obtenerDiasConmemorativos(?Carbon $fecha = null): array
    {
        $fecha = $fecha ?: Carbon::today();
        $cacheKey = 'efemerides:holidays:' . $fecha->toDateString();

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $mes = str_pad((string) $fecha->month, 2, '0', STR_PAD_LEFT);
        $dia = str_pad((string) $fecha->day, 2, '0', STR_PAD_LEFT);
        $url = sprintf(self::ENDPOINT_HOLIDAYS, $mes, $dia);

        try {
            $response = $this->http->get($url, [
                'headers' => [
                    'User-Agent' => 'DashboardRoles/1.0 (efemerides; contacto interno)',
                    'Accept' => 'application/json',
                ],
            ]);
            $datos = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar días conmemorativos Wikipedia', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            Cache::put($cacheKey, [], now()->addHours(self::CACHE_TTL_HORAS));
            return [];
        }

        if (! is_array($datos) || ! isset($datos['holidays']) || ! is_array($datos['holidays'])) {
            Cache::put($cacheKey, [], now()->addHours(self::CACHE_TTL_HORAS));
            return [];
        }

        $dias = [];
        foreach ($datos['holidays'] as $holiday) {
            $texto = trim((string) ($holiday['text'] ?? ''));
            if ($texto === '') {
                continue;
            }
            // La API de Wikipedia a veces repite el nombre de la región al inicio: "Argentina Argentina:\n..."
            $texto = preg_replace('/^(.+?)\h+\1:\s*/u', '', $texto);
            $urlPagina = null;
            if (! empty($holiday['pages'][0]['content_urls']['desktop']['page'])) {
                $urlPagina = $holiday['pages'][0]['content_urls']['desktop']['page'];
            }
            $dias[] = ['texto' => $texto, 'url' => $urlPagina];
        }

        Cache::put($cacheKey, $dias, now()->addHours(self::CACHE_TTL_HORAS));
        return $dias;
    }

    /**
     * @param  array<int, array{anio:?int, texto:string, url:?string}>  $lista
     * @return array{anio:?int, texto:string, url:?string}|null
     */
    private function mejorCandidata(array $lista): ?array
    {
        if (empty($lista)) {
            return null;
        }
        // Prefiere con URL; entre las que tienen URL (o entre las que no), la de año más antiguo.
        usort($lista, function ($a, $b) {
            $aTieneUrl = ! empty($a['url']);
            $bTieneUrl = ! empty($b['url']);
            if ($aTieneUrl !== $bTieneUrl) {
                return $aTieneUrl ? -1 : 1;
            }
            return ($a['anio'] ?? PHP_INT_MAX) <=> ($b['anio'] ?? PHP_INT_MAX);
        });
        return $lista[0];
    }

    /**
     * Consulta la API y reemplaza el contenido del caché para la fecha indicada.
     *
     * @return array{fecha:string, generado_en:string, fuente_url:string, argentina:array<int,array>, entre_rios:array<int,array>}
     */
    public function refrescar(?Carbon $fecha = null): array
    {
        $fecha = $fecha ?: Carbon::today();
        $mes = str_pad((string) $fecha->month, 2, '0', STR_PAD_LEFT);
        $dia = str_pad((string) $fecha->day, 2, '0', STR_PAD_LEFT);
        $url = sprintf(self::ENDPOINT, $mes, $dia);

        $payload = $this->descargarEventos($url);
        [$argentina, $entreRios] = $this->clasificar($payload);

        $resultado = [
            'fecha' => $fecha->toDateString(),
            'generado_en' => Carbon::now()->toIso8601String(),
            'fuente_url' => sprintf('https://es.wikipedia.org/wiki/%s_de_%s', (int) $dia, $this->nombreMes((int) $mes)),
            'argentina' => $argentina,
            'entre_rios' => $entreRios,
        ];

        Cache::put($this->cacheKey($fecha), $resultado, now()->addHours(self::CACHE_TTL_HORAS));

        return $resultado;
    }

    /**
     * @return array<int, array{anio:?int, texto:string, url:?string}>
     */
    private function descargarEventos(string $url): array
    {
        try {
            $response = $this->http->get($url, [
                'headers' => [
                    'User-Agent' => 'DashboardRoles/1.0 (efemerides; contacto interno)',
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar efemérides Wikipedia', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        $datos = json_decode((string) $response->getBody(), true);
        if (! is_array($datos) || ! isset($datos['events']) || ! is_array($datos['events'])) {
            return [];
        }

        $eventos = [];
        foreach ($datos['events'] as $evento) {
            $texto = trim((string) ($evento['text'] ?? ''));
            if ($texto === '') {
                continue;
            }
            $url = null;
            if (! empty($evento['pages'][0]['content_urls']['desktop']['page'])) {
                $url = $evento['pages'][0]['content_urls']['desktop']['page'];
            }
            $eventos[] = [
                'anio' => isset($evento['year']) ? (int) $evento['year'] : null,
                'texto' => $texto,
                'url' => $url,
                'paginas_titulos' => array_map(
                    fn ($p) => (string) ($p['normalizedtitle'] ?? $p['title'] ?? ''),
                    is_array($evento['pages'] ?? null) ? $evento['pages'] : []
                ),
            ];
        }
        return $eventos;
    }

    /**
     * @param  array<int, array{anio:?int, texto:string, url:?string, paginas_titulos:array<int,string>}>  $eventos
     * @return array{0: array<int,array>, 1: array<int,array>}
     */
    private function clasificar(array $eventos): array
    {
        $argentina = [];
        $entreRios = [];

        foreach ($eventos as $evento) {
            $textoOriginal = $evento['texto'] . ' ' . implode(' ', $evento['paginas_titulos']);
            $textoMinusculas = mb_strtolower($textoOriginal);

            $esArgentinaPorFuerte = $this->contieneTermino($textoMinusculas, self::ARGENTINA_FUERTES);
            $esArgentinaPorLocalidad = $this->contieneLocalidad($textoOriginal, self::ARGENTINA_LOCALIDADES);
            $esArgentina = $esArgentinaPorFuerte || $esArgentinaPorLocalidad;

            $esEntreRios = $this->contieneTermino($textoMinusculas, self::ENTRE_RIOS_FUERTES);
            if (! $esEntreRios && $esArgentina && $this->contieneLocalidad($textoOriginal, self::ENTRE_RIOS_LOCALIDADES)) {
                // Las localidades ambiguas (Paraná, Victoria, etc.) sólo cuentan
                // si además hay contexto argentino confirmado en el mismo evento.
                $esEntreRios = true;
            }

            $registro = [
                'anio' => $evento['anio'],
                'texto' => $evento['texto'],
                'url' => $evento['url'],
            ];

            if ($esEntreRios) {
                $entreRios[] = $registro;
                $esArgentina = true;
            }
            if ($esArgentina) {
                $argentina[] = $registro;
            }
        }

        usort($argentina, fn ($a, $b) => ($b['anio'] ?? 0) <=> ($a['anio'] ?? 0));
        usort($entreRios, fn ($a, $b) => ($b['anio'] ?? 0) <=> ($a['anio'] ?? 0));

        return [$argentina, $entreRios];
    }

    /**
     * @param  array<int,string>  $terminos
     */
    private function contieneTermino(string $textoMinusculas, array $terminos): bool
    {
        foreach ($terminos as $t) {
            if (str_contains($textoMinusculas, $t)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Coincide cuando la localidad aparece como palabra completa, con mayúscula inicial,
     * en el texto original (case-sensitive). Previene falsos positivos con palabras
     * comunes como "victoria", "federal", "concordia".
     *
     * @param  array<int,string>  $localidades
     */
    private function contieneLocalidad(string $textoOriginal, array $localidades): bool
    {
        foreach ($localidades as $loc) {
            $patron = '/(?<![\p{L}\p{N}])' . preg_quote($loc, '/') . '(?![\p{L}\p{N}])/u';
            if (preg_match($patron, $textoOriginal) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 1 = Argentina/Nacional · 2 = Día Mundial/Internacional · 3 = resto
     *
     * @param  array{texto:string, url:?string}  $holiday
     */
    private function prioridadHoliday(array $holiday): int
    {
        $texto = mb_strtolower($holiday['texto']);

        $esMundial = str_contains($texto, 'mundial') || str_contains($texto, 'internacional');

        $esArgentino = str_contains($texto, 'argentina') || str_contains($texto, 'argentino') ||
            str_contains($texto, 'argentinos') ||
            str_contains($texto, 'entre ríos') || str_contains($texto, 'entre rios') ||
            (! $esMundial && str_contains($texto, 'nacional'));

        if ($esArgentino) {
            return 1;
        }

        if ($esMundial) {
            return 2;
        }

        return 3;
    }

    private function cacheKey(Carbon $fecha): string
    {
        return 'efemerides:' . $fecha->toDateString();
    }

    private function nombreMes(int $mes): string
    {
        return [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ][$mes] ?? 'enero';
    }
}
