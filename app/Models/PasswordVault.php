<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PasswordVault extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'system_name',
        'system_type',
        'vpn_type',
        'vpn_host',
        'vpn_preshared_key',
        'url',
        'username',
        'password',
        'notes',
        'icon',
        'favorite',
        'last_accessed_at'
    ];

    protected $hidden = [
        'password',
        'vpn_preshared_key',
    ];

    protected $casts = [
        'favorite' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    // Encriptar automáticamente la contraseña
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    // Desencriptar automáticamente la contraseña
    public function getPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Encriptar clave previamente compartida VPN
    public function setVpnPresharedKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['vpn_preshared_key'] = Crypt::encryptString($value);
        }
    }

    // Desencriptar clave previamente compartida VPN
    public function getVpnPresharedKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Encriptar notas
    public function setNotesAttribute($value)
    {
        if ($value) {
            $this->attributes['notes'] = Crypt::encryptString($value);
        }
    }

    public function getNotesAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shares()
    {
        return $this->hasMany(PasswordVaultShare::class);
    }

    /**
     * Relación con el usuario dueño
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'password_vault_shares', 'password_vault_id', 'shared_with_user_id')
            ->withPivot('can_edit', 'shared_by_user_id')
            ->withTimestamps();
    }

    /**
     * Verifica si la contraseña está compartida con un usuario específico
     */
    public function isSharedWith($userId)
    {
        return $this->shares()->where('shared_with_user_id', $userId)->exists();
    }

    /**
     * Verifica si un usuario tiene permiso de edición
     */
    public function canBeEditedBy($userId)
    {
        // El dueño siempre puede editar
        if ($this->user_id == $userId) {
            return true;
        }

        // Verificar permiso de edición compartida
        $share = $this->shares()->where('shared_with_user_id', $userId)->first();
        return $share && $share->can_edit;
    }

    // Scopes
    public function scopeFavorites($query)
    {
        return $query->where('favorite', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('system_type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('system_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%");
        });
    }

    // Método para registrar acceso
    public function recordAccess()
    {
        $this->update(['last_accessed_at' => now()]);
    }

    // Tipos de sistemas disponibles
    public static function getSystemTypes()
    {
        return [
            'web' => ['label' => 'Sitio Web', 'icon' => 'fas fa-globe'],
            'vpn' => ['label' => 'VPN', 'icon' => 'fas fa-shield-alt'],
            'escritorio' => ['label' => 'Sistema de Escritorio', 'icon' => 'fas fa-desktop'],
            'servidor' => ['label' => 'Servidor', 'icon' => 'fas fa-server'],
            'remoto' => ['label' => 'Acceso Remoto', 'icon' => 'fas fa-laptop-house'],
            'base_datos' => ['label' => 'Base de Datos', 'icon' => 'fas fa-database'],
            'email' => ['label' => 'Email', 'icon' => 'fas fa-envelope'],
            'ftp' => ['label' => 'FTP/SFTP', 'icon' => 'fas fa-server'],
            'ssh' => ['label' => 'SSH', 'icon' => 'fas fa-terminal'],
            'otro' => ['label' => 'Otro', 'icon' => 'fas fa-key'],
        ];
    }

    // Tipos de VPN disponibles
    public static function getVpnTypes()
    {
        return [
            'pptp' => 'PPTP',
            'l2tp' => 'L2TP/IPsec',
            'ipsec' => 'IPsec',
            'openvpn' => 'OpenVPN',
            'wireguard' => 'WireGuard',
            'sstp' => 'SSTP',
            'ikev2' => 'IKEv2',
            'otro' => 'Otro'
        ];
    }
}
