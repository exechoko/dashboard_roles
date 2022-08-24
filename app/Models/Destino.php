<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destino';

    public function direccion(){
        return $this->belongsTo(Direcccion::class);
    }

    public function departamental(){
        return $this->belongsTo(Departamental::class);
    }

    public function division(){
        return $this->belongsTo(Division::class);
    }

    public function destacamento(){
        return $this->belongsTo(Destacamento::class);
    }

    public function comisaria(){
        return $this->belongsTo(Comisaria::class);
    }

    public function seccion(){
        return $this->belongsTo(Seccion::class);
    }

    public function empresa_soporte(){
        return $this->belongsTo(EmpresaSoporte::class);
    }
}
