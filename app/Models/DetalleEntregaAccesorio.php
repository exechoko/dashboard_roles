<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleEntregaAccesorio extends Model
{
    use HasFactory;

    protected $table = 'detalle_entrega_accesorios';

    protected $fillable = [
        'entrega_id',
        'tipo_accesorio',
        'cantidad',
        'marca',
        'numero_serie',
        'observaciones'
    ];

    // Constantes para tipos de accesorios
    const TIPO_CUNA_CARGADORA = 'cuna_cargadora';
    const TIPO_TRANSFORMADOR = 'transformador';

    // Constantes para marcas de cunas
    const MARCA_SEPURA = 'Sepura';
    const MARCA_TELTRONIC = 'Teltronic';

    // Relación con la entrega principal
    public function entrega()
    {
        return $this->belongsTo(EntregaEquipo::class, 'entrega_id');
    }

    // Scope para cunas cargadoras
    public function scopeCunasCargadoras($query)
    {
        return $query->where('tipo_accesorio', self::TIPO_CUNA_CARGADORA);
    }

    // Scope para transformadores
    public function scopeTransformadores($query)
    {
        return $query->where('tipo_accesorio', self::TIPO_TRANSFORMADOR);
    }

    // Accessor para mostrar el tipo formateado
    public function getTipoFormateadoAttribute()
    {
        return $this->tipo_accesorio === self::TIPO_CUNA_CARGADORA
            ? 'Cuna Cargadora'
            : 'Transformador 12V';
    }

    // Método para obtener todas las marcas disponibles para cunas
    public static function getMarcasCunas()
    {
        return [
            self::MARCA_SEPURA,
            self::MARCA_TELTRONIC
        ];
    }

    // Validar que la marca es válida para cunas
    public function esMarcaValidaCuna()
    {
        return in_array($this->marca, self::getMarcasCunas());
    }
}
