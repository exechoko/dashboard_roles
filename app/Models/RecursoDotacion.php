<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoDotacion extends Model
{
    protected $table = 'recurso_dotaciones';

    protected $fillable = [
        'recurso_id', 'personal_id', 'parte_diario_id', 'es_chofer', 'orden',
        'guardia', 'horario', 'fecha_inicio', 'fecha_fin', 'user_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
        'es_chofer'    => 'boolean',
        'orden'        => 'integer',
    ];

    public static array $guardias = [
        'guardia_1' => 'Guardia 1',
        'guardia_2' => 'Guardia 2',
        'guardia_3' => 'Guardia 3',
        'guardia_4' => 'Guardia 4',
    ];

    public static array $horarios = [
        '06_18' => '06:15 a 18:15',
        '18_06' => '18:15 a 06:15',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function parteDiario(): BelongsTo
    {
        return $this->belongsTo(ParteDiario::class);
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
