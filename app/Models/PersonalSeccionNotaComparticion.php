<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalSeccionNotaComparticion extends Model
{
    protected $table = 'personal_seccion_nota_comparticiones';

    protected $fillable = [
        'nota_id',
        'user_id',
        'compartido_por',
    ];

    public function nota(): BelongsTo
    {
        return $this->belongsTo(PersonalSeccionNota::class, 'nota_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function compartidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compartido_por');
    }
}
