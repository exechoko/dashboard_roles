<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConstanciaCredencial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'constancias_credenciales';

    protected $fillable = [
        'user_id',
        'nombre_apellido',
        'dni',
        'email',
        'contrasena',
        'lugar',
        'fecha_entrega',
        'firmada',
        'fecha_firma',
        'ruta_archivo',
        'ruta_archivo_firmado',
        'email_enviado',
        'fecha_envio_email',
        'observaciones',
        'usuario_creador_id',
        'usuario_creador_nombre',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'firmada' => 'boolean',
        'email_enviado' => 'boolean',
        'fecha_firma' => 'datetime',
        'fecha_envio_email' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function scopeFirmadas($query)
    {
        return $query->where('firmada', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('firmada', false);
    }

    public function scopeEmailEnviado($query)
    {
        return $query->where('email_enviado', true);
    }

    public function scopeBuscar(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('nombre_apellido', 'LIKE', "%{$term}%")
                ->orWhere('dni', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
        });
    }

    public function getFechaEntregaFormateadaAttribute(): string
    {
        return $this->fecha_entrega->format('d/m/Y');
    }

    public function getEstadoAttribute(): string
    {
        return $this->firmada ? 'Firmada' : 'Pendiente de firma';
    }

    public function getEstadoBadgeAttribute(): string
    {
        return $this->firmada
            ? '<span class="badge badge-success">Firmada</span>'
            : '<span class="badge badge-warning">Pendiente</span>';
    }

    public function getEmailEstadoAttribute(): string
    {
        if (!$this->email_enviado) {
            return '<span class="badge badge-danger">No enviado</span>';
        }

        return '<span class="badge badge-success">Enviado</span>';
    }
}
