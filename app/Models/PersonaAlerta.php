<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PersonaAlerta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personas_alerta';

    protected $fillable = [
        'dni',
        'apellido_nombre',
        'direccion',
        'motivo',
        'solicitado_por',
        'funcionario_carga',
        'notificar_a',
        'identificado',
        'finalizado',
        'foto',
        'fecha_hecho',
        'fecha_carga',
        'activo',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'identificado' => 'boolean',
        'finalizado' => 'boolean',
        'fecha_hecho' => 'date',
        'fecha_carga' => 'date',
    ];

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(AlertaMovimiento::class, 'movable')->orderByDesc('created_at');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function getEstadoLabelAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('anexos')->url($this->foto) : null;
    }

    /**
     * Normaliza el D.N.I. quitando puntos y espacios, para que la
     * detección de duplicados sea confiable (ej. "30.111.222" => "30111222").
     */
    public static function normalizarDni(mixed $dni): ?string
    {
        $dni = trim((string) $dni);

        if ($dni === '' || $dni === '-') {
            return null;
        }

        $dni = preg_replace('/[.\s]+/', '', $dni);

        return $dni === '' ? null : $dni;
    }

    /**
     * Busca personas ya cargadas que puedan ser la misma que se está
     * por cargar, aunque no se tenga el D.N.I.: por D.N.I. exacto y/o
     * por coincidencia de palabras del apellido y nombre (nombre
     * completo o parcial, en cualquier orden).
     *
     * @return Collection<int, self>
     */
    public static function buscarCoincidencias(?string $dni, ?string $nombre, int $limite = 8): Collection
    {
        $dniNormalizado = self::normalizarDni($dni);

        $palabras = collect(preg_split('/\s+/', mb_strtoupper(trim((string) $nombre))))
            ->filter(fn ($palabra) => mb_strlen($palabra) >= 3)
            ->unique()
            ->values();

        if ($dniNormalizado === null && $palabras->isEmpty()) {
            return collect();
        }

        $candidatos = static::query()
            ->where(function ($query) use ($dniNormalizado, $palabras) {
                if ($dniNormalizado !== null) {
                    $query->orWhere('dni', $dniNormalizado);
                }

                foreach ($palabras as $palabra) {
                    $query->orWhereRaw('UPPER(apellido_nombre) LIKE ?', ['%' . $palabra . '%']);
                }
            })
            ->limit(50)
            ->get();

        return $candidatos
            ->map(function (self $persona) use ($dniNormalizado, $palabras) {
                $score = ($dniNormalizado !== null && $persona->dni === $dniNormalizado) ? 100 : 0;
                $nombreUpper = mb_strtoupper($persona->apellido_nombre);

                foreach ($palabras as $palabra) {
                    if (str_contains($nombreUpper, $palabra)) {
                        $score++;
                    }
                }

                $persona->coincidencia_score = $score;

                return $persona;
            })
            ->sortByDesc('coincidencia_score')
            ->take($limite)
            ->values();
    }
}
