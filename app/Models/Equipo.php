<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    /**
     * Nombres de estado que se consideran "operativo".
     *
     * "Temporal" cuenta como operativo: es un equipo prestado/provisorio en uso
     * real, no un equipo roto ni fuera de servicio.
     */
    public const ESTADOS_OPERATIVOS = ['Nuevo', 'Usado', 'Reparado', 'Temporal'];

    /**
     * Nombres de estado que se consideran "no operativo".
     *
     * "Recambio" cuenta como no operativo porque esos equipos ya no los tiene
     * la Policía: fueron devueltos o cambiados por otro.
     */
    public const ESTADOS_NO_OPERATIVOS = ['Baja', 'No funciona', 'Perdido', 'Degradado - Sin Accesorios', 'Recambio'];

    /**
     * Accesorios que se relevan por equipo, con su etiqueta para mostrar.
     *
     * El valor es de tres estados a propósito: NULL = no relevado (o no aplica a
     * ese modelo), true = lo tiene, false = le falta. Solo el false degrada la
     * disponibilidad, así que un equipo sin relevar nunca se penaliza.
     *
     * @var array<string, string>
     */
    public const ACCESORIOS = [
        'rf' => 'Antena R.F.',
        'frente_remoto' => 'Frente remoto',
        'gps' => 'GPS',
        'kit_inst' => 'Kit de instalación',
    ];

    /**
     * Columna de observación libre asociada a cada accesorio.
     *
     * Los nombres vienen del esquema original y no son regulares
     * ("frente_remoto" guarda su descripción en "desc_frente").
     *
     * @var array<string, string>
     */
    public const ACCESORIOS_DESCRIPCION = [
        'rf' => 'desc_rf',
        'frente_remoto' => 'desc_frente',
        'gps' => 'desc_gps',
        'kit_inst' => 'desc_kit_inst',
    ];

    /**
     * Columna de observación que corresponde a un accesorio.
     */
    public static function descripcionCampo(string $accesorio): string
    {
        return self::ACCESORIOS_DESCRIPCION[$accesorio];
    }

    protected $table = 'equipos';
    protected $fillable = [
        'issi',
        'tei',
        'numero_bateria',
        'numero_segunda_bateria',
        'tipo_terminal_id',
        'estado_id',
        'fecha_estado',
        'gps',
        'desc_gps',
        'frente_remoto',
        'desc_frente',
        'rf',
        'desc_rf',
        'kit_inst',
        'desc_kit_inst',
        'propietario',
        'condicion',
        'con_garantia',
        'fecha_venc_garantia',
        'observaciones',
    ];

    protected $casts = [
        'fecha_estado' => 'date',
        'fecha_venc_garantia' => 'date',
        'gps' => 'boolean',
        'frente_remoto' => 'boolean',
        'rf' => 'boolean',
        'kit_inst' => 'boolean',
        'con_garantia' => 'boolean',
    ];

    public function tipo_terminal(){
        return $this->belongsTo(TipoTerminal::class);
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }

    public function actuacion_policial(){
        return $this->hasMany(ActuacionPolicial::class);
    }

    public function flota_general(){
        return $this->hasMany(FlotaGeneral::class);
    }

    public function cecocoAliases(): HasMany
    {
        return $this->hasMany(CecocoRecursoAlias::class);
    }

    public function historico(){
        return $this->hasMany(Historico::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    /**
     * IDs de los estados considerados "operativo" (Nuevo/Usado/Reparado).
     */
    public static function operativoEstadoIds(): \Illuminate\Support\Collection
    {
        return static::estadoIdsPorNombre(self::ESTADOS_OPERATIVOS);
    }

    /**
     * IDs de los estados considerados "no operativo" (Baja/No funciona/Perdido/
     * Degradado - Sin Accesorios/Recambio).
     */
    public static function noOperativoEstadoIds(): \Illuminate\Support\Collection
    {
        return static::estadoIdsPorNombre(self::ESTADOS_NO_OPERATIVOS);
    }

    private static function estadoIdsPorNombre(array $nombres): \Illuminate\Support\Collection
    {
        return Estado::whereIn('nombre', $nombres)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Equipos operativos (Nuevo/Usado/Reparado), sin importar instalación.
     */
    public function scopeOperativo($query)
    {
        return $query->whereIn('equipos.estado_id', self::operativoEstadoIds());
    }

    /**
     * Equipos no operativos (Baja/No funciona/Perdido/Degradado - Sin Accesorios/Recambio).
     */
    public function scopeNoOperativo($query)
    {
        return $query->whereIn('equipos.estado_id', self::noOperativoEstadoIds());
    }

    /**
     * Al equipo le falta al menos un accesorio relevado (algún flag en false).
     *
     * Es independiente del estado: un equipo puede estar "Usado" (el transceptor
     * funciona) y aun así no poder salir a la calle porque no tiene antena.
     */
    public function scopeSinAccesorios($query)
    {
        return self::filtrarSinAccesorios($query);
    }

    /**
     * Al equipo no le falta ningún accesorio relevado.
     */
    public function scopeConAccesoriosCompletos($query)
    {
        return self::filtrarConAccesoriosCompletos($query);
    }

    /**
     * Igual que el scope, pero aplicable a cualquier query que tenga joineada la
     * tabla equipos (por ejemplo las de flota_general), donde los scopes del
     * modelo Equipo no están disponibles.
     */
    public static function filtrarSinAccesorios($query)
    {
        return $query->where(function ($q) {
            foreach (array_keys(self::ACCESORIOS) as $accesorio) {
                $q->orWhere("equipos.{$accesorio}", false);
            }
        });
    }

    /**
     * Igual que el scope, pero aplicable a cualquier query con equipos joineada.
     */
    public static function filtrarConAccesoriosCompletos($query)
    {
        return $query->where(function ($q) {
            foreach (array_keys(self::ACCESORIOS) as $accesorio) {
                $q->where(fn ($sub) => $sub->whereNull("equipos.{$accesorio}")
                    ->orWhere("equipos.{$accesorio}", true));
            }
        });
    }

    /**
     * Equipos realmente disponibles: estado operativo y sin accesorios faltantes.
     */
    public function scopeDisponible($query)
    {
        return $query->operativo()->conAccesoriosCompletos();
    }

    /**
     * Equipos degradados: el equipo en sí funciona, pero le falta un accesorio,
     * así que hoy no presta servicio. Se recuperan comprando el repuesto.
     */
    public function scopeDegradado($query)
    {
        return $query->operativo()->sinAccesorios();
    }

    /**
     * Etiquetas de los accesorios que le faltan a este equipo.
     *
     * @return array<int, string>
     */
    public function accesoriosFaltantes(): array
    {
        $faltantes = [];

        foreach (self::ACCESORIOS as $campo => $etiqueta) {
            if ($this->{$campo} === false) {
                $faltantes[] = $etiqueta;
            }
        }

        return $faltantes;
    }
}
