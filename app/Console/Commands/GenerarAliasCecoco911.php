<?php

namespace App\Console\Commands;

use App\Models\CecocoRecursoAlias;
use App\Models\Destino;
use App\Models\DetalleExpedienteCecoco;
use App\Models\Recurso;
use Illuminate\Console\Command;

/**
 * Genera mapeos CECOCO -> Recurso (tabla cecoco_recurso_aliases) a partir de las
 * "Unidad" realmente observadas en los detalles de expediente ya cacheados,
 * cruzadas por numero contra los moviles de la Division 911 y Videovigilancia.
 *
 * Sólo propone un alias cuando el numero matchea con exactamente un recurso del
 * destino esperado para ese prefijo (P -> Seccion Patrulla, MP -> Seccion
 * Patrulla Motorizada). Por defecto corre en modo simulacion (dry-run): no
 * guarda nada hasta pasar --aplicar.
 */
class GenerarAliasCecoco911 extends Command
{
    protected $signature = 'cecoco:generar-alias-911
                            {--destino-911=42 : ID del destino raiz de la Division 911 y Videovigilancia}
                            {--aplicar : Si se pasa, guarda los alias en cecoco_recurso_aliases. Sin esta opcion solo se muestra un resumen (dry-run)}';

    protected $description = 'Genera mapeos CECOCO-Recurso para los moviles del 911, a partir de las Unidades observadas en los detalles ya cacheados';

    public function handle(): int
    {
        $aplicar = (bool) $this->option('aplicar');

        $destino911 = Destino::find((int) $this->option('destino-911'));
        if (!$destino911) {
            $this->error('No existe el destino raiz indicado.');
            return self::FAILURE;
        }

        $this->line('========================================');
        $this->info('Modo: ' . ($aplicar ? 'APLICAR (va a guardar en la base)' : 'SIMULACION (dry-run, no guarda nada)'));

        // 1) Recolectar todas las "Unidad" P#### / MP#### realmente vistas en CECOCO.
        $conteoAlias = [];
        DetalleExpedienteCecoco::query()
            ->whereNotNull('detalle_json')
            ->select('id', 'detalle_json')
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use (&$conteoAlias) {
                foreach ($chunk as $detalle) {
                    foreach (($detalle->detalle_json['tramites'] ?? []) as $tramite) {
                        $unidad = trim((string) ($tramite['unidad'] ?? ''));
                        if ($unidad === '') {
                            continue;
                        }

                        if (!preg_match('/^([a-zA-Z]{1,3})\s*0*(\d+)\s*$/', $unidad, $m)) {
                            continue;
                        }

                        $prefijo = mb_strtolower($m[1]);
                        if (!in_array($prefijo, ['p', 'mp'], true)) {
                            continue;
                        }

                        $alias = mb_strtoupper($prefijo) . (int) $m[2];
                        $conteoAlias[$alias] = ($conteoAlias[$alias] ?? 0) + 1;
                    }
                }
            });

        $this->info('Alias CECOCO distintos observados (prefijo P/MP): ' . count($conteoAlias));

        // 2) Recursos candidatos, en TODO el subarbol de la division 911 (no solo
        // las secciones hijas: hay moviles cargados directamente bajo el destino
        // padre). Se separan por si el nombre contiene "moto" (motorizada, prefijo
        // MP) o no (patrulla de a pie/auto, prefijo P), en vez de por destino_id,
        // porque hay excepciones (ej. "Móvil 1013" cargado bajo la seccion
        // motorizada pese al nombre "Móvil").
        $destinoIds = $destino911->getDestinosHijosRecursivo();
        $recursosSubarbol = Recurso::whereIn('destino_id', $destinoIds)->get(['id', 'nombre']);

        $recursosPatrulla = collect();
        $recursosMotorizada = collect();
        foreach ($recursosSubarbol as $r) {
            if (!preg_match('/(\d+)/', $r->nombre, $m)) {
                continue;
            }
            $numero = (int) $m[1];
            $destino = str_contains(mb_strtolower($r->nombre), 'moto') ? 'motorizada' : 'patrulla';
            if ($destino === 'motorizada') {
                $recursosMotorizada->put($numero, $r);
            } else {
                $recursosPatrulla->put($numero, $r);
            }
        }

        $yaMapeados = CecocoRecursoAlias::pluck('recurso_id', 'alias_cecoco');

        $propuestas = [];
        $sinRecurso = [];
        $yaExistentes = 0;

        foreach ($conteoAlias as $alias => $cantidad) {
            if ($yaMapeados->has($alias)) {
                $yaExistentes++;
                continue;
            }

            $prefijo = mb_strtolower(rtrim(preg_replace('/\d+$/', '', $alias)));
            $numero = (int) preg_replace('/^\D+/', '', $alias);

            $recurso = $prefijo === 'p'
                ? $recursosPatrulla->get($numero)
                : $recursosMotorizada->get($numero);

            if ($recurso) {
                $propuestas[] = [
                    'alias_cecoco' => $alias,
                    'recurso_id' => $recurso->id,
                    'recurso_nombre' => $recurso->nombre,
                    'observaciones' => $cantidad,
                ];
            } else {
                $sinRecurso[$alias] = $cantidad;
            }
        }

        $this->info("Ya mapeados anteriormente:        {$yaExistentes}");
        $this->info('Propuestas con recurso encontrado: ' . count($propuestas));
        $this->info('Sin recurso correspondiente:       ' . count($sinRecurso));

        if (!empty($propuestas)) {
            $this->table(
                ['Alias CECOCO', 'Recurso', 'Veces visto'],
                array_map(fn ($p) => [$p['alias_cecoco'], $p['recurso_nombre'], $p['observaciones']], $propuestas)
            );
        }

        if (!empty($sinRecurso)) {
            $this->line('');
            $this->warn('Alias vistos en CECOCO sin recurso correspondiente en el sistema (revisar manualmente):');
            arsort($sinRecurso);
            foreach ($sinRecurso as $alias => $cantidad) {
                $this->line("  [{$cantidad}] {$alias}");
            }
        }

        if (!$aplicar) {
            $this->line('');
            $this->comment('Simulacion: no se guardo nada. Volve a correr con --aplicar para persistir estas ' . count($propuestas) . ' filas en cecoco_recurso_aliases.');
            $this->line('========================================');
            return self::SUCCESS;
        }

        $creados = 0;
        foreach ($propuestas as $p) {
            CecocoRecursoAlias::create([
                'alias_cecoco' => $p['alias_cecoco'],
                'recurso_id' => $p['recurso_id'],
                'activo' => true,
                'observaciones' => "Generado automaticamente desde detalle CECOCO (visto {$p['observaciones']} veces).",
            ]);
            $creados++;
        }

        $this->info("Guardados {$creados} mapeos en cecoco_recurso_aliases.");
        $this->line('========================================');

        return self::SUCCESS;
    }
}
