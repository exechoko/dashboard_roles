<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function flota_general(){
        return $this->hasMany(FlotaGeneral::class);
    }

    public function historico(){
        return $this->hasMany(Historico::class);
    }

    public function dependeDe(){
        $depende = '';
        //Verificar que clase de dependencia es
        //Verificar que si es una Direccion no depende de una direccion

        if(Str::contains($this->nombre, 'Direcc')){
            $depende .= 'Jefatura Policia de Entre Ríos';
        }
        if(Str::contains($this->nombre, 'Departamental')){
            $depende .= 'Jefatura Policia de Entre Ríos';
        }
        if( (Str::contains($this->nombre, 'Divis')) && (!is_null($this->direccion_id)) ){
            $depende .= $this->direccion->nombre . '';
        }
        if( (Str::contains($this->nombre, 'Divis')) && (!is_null($this->departamental_id)) ){
            $depende .= $this->departamental->nombre . '';
        }
        if( Str::contains($this->nombre, 'Comisar')){
            $depende .= $this->departamental->nombre . '';
        }

        return $depende;
    }
}
