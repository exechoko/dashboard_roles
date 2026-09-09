<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DescargaCategoria extends Model
{
    use HasFactory;

    protected $table = 'descarga_categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'slug',
        'icono',
        'color',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function archivos(): HasMany
    {
        return $this->hasMany(DescargaArchivo::class, 'categoria_id');
    }

    public function archivosActivos(): HasMany
    {
        return $this->archivos()->where('activo', true);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DescargaCategoria $categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nombre);
            }
        });
    }
}
