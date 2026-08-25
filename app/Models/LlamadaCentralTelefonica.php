<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LlamadaCentralTelefonica extends Model
{
    use HasFactory;

    protected $table = 'llamadas_central_telefonica';

    public const TIPO_RECIBIDA = 'recibida';

    public const TIPO_SALIENTE = 'saliente';

    public const TIPO_INTERNA = 'interna';

    public const TIPO_OTRA = 'otra';

    protected $fillable = [
        'uid',
        'calldate',
        'ani',
        'dialed_number',
        'final_dnis',
        'forwarded_to',
        'tipo_llamada',
        'duration',
        'bill_duration',
        'atendida',
        'periodo',
        'anio',
        'mes',
        'archivo_origen',
    ];

    protected $casts = [
        'calldate' => 'datetime',
        'duration' => 'integer',
        'bill_duration' => 'integer',
        'atendida' => 'boolean',
        'anio' => 'integer',
        'mes' => 'integer',
    ];

    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeDelMes($query, int $mes)
    {
        return $query->where('mes', $mes);
    }

    public function scopeEntreFechas($query, string $desde, string $hasta)
    {
        return $query->whereBetween('calldate', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
    }

    public function scopeNumero($query, string $numero)
    {
        return $query->where(function ($q) use ($numero) {
            $q->where('ani', 'LIKE', "%{$numero}%")
                ->orWhere('final_dnis', 'LIKE', "%{$numero}%")
                ->orWhere('dialed_number', 'LIKE', "%{$numero}%");
        });
    }

    public function scopeRecibidas($query)
    {
        return $query->where('tipo_llamada', self::TIPO_RECIBIDA);
    }

    public function scopeSalientes($query)
    {
        return $query->where('tipo_llamada', self::TIPO_SALIENTE);
    }

    public function scopeInternas($query)
    {
        return $query->where('tipo_llamada', self::TIPO_INTERNA);
    }

    public function scopeAtendidas($query)
    {
        return $query->where('atendida', true);
    }

    public function scopeDescartadas($query)
    {
        return $query->where('atendida', false);
    }
}
