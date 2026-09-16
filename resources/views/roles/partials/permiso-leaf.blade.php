@php
    $actionIcons = [
        'ver-menu' => 'fas fa-eye text-danger',
        'ver' => 'fas fa-eye text-info',
        'crear' => 'fas fa-plus text-success',
        'editar' => 'fas fa-edit text-warning',
        'borrar' => 'fas fa-trash text-danger',
        'baja' => 'fas fa-trash text-danger',
        'eliminar' => 'fas fa-trash text-danger',
        'buscar' => 'fas fa-search text-info',
        'reiniciar' => 'fas fa-sync-alt text-primary',
        'cargar' => 'fas fa-upload text-success',
        'subir' => 'fas fa-upload text-success',
        'restaurar' => 'fas fa-undo text-success',
        'descargar' => 'fas fa-download text-primary',
        'refrescar' => 'fas fa-sync-alt text-primary',
        'firmar' => 'fas fa-signature text-primary',
        'gestionar' => 'fas fa-cogs text-secondary',
        'administrar' => 'fas fa-cogs text-secondary',
        'compartir' => 'fas fa-share-alt text-info',
        'exportar' => 'fas fa-file-export text-success',
        'importar' => 'fas fa-file-import text-primary',
        'posicionar' => 'fas fa-map-marker-alt text-info',
        'credenciales' => 'fas fa-id-badge text-info',
        'escuchar' => 'fas fa-headphones text-info',
        'generar' => 'fas fa-magic text-primary',
        'enviar' => 'fas fa-paper-plane text-primary',
    ];

    $perm = $item['perm'];
    $checked = in_array($perm->id, $rolePermissions);
    $label = $item['label'] ?? $perm->name;
    $icon = $actionIcons[$item['action'] ?? ''] ?? 'fas fa-key text-info';
    $searchText = Str::lower($perm->name . ' ' . ($item['module'] ?? '') . ' ' . $groupName);
@endphp
<div class="perm-leaf-row" data-search="{{ $searchText }}">
    <div class="custom-control custom-switch perm-leaf-switch">
        {{ Form::hidden('permission[]', 0) }}
        {{ Form::checkbox('permission[]', $perm->id, $checked, [
            'class' => 'custom-control-input permission-checkbox',
            'id' => 'permission_' . $perm->id,
        ]) }}
        <label class="custom-control-label" for="permission_{{ $perm->id }}">
            <i class="{{ $icon }} mr-1"></i>{{ $label }}
        </label>
    </div>
</div>
