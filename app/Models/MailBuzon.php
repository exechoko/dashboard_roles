<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class MailBuzon extends Model
{
    protected $table = 'mail_buzones';

    protected $fillable = [
        'nombre',
        'carpeta',
        'email',
        'role_id',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(MailArchivo::class, 'buzon_id');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(MailMensaje::class, 'buzon_id');
    }

    /**
     * Limita el listado a los buzones que el usuario puede ver: todos si
     * administra el visor de correos, o sólo los de sus propios roles.
     */
    public function scopeAccesiblesPor(Builder $query, User $user): Builder
    {
        if ($user->can('administrar-visor-mails')) {
            return $query;
        }

        return $query->whereIn('role_id', $user->roles()->pluck('id'));
    }
}
