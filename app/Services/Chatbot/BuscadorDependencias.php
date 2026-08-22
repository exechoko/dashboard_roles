<?php

namespace App\Services\Chatbot;

use App\Models\Destino;
use Illuminate\Support\Collection;

/**
 * Resuelve el nombre de una dependencia tal como lo escribe una persona
 * ("la segunda", "Cria. 6ta", "Sección Técnica del 911") contra la tabla de
 * destinos, y expande la jerarquía para contar también lo que cuelga de ella.
 */
class BuscadorDependencias
{
    /**
     * Dependencias que se listan cuando el nombre buscado es ambiguo.
     */
    private const MAXIMO_SUGERENCIAS = 8;

    public function __construct(private NormalizadorTexto $normalizador)
    {
    }

    /**
     * Coincidencias ordenadas por relevancia. Se prueba primero el nombre
     * completo y, si no da resultados, se buscan las dependencias que más
     * palabras comparten con lo escrito: así "Sección Técnica del 911"
     * encuentra a la Sección Técnica aunque el nombre no incluya el 911.
     *
     * @return Collection<int, Destino>
     */
    public function coincidencias(string $texto): Collection
    {
        $buscado = $this->normalizador->normalizar($texto);

        if ($buscado === '') {
            return collect();
        }

        $dependencias = Destino::query()
            ->orderBy('nombre')
            ->get()
            ->each(fn (Destino $destino) => $destino->setAttribute(
                'nombre_normalizado',
                $this->normalizador->normalizar((string) $destino->nombre)
            ));

        $porFrase = $this->buscarPorFraseCompleta($dependencias, $buscado);

        if ($porFrase->isNotEmpty()) {
            return $porFrase;
        }

        return $this->buscarPorPalabras($dependencias, $texto);
    }

    /**
     * Devuelve el texto a responder cuando no hay una única dependencia clara,
     * o null si la búsqueda se resolvió sin ambigüedad.
     *
     * @param  Collection<int, Destino>  $coincidencias
     */
    public function mensajeDeAmbiguedad(string $texto, Collection $coincidencias): ?string
    {
        if ($coincidencias->isEmpty()) {
            return 'No encontré ninguna dependencia que coincida con "' . $texto . '". '
                . 'Podés ver el listado completo en [Dependencias](/dependencias).';
        }

        $buscado = $this->normalizador->normalizar($texto);
        $exactas = $coincidencias->filter(
            fn (Destino $destino): bool => $this->normalizador->normalizar((string) $destino->nombre) === $buscado
        );

        if ($exactas->count() === 1 || $coincidencias->count() === 1) {
            return null;
        }

        $opciones = $coincidencias
            ->take(self::MAXIMO_SUGERENCIAS)
            ->map(fn (Destino $destino): string => '- ' . $destino->nombre)
            ->implode("\n");

        $mensaje = 'Hay varias dependencias que coinciden con "' . $texto . "\". ¿Cuál de estas necesitás?\n" . $opciones;

        if ($coincidencias->count() > self::MAXIMO_SUGERENCIAS) {
            $mensaje .= "\n- …y " . ($coincidencias->count() - self::MAXIMO_SUGERENCIAS) . ' más.';
        }

        return $mensaje;
    }

    /**
     * IDs de la dependencia y de todas las que dependen de ella, en cualquier
     * nivel: cuando alguien pregunta por una departamental espera que estén
     * incluidas sus comisarías y divisiones.
     *
     * @return array<int, int>
     */
    public function idsConDescendientes(Destino $dependencia): array
    {
        $hijosPorPadre = Destino::query()
            ->whereNotNull('parent_id')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [];
        $pendientes = [(int) $dependencia->id];

        while ($pendientes !== []) {
            $actual = array_pop($pendientes);

            if (in_array($actual, $ids, true)) {
                continue;
            }

            $ids[] = $actual;

            foreach ($hijosPorPadre->get($actual, collect()) as $hijo) {
                $pendientes[] = (int) $hijo->id;
            }
        }

        return $ids;
    }

    /**
     * Cómo describir el alcance de un conteo jerárquico, o cadena vacía si la
     * dependencia no tiene nada colgando.
     */
    public function detalleDeAlcance(Destino $dependencia, int $cantidadDeIds): string
    {
        $dependientes = $cantidadDeIds - 1;

        if ($dependientes < 1) {
            return '';
        }

        return ' (incluyendo ' . $dependientes . ' dependencia' . ($dependientes === 1 ? '' : 's') . ' que dependen de ella)';
    }

    /**
     * Coincidencias por el texto completo: exacto, por prefijo o contenido.
     *
     * @param  Collection<int, Destino>  $dependencias
     * @return Collection<int, Destino>
     */
    private function buscarPorFraseCompleta(Collection $dependencias, string $buscado): Collection
    {
        return $dependencias
            ->filter(fn (Destino $destino): bool => str_contains($destino->nombre_normalizado, $buscado))
            ->sortBy(fn (Destino $destino): int => match (true) {
                $destino->nombre_normalizado === $buscado => 0,
                str_starts_with($destino->nombre_normalizado, $buscado) => 1,
                default => 2,
            })
            ->values();
    }

    /**
     * Coincidencias por palabras sueltas: se queda con las dependencias que
     * comparten la mayor cantidad de palabras con lo escrito.
     *
     * @param  Collection<int, Destino>  $dependencias
     * @return Collection<int, Destino>
     */
    private function buscarPorPalabras(Collection $dependencias, string $texto): Collection
    {
        $tokens = $this->normalizador->tokens($texto);

        if ($tokens === []) {
            return collect();
        }

        $puntajes = $dependencias
            ->map(function (Destino $destino) use ($tokens): array {
                $aciertos = 0;

                foreach ($tokens as $token) {
                    if ($this->normalizador->tokenCoincide($destino->nombre_normalizado, $token)) {
                        $aciertos++;
                    }
                }

                return ['dependencia' => $destino, 'aciertos' => $aciertos];
            })
            ->filter(fn (array $fila): bool => $fila['aciertos'] > 0);

        if ($puntajes->isEmpty()) {
            return collect();
        }

        $mejor = $puntajes->max('aciertos');

        // Sin mayoría de palabras en común no hay coincidencia real: "Comisaría
        // de Ciudad Gótica" comparte una palabra con las veinte comisarías.
        if ($mejor * 2 <= count($tokens)) {
            return collect();
        }

        return $puntajes
            ->filter(fn (array $fila): bool => $fila['aciertos'] === $mejor)
            ->map(fn (array $fila): Destino => $fila['dependencia'])
            ->sortBy(fn (Destino $destino): int => mb_strlen((string) $destino->nombre))
            ->values();
    }
}
