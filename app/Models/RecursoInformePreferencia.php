<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoInformePreferencia extends Model
{
    protected $table = 'recurso_informe_preferencias';

    protected $fillable = ['user_id', 'destino_id', 'recurso_ids'];

    protected $casts = ['recurso_ids' => 'array'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }
}
