<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoDotacion extends Model
{
    protected $table = 'recurso_dotaciones';

    protected $fillable = ['recurso_id', 'personal_id', 'guardia', 'horario', 'fecha_inicio', 'fecha_fin', 'user_id'];

    protected $casts = ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];

    public static array $guardias = [
        'guardia_1' => 'Guardia 1',
        'guardia_2' => 'Guardia 2',
        'guardia_3' => 'Guardia 3',
        'guardia_4' => 'Guardia 4',
    ];

    public static array $horarios = [
        '07_19' => '07:00 a 19:00',
        '19_07' => '19:00 a 07:00',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
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
