<?php

namespace App\Services;

use App\Models\ActivacionTotem;
use App\Models\EventoCecoco;
use Carbon\Carbon;

class DetectorActivacionesTotem
{
    /**
     * Fragmentos que, presentes en la descripción, identifican una activación
     * real de tótem BDE (case-insensitive vía LIKE, MySQL collation es _ci).
     *
     * @var array<int, string>
     */
    private const PALABRAS_CLAVE = ['totem', 'tótem'];

    /**
     * Frases que, aunque contienen "totem", refieren al boliche del mismo
     * nombre y no al tótem de emergencia: hay que descartarlas.
     *
     * @var array<int, string>
     */
    private const EXCLUSIONES = ['boliche totem', 'totem disco', 'disco totem'];

    public function detectar(int $dias = 7): int
    {
        $desde = Carbon::now()->subDays($dias)->startOfDay();

        $eventos = EventoCecoco::query()
            ->where('fecha_hora', '>=', $desde)
            ->where(function ($query): void {
                foreach (self::PALABRAS_CLAVE as $palabra) {
                    $query->orWhere('descripcion', 'LIKE', "%{$palabra}%");
                }
                $query->orWhereRaw("descripcion REGEXP '\\\\bBDE\\\\b'");
            })
            ->where(function ($query): void {
                foreach (self::EXCLUSIONES as $exclusion) {
                    $query->where('descripcion', 'NOT LIKE', "%{$exclusion}%");
                }
            })
            ->whereNotIn('id', function ($query): void {
                $query->select('evento_cecoco_id')->from('activaciones_totem');
            })
            ->get();

        $creadas = 0;

        foreach ($eventos as $evento) {
            ActivacionTotem::create([
                'evento_cecoco_id' => $evento->id,
                'nro_expediente' => $evento->nro_expediente,
                'fecha_evento' => $evento->fecha_hora,
                'palabra_detectada' => $this->detectarPalabra($evento->descripcion ?? ''),
                'estado' => ActivacionTotem::ESTADO_PENDIENTE,
            ]);
            $creadas++;
        }

        return $creadas;
    }

    private function detectarPalabra(string $descripcion): string
    {
        $descripcionLower = mb_strtolower($descripcion);

        foreach (self::PALABRAS_CLAVE as $palabra) {
            if (str_contains($descripcionLower, $palabra)) {
                return 'totem';
            }
        }

        return 'BDE';
    }
}
