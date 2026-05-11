<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActuacionPolicial extends Model
{
    protected $table = 'actuaciones_policiales';

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function destino(){
        return $this->belongsTo(Destino::class);
    }

    public function equipo(){
        return $this->belongsTo(Equipo::class);
    }
    
    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

}
