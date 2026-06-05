<?php

namespace App\Console\Commands;

use App\Models\Calle;
use App\Models\Localidad;
use App\Services\Address\AliasNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportCallesGeoref extends Command
{
    protected $signature = 'calles:import-georef
                            {--provincia=Entre Ríos : Nombre o ID de provincia a importar}
                            {--dry-run : No persiste, solo muestra cantidades}';

    protected $description = 'Importa calles de Entre Ríos desde Georef (descarga CSV completa, sin rate limit)';

    private const ENDPOINT        = 'https://apis.datos.gob.ar/georef/api/calles';
    private const ENDPOINT_DEPTOS = 'https://apis.datos.gob.ar/georef/api/departamentos';
    private const ENDPOINT_BULK   = 'https://apis.datos.gob.ar/georef/api';
    private const LIMITE_API      = 10000;
    private const BULK_MAX_TOTAL  = 5000; // límite de resultados por bulk request
    private const BULK_PAG_SIZE   = 1000; // resultados por consulta dentro del bulk

    private int $insertados         = 0;
    private int $actualizados       = 0;
    private int $sinonimosGenerados = 0;

    public function handle(): int
    {
        $provincia = $this->option('provincia');
        $dry       = (bool) $this->option('dry-run');

        $this->info("Descargando CSV de calles — provincia: $provincia" . ($dry ? ' [DRY-RUN]' : ''));

        $url = self::ENDPOINT . '.csv?' . http_build_query(['provincia' => $provincia]);
        $this->line("URL: $url");

        $csv = $this->descargarCsv($url);
        if ($csv === null) {
            return 1;
        }

        $this->procesarCsv($csv, $dry);

        $this->line('');
        $this->info("Insertados: {$this->insertados} | Actualizados: {$this->actualizados} | Sinónimos generados: {$this->sinonimosGenerados}");
        return 0;
    }

    private function descargarCsv(string $url): ?string
    {
        $this->line('Descargando...');
        try {
            $resp = Http::timeout(120)->withHeaders([
                'User-Agent' => 'DashboardRoles/1.0 (importacion calles)',
            ])->get($url);

            if (!$resp->successful()) {
                $this->error("Error descargando CSV HTTP {$resp->status()}: " . $resp->body());
                return null;
            }

            $contenido = $resp->body();
            $lineas    = substr_count($contenido, "\n");
            $this->info("Descarga completa. Líneas: $lineas");
            return $contenido;
        } catch (\Exception $e) {
            $this->error("Error de conexión: " . $e->getMessage());
            return null;
        }
    }

    private function procesarCsv(string $csv, bool $dry): void
    {
        $lineas = explode("\n", trim($csv));
        $header = str_getcsv(array_shift($lineas));

        // Mapear índices de columnas por nombre
        $idx = array_flip($header);

        $total = count($lineas);
        $this->info("Calles a procesar: $total");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $row = str_getcsv($linea);

            $georefId   = $row[$idx['calle_id'] ?? $idx['id'] ?? -1] ?? null;
            $nombre     = trim($row[$idx['calle_nombre'] ?? $idx['nombre'] ?? -1] ?? '');
            $tipo       = trim($row[$idx['calle_categoria'] ?? $idx['categoria'] ?? -1] ?? '');
            $alturaIni  = (int) ($row[$idx['calle_altura_inicio_derecha'] ?? -1] ?? 0);
            $alturaFin  = (int) ($row[$idx['calle_altura_fin_derecha'] ?? -1] ?? 0);
            $locNombre  = trim($row[$idx['localidad_censal_nombre'] ?? -1] ?? '');
            $provNombre = trim($row[$idx['provincia_nombre'] ?? -1] ?? '');

            if (empty($georefId) || empty($nombre)) {
                $bar->advance();
                continue;
            }

            $nombreLimpio    = self::limpiarPrefijoTipo($nombre);
            $nombreParaCalle = $tipo ? trim("$tipo $nombreLimpio") : trim($nombre);
            $callenorm       = AliasNormalizer::toAlias($nombreParaCalle);

            if ($dry) {
                $bar->advance();
                continue;
            }

            $loc         = $this->resolveLocalidad($locNombre, $provNombre);
            $localidadId = $loc['id'];
            $localidadCp = $loc['cp'];

            $data = [
                'georef_id'         => $georefId,
                'calle'             => $nombreParaCalle,
                'tipo'              => $tipo ?: null,
                'calle_normalizada' => $callenorm,
                'altura_inicio'     => $alturaIni,
                'altura_fin'        => $alturaFin,
                'localidad'         => $locNombre,
                'localidad_id'      => $localidadId,
                'provincia'         => $provNombre,
                'cp'                => $localidadCp,
                'user'              => 'georef',
            ];

            $existing = Calle::where('georef_id', $georefId)->first();
            if ($existing) {
                $existing->update($data);
                $calleId = $existing->id;
                $this->actualizados++;
            } else {
                $nueva   = Calle::create($data);
                $calleId = $nueva->id;
                $this->insertados++;
            }

            $this->sinonimosGenerados += $this->generarSinonimos($calleId, $nombre, $tipo, $localidadId);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    private function traerDepartamentos(string $provincia): array
    {
        $resp = $this->fetchConReintentos(self::ENDPOINT_DEPTOS, [
            'provincia' => $provincia,
            'max'       => 1000,
            'campos'    => 'id,nombre',
        ]);
        return $resp ? ($resp->json()['departamentos'] ?? []) : [];
    }

    private function totalDeChunk(array $filtros): int
    {
        usleep(300_000); // 0.3s entre sondeos
        $resp = $this->fetchConReintentos(self::ENDPOINT, $filtros + ['max' => 1, 'campos' => 'id']);
        return $resp ? (int) ($resp->json()['total'] ?? 0) : 0;
    }

    private function procesarPorLocalidades(string $provincia, mixed $departamentoId, int $max, bool $dry): void
    {
        $resp = $this->fetchConReintentos('https://apis.datos.gob.ar/georef/api/localidades', [
            'provincia'    => $provincia,
            'departamento' => $departamentoId,
            'max'          => 5000,
            'campos'       => 'id,nombre',
        ]);
        $locs = $resp ? ($resp->json()['localidades'] ?? []) : [];
        foreach ($locs as $loc) {
            $total = $this->totalDeChunk(['provincia' => $provincia, 'localidad_censal' => $loc['id']]);
            if ($total === 0) {
                continue;
            }
            $this->line("   · Localidad: {$loc['nombre']} ($total)");
            $this->procesarChunk(['provincia' => $provincia, 'localidad_censal' => $loc['id']], $total, $max, $dry);
        }
    }

    private function procesarChunk(array $filtros, int $total, int $max, bool $dry): void
    {
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Armar todas las páginas que hacen falta.
        $paginas = [];
        for ($inicio = 0; $inicio < min($total, self::LIMITE_API); $inicio += self::BULK_PAG_SIZE) {
            $paginas[] = $filtros + ['max' => self::BULK_PAG_SIZE, 'inicio' => $inicio];
        }

        // Agrupar en batches de hasta 5 consultas (5 × 1000 = 5000 resultados, límite bulk).
        $batches    = array_chunk($paginas, (int) (self::BULK_MAX_TOTAL / self::BULK_PAG_SIZE));
        $callesBuff = [];

        foreach ($batches as $batch) {
            $body = ['calles' => $batch];
            $resp = $this->fetchBulk($body);
            if (!$resp) {
                return;
            }

            foreach ($resp->json()['resultados'] ?? [] as $resultado) {
                foreach ($resultado['calles'] ?? [] as $c) {
                    $callesBuff[] = $c;
                }
            }

            usleep(300_000); // 0.3 s entre bulk requests
        }

        foreach ($callesBuff as $c) {
            $georefId   = $c['id'] ?? null;
            $nombre     = trim($c['nombre'] ?? '');
            $tipo       = trim($c['categoria'] ?? '');
            $alturaIni  = (int) data_get($c, 'altura.inicio.derecha', 0);
            $alturaFin  = (int) data_get($c, 'altura.fin.derecha', 0);
            $locNombre  = trim(data_get($c, 'localidad_censal.nombre', '') ?? '');
            $provNombre = trim(data_get($c, 'provincia.nombre', '') ?? '');

            if (empty($georefId) || empty($nombre)) {
                $bar->advance();
                continue;
            }

            $nombreLimpio    = self::limpiarPrefijoTipo($nombre);
            $nombreParaCalle = $tipo ? trim("$tipo $nombreLimpio") : trim($nombre);
            $callenorm       = AliasNormalizer::toAlias($nombreParaCalle);

            if ($dry) {
                $bar->advance();
                continue;
            }

            $loc         = $this->resolveLocalidad($locNombre, $provNombre);
            $localidadId = $loc['id'];
            $localidadCp = $loc['cp'];

            $data = [
                'georef_id'         => $georefId,
                'calle'             => $nombreParaCalle,
                'tipo'              => $tipo ?: null,
                'calle_normalizada' => $callenorm,
                'altura_inicio'     => $alturaIni,
                'altura_fin'        => $alturaFin,
                'localidad'         => $locNombre,
                'localidad_id'      => $localidadId,
                'provincia'         => $provNombre,
                'cp'                => $localidadCp,
                'user'              => 'georef',
            ];

            $existing = Calle::where('georef_id', $georefId)->first();
            if ($existing) {
                $existing->update($data);
                $calleId = $existing->id;
                $this->actualizados++;
            } else {
                $nueva   = Calle::create($data);
                $calleId = $nueva->id;
                $this->insertados++;
            }

            $this->sinonimosGenerados += $this->generarSinonimos($calleId, $nombre, $tipo, $localidadId);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    /**
     * POST al endpoint bulk. Hasta 5 consultas × 1000 resultados = 5000 por request.
     */
    private function fetchBulk(array $body, int $intentos = 5): ?\Illuminate\Http\Client\Response
    {
        $espera = 2;
        for ($i = 0; $i < $intentos; $i++) {
            $resp = Http::timeout(60)->post(self::ENDPOINT_BULK, $body);

            if ($resp->successful()) {
                return $resp;
            }

            if ($resp->status() === 429 || $resp->status() >= 500) {
                $this->warn("  Rate limit bulk {$resp->status()}, esperando {$espera}s...");
                sleep($espera);
                $espera = min($espera * 2, 60);
                continue;
            }

            $this->error("Error bulk Georef HTTP {$resp->status()}: " . $resp->body());
            return null;
        }

        $this->error('Se agotaron los reintentos en bulk');
        return null;
    }

    /**
     * Hace GET con reintentos exponenciales ante rate limit (429) o errores transitorios.
     * Devuelve null si se agotan los intentos.
     */
    private function fetchConReintentos(string $url, array $params, int $intentos = 5): ?\Illuminate\Http\Client\Response
    {
        $espera = 2;
        for ($i = 0; $i < $intentos; $i++) {
            $resp = Http::timeout(60)->get($url, $params);

            if ($resp->successful()) {
                return $resp;
            }

            if ($resp->status() === 429 || $resp->status() >= 500) {
                $this->warn("  Rate limit o error {$resp->status()}, esperando {$espera}s...");
                sleep($espera);
                $espera = min($espera * 2, 60);
                continue;
            }

            $this->error("Error API Georef HTTP {$resp->status()}: " . $resp->body());
            return null;
        }

        $this->error("Se agotaron los reintentos para $url");
        return null;
    }

    private function resolveLocalidad(string $nombre, string $provincia): array
    {
        if ($nombre === '') {
            return ['id' => null, 'cp' => null];
        }

        static $cache = [];
        $key = mb_strtolower($nombre);

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $loc = Localidad::whereRaw('LOWER(nombre) = ?', [$key])->first();

        if (!$loc) {
            $loc = Localidad::create(['nombre' => $nombre, 'provincia' => $provincia]);
        }

        return $cache[$key] = ['id' => $loc->id, 'cp' => $loc->cp];
    }

    /**
     * Genera variantes deterministas y las upsertea en sinonimos_calles.
     * Devuelve cuántos sinónimos nuevos se crearon.
     */
    private function generarSinonimos(int $calleId, string $nombre, string $tipo, ?int $localidadId): int
    {
        $norm = fn(string $s) => AliasNormalizer::toAlias($s);

        $nombreLimpio = AliasNormalizer::toAliasSinTipo($nombre);

        $variantes   = [];
        $variantes[] = $norm($nombreLimpio);
        $variantes[] = $norm($nombre);

        if ($tipo !== '') {
            $variantes[] = $norm("$tipo $nombreLimpio");
            foreach (self::abreviaturasTipo($tipo) as $abrev) {
                if ($abrev !== '') {
                    $variantes[] = $norm("$abrev $nombreLimpio");
                }
            }
        }

        foreach (self::expandirTitulos($nombreLimpio) as $alt) {
            $variantes[] = $norm($alt);
        }

        $variantes = array_values(array_unique(array_filter($variantes, fn($v) => $v !== '' && mb_strlen($v) >= 2)));

        $nuevos = 0;
        foreach ($variantes as $alias) {
            $alias = mb_substr($alias, 0, 150);
            try {
                DB::table('sinonimos_calles')->updateOrInsert(
                    ['alias' => $alias, 'localidad_id' => $localidadId],
                    [
                        'calle_id'   => $calleId,
                        'origen'     => 'georef',
                        'confianza'  => 90,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $nuevos++;
            } catch (\Throwable) {
                // alias colisiona con otra calle en la misma localidad: se ignora
            }
        }

        return $nuevos;
    }

    private static function limpiarPrefijoTipo(string $nombre): string
    {
        $patrones = [
            '/^(avenida|avda|av)\s+/i',
            '/^(pasaje|pje)\s+/i',
            '/^(boulevard|bv)\s+/i',
            '/^(diagonal|diag)\s+/i',
            '/^calle\s+(?!\d)/i',
        ];
        return trim(preg_replace($patrones, '', $nombre));
    }

    private static function abreviaturasTipo(string $tipo): array
    {
        $t   = mb_strtoupper(trim($tipo));
        $map = [
            'AVENIDA'   => ['av', 'avda'],
            'CALLE'     => [''],
            'PASAJE'    => ['pje'],
            'BOULEVARD' => ['bv', 'blvd'],
            'DIAGONAL'  => ['diag'],
            'RUTA'      => ['rn', 'rp'],
        ];
        return $map[$t] ?? [];
    }

    /**
     * Expande/colapsa títulos militares y nombres compuestos.
     */
    private static function expandirTitulos(string $nombre): array
    {
        $out    = [];
        $titulos = [
            '/\bgeneral\b/i'    => 'gral',
            '/\bcoronel\b/i'    => 'cnel',
            '/\balmirante\b/i'  => 'alte',
            '/\bcapitan\b/i'    => 'cap',
            '/\bteniente\b/i'   => 'tte',
            '/\bsargento\b/i'   => 'sgt',
            '/\bdoctor\b/i'     => 'dr',
            '/\bpresidente\b/i' => 'pte',
        ];
        foreach ($titulos as $largo => $corto) {
            if (preg_match($largo, $nombre)) {
                $out[] = preg_replace($largo, $corto, $nombre);
                $out[] = trim(preg_replace($largo, '', $nombre));
            }
        }

        $tokens = preg_split('/\s+/', trim($nombre));
        if (count($tokens) >= 3) {
            $out[] = implode(' ', array_slice($tokens, -2));
            $out[] = end($tokens);
        }

        return $out;
    }
}
