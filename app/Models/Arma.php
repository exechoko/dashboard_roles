<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arma extends Model
{
    use HasFactory;

    protected $fillable = ['numero', 'arma_tipo_id', 'origen'];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(ArmaTipo::class, 'arma_tipo_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PersonalArmaAsignacion::class);
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(ArmaRetencion::class);
    }
}
