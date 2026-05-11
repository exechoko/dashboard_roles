<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historico extends Model
{
    protected $table = 'historico';

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function flota(){
        return $this->hasMany(FlotaGeneral::class);
    }

    public function equipo(){
        return $this->belongsTo(Equipo::class);
    }

    public function recurso(){
        return $this->belongsTo(Recurso::class);
    }

    public function tipoMovimiento(){
        return $this->belongsTo(TipoMovimiento::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }
}
