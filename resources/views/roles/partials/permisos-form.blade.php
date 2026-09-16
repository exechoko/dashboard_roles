@php
    $rolePermissions = $rolePermissions ?? [];

    // Agrupar permisos por categorías principales
    $mainGroups = [
        'Menús' => [
            'menu-dashboard',
            'menu-equipamientos',
            'menu-bodycams',
            'menu-camaras',
            'menu-dependencias',
            'menu-mapa',
            'menu-usuarios',
            'menu-auditoria',
            'menu-cecoco',
            'menu-entregas',
            'menu-tareas',
            'menu-documentacion',
            'menu-transcripcion',
            'menu-transcripcion-aws',
            'menu-ia',
            'menu-gestor-claves',
            'menu-patrimonio',
            'menu-armamento',
            'menu-personal',
            'menu-incidencias-911',
            'menu-plano-edificio',
            'menu-manuales',
            'menu-herramientas',
            'menu-constancias-credenciales',
            'menu-chat',
            'menu-infraestructura',
            'menu-configuracion-sistema',
            'menu-descargas',
        ],
        'Administración' => [
            'rol',
            'usuario',
            'auditoria'
        ],
        'Configuración del Sistema' => [
            'configuracion-env',
            'configuracion-env-critico',
            'configuracion-ia',
            'configuracion-workers',
            'configuracion-backup',
        ],
        'Dependencias' => [
            'dependencia'
        ],
        'Bodycams' => [
            'bodycam'
        ],
        'Equipamientos' => [
            'equipo',
            'terminal',
            'antena',
            'flota',
            'historico'
        ],
        'Recursos' => [
            'recurso',
            'vehiculo'
        ],
        'Cámaras' => [
            'camara',
            'stream-camara',
            'tipo-camara',
            'sitio'
        ],
        'Cecoco' => [
            'llamadas-cecoco',
            'moviles-cecoco',
            'eventos-cecoco',
            'ver-expediente-cecoco',
            'mapa-calor-servicios-cecoco',
            'mapa-cecoco-en-vivo',
            'ver-analizador-eventos-cecoco',
            'ver-analitica-eventos-cecoco',
            'ver-tiempos-respuesta-cecoco',
            'mapa-gis-cecoco',
            'mapa-gis-historico-cecoco',
            'importar-eventos',
            'ver-grabacion-evento',
            'modulaciones-cecoco',
            'ver-historico-movil-cecoco',
            'ver-historico-movil-gis-cecoco',
            'recurso-alias-cecoco',
            'ver-reporte-llamadas-central-telefonica',
            'importar-llamadas-central-telefonica',
            'whatsapp-cecoco',
        ],
        'Entregas' => [
            'entrega-equipos',
            'entrega-bodycams',
            'entrega-combustible'
        ],
        'Tareas' => [
            'tarea',
            'ticket-pg',
            'activacion-totem'
        ],
        'Claves' => [
            'clave'
        ],
        'Patrimonio' => [
            'bien',
            'tipo-bien',
            'patrimonio-cargos',
            'patrimonio'
        ],
        'Control de Armas' => [
            'menu-armamento',
            'arma-retencion',
            'arma-motivo',
            'arma-tipo',
            'armeria',
        ],
        'Personal' => [
            'personal',
            'datos-personales-personal',
        ],
        'Incidencias 911' => [
            'periodo-911',
            'incidencia-911',
            'generar-informe-911'
        ],
        'Actas de Credenciales' => [
            'constancias-credenciales'
        ],
        'Plano Edificio' => [
            'plano-edificio'
        ],
        'IA' => [
            'rag'
        ],
        'Chat' => [
            'chat'
        ],
        'Manuales' => [
            'manual-usuario',
            'manuales-cecoco',
            'instructivos',
        ],
        'Herramientas' => [
            'hash-archivo',
            'visor-mails',
        ],
        'Operaciones' => [
            'buscar-moviles-parados',
            'buscar-moviles-recorridos',
            'reiniciar-camara',
            'herramientas-mapa'
        ],
        'Administrar Web' => [
            'menu-web',
            'web-contadores',
            'web-textos',
            'web-historia',
            'web-tecnologia',
            'web-galeria',
            'web-dependencias',
            'noticia'
        ],
        'Infraestructura' => [
            'infraestructura-pcs',
            'infraestructura-servidores',
            'infraestructura-camaras',
            'infraestructura-red',
            'infraestructura-librenms',
            'infraestructura-central-telefonica',
            'infraestructura-workers',
            'infraestructura-notificaciones',
            'infraestructura',
        ],
        'Descargas' => [
            'plataforma-descargas',
            'archivos-descargas',
            'logs-descargas',
            'links-publicos',
        ],
        'Datos del 911 (PWA)' => [
            'datos-911',
        ]
    ];

    $groupedPermissions = [];
    $assignedPermissionIds = [];

    // Primero procesar grupos principales
    foreach ($mainGroups as $groupName => $modules) {
        foreach ($modules as $module) {
            foreach ($permission as $perm) {
                // Evitar asignar duplicados
                if (in_array($perm->id, $assignedPermissionIds)) continue;

                // Verificar si el permiso pertenece a este módulo
                $pattern = '/^([^-]+-)?' . preg_quote($module, '/') . '$/';
                if (preg_match($pattern, $perm->name)) {
                    if (!isset($groupedPermissions[$groupName][$module])) {
                        $groupedPermissions[$groupName][$module] = [];
                    }
                    $parts = explode('-', $perm->name, 2);
                    $action = $parts[0];
                    if (!isset($groupedPermissions[$groupName][$module][$action])) {
                        $groupedPermissions[$groupName][$module][$action] = [];
                    }
                    $groupedPermissions[$groupName][$module][$action][] = $perm;
                    $assignedPermissionIds[] = $perm->id;
                }
            }
        }
    }

    // Agregar permisos no asignados en "Otros"
    foreach ($permission as $perm) {
        if (!in_array($perm->id, $assignedPermissionIds)) {
            if (!isset($groupedPermissions['Otros'])) {
                $groupedPermissions['Otros'] = [];
            }
            $groupedPermissions['Otros'][] = $perm;
        }
    }

    // Totales y seleccionados por grupo (para el badge del acordeón)
    $groupStats = [];
    foreach ($groupedPermissions as $groupName => $modules) {
        $total = 0;
        $selected = 0;
        if ($groupName === 'Otros') {
            foreach ($modules as $perm) {
                $total++;
                if (in_array($perm->id, $rolePermissions)) {
                    $selected++;
                }
            }
        } else {
            foreach ($modules as $actions) {
                foreach ($actions as $permissions) {
                    foreach ($permissions as $perm) {
                        $total++;
                        if (in_array($perm->id, $rolePermissions)) {
                            $selected++;
                        }
                    }
                }
            }
        }
        $groupStats[$groupName] = ['total' => $total, 'selected' => $selected];
    }
@endphp

<div class="permissions-tree">
    <div class="permissions-toolbar sticky-top bg-white py-2">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="permission-search" class="form-control"
                        placeholder="Buscar permiso (ej: crear, cámara, patrimonio)...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="clear-permission-search" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-right">
                <span class="badge badge-primary mr-2" id="permissions-selected-count">
                    0 permisos seleccionados
                </span>
                <button type="button" class="btn btn-sm btn-success" id="select-all-permissions">
                    <i class="fas fa-check-double"></i> Seleccionar Todos
                </button>
                <button type="button" class="btn btn-sm btn-secondary ml-1" id="deselect-all-permissions">
                    <i class="fas fa-times"></i> Deseleccionar Todos
                </button>
            </div>
        </div>
        <div id="permission-search-empty" class="alert alert-warning mt-2 mb-0 d-none">
            <i class="fas fa-info-circle"></i> No se encontraron permisos que coincidan con la búsqueda.
        </div>
    </div>

    @foreach ($groupedPermissions as $groupName => $modules)
        @php
            $groupSlug = 'group-' . $loop->index;
            $stats = $groupStats[$groupName];
            $expanded = $stats['selected'] > 0;
        @endphp
        <div class="group-section mb-2" data-group-search="{{ Str::lower($groupName) }}">
            <h5 class="text-primary mb-0 group-toggle" data-toggle="collapse" data-target="#{{ $groupSlug }}"
                role="button" aria-expanded="{{ $expanded ? 'true' : 'false' }}" aria-controls="{{ $groupSlug }}">
                <i class="fas fa-chevron-right group-caret mr-1"></i>
                <i class="fas fa-folder-open"></i> {{ $groupName }}
                <span class="badge badge-light group-count" data-total="{{ $stats['total'] }}">
                    {{ $stats['selected'] }}/{{ $stats['total'] }}
                </span>
            </h5>
            <div id="{{ $groupSlug }}" class="collapse {{ $expanded ? 'show' : '' }} pt-3">
                <div class="row">
                    @if ($groupName === 'Otros')
                        <div class="col-12">
                            <div class="permission-group">
                                <div class="card border-left-info">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-info">
                                            <i class="fas fa-cog"></i> Permisos varios
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            @foreach ($modules as $perm)
                                                <div class="col-lg-4 col-md-6 col-sm-12 permission-item"
                                                    data-search="{{ Str::lower($perm->name) }}">
                                                    <div class="custom-control custom-switch mb-3">
                                                        {{ Form::hidden('permission[]', 0) }}
                                                        {{ Form::checkbox('permission[]', $perm->id, in_array($perm->id, $rolePermissions), [
                                                            'class' => 'custom-control-input permission-checkbox',
                                                            'id' => 'permission_'.$perm->id,
                                                            'data-group' => 'otros',
                                                        ]) }}
                                                        <label class="custom-control-label" for="permission_{{ $perm->id }}">
                                                            <i class="fas fa-key text-info mr-1"></i>
                                                            {{ $perm->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        @foreach ($modules as $module => $actions)
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-3">
                                <div class="permission-group h-100">
                                    <div class="card border-left-primary h-100">
                                        <div class="card-header bg-light py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-danger">
                                                    <i class="fa fa-certificate"></i>
                                                    {{ ucfirst(str_replace('-', ' ', $module)) }}
                                                </h6>
                                                <div class="group-controls">
                                                    <button type="button" class="btn btn-xs btn-outline-success select-all-group" data-group="{{ $module }}">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-outline-danger deselect-all-group" data-group="{{ $module }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body py-2">
                                            <div class="row">
                                                @php
                                                    $actionIcons = [
                                                        'ver-menu' => 'fas fa-eye text-danger',
                                                        'ver' => 'fas fa-eye text-info',
                                                        'crear' => 'fas fa-plus text-success',
                                                        'editar' => 'fas fa-edit text-warning',
                                                        'borrar' => 'fas fa-trash text-danger',
                                                        'buscar' => 'fas fa-search text-info',
                                                        'reiniciar' => 'fas fa-sync-alt text-primary',
                                                        'cargar' => 'fas fa-upload text-success',
                                                        'restaurar' => 'fas fa-undo text-success',
                                                        'descargar' => 'fas fa-download text-primary',
                                                        'refrescar' => 'fas fa-sync-alt text-primary',
                                                    ];
                                                @endphp

                                                @foreach ($actions as $action => $permissions)
                                                    @foreach ($permissions as $perm)
                                                        <div class="col-12 permission-item"
                                                            data-search="{{ Str::lower($perm->name.' '.$groupName.' '.$module) }}">
                                                            <div class="custom-control custom-switch mb-2">
                                                                {{ Form::hidden('permission[]', 0) }}
                                                                {{ Form::checkbox('permission[]', $perm->id, in_array($perm->id, $rolePermissions), [
                                                                    'class' => 'custom-control-input permission-checkbox',
                                                                    'id' => 'permission_'.$perm->id,
                                                                    'data-group' => $module
                                                                ]) }}
                                                                <label class="custom-control-label d-block" for="permission_{{ $perm->id }}">
                                                                    <i class="{{ $actionIcons[$action] ?? 'fas fa-cog' }} mr-1"></i>
                                                                    {{ ucfirst($action) }} - {{ ucfirst(str_replace('-', ' ', $module)) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<style>
    .permissions-tree .card {
        border-radius: 8px;
        transition: transform 0.2s;
    }
    .permissions-tree .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .permissions-toolbar {
        z-index: 10;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 15px;
    }
    .group-section {
        background-color: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 12px;
        border-left: 4px solid #007bff;
    }
    .group-toggle {
        cursor: pointer;
        user-select: none;
    }
    .group-toggle .group-caret {
        transition: transform 0.2s;
        font-size: 0.8rem;
    }
    .group-toggle[aria-expanded="true"] .group-caret {
        transform: rotate(90deg);
    }
    .group-toggle .group-count {
        float: right;
    }
    .permission-group .card-header {
        padding: 0.5rem 1rem;
    }
    .group-controls .btn {
        padding: 0.15rem 0.3rem;
        font-size: 0.7rem;
        line-height: 1.2;
    }
    .custom-control-label {
        font-weight: 500;
        color: #495057;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const permissionCheckboxes = () => document.querySelectorAll('.permission-checkbox');

        function updateSelectedCount() {
            const total = document.querySelectorAll('.permission-checkbox:checked').length;
            document.getElementById('permissions-selected-count').textContent = total + ' permisos seleccionados';
        }

        function updateGroupCount(groupSection) {
            const badge = groupSection.querySelector('.group-count');
            if (!badge) return;
            const selected = groupSection.querySelectorAll('.permission-checkbox:checked').length;
            const total = badge.getAttribute('data-total');
            badge.textContent = selected + '/' + total;
        }

        function updateAllGroupCounts() {
            document.querySelectorAll('.group-section').forEach(updateGroupCount);
        }

        // Controles globales
        document.getElementById('select-all-permissions').addEventListener('click', function() {
            permissionCheckboxes().forEach(checkbox => { checkbox.checked = true; });
            updateSelectedCount();
            updateAllGroupCounts();
        });

        document.getElementById('deselect-all-permissions').addEventListener('click', function() {
            permissionCheckboxes().forEach(checkbox => { checkbox.checked = false; });
            updateSelectedCount();
            updateAllGroupCounts();
        });

        // Controles por grupo
        document.querySelectorAll('.select-all-group').forEach(button => {
            button.addEventListener('click', function() {
                const group = this.getAttribute('data-group');
                document.querySelectorAll(`[data-group="${group}"]`).forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelectedCount();
                updateAllGroupCounts();
            });
        });

        document.querySelectorAll('.deselect-all-group').forEach(button => {
            button.addEventListener('click', function() {
                const group = this.getAttribute('data-group');
                document.querySelectorAll(`[data-group="${group}"]`).forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedCount();
                updateAllGroupCounts();
            });
        });

        permissionCheckboxes().forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                const groupSection = this.closest('.group-section');
                if (groupSection) updateGroupCount(groupSection);
            });
        });

        // Búsqueda en vivo
        const searchInput = document.getElementById('permission-search');
        const emptyMessage = document.getElementById('permission-search-empty');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            let anyVisible = false;

            document.querySelectorAll('.group-section').forEach(groupSection => {
                const collapseEl = groupSection.querySelector('.collapse');
                const toggle = groupSection.querySelector('.group-toggle');
                let groupHasMatch = false;

                if (query === '') {
                    groupSection.classList.remove('d-none');
                    groupSection.querySelectorAll('.permission-item').forEach(item => {
                        item.classList.remove('d-none');
                    });
                    // Restaurar estado original del acordeón (abierto solo si tenía seleccionados)
                    const shouldBeOpen = toggle.getAttribute('data-default-expanded') === '1';
                    collapseEl.classList.toggle('show', shouldBeOpen);
                    toggle.setAttribute('aria-expanded', shouldBeOpen ? 'true' : 'false');
                    anyVisible = true;
                    return;
                }

                groupSection.querySelectorAll('.permission-item').forEach(item => {
                    const matches = item.getAttribute('data-search').includes(query);
                    item.classList.toggle('d-none', !matches);
                    if (matches) groupHasMatch = true;
                });

                groupSection.classList.toggle('d-none', !groupHasMatch);

                if (groupHasMatch) {
                    collapseEl.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                    anyVisible = true;
                }
            });

            emptyMessage.classList.toggle('d-none', anyVisible);
        });

        document.getElementById('clear-permission-search').addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });

        // Guardar el estado inicial de cada acordeón para poder restaurarlo al limpiar la búsqueda
        document.querySelectorAll('.group-toggle').forEach(toggle => {
            toggle.setAttribute('data-default-expanded', toggle.getAttribute('aria-expanded') === 'true' ? '1' : '0');
        });

        updateSelectedCount();
    });
</script>
