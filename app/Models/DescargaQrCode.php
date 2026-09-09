<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class DescargaQrCode extends Model
{
    protected $table = 'descarga_qr_codes';

    public $timestamps = false;

    protected $fillable = [
        'archivo_id',
        'token',
        'ruta_qr',
        'password',
        'max_usos',
        'usos_count',
        'expira_at',
        'generado_por',
        'activo',
        'created_at',
    ];

    protected $casts = [
        'max_usos' => 'integer',
        'usos_count' => 'integer',
        'activo' => 'boolean',
        'expira_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescargaArchivo::class, 'archivo_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function generadoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeValidos($query)
    {
        return $query->activos()
            ->where('expira_at', '>', now())
            ->whereColumn('usos_count', '<', 'max_usos');
    }

    public function getEstaExpiradoAttribute(): bool
    {
        return $this->expira_at->isPast();
    }

    public function getUsosRestantesAttribute(): int
    {
        return max(0, $this->max_usos - $this->usos_count);
    }

    public function getEsUtilizableAttribute(): bool
    {
        return $this->activo && !$this->esta_expirado && $this->usos_restantes > 0;
    }

    public function requierePassword(): bool
    {
        return !empty($this->password);
    }

    public function verificarPassword(?string $password): bool
    {
        if (!$this->requierePassword()) {
            return true;
        }

        return $password && Hash::check($password, $this->password);
    }

    public function registrarUso(): void
    {
        $this->increment('usos_count');

        if ($this->usos_count + 1 >= $this->max_usos) {
            $this->update(['activo' => false]);
        }
    }
}
