<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'equipos';
    protected $fillable = [
        'issi',
        'tei',
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

    public function tipo_terminal(){
        return $this->belongsTo(TipoTerminal::class);
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }

    public function actuacion_policial(){
        return $this->hasMany(ActuacionPolicial::class);
    }
}
