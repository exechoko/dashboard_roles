<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CamaraFisica extends Model
{
    use HasFactory;

    protected $table = 'camara_fisicas';

    protected $fillable = [
        'tipo_camara_id',
        'numero_serie',
        'estado',
        'remito',
        'fecha_remito',
        'observacion',
        'activo',
    ];

    public function tipoCamara()
    {
        return $this->belongsTo(TipoCamara::class);
    }

    public function camara()
    {
        return $this->hasOne(Camara::class, 'camara_fisica_id');
    }
}
