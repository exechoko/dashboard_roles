<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoInformePreferencia extends Model
{
    protected $table = 'vehiculo_informe_preferencias';

    protected $fillable = ['user_id', 'destino_id', 'vehiculo_ids'];

    protected $casts = ['vehiculo_ids' => 'array'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }
}
