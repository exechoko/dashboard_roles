<?php

namespace App\Services;

use App\Models\ParteDiario;
use App\Models\Personal;
use App\Models\RecursoEstadoDiario;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Arma el BORRADOR de un parte diario a partir de la base de personal 911
 * (funciones + jerarquía). Es sólo un punto de partida: todo lo que devuelve
 * se copia al parte y luego se edita a mano (personal extra, ausencias,
 * cambios de guardia imprevistos, personal no actualizado en 911, etc.).
 */
class ParteDiarioBorradorService
{
    /**
     * Funciones (`personals.funcion_personal911`) por tipo de parte y rol.
     * `%G%` = sufijo de guardia (G1..G4); `%T%` = turno (1 día / 2 noche).
     *
     * @var array<string, array<string, list<string>>>
     */
    private const FUNCIONES = [
        ParteDiario::TIPO_MOVILES => [
            'calle'           => ['Móviles %G%'],
            'guardia_interna' => ['SubOf Gdia. Pat. %G%'],
            'jefe_patrulla'   => ['Jefe Patrulla 911'],
            'jefe_calle'      => ['Oficial de Calle'],
            'chofer_jefe'     => ['Chofer Jefe Patrulla'],
            'chofer_segundo'  => ['Chofer 2° Jefe Patrulla', 'Chofer 2º Jefe Patrulla'],
        ],
        ParteDiario::TIPO_MOTOS => [
            'calle'           => ['Motoristas %G%'],
            'guardia_interna' => ['SubOf Gdia. Pat. %G%'],
            'jefe'            => ['Jefe Sección Motorizada'],
            'segundo_jefe'    => ['2° Jefe Sección Motorizada', '2º Jefe Sección Motorizada'],
            'chofer_jefe'     => ['Chofer Jefe Div. - Turno %T%'],
            'chofer_segundo'  => ['Chofer 2° Jefe Div.- Turno %T%', 'Chofer 2º Jefe Div.- Turno %T%'],
        ],
    ];

    /**
     * Rubros de NOVEDADES que se pueden derivar del personal de la guardia.
     *
     * @var array<string, list<string>>
     */
    private const NOVEDADES_FUNCIONES = [
        'sala_monitoreo'   => ['Monitoreo V.G. %G%', 'Video Vigilancia %G%'],
        'sala_armas'       => ['Armería %G%'],
        'personal_guardia' => ['SubOf Gdia. 911 %G%'],
    ];

    /**
     * @return array{
     *     tipo: string,
     *     guardia: string,
     *     guardia_label: string,
     *     turno: string,
     *     mando: array<string, Personal|null>,
     *     guardia_interna: \Illuminate\Support\Collection<int, Personal>,
     *     personal_calle: \Illuminate\Support\Collection<int, Personal>,
     *     novedades: array<string, string>
     * }
     */
    public function armar(string $tipo, string $guardia, Carbon $fechaInicio): array
    {
        $sufijoG = $this->sufijoGuardia($guardia);
        $turno = $fechaInicio->hour < 12 ? '1' : '2';
        $mapa = self::FUNCIONES[$tipo] ?? self::FUNCIONES[ParteDiario::TIPO_MOVILES];

        $roles = ['jefe', 'segundo_jefe', 'jefe_patrulla', 'jefe_calle', 'chofer_jefe', 'chofer_segundo'];
        $mando = [];
        foreach ($roles as $rol) {
            $mando[$rol] = isset($mapa[$rol])
                ? $this->personalPorFuncion($this->expandir($mapa[$rol], $sufijoG, $turno))->first()
                : null;
        }

        return [
            'tipo'            => $tipo,
            'guardia'         => $guardia,
            'guardia_label'   => RecursoEstadoDiario::$guardias[$guardia] ?? $guardia,
            'turno'           => $turno,
            'mando'           => $mando,
            'guardia_interna' => $this->personalPorFuncion($this->expandir($mapa['guardia_interna'] ?? [], $sufijoG, $turno)),
            'personal_calle'  => $this->ordenarJerarquico(
                $this->personalPorFuncion($this->expandir($mapa['calle'] ?? [], $sufijoG, $turno))
            ),
            'novedades'       => $this->novedadesDesdePersonal($sufijoG),
        ];
    }

    /**
     * Rubros de NOVEDADES referidos a móviles, derivados de los estados del turno.
     *
     * @param  \Illuminate\Support\Collection<int, RecursoEstadoDiario>  $estados
     * @return array<string, string>
     */
    public function novedadesDesdeEstados(Collection $estados): array
    {
        $porEstado = fn (array $claves): string => $estados
            ->filter(fn (RecursoEstadoDiario $e) => in_array($e->estado_dia, $claves, true))
            ->map(function (RecursoEstadoDiario $e): string {
                $nombre = $e->recurso?->nombre ?? ('Recurso #' . $e->recurso_id);

                return $e->motivo ? "{$nombre} ({$e->motivo})" : $nombre;
            })
            ->join(' - ');

        return [
            'moviles_fuera_servicio' => $porEstado(['fuera_de_servicio']),
            'moviles_qap'            => $porEstado(['qap_playon', 'reserva']),
            'movil_presto'           => $porEstado(['a_presto']),
            'movil_traslado'         => $porEstado(['de_traslado']),
            'movil_comision'         => $porEstado(['en_comision']),
        ];
    }

    /**
     * @param  list<string>  $funciones
     * @return \Illuminate\Support\Collection<int, Personal>
     */
    private function personalPorFuncion(array $funciones): Collection
    {
        if ($funciones === []) {
            return collect();
        }

        return Personal::query()
            ->activos()
            ->whereIn('funcion_personal911', $funciones)
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Personal>  $personal
     * @return \Illuminate\Support\Collection<int, Personal>
     */
    private function ordenarJerarquico(Collection $personal): Collection
    {
        return $personal
            ->sortBy(fn (Personal $p) => sprintf(
                '%03d|%s|%s',
                Personal::pesoJerarquia($p->jerarquia),
                Str::lower((string) $p->apellido),
                Str::lower((string) $p->nombre),
            ))
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function novedadesDesdePersonal(string $sufijoG): array
    {
        $novedades = [];

        foreach (self::NOVEDADES_FUNCIONES as $rubro => $plantillas) {
            $novedades[$rubro] = $this->formatearLista(
                $this->personalPorFuncion($this->expandir($plantillas, $sufijoG, '1'))
            );
        }

        $novedades['licencia_ordinaria'] = $this->formatearLista(
            Personal::query()->activos()->de911()->get()->filter(
                fn (Personal $p) => Str::contains(
                    Str::lower($p->funcion_personal911 . ' ' . $p->situacion_personal911),
                    'licencia'
                )
            )
        );

        return $novedades;
    }

    /**
     * @param  list<string>  $plantillas
     * @return list<string>
     */
    private function expandir(array $plantillas, string $sufijoG, string $turno): array
    {
        return array_map(
            fn (string $f): string => str_replace(['%G%', '%T%'], [$sufijoG, $turno], $f),
            $plantillas,
        );
    }

    private function sufijoGuardia(string $guardia): string
    {
        return 'G' . preg_replace('/\D+/', '', $guardia);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Personal>  $personal
     */
    private function formatearLista(Collection $personal): string
    {
        return $personal
            ->map(fn (Personal $p) => trim("{$p->jerarquia} {$p->apellido} {$p->nombre}"))
            ->filter()
            ->join('; ');
    }
}
