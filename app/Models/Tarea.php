<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tareas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'recurrencia_tipo',
        'recurrencia_intervalo',
        'recurrencia_dia_semana',
        'recurrencia_dia_mes',
        'fecha_inicio',
        'activa',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'activa' => 'boolean',
    ];

    public const RECURRENCIAS = [
        'none' => 'No repetitiva',
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
    ];

    public function items()
    {
        return $this->hasMany(TareaItem::class, 'tarea_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generarItems(Carbon $desde, Carbon $hasta): int
    {
        if (!$this->activa) {
            return 0;
        }

        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->endOfDay();

        $inicio = $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->startOfDay() : $desde->copy();
        if ($inicio->lt($desde)) {
            $inicio = $desde->copy();
        }

        if ($this->recurrencia_tipo === 'none') {
            $fecha = $inicio->copy();
            if ($fecha->lte($hasta)) {
                $this->items()->firstOrCreate([
                    'fecha_programada' => $fecha->toDateString(),
                ], [
                    'estado' => TareaItem::ESTADO_PENDIENTE,
                ]);

                return 1;
            }

            return 0;
        }

        $creados = 0;
        $intervalo = (int) ($this->recurrencia_intervalo ?: 1);

        if ($this->recurrencia_tipo === 'daily') {
            $fecha = $inicio->copy();
            while ($fecha->lte($hasta)) {
                $this->items()->firstOrCreate([
                    'fecha_programada' => $fecha->toDateString(),
                ], [
                    'estado' => TareaItem::ESTADO_PENDIENTE,
                ]);
                $creados++;
                $fecha->addDays($intervalo);
            }

            return $creados;
        }

        if ($this->recurrencia_tipo === 'weekly') {
            $diaSemana = (int) ($this->recurrencia_dia_semana ?: $inicio->dayOfWeekIso);
            $fecha = $inicio->copy();
            $carbonDow = $diaSemana === 7 ? Carbon::SUNDAY : $diaSemana;
            if ($fecha->dayOfWeekIso !== $diaSemana) {
                $fecha = $fecha->next($carbonDow);
            }

            while ($fecha->lte($hasta)) {
                $this->items()->firstOrCreate([
                    'fecha_programada' => $fecha->toDateString(),
                ], [
                    'estado' => TareaItem::ESTADO_PENDIENTE,
                ]);
                $creados++;
                $fecha->addWeeks($intervalo);
            }

            return $creados;
        }

        if ($this->recurrencia_tipo === 'monthly') {
            $diaMes = (int) ($this->recurrencia_dia_mes ?: $inicio->day);

            $fecha = $inicio->copy();
            $diaMesAjustado = min($diaMes, $fecha->daysInMonth);
            $fecha->day($diaMesAjustado);

            if ($fecha->lt($inicio)) {
                $fecha->addMonthNoOverflow($intervalo);
                $diaMesAjustado = min($diaMes, $fecha->daysInMonth);
                $fecha->day($diaMesAjustado);
            }

            while ($fecha->lte($hasta)) {
                $this->items()->firstOrCreate([
                    'fecha_programada' => $fecha->toDateString(),
                ], [
                    'estado' => TareaItem::ESTADO_PENDIENTE,
                ]);
                $creados++;

                $fecha->addMonthNoOverflow($intervalo);
                $diaMesAjustado = min($diaMes, $fecha->daysInMonth);
                $fecha->day($diaMesAjustado);
            }

            return $creados;
        }

        return 0;
    }
}
