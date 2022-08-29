<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaSoporte extends Model
{
    protected $table = 'empresas_soporte';

    public function destino(){
        return $this->hasMany(Destino::class);
    }
}
