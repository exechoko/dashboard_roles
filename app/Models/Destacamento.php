<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destacamento extends Model
{
    protected $table = 'destacamentos';
    protected $fillable = [
        'nombre'
    ];

    public function comisaria(){
        return $this->belongsTo(Comisaria::class);
    }

    public function division(){
        return $this->belongsTo(Division::class);
    }
}
