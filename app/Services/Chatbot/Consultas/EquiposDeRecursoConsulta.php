<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\FlotaGeneral;
use App\Models\Recurso;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use App\Services\Chatbot\NormalizadorTexto;
use Illuminate\Support\Collection;

class EquiposDeRecursoConsulta extends ConsultaDatos
{
    /**
     * Recursos que se listan cuando el nombre buscado es ambiguo.
     */
    private const MAXIMO_SUGERENCIAS = 8;

    public function __construct(private NormalizadorTexto $normalizador)
    {
    }

    public function nombre(): string
    {
        return 'equipos_de_recurso';
    }

    public function descripcion(): string
    {
        return 'Equipos de comunicación que tiene asignados un recurso puntual (por ejemplo "Móvil 802"), con su ISSI, modelo y fecha de asignación.';
    }

    public function permisos(): array
    {
        return ['ver-flota'];
    }

    public function parametros(): array
    {
        return [
            'recurso' => 'Nombre del recurso, por ejemplo "Móvil 802".',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $nombre = $this->texto($parametros, 'recurso');

        if ($nombre === null) {
            return 'Necesito el nombre del recurso, por ejemplo "Móvil 802".';
        }

        $coincidencias = $this->buscarRecursos($nombre);

        if ($coincidencias->isEmpty()) {
            return 'No encontré ningún recurso que coincida con "' . $nombre . '". '
                . 'Podés buscarlo en [Recursos](/recursos).';
        }

        if ($coincidencias->count() > 1) {
            $opciones = $coincidencias
                ->take(self::MAXIMO_SUGERENCIAS)
                ->map(fn (Recurso $recurso): string => '- ' . $recurso->nombre)
                ->implode("\n");

            return 'Hay varios recursos que coinciden con "' . $nombre . "\". ¿Cuál necesitás?\n" . $opciones;
        }

        /** @var Recurso $recurso */
        $recurso = $coincidencias->first();

        $asignaciones = FlotaGeneral::query()
            ->with(['equipo.tipo_terminal', 'equipo.estado'])
            ->where('recurso_id', $recurso->id)
            ->whereNull('fecha_desasignacion')
            ->orderByDesc('fecha_asignacion')
            ->get();

        $dependencia = $recurso->destino?->nombre;
        $encabezado = $recurso->nombre . ($dependencia !== null ? ' (' . $dependencia . ')' : '');

        if ($asignaciones->isEmpty()) {
            return $encabezado . ' no tiene equipos de comunicación asignados actualmente. '
                . 'Detalle en [Flota general](/flota).';
        }

        $lineas = $asignaciones
            ->map(function (FlotaGeneral $asignacion): string {
                $equipo = $asignacion->equipo;
                $terminal = $equipo?->tipo_terminal;

                $detalle = 'ISSI ' . ($equipo?->issi ?: 'no informado');

                if ($terminal !== null) {
                    $detalle .= ', ' . trim($terminal->marca . ' ' . $terminal->modelo);
                }

                if ($equipo?->estado?->nombre) {
                    $detalle .= ', estado ' . $equipo->estado->nombre;
                }

                if ($asignacion->fecha_asignacion !== null) {
                    $detalle .= ', asignado el ' . $asignacion->fecha_asignacion->format('d/m/Y');
                }

                return '- ' . $detalle . '.';
            })
            ->implode("\n");

        return $encabezado . ' tiene '
            . $this->pluralizar($asignaciones->count(), 'equipo asignado', 'equipos asignados') . ":\n"
            . $lineas
            . "\n\nDetalle en [Flota general](/flota).";
    }

    /**
     * Busca el recurso tolerando abreviaturas ("Mov. 802" encuentra al
     * "Móvil 802") y, si el nombre completo no aparece, quedándose con los
     * recursos que más palabras comparten con lo escrito.
     *
     * @return Collection<int, Recurso>
     */
    private function buscarRecursos(string $nombre): Collection
    {
        $buscado = $this->normalizador->normalizar($nombre);

        $recursos = Recurso::query()
            ->with('destino')
            ->orderBy('nombre')
            ->get()
            ->each(fn (Recurso $recurso) => $recurso->setAttribute(
                'nombre_normalizado',
                $this->normalizador->normalizar((string) $recurso->nombre)
            ));

        $porFrase = $recursos
            ->filter(fn (Recurso $recurso): bool => str_contains($recurso->nombre_normalizado, $buscado))
            ->sortBy(fn (Recurso $recurso): int => $recurso->nombre_normalizado === $buscado ? 0 : 1)
            ->values();

        if ($porFrase->isNotEmpty()) {
            return $porFrase->first()->nombre_normalizado === $buscado
                ? $porFrase->take(1)
                : $porFrase;
        }

        return $this->buscarPorPalabras($recursos, $nombre);
    }

    /**
     * @param  Collection<int, Recurso>  $recursos
     * @return Collection<int, Recurso>
     */
    private function buscarPorPalabras(Collection $recursos, string $nombre): Collection
    {
        $tokens = $this->normalizador->tokens($nombre);

        if ($tokens === []) {
            return collect();
        }

        $puntajes = $recursos
            ->map(function (Recurso $recurso) use ($tokens): array {
                $aciertos = 0;

                foreach ($tokens as $token) {
                    if ($this->normalizador->tokenCoincide($recurso->nombre_normalizado, $token)) {
                        $aciertos++;
                    }
                }

                return ['recurso' => $recurso, 'aciertos' => $aciertos];
            })
            ->filter(fn (array $fila): bool => $fila['aciertos'] > 0);

        if ($puntajes->isEmpty()) {
            return collect();
        }

        $mejor = $puntajes->max('aciertos');

        // Sin mayoría de palabras en común no hay coincidencia real.
        if ($mejor * 2 <= count($tokens)) {
            return collect();
        }

        return $puntajes
            ->filter(fn (array $fila): bool => $fila['aciertos'] === $mejor)
            ->map(fn (array $fila): Recurso => $fila['recurso'])
            ->sortBy(fn (Recurso $recurso): int => mb_strlen((string) $recurso->nombre))
            ->values();
    }
}
