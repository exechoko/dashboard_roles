<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camara extends Model
{
    protected $table = 'camaras';
    protected $fillable = [
        'tipo_camara_id',
        'camara_fisica_id',
        'sitio_id',
        'nombre',
        'ip',
        'tipo',
        'inteligencia',
        'marca',
        'modelo',
        'nro_serie',
        'etapa',
        'sitio',
        'latitud',
        'longitud'
    ];

    public function tipoCamara()
    {
        return $this->belongsTo(TipoCamara::class);
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    public function auditoria()
    {
        return $this->hasMany(Auditoria::class);
    }

    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'sitio_id');
    }
    public function camaraFisica()
    {
        return $this->belongsTo(CamaraFisica::class, 'camara_fisica_id');
    }
}
