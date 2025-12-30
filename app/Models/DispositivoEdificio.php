<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class DispositivoEdificio extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPOS = [
        'pc',
        'puesto_cecoco',
        'puesto_video',
        'router',
        'switch',
        'camara_interna',
        'servidor',
        'servidor_cecoco',
        'servidor_nebula',
        'grabador_nebula',
        'nvr',
        'access_point',
    ];

    public const TIPOS_CON_SO = [
        'pc',
        'servidor',
        'servidor_cecoco',
        'servidor_nebula',
    ];

    public const TIPOS_CON_PUERTOS = [
        'router',
        'switch',
        'access_point',
    ];

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
        'username',
        'password',
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

    public function setPasswordAttribute($value)
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password'] = Crypt::encryptString($value);
            return;
        }

        $this->attributes['password'] = $value;
    }

    public function getPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Exception $e) {
            return $value;
        }
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
        return $query
            ->whereNotNull('username')->where('username', '!=', '')
            ->whereNotNull('password')->where('password', '!=', '');
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
            'camara_interna' => 'fas fa-video',
            'servidor' => 'fas fa-server',
            'servidor_cecoco' => 'fas fa-server',
            'servidor_nebula' => 'fas fa-cloud',
            'grabador_nebula' => 'fas fa-record-vinyl',
            'nvr' => 'fas fa-hdd',
            'access_point' => 'fas fa-broadcast-tower',
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
            'servidor' => '#6f42c1',
            'servidor_cecoco' => '#20c997',
            'servidor_nebula' => '#0dcaf0',
            'grabador_nebula' => '#fd7e14',
            'nvr' => '#e83e8c',
            'access_point' => '#198754',
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
            'servidor' => 'Servidor',
            'servidor_cecoco' => 'Servidor CECOCO',
            'servidor_nebula' => 'Servidor Nebula',
            'grabador_nebula' => 'Grabador Nebula',
            'nvr' => 'NVR',
            'access_point' => 'Access Point',
        ];

        return $labels[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function tieneCredenciales()
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function getCredenciales()
    {
        if (!$this->tieneCredenciales()) {
            return null;
        }

        return [
            'username' => $this->username,
            'password' => $this->password,
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
                'icon' => 'fas fa-video',
                'color' => '#17a2b8',
                'campos_especificos' => []
            ],
            'servidor' => [
                'label' => 'Servidor',
                'icon' => 'fas fa-server',
                'color' => '#6f42c1',
                'campos_especificos' => ['sistema_operativo']
            ],
            'servidor_cecoco' => [
                'label' => 'Servidor CECOCO',
                'icon' => 'fas fa-server',
                'color' => '#20c997',
                'campos_especificos' => ['sistema_operativo']
            ],
            'servidor_nebula' => [
                'label' => 'Servidor Nebula',
                'icon' => 'fas fa-cloud',
                'color' => '#0dcaf0',
                'campos_especificos' => ['sistema_operativo']
            ],
            'grabador_nebula' => [
                'label' => 'Grabador Nebula',
                'icon' => 'fas fa-record-vinyl',
                'color' => '#fd7e14',
                'campos_especificos' => []
            ],
            'nvr' => [
                'label' => 'NVR',
                'icon' => 'fas fa-hdd',
                'color' => '#e83e8c',
                'campos_especificos' => []
            ],
            'access_point' => [
                'label' => 'Access Point',
                'icon' => 'fas fa-broadcast-tower',
                'color' => '#198754',
                'campos_especificos' => ['puertos']
            ],
        ];
    }

    public static function getSistemasOperativos()
    {
        return [
            'Windows 10' => 'Windows 10',
            'Windows 11' => 'Windows 11',
            'Windows Server 2012 R2' => 'Windows Server 2012 R2',
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
