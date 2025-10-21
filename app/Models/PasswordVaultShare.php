<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordVaultShare extends Model
{
    use HasFactory;

    protected $table = 'password_vault_shares';

    /**
     * Los atributos que son asignables masivamente.
     * @var array<int, string>
     */
    protected $fillable = [
        'password_vault_id',
        'shared_with_user_id',
        'shared_by_user_id',
        'can_edit',
    ];

    /**
     * Los atributos que deben ser casteados.
     * @var array<string, string>
     */
    protected $casts = [
        'can_edit' => 'boolean',
    ];
    
    /**
     * Relación con la contraseña que está siendo compartida.
     */
    public function vault()
    {
        return $this->belongsTo(PasswordVault::class, 'password_vault_id');
    }

    /**
     * Relación con el usuario con quien se está compartiendo.
     */
    public function sharedWith()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    /**
     * Relación con el usuario que realizó la acción de compartir (el dueño).
     */
    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }
}
