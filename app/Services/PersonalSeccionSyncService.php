<?php

namespace App\Services;

use App\Models\Personal;
use App\Models\PersonalSeccion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene `personal_secciones` al día con la sección real de cada
 * funcionario de 911, derivada de `personal911.funciones.Id_Lugar` (no del
 * texto de `funcion_personal911`, que puede repetirse entre secciones o
 * quedar desactualizado). Pensado para correr después de
 * Personal911ImportService::importar(), una vez que `personals` ya refleja
 * la función vigente de cada uno.
 */
class PersonalSeccionSyncService
{
    public const CACHE_KEY_ULTIMA_SINCRONIZACION = 'personal_secciones.ultima_sincronizacion';

    /**
     * Intervalo mínimo entre sincronizaciones manuales disparadas desde la
     * web, para no golpear la conexión personal911 ni re-procesar de más si
     * alguien le da varias veces al botón.
     */
    public const INTERVALO_MINIMO_SYNC_MANUAL_MINUTOS = 5;

    /**
     * Funciones "neutras" (licencia, pasivo, disponibilidad, etc.) que no
     * pertenecen a ninguna sección operativa. Quien pasa por una de estas
     * sigue contando como miembro de su última sección real, marcado
     * "en licencia", en vez de aparecer como que la dejó.
     *
     * @var list<string>
     */
    private const FUNCIONES_NEUTRAS = [
        'Licencia Excepcional',
        'Pasivo',
        'Disponibilidad',
        'En Comisión',
        'Sin función',
        'J.M.S. - I.T.P.',
    ];

    /**
     * @return array{activos: int, en_licencia: int, bajas: int, sin_cambios: int}
     */
    public function sincronizar(): array
    {
        $mapaFuncionLugar = $this->mapaFuncionLugar();
        $mapaLugares = $this->mapaLugares();
        $hoy = Carbon::today()->toDateString();

        $resultado = ['activos' => 0, 'en_licencia' => 0, 'bajas' => 0, 'sin_cambios' => 0];

        Personal::withTrashed()
            ->whereNotNull('personal911_id')
            ->with('seccion')
            ->chunkById(200, function ($personales) use ($mapaFuncionLugar, $mapaLugares, $hoy, &$resultado) {
                foreach ($personales as $personal) {
                    $clasificacion = self::clasificar(
                        $personal->funcion_personal911,
                        $mapaFuncionLugar,
                        $mapaLugares,
                        $personal->trashed() || $personal->situacion_personal911 === 'Baja'
                    );

                    $cambio = $this->aplicar($personal, $clasificacion, $hoy);
                    $resultado[$cambio]++;
                }
            });

        Cache::forever(self::CACHE_KEY_ULTIMA_SINCRONIZACION, now());

        return $resultado;
    }

    public static function ultimaSincronizacion(): ?Carbon
    {
        return Cache::get(self::CACHE_KEY_ULTIMA_SINCRONIZACION);
    }

    /**
     * Minutos que faltan para poder volver a disparar una sincronización
     * manual, o 0 si ya se puede.
     */
    public static function minutosParaProximaSyncManual(): int
    {
        $ultima = self::ultimaSincronizacion();

        if ($ultima === null) {
            return 0;
        }

        $faltan = self::INTERVALO_MINIMO_SYNC_MANUAL_MINUTOS - $ultima->diffInMinutes(now());

        return max(0, (int) $faltan);
    }

    /**
     * Clasificación pura: dado el texto de función vigente y los mapas de
     * catálogo de personal911, determina a qué estado corresponde. Sin
     * efectos secundarios, testeable sin base de datos.
     *
     * @param  array<string, int>  $mapaFuncionLugar  Nombre de función (trim) => Id_Lugar
     * @param  array<int, string>  $mapaLugares  Id_Lugar => Nombre de lugar
     * @return array{estado: 'activo'|'en_licencia'|'baja', id_lugar?: int, seccion?: string, funcion?: ?string, motivo?: string}
     */
    public static function clasificar(?string $funcionActual, array $mapaFuncionLugar, array $mapaLugares, bool $esBajaOSoftDelete): array
    {
        // La baja policial o el soft-delete ganan siempre, sin importar qué
        // función tenga cargada en ese momento (podría seguir figurando en
        // una neutra o incluso en una operativa si el dato no se actualizó).
        if ($esBajaOSoftDelete) {
            return ['estado' => 'baja', 'motivo' => PersonalSeccion::MOTIVO_BAJA_POLICIAL];
        }

        $funcion = trim((string) $funcionActual);

        // Se evalúa antes que el mapa de lugares a propósito: "Licencia
        // Excepcional", "Pasivo", etc. SÍ existen en el catálogo de
        // personal911 (con Id_Lugar=1, División 911), así que si se
        // consultara el mapa primero siempre resolverían como "activo en
        // División 911" y jamás como ausencia temporal de su sección real.
        if (in_array($funcion, self::FUNCIONES_NEUTRAS, true)) {
            return ['estado' => 'en_licencia', 'funcion' => $funcion !== '' ? $funcion : null];
        }

        $idLugar = $mapaFuncionLugar[$funcion] ?? null;

        if ($idLugar !== null) {
            return [
                'estado' => 'activo',
                'id_lugar' => $idLugar,
                'seccion' => $mapaLugares[$idLugar] ?? "Lugar #{$idLugar}",
                'funcion' => $funcion !== '' ? $funcion : null,
            ];
        }

        return ['estado' => 'baja', 'motivo' => PersonalSeccion::MOTIVO_CAMBIO_SECCION];
    }

    /**
     * @param  array{estado: string, id_lugar?: int, seccion?: string, funcion?: ?string, motivo?: string}  $clasificacion
     * @return 'activos'|'en_licencia'|'bajas'|'sin_cambios'
     */
    private function aplicar(Personal $personal, array $clasificacion, string $hoy): string
    {
        $registro = $personal->seccion ?? new PersonalSeccion(['personal_id' => $personal->id]);

        if ($clasificacion['estado'] === 'activo') {
            $cambioDeSeccion = !$registro->exists
                || (int) $registro->id_lugar_personal911 !== $clasificacion['id_lugar']
                || !$registro->activo;

            $registro->fill([
                'id_lugar_personal911' => $clasificacion['id_lugar'],
                'seccion' => $clasificacion['seccion'],
                'activo' => true,
                'en_licencia' => false,
                'funcion_actual' => $clasificacion['funcion'],
                'fecha_baja' => null,
                'motivo_baja' => null,
            ]);

            if ($cambioDeSeccion) {
                $registro->fecha_alta = $hoy;
            }

            if ($registro->isDirty() || !$registro->exists) {
                $registro->save();

                return 'activos';
            }

            return 'sin_cambios';
        }

        if (!$registro->exists || !$registro->activo) {
            // Nunca perteneció a una sección rastreada (o ya estaba de baja): nada que actualizar.
            return 'sin_cambios';
        }

        if ($clasificacion['estado'] === 'en_licencia') {
            $registro->fill(['en_licencia' => true, 'funcion_actual' => $clasificacion['funcion']]);
            $registro->save();

            return 'en_licencia';
        }

        $registro->fill([
            'activo' => false,
            'en_licencia' => false,
            'fecha_baja' => $hoy,
            'motivo_baja' => $clasificacion['motivo'],
        ]);
        $registro->save();

        return 'bajas';
    }

    /**
     * @return array<string, int>
     */
    private function mapaFuncionLugar(): array
    {
        return DB::connection('personal911')
            ->table('funciones')
            ->get(['Nom_Funcion', 'Id_Lugar'])
            ->mapWithKeys(fn ($fila) => [trim((string) $fila->Nom_Funcion) => (int) $fila->Id_Lugar])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function mapaLugares(): array
    {
        return DB::connection('personal911')
            ->table('lugares')
            ->get(['Id_lugar', 'Nom_Lugar'])
            ->mapWithKeys(fn ($fila) => [(int) $fila->Id_lugar => trim((string) $fila->Nom_Lugar)])
            ->all();
    }
}
