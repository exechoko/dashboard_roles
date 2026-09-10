<?php

namespace App\Console\Commands;

use App\Models\CecocoRecursoAlias;
use App\Models\DetalleExpedienteCecoco;
use App\Models\EventoCecoco;
use App\Services\CecocoExpedienteService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Relevamiento ad-hoc para la Circular Parcial D.G.A.P. y D.E. N° 05/26 (Pautas
 * de Intervención en Crisis de Salud Mental): cuenta expedientes CECOCO cuya
 * descripción menciona una crisis, filtrando solo los que tuvieron al menos un
 * móvil de la División 911 y Videovigilancia interviniendo.
 *
 * El "quién intervino" no está en evento_cecoco: sólo aparece en la tabla de
 * Trámites del detalle por expediente (detalle_expediente_cecoco.detalle_json),
 * que para expedientes viejos no está cacheada y hay que traerla en vivo de
 * CECOCO. Por eso conviene correr primero con --limite chico para medir cuántos
 * expedientes viejos responden bien antes de lanzar todo el rango.
 */
class ReporteCrisisMoviles911 extends Command
{
    protected $signature = 'cecoco:crisis-moviles-911
                            {--desde=2024-05-30 : Fecha de inicio (Y-m-d)}
                            {--hasta= : Fecha de fin (Y-m-d), default hoy}
                            {--palabras=crisis : Palabras clave a buscar en la descripcion, separadas por coma}
                            {--prefijos=p,mp : Prefijos de "Unidad" CECOCO que se consideran moviles del 911, separados por coma}
                            {--pausa=200 : Milisegundos de pausa entre consultas en vivo a CECOCO}
                            {--limite= : Maximo de expedientes a consultar en vivo (para probar en lotes chicos)}
                            {--solo-cache : No consultar CECOCO en vivo, usar solo el detalle ya cacheado}
                            {--exportar= : Ruta de archivo CSV donde volcar el detalle expediente por expediente}';

    protected $description = 'Cuenta eventos CECOCO con intervencion en crisis, filtrando solo los que tuvieron un movil del 911';

    public function handle(CecocoExpedienteService $servicio): int
    {
        $desde = Carbon::parse($this->option('desde'))->startOfDay();
        $hasta = $this->option('hasta')
            ? Carbon::parse($this->option('hasta'))->endOfDay()
            : now()->endOfDay();

        $palabras = collect(explode(',', (string) $this->option('palabras')))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values();

        if ($palabras->isEmpty()) {
            $this->error('Debe indicar al menos una palabra clave con --palabras.');
            return self::FAILURE;
        }

        $prefijos911 = collect(explode(',', (string) $this->option('prefijos')))
            ->map(fn ($p) => mb_strtolower(trim($p)))
            ->filter()
            ->values();

        $pausaMs = max(0, (int) $this->option('pausa'));
        $limite = $this->option('limite') !== null ? max(0, (int) $this->option('limite')) : null;
        $soloCache = (bool) $this->option('solo-cache');
        $rutaExportar = $this->option('exportar');

        $this->line('========================================');
        $this->info("Rango: {$desde->format('d/m/Y')} a {$hasta->format('d/m/Y')} | Palabras: " . $palabras->implode(', ') . ' | Prefijos 911: ' . $prefijos911->implode(', '));

        // 1) Universo de moviles del 911: alias CECOCO ya mapeados en cecoco_recurso_aliases
        // (ver comando cecoco:generar-alias-911 y /cecoco/recursos-alias/ para mantenerlos).
        $aliasActivos = CecocoRecursoAlias::where('activo', true)
            ->with('recurso:id,nombre')
            ->get()
            ->keyBy(fn ($alias) => mb_strtoupper($alias->alias_cecoco));

        $this->info("Alias CECOCO-911 mapeados y activos: {$aliasActivos->count()}");

        // 2) Candidatos: eventos cuya descripcion menciona alguna de las palabras clave.
        $candidatos = EventoCecoco::query()
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->orWhere('descripcion', 'LIKE', "%{$palabra}%");
                }
            })
            ->orderBy('fecha_hora')
            ->get(['id', 'nro_expediente', 'fecha_hora', 'descripcion']);

        $this->info("Eventos candidatos (por texto): {$candidatos->count()}");

        if ($candidatos->isEmpty()) {
            $this->line('========================================');
            return self::SUCCESS;
        }

        // 3) Separar los que ya tienen detalle cacheado de los que faltan.
        $detalles = DetalleExpedienteCecoco::whereIn('evento_cecoco_id', $candidatos->pluck('id'))
            ->get()
            ->keyBy('evento_cecoco_id');

        $pendientes = $candidatos->reject(fn ($e) => $detalles->has($e->id))->values();
        $this->info("Ya cacheados: {$detalles->count()} | Pendientes de consultar: {$pendientes->count()}");

        // 4) Consultar en vivo los pendientes (salvo --solo-cache).
        $erroresPorTipo = [];
        if (!$soloCache && $pendientes->isNotEmpty()) {
            $aProcesar = $limite !== null ? $pendientes->take($limite) : $pendientes;
            $totalAProcesar = $aProcesar->count();
            $this->info("Consultando en vivo a CECOCO: {$totalAProcesar} expediente(s)" . ($limite !== null ? " (limitado con --limite={$limite})" : ''));

            $client = $servicio->iniciarSesionCompartida();
            $ok = 0;
            $consecutivos = 0;

            foreach ($aProcesar->values() as $i => $evento) {
                try {
                    $detalleJson = $servicio->obtenerDetalleExpediente((string) $evento->nro_expediente, $client);

                    $registro = DetalleExpedienteCecoco::updateOrCreate(
                        ['evento_cecoco_id' => $evento->id],
                        [
                            'nro_expediente' => $evento->nro_expediente,
                            'detalle_json' => $detalleJson,
                            'fecha_consulta' => now(),
                        ]
                    );

                    $detalles->put($evento->id, $registro);
                    $ok++;
                    $consecutivos = 0;
                } catch (\Throwable $e) {
                    $consecutivos++;
                    $mensaje = $e->getMessage();
                    $erroresPorTipo[$mensaje] = ($erroresPorTipo[$mensaje] ?? 0) + 1;

                    Log::warning('cecoco:crisis-moviles-911: error consultando expediente', [
                        'expediente' => $evento->nro_expediente,
                        'error' => $mensaje,
                    ]);

                    if ($consecutivos >= 3) {
                        $this->warn('  Reiniciando sesion CECOCO tras fallos consecutivos...');
                        try {
                            $client = $servicio->iniciarSesionCompartida();
                        } catch (\Throwable $e2) {
                            Log::error('cecoco:crisis-moviles-911: no se pudo reiniciar sesion', ['error' => $e2->getMessage()]);
                        }
                        $consecutivos = 0;
                    }
                }

                if (($i + 1) % 50 === 0) {
                    $this->line('  [' . now()->format('H:i:s') . '] ' . ($i + 1) . "/{$totalAProcesar} (ok: {$ok})");
                }

                if ($pausaMs > 0) {
                    usleep($pausaMs * 1000);
                }
            }

            $this->info("Consultas en vivo: {$ok} ok, " . array_sum($erroresPorTipo) . ' con error.');
        }

        // 5) Cruzar cada evento con detalle disponible contra el universo de moviles 911.
        $filas = [];
        $conDetalle = 0;
        $con911 = 0;
        $sinDetalle = 0;
        $prefijosNoContados = [];

        foreach ($candidatos as $evento) {
            $detalle = $detalles->get($evento->id);

            if (!$detalle || empty($detalle->detalle_json)) {
                $sinDetalle++;
                continue;
            }

            $conDetalle++;
            $moviles911 = $this->extraerMoviles911($detalle->detalle_json, $aliasActivos, $prefijos911, $prefijosNoContados);

            if (!empty($moviles911)) {
                $con911++;
            }

            if ($rutaExportar) {
                $filas[] = [
                    'nro_expediente' => $evento->nro_expediente,
                    'fecha_hora' => $evento->fecha_hora,
                    'moviles_911' => implode(' / ', $moviles911),
                    'descripcion' => mb_substr((string) $evento->descripcion, 0, 200),
                ];
            }
        }

        $this->line('========================================');
        $this->info("Total candidatos (texto):         {$candidatos->count()}");
        $this->info("Con detalle disponible:            {$conDetalle}");
        $this->info("Sin detalle (no cacheado/no OK):   {$sinDetalle}");
        $this->info("Con movil 911 interviniente:       {$con911}");

        if (!empty($erroresPorTipo)) {
            $this->line('');
            $this->warn('Errores al consultar CECOCO, agrupados por mensaje:');
            arsort($erroresPorTipo);
            foreach ($erroresPorTipo as $mensaje => $cantidad) {
                $this->line("  [{$cantidad}] {$mensaje}");
            }
        }

        if (!empty($prefijosNoContados)) {
            $this->line('');
            $this->warn('Prefijos de "Unidad" con numero que NO se contaron como 911 (revisar si falta agregarlos con --prefijos):');
            arsort($prefijosNoContados);
            foreach (array_slice($prefijosNoContados, 0, 20, true) as $prefijo => $cantidad) {
                $this->line("  [{$cantidad}] \"{$prefijo}\"");
            }
        }

        if ($rutaExportar && !empty($filas)) {
            $this->exportarCsv($rutaExportar, $filas);
            $this->info("Detalle exportado a: {$rutaExportar}");
        }

        $this->line('========================================');

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $detalleJson
     * @param Collection<string, CecocoRecursoAlias> $aliasActivos
     * @param Collection<int, string> $prefijos911
     * @param array<string, int> $prefijosNoContados
     * @return array<int, string>
     */
    private function extraerMoviles911(array $detalleJson, Collection $aliasActivos, Collection $prefijos911, array &$prefijosNoContados): array
    {
        $encontrados = [];

        foreach ($detalleJson['tramites'] ?? [] as $tramite) {
            $unidad = trim((string) ($tramite['unidad'] ?? ''));
            if ($unidad === '' || $unidad === '-') {
                continue;
            }

            if (!preg_match('/^([a-zA-Z]{1,3})\s*0*(\d+)\s*$/', $unidad, $m)) {
                continue;
            }

            $prefijo = mb_strtolower($m[1]);
            $numero = (int) $m[2];
            $aliasNormalizado = mb_strtoupper($prefijo) . $numero;

            $alias = $aliasActivos->get($aliasNormalizado);
            if ($alias) {
                $encontrados[] = $unidad . ' (' . ($alias->recurso->nombre ?? $aliasNormalizado) . ')';
                continue;
            }

            if (!$prefijos911->contains($prefijo)) {
                $prefijosNoContados[$prefijo] = ($prefijosNoContados[$prefijo] ?? 0) + 1;
            } else {
                // Prefijo P/MP pero sin alias cargado todavia: conviene revisar
                // /cecoco/recursos-alias/ y correr cecoco:generar-alias-911 de nuevo.
                $prefijosNoContados["{$aliasNormalizado} (sin alias cargado)"] = ($prefijosNoContados["{$aliasNormalizado} (sin alias cargado)"] ?? 0) + 1;
            }
        }

        return array_unique($encontrados);
    }

    /**
     * @param array<int, array<string, string>> $filas
     */
    private function exportarCsv(string $ruta, array $filas): void
    {
        $handle = fopen($ruta, 'w');
        fputcsv($handle, array_keys($filas[0]));
        foreach ($filas as $fila) {
            fputcsv($handle, $fila);
        }
        fclose($handle);
    }
}
