<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParteDiarioNovedades extends Model
{
    protected $table = 'parte_diario_novedades';

    /**
     * Rubros fijos de la hoja NOVEDADES (dorso del parte de móviles),
     * en el orden en que se imprimen. clave interna => etiqueta.
     */
    public const RUBROS = [
        'sala_monitoreo'         => 'PERSONAL SALA DE MONITOREO',
        'personal_guardia'       => 'PERSONAL DE GUARDIA',
        'sala_armas'             => 'PERSONAL SALA DE ARMAS',
        'licencia_ordinaria'     => 'PERSONAL LICENCIA ORDINARIA',
        'parte_enfermo'          => 'PERSONAL PARTE DE ENFERMO',
        'cuidado_familiar'       => 'PERSONAL CUIDADO FAMILIAR',
        'bajo_art'               => 'PERSONAL BAJO A.R.T.',
        'autorizados'            => 'PERSONAL AUTORIZADOS',
        'moviles_prever'         => 'MÓVILES EN PREVER',
        'moviles_fuera_servicio' => 'MÓVILES FUERA DE SERVICIO (PLAYÓN 911)',
        'movil_presto'           => 'MÓVIL A PRESTO',
        'moviles_qap'            => 'MÓVILES Q.A.P. (PLAYÓN 911)',
        'movil_traslado'         => 'MÓVIL DE TRASLADO',
        'movil_comision'         => 'MÓVIL EN COMISIÓN',
    ];

    public const SIN_NOVEDAD = 'Sin Novedad';

    /**
     * Rubros de móviles que se calculan solos a partir del estado_dia cargado
     * en la grilla principal del parte (ver ParteDiarioBorradorService::novedadesDesdeEstados()).
     * No admiten edición manual: lo que el usuario tipeó a mano para estas
     * claves se ignora al guardar y al generar el documento.
     *
     * @var list<string>
     */
    public const RUBROS_ESTADO_RECURSO = [
        'moviles_fuera_servicio',
        'movil_presto',
        'moviles_qap',
        'movil_traslado',
        'movil_comision',
    ];

    /**
     * Rubros que refieren a PERSONAL (admiten selección de funcionarios con buscador
     * además de la edición libre del texto). El resto refiere a móviles.
     *
     * @var list<string>
     */
    public const RUBROS_PERSONAL = [
        'sala_monitoreo',
        'personal_guardia',
        'sala_armas',
        'licencia_ordinaria',
        'parte_enfermo',
        'cuidado_familiar',
        'bajo_art',
        'autorizados',
    ];

    protected $fillable = [
        'fecha',
        'guardia',
        'horario',
        'fecha_inicio',
        'fecha_fin',
        'contenido',
        'user_id',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
        'contenido'    => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Devuelve los 14 rubros con su valor, completando con "Sin Novedad".
     * Los rubros de RUBROS_ESTADO_RECURSO ignoran el contenido guardado y
     * usan siempre $computados (ver ParteDiario::rubrosNovedades()).
     *
     * @param  array<string, string>  $computados
     * @return array<string, array{etiqueta: string, valor: string}>
     */
    public function rubrosCompletos(array $computados = []): array
    {
        $contenido = $this->contenido ?? [];
        $salida = [];

        foreach (self::RUBROS as $clave => $etiqueta) {
            $valor = in_array($clave, self::RUBROS_ESTADO_RECURSO, true)
                ? trim((string) ($computados[$clave] ?? ''))
                : trim((string) ($contenido[$clave] ?? ''));

            $salida[$clave] = [
                'etiqueta' => $etiqueta,
                'valor'    => $valor !== '' ? $valor : self::SIN_NOVEDAD,
            ];
        }

        return $salida;
    }
}
