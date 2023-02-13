<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMovimiento extends Model
{
    protected $table = 'tipo_movimiento';

    public function historico(){
        return $this->hasMany(Historico::class);
    }
}
