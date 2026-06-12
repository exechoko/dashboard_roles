<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
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
}
