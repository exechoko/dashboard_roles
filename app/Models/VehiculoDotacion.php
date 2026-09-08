<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoDotacion extends Model
{
    protected $table = 'vehiculo_dotaciones';

    protected $fillable = ['vehiculo_id', 'personal_id', 'fecha', 'user_id'];

    protected $casts = ['fecha' => 'date'];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
