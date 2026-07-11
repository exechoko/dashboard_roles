<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'nombre',
        'apellido',
        'lp',
        'jerarquia',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lp' => 'string',
    ];

    // 🔎 Scope
    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    // 🧠 Formato listo para mostrar
    public function getNombreCompletoAttribute()
    {
        return "{$this->jerarquia} {$this->apellido}, {$this->nombre}, L.P. Nº {$this->lp}";
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(ArmaRetencion::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
