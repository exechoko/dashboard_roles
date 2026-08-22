<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\Equipo;
use App\Models\TipoTerminal;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;

class EquiposPorTipoTerminalConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'equipos_por_tipo_terminal';
    }

    public function descripcion(): string
    {
        return 'Cantidad de equipos de comunicación del parque completo agrupados por modelo de terminal (marca y modelo) y por tipo de uso (Portatil, Movil, Base).';
    }

    public function permisos(): array
    {
        return ['ver-equipo'];
    }

    public function parametros(): array
    {
        return [
            'uso' => 'opcional. "Portatil", "Movil", "Base" o "Base - Movil" para limitar el conteo a ese tipo de uso.',
            'estado' => 'opcional. "operativos" o "no operativos" para contar sólo los equipos en esa condición.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $uso = $this->texto($parametros, 'uso');
        $estado = mb_strtolower((string) $this->texto($parametros, 'estado'));

        $consulta = TipoTerminal::query()->with('tipo_uso');

        if ($uso !== null) {
            $consulta->whereHas('tipo_uso', fn ($query) => $query->whereRaw('LOWER(uso) = ?', [mb_strtolower($uso)]));
        }

        $filtroEstado = match (true) {
            str_starts_with($estado, 'no ') => 'noOperativo',
            $estado !== '' => 'operativo',
            default => null,
        };

        $terminales = $consulta->get();

        if ($terminales->isEmpty()) {
            return $uso !== null
                ? 'No hay modelos de terminal cargados con el tipo de uso "' . $uso . '".'
                : 'No hay modelos de terminal cargados en el sistema.';
        }

        $conteos = [];
        foreach ($terminales as $terminal) {
            $query = Equipo::query()->where('tipo_terminal_id', $terminal->id);

            if ($filtroEstado === 'operativo') {
                $query->operativo();
            } elseif ($filtroEstado === 'noOperativo') {
                $query->noOperativo();
            }

            $cantidad = $query->count();
            if ($cantidad === 0) {
                continue;
            }

            $etiqueta = trim($terminal->marca . ' ' . $terminal->modelo);
            $etiqueta = $etiqueta !== '' ? $etiqueta : 'Terminal #' . $terminal->id;

            if ($terminal->tipo_uso?->uso) {
                $etiqueta .= ' (' . $terminal->tipo_uso->uso . ')';
            }

            $conteos[$etiqueta] = ($conteos[$etiqueta] ?? 0) + $cantidad;
        }

        if ($conteos === []) {
            return 'No hay equipos cargados que coincidan con esos filtros.';
        }

        $encabezado = 'Equipos por modelo de terminal';
        if ($uso !== null) {
            $encabezado .= ' con uso ' . $uso;
        }
        if ($filtroEstado !== null) {
            $encabezado .= $filtroEstado === 'operativo' ? ', sólo operativos' : ', sólo no operativos';
        }

        return $encabezado . ' (total ' . $this->numero(array_sum($conteos)) . "):\n"
            . $this->listaDeConteos($conteos)
            . "\n\nDetalle en [Equipos](/equipos).";
    }
}
