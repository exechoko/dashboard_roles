<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Bodycam;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;

class BodycamsPorEstadoConsulta extends ConsultaDatos
{
    /**
     * Etiquetas legibles de los valores guardados en la columna `estado`.
     */
    private const ETIQUETAS = [
        Bodycam::ESTADO_DISPONIBLE => 'Disponibles',
        Bodycam::ESTADO_ENTREGADA => 'Entregadas',
        Bodycam::ESTADO_PERDIDA => 'Perdidas',
        Bodycam::ESTADO_MANTENIMIENTO => 'En mantenimiento',
        Bodycam::ESTADO_DADA_BAJA => 'Dadas de baja',
    ];

    public function nombre(): string
    {
        return 'bodycams_por_estado';
    }

    public function descripcion(): string
    {
        return 'Cantidad de bodycams por estado: disponibles, entregadas, perdidas, en mantenimiento o dadas de baja.';
    }

    public function permisos(): array
    {
        return ['ver-bodycam'];
    }

    public function parametros(): array
    {
        return [
            'estado' => 'opcional. "disponible", "entregada", "perdida", "mantenimiento" o "dada_baja".',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $estado = $this->texto($parametros, 'estado');

        if ($estado !== null) {
            $clave = mb_strtolower(str_replace(' ', '_', $estado));

            if (!array_key_exists($clave, self::ETIQUETAS)) {
                return 'El estado "' . $estado . '" no existe. Los estados válidos son: '
                    . implode(', ', array_keys(self::ETIQUETAS)) . '.';
            }

            $cantidad = Bodycam::query()->where('estado', $clave)->count();

            return 'Hay ' . $this->pluralizar($cantidad, 'bodycam', 'bodycams') . ' en estado '
                . self::ETIQUETAS[$clave] . '. Detalle en [Bodycams](/bodycams).';
        }

        $conteos = Bodycam::query()
            ->selectRaw('estado, COUNT(*) AS cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado')
            ->mapWithKeys(fn ($cantidad, $estado): array => [
                self::ETIQUETAS[$estado] ?? ($estado ?: 'Sin estado') => (int) $cantidad,
            ])
            ->all();

        if ($conteos === []) {
            return 'No hay bodycams cargadas en el sistema.';
        }

        return 'Bodycams por estado (total ' . $this->numero(array_sum($conteos)) . "):\n"
            . $this->listaDeConteos($conteos)
            . "\n\nDetalle en [Bodycams](/bodycams).";
    }
}
