<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

//spatie
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'lp',
        'dni',
        'email',
        'password',
        'photo',
        'theme',
        'acceso_externo',
        'master_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'master_password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'notificaciones_vistas_en' => 'datetime',
    ];

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    public function ultimaConstanciaCredencial(): HasOne
    {
        return $this->hasOne(ConstanciaCredencial::class)->latestOfMany();
    }

    public function chatbotConversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class);
    }

    public function chatConversaciones(): BelongsToMany
    {
        return $this->belongsToMany(ChatConversacion::class, 'chat_participantes')
            ->withPivot(['es_admin', 'ultimo_leido_id', 'ultimo_leido_at'])
            ->withTimestamps();
    }

    public function archivosSubidos(): HasMany
    {
        return $this->hasMany(DescargaArchivo::class, 'user_id');
    }

    public function getRoleColor($roleName) {
        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        return $role ? $role->color : null;
    }

}
