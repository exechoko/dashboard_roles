<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camara extends Model
{
    protected $table = 'camaras';
    protected $fillable = ['nombre', 'ip', 'tipo', 'inteligencia','marca', 'modelo', 'nro_serie', 'etapa', 'sitio', 'latitud', 'longitud'];

    public function tipoCamara(){
        return $this->belongsTo(TipoCamara::class);
    }
}
