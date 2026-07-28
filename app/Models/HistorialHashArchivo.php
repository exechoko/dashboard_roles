<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class HistorialHashArchivo extends Model
{
    use HasFactory;

    protected $table = 'historial_hash_archivos';

    protected $fillable = [
        'user_id',
        'nombre_archivo',
        'cifrado_aplicado',
        'hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
