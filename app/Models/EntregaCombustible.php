<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EntregaCombustible extends Model
{
    use HasFactory;

    protected $table = 'entregas_combustible';

    protected $fillable = [
        'fecha_entrega',
        'hora_entrega',
        'ticket',
        'remito',
        'empresa_soporte',
        'personal_receptor',
        'legajo_receptor',
        'personal_entrega',
        'legajo_entrega',
        'cantidad_litros',
        'cantidad_bidones',
        'litros_por_bidon',
        'combustible',
        'estacion_servicio',
        'observaciones',
        'ruta_archivo',
        'ruta_acta_firmada',
        'usuario_creador',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'cantidad_litros' => 'integer',
        'cantidad_bidones' => 'integer',
        'litros_por_bidon' => 'integer',
    ];

    public function scopeBuscarPorTicket(Builder $query, ?string $ticket): Builder
    {
        if (!$ticket) {
            return $query;
        }

        return $query->where('ticket', 'LIKE', "%{$ticket}%");
    }

    public function scopeBuscarPorEmpresa(Builder $query, ?string $empresa): Builder
    {
        if (!$empresa) {
            return $query;
        }

        return $query->where('empresa_soporte', 'LIKE', "%{$empresa}%");
    }
}
