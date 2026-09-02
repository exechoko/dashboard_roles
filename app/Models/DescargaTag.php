<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class DescargaTag extends Model
{
    use HasFactory;

    protected $table = 'descarga_tags';

    protected $fillable = [
        'nombre',
        'slug',
    ];

    public function archivos(): BelongsToMany
    {
        return $this->belongsToMany(
            DescargaArchivo::class,
            'descarga_archivo_tags',
            'tag_id',
            'archivo_id'
        )->withTimestamps();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DescargaTag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->nombre);
            }
        });
    }
}
