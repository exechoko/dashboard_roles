<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $table = 'destino';

    protected $fillable = [
        'nombre',
        'tipo',
        'parent_id',
    ];

    // Relación hacia el destino padre
    public function padre()
    {
        return $this->belongsTo(Destino::class, 'parent_id');
    }

    // Relación hacia los destinos hijos
    public function hijos()
    {
        return $this->hasMany(Destino::class, 'parent_id');
    }

    // Devuelve el nombre del destino del que depende (o "Jefatura..." por defecto)
    public function dependeDe()
    {
        return $this->padre->nombre ?? 'Jefatura de Policía de Entre Ríos';
    }

    /**
     * Obtiene la clase CSS del badge según el tipo de dependencia
     */
    public function getBadgeClass()
    {
        $clases = [
            'jefatura' => 'dark',
            'subjefatura' => 'secondary',
            'direccion' => 'light',
            'departamental' => 'primary',
            'division' => 'success',
            'comisaria' => 'warning',
            'seccion' => 'info',
            'destacamento' => 'danger'
        ];

        return $clases[$this->tipo] ?? 'light';
    }

    /**
     * Obtiene el color del badge
     */
    public function getBadgeColor()
    {
        $colores = [
            'jefatura' => '#6c757d',
            'subjefatura' => '#28a745',
            'direccion' => '#6c757d',
            'departamental' => '#007bff',
            'division' => '#28a745',
            'comisaria' => '#ffc107',
            'seccion' => '#17a2b8',
            'destacamento' => '#dc3545'
        ];

        return $colores[$this->tipo] ?? '#f8f9fa';
    }

    /**
     * Verifica si tiene número de WhatsApp (para comisarías)
     */
    public function getTelefonoWhatsapp()
    {
        if ($this->tipo === 'comisaria' && $this->telefono) {
            preg_match('/Celular:\s*(\d+)/', $this->telefono, $matches);
            return $matches[1] ?? null;
        }
        return null;
    }

    /**
     * Obtiene la URL de WhatsApp si tiene celular
     */
    public function getWhatsappUrl()
    {
        $celular = $this->getTelefonoWhatsapp();
        return $celular ? "https://wa.me/549{$celular}" : null;
    }

    /**
     * Obtiene la ruta jerárquica completa
     */
    public function getRutaJerarquica()
    {
        $jerarquia = [];
        $actual = $this;

        while ($actual->padre) {
            array_unshift($jerarquia, $actual->padre);
            $actual = $actual->padre;
        }

        return $jerarquia;
    }

    /**
     * Verifica si puede ser eliminada
     */
    public function puedeSerEliminada()
    {
        // Solo secciones y destacamentos pueden ser eliminadas
        if (!in_array($this->tipo, ['seccion', 'destacamento'])) {
            return false;
        }

        // No debe tener dependencias hijas
        return $this->hijos()->count() === 0;
    }

    /**
     * Obtiene un resumen de estadísticas
     */
    public static function getEstadisticas()
    {
        return self::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->orderByRaw("
            CASE tipo
                WHEN 'jefatura' THEN 1
                WHEN 'subjefatura' THEN 2
                WHEN 'direccion' THEN 3
                WHEN 'departamental' THEN 4
                WHEN 'division' THEN 5
                WHEN 'comisaria' THEN 6
                WHEN 'seccion' THEN 7
                WHEN 'destacamento' THEN 8
                ELSE 9
            END
        ")
            ->get()
            ->pluck('total', 'tipo');
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para buscar por texto
     */
    public function scopeBuscar($query, $texto)
    {
        return $query->where(function ($q) use ($texto) {
            $q->where('nombre', 'LIKE', "%{$texto}%")
                ->orWhere('telefono', 'LIKE', "%{$texto}%")
                ->orWhere('ubicacion', 'LIKE', "%{$texto}%");
        });
    }
}
