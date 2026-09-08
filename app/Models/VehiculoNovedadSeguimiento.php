<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoNovedadSeguimiento extends Model
{
    protected $table = 'vehiculo_novedad_seguimientos';

    protected $fillable = ['novedad_id', 'user_id', 'descripcion'];

    public function novedad(): BelongsTo
    {
        return $this->belongsTo(VehiculoNovedad::class, 'novedad_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
