<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destino';

    public function direccion(){
        return $this->belongsTo(Direccion::class);
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

    public function recurso(){
        return $this->hasMany(Recurso::class);
    }

    public function dependeDe(){
        $depende = '';
        //Verificar que clase de dependencia es
        //Verificar que si es una Direccion no depende de una direccion

        if(!is_null($this->direccion)){
            $depende .= $this->direccion->nombre . '';
        }
        if(!is_null($this->departamental)){
            $depende .= $this->departamental->nombre . '';
        }
        if(!is_null($this->division)){
            $depende .= $this->division->nombre . '';
        }
        if(!is_null($this->comisaria)){
            $depende .= $this->comisaria->nombre . '';
        }
        return $depende;
    }
}
