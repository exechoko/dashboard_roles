<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoDotacion extends Model
{
    protected $table = 'recurso_dotaciones';

    protected $fillable = ['recurso_id', 'personal_id', 'fecha', 'user_id'];

    protected $casts = ['fecha' => 'date'];

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
