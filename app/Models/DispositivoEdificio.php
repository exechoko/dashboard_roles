<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispositivoEdificio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dispositivos_edificio';

    protected $fillable = [
        'tipo',
        'nombre',
        'ip',
        'mac',
        'marca',
        'modelo',
        'serie',
        'oficina',
        'piso',
        'posicion_x',
        'posicion_y',
        'sistema_operativo',
        'puertos',
        'password_vault_id',
        'observaciones',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'posicion_x' => 'decimal:4',
        'posicion_y' => 'decimal:4',
        'puertos' => 'integer',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function passwordVault()
    {
        return $this->belongsTo(PasswordVault::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorOficina($query, $oficina)
    {
        return $query->where('oficina', 'like', "%{$oficina}%");
    }

    public function scopePorPiso($query, $piso)
    {
        return $query->where('piso', $piso);
    }

    public function scopeConCredenciales($query)
    {
        return $query->whereNotNull('password_vault_id');
    }

    // Métodos auxiliares
    public function getIconoAttribute()
    {
        $iconos = [
            'pc' => 'fas fa-desktop',
            'puesto_cecoco' => 'fas fa-headset',
            'puesto_video' => 'fas fa-video',
            'router' => 'fas fa-wifi',
            'switch' => 'fas fa-network-wired',
            'camara_interna' => 'fas fa-camera',
        ];

        return $iconos[$this->tipo] ?? 'fas fa-cube';
    }

    public function getColorAttribute()
    {
        $colores = [
            'pc' => '#007bff',
            'puesto_cecoco' => '#28a745',
            'puesto_video' => '#ffc107',
            'router' => '#dc3545',
            'switch' => '#6610f2',
            'camara_interna' => '#17a2b8',
        ];

        return $colores[$this->tipo] ?? '#6c757d';
    }

    public function getTipoLabelAttribute()
    {
        $labels = [
            'pc' => 'PC',
            'puesto_cecoco' => 'Puesto CECOCO',
            'puesto_video' => 'Puesto de Video',
            'router' => 'Router',
            'switch' => 'Switch',
            'camara_interna' => 'Cámara Interna',
        ];

        return $labels[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function tieneCredenciales()
    {
        return $this->password_vault_id && $this->passwordVault;
    }

    public function getCredenciales()
    {
        if (!$this->tieneCredenciales()) {
            return null;
        }

        return [
            'username' => $this->passwordVault->username,
            'password' => $this->passwordVault->password,
        ];
    }

    // Métodos estáticos
    public static function getTiposDispositivos()
    {
        return [
            'pc' => [
                'label' => 'PC',
                'icon' => 'fas fa-desktop',
                'color' => '#007bff',
                'campos_especificos' => ['sistema_operativo']
            ],
            'puesto_cecoco' => [
                'label' => 'Puesto CECOCO',
                'icon' => 'fas fa-headset',
                'color' => '#28a745',
                'campos_especificos' => []
            ],
            'puesto_video' => [
                'label' => 'Puesto de Video',
                'icon' => 'fas fa-video',
                'color' => '#ffc107',
                'campos_especificos' => []
            ],
            'router' => [
                'label' => 'Router',
                'icon' => 'fas fa-wifi',
                'color' => '#dc3545',
                'campos_especificos' => ['puertos']
            ],
            'switch' => [
                'label' => 'Switch',
                'icon' => 'fas fa-network-wired',
                'color' => '#6610f2',
                'campos_especificos' => ['puertos']
            ],
            'camara_interna' => [
                'label' => 'Cámara Interna',
                'icon' => 'fas fa-camera',
                'color' => '#17a2b8',
                'campos_especificos' => []
            ],
        ];
    }

    public static function getSistemasOperativos()
    {
        return [
            'Windows 10' => 'Windows 10',
            'Windows 11' => 'Windows 11',
            'Windows Server 2019' => 'Windows Server 2019',
            'Windows Server 2022' => 'Windows Server 2022',
            'Ubuntu 20.04' => 'Ubuntu 20.04',
            'Ubuntu 22.04' => 'Ubuntu 22.04',
            'CentOS 7' => 'CentOS 7',
            'CentOS 8' => 'CentOS 8',
            'Debian 10' => 'Debian 10',
            'Debian 11' => 'Debian 11',
            'Otro' => 'Otro',
        ];
    }
}
