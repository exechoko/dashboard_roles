<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionLlamadaCentralTelefonica extends Model
{
    use HasFactory;

    protected $table = 'importaciones_llamadas_central_telefonica';

    protected $fillable = [
        'nombre_archivo',
        'total_registros',
        'registros_importados',
        'registros_omitidos',
        'estado',
        'error_mensaje',
        'tiempo_procesamiento',
        'usuario_id',
    ];

    protected $casts = [
        'total_registros' => 'integer',
        'registros_importados' => 'integer',
        'registros_omitidos' => 'integer',
        'tiempo_procesamiento' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
