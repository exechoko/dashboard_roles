<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleEntregaBodycam extends Model
{
    use HasFactory;

    protected $table = 'detalle_entrega_bodycams';

    protected $fillable = [
        'entrega_id',
        'bodycam_id',
        'observaciones'
    ];

    // Relaciones
    public function entrega()
    {
        return $this->belongsTo(EntregaBodycam::class, 'entrega_id');
    }

    public function bodycam()
    {
        return $this->belongsTo(Bodycam::class, 'bodycam_id');
    }
}
