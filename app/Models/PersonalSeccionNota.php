<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Anotación inmutable de un operador sobre un funcionario. Privada por
 * defecto (solo la ve su autor): otro usuario la ve únicamente si el autor
 * la comparte explícitamente (ver `PersonalSeccionNotaComparticion`), o si
 * tiene rol Administrador/Super Administrador (ven todo siempre).
 */
class PersonalSeccionNota extends Model
{
    protected $table = 'personal_seccion_notas';

    /**
     * @var list<string>
     */
    public const ROLES_VEN_TODO = ['Administrador', 'Super Administrador'];

    protected $fillable = [
        'personal_id',
        'user_id',
        'texto',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class)->withTrashed();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function compartidas(): HasMany
    {
        return $this->hasMany(PersonalSeccionNotaComparticion::class, 'nota_id');
    }

    public function scopeRecientesPrimero(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Solo las anotaciones que $usuario puede ver: las propias, las
     * compartidas con él, o todas si tiene rol de administración.
     */
    public function scopeVisiblesPara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasRole(self::ROLES_VEN_TODO)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($usuario) {
            $q->where('user_id', $usuario->id)
                ->orWhereHas('compartidas', fn (Builder $qq) => $qq->where('user_id', $usuario->id));
        });
    }

    public function esAutor(User $usuario): bool
    {
        return $this->user_id === $usuario->id;
    }

    public function esPrivada(): bool
    {
        return $this->relationLoaded('compartidas')
            ? $this->compartidas->isEmpty()
            : $this->compartidas()->doesntExist();
    }
}
