<?php

namespace App\Console\Commands;

use App\Models\GeocodificacionDirecta;
use App\Services\GeocodificacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reintenta con Nominatim (self-hosted) las direcciones que quedaron cacheadas
 * sin coordenadas en geocodificacion_directa, aplicando las variantes
 * normalizadas de GeocodificacionService::resolverConNominatim().
 *
 * El grueso del backlog son fallos de la época en que Google era el único motor
 * (fuente='google'). Tras cada intento el registro cambia de fuente: a
 * 'nominatim'/'nominatim_interseccion' si se resolvió, o a 'nominatim' (sin
 * coordenadas) si no — así la corrida es reanudable y nunca repite registros.
 */
class ReintentarGeocodificacionFallida extends Command
{
    protected $signature = 'cecoco:reintentar-geocodificacion-fallida
                            {--limite=2000  : Máximo de direcciones a reintentar en esta corrida (0 = sin tope)}
                            {--pausa=       : Milisegundos entre direcciones (default: delay configurado de Nominatim)}
                            {--fuente=google : Solo reintenta registros fallidos de esta fuente}';

    protected $description = 'Reintenta con Nominatim las direcciones cacheadas sin coordenadas en geocodificacion_directa';

    public function handle(GeocodificacionService $geocoder): int
    {
        if (!$geocoder->nominatimDisponible()) {
            $this->error('El servidor Nominatim no responde. Abortando para no marcar registros como reintentados en vano.');
            return self::FAILURE;
        }

        $limite  = max(0, (int) $this->option('limite'));
        $fuente  = (string) $this->option('fuente');
        $pausaMs = $this->option('pausa') !== null
            ? max(0, (int) $this->option('pausa'))
            : $geocoder->pausaRecomendadaMs();

        $query = GeocodificacionDirecta::whereNull('latitud')
            ->where('fuente', $fuente)
            ->orderBy('id');

        $totalBacklog = (clone $query)->count();

        $this->line('========================================');
        $this->line('[' . now()->format('Y-m-d H:i:s') . '] cecoco:reintentar-geocodificacion-fallida iniciado');
        $this->info("Backlog (fuente={$fuente}, sin coordenadas): {$totalBacklog} | Límite: " . ($limite ?: 'sin tope') . " | Pausa: {$pausaMs}ms");

        if ($totalBacklog === 0) {
            $this->info('No hay direcciones pendientes de reintento.');
            return self::SUCCESS;
        }

        $procesadas = 0;
        $resueltas  = 0;
        $porFuente  = [];

        $query->chunkById(200, function ($registros) use ($geocoder, $limite, $pausaMs, &$procesadas, &$resueltas, &$porFuente) {
            foreach ($registros as $registro) {
                if ($limite > 0 && $procesadas >= $limite) {
                    return false;
                }
                $procesadas++;

                try {
                    $resolucion = $geocoder->resolverConNominatim($registro->direccion_original);
                } catch (\Exception $e) {
                    Log::warning('reintentar-geocodificacion-fallida: error en dirección', [
                        'id'        => $registro->id,
                        'direccion' => $registro->direccion_original,
                        'error'     => $e->getMessage(),
                    ]);
                    continue;
                }

                if ($resolucion) {
                    $registro->update([
                        'latitud'               => $resolucion['lat'],
                        'longitud'              => $resolucion['lng'],
                        'direccion_normalizada' => $resolucion['candidato'],
                        'fuente'                => $resolucion['fuente'],
                    ]);
                    $resueltas++;
                    $porFuente[$resolucion['fuente']] = ($porFuente[$resolucion['fuente']] ?? 0) + 1;
                } else {
                    // Marca el intento cambiando la fuente para no volver a procesarla.
                    $registro->update(['fuente' => 'nominatim']);
                }

                if ($pausaMs > 0) {
                    usleep($pausaMs * 1000);
                }
            }

            $this->line('[' . now()->format('H:i:s') . "] Procesadas: {$procesadas} | Resueltas: {$resueltas}");
            return true;
        });

        $porcentaje = $procesadas > 0 ? round($resueltas * 100 / $procesadas, 1) : 0;
        $this->info("Finalizado. Procesadas: {$procesadas} | Resueltas: {$resueltas} ({$porcentaje}%) | Restan en backlog: " . max(0, $totalBacklog - $procesadas));
        $this->line('========================================');

        Log::info('cecoco:reintentar-geocodificacion-fallida completado', [
            'fuente'     => $fuente,
            'procesadas' => $procesadas,
            'resueltas'  => $resueltas,
            'por_fuente' => $porFuente,
            'backlog'    => max(0, $totalBacklog - $procesadas),
        ]);

        return self::SUCCESS;
    }
}
