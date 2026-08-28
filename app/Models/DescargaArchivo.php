<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DescargaArchivo extends Model
{
    use HasFactory;

    protected $table = 'descarga_archivos';

    protected $fillable = [
        'categoria_id',
        'nombre_original',
        'nombre_archivo',
        'ruta_relativa',
        'mime_type',
        'extension',
        'tamano_bytes',
        'descripcion',
        'destacado',
        'user_id',
        'descargas_count',
        'expira_at',
        'activo',
    ];

    protected $casts = [
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'tamano_bytes' => 'integer',
        'descargas_count' => 'integer',
        'expira_at' => 'datetime',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(DescargaCategoria::class, 'categoria_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class,
            'descarga_archivo_roles',
            'archivo_id',
            'role_id'
        )->withTimestamps();
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(DescargaComentario::class, 'archivo_id')->latest();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DescargaLog::class, 'archivo_id');
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(DescargaVersion::class, 'archivo_id')->orderByDesc('version_numero');
    }

    public function linksPublicos(): HasMany
    {
        return $this->hasMany(DescargaLinkPublico::class, 'archivo_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            DescargaTag::class,
            'descarga_archivo_tags',
            'archivo_id',
            'tag_id'
        )->withTimestamps();
    }

    public function scopeAccesiblesPor(Builder $query, User $user): Builder
    {
        if ($user->can('administrar-plataforma-descargas')) {
            return $query;
        }

        return $query->whereHas('roles', function (Builder $q) use ($user) {
            $q->whereIn('roles.id', $user->roles()->pluck('id'));
        });
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeNoExpirados($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expira_at')
              ->orWhere('expira_at', '>', now());
        });
    }

    public function scopeDestacados($query)
    {
        return $query->where('destacado', true);
    }

    public function scopeExpirados($query)
    {
        return $query->where('expira_at', '<=', now());
    }

    public function scopeConExpiracion($query)
    {
        return $query->whereNotNull('expira_at');
    }

    public function getEstaExpiradoAttribute(): bool
    {
        return $this->expira_at && $this->expira_at->isPast();
    }

    public function getDiasParaExpirarAttribute(): ?int
    {
        if (!$this->expira_at) {
            return null;
        }

        if ($this->esta_expirado) {
            return 0;
        }

        return (int) now()->diffInDays($this->expira_at, false);
    }

    public function getTamanoHumanoAttribute(): string
    {
        $bytes = $this->tamano_bytes;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getIconoExtensionAttribute(): string
    {
        $iconos = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'jpg' => 'fas fa-file-image text-info',
            'jpeg' => 'fas fa-file-image text-info',
            'png' => 'fas fa-file-image text-info',
            'gif' => 'fas fa-file-image text-info',
            'zip' => 'fas fa-file-archive text-secondary',
            'rar' => 'fas fa-file-archive text-secondary',
            'txt' => 'fas fa-file-alt text-dark',
            'csv' => 'fas fa-file-csv text-success',
        ];

        return $iconos[strtolower($this->extension)] ?? 'fas fa-file text-secondary';
    }

    public function getEsPrevieweableAttribute(): bool
    {
        $extensionesPreview = config('descargas.preview_extensiones', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
        return in_array(strtolower($this->extension), $extensionesPreview);
    }

    public function puedeDescargar(User $user): bool
    {
        if ($user->can('administrar-plataforma-descargas')) {
            return true;
        }

        if (!$this->activo || $this->esta_expirado) {
            return false;
        }

        return $this->roles->pluck('id')->intersect($user->roles->pluck('id'))->isNotEmpty();
    }
}
