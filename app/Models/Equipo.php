<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    /**
     * Nombres de estado que se consideran "operativo".
     */
    public const ESTADOS_OPERATIVOS = ['Nuevo', 'Usado', 'Reparado'];

    /**
     * Nombres de estado que se consideran "no operativo".
     *
     * "Recambio" cuenta como no operativo porque esos equipos ya no los tiene
     * la Policía: fueron devueltos o cambiados por otro.
     */
    public const ESTADOS_NO_OPERATIVOS = ['Baja', 'No funciona', 'Perdido', 'Degradado - Sin Accesorios', 'Recambio'];

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
        'operativo',
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
        'operativo' => 'boolean',
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
}
