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
            'menu-alertas-video',
            'menu-personal',
            'menu-armeria',
            'menu-personal-secciones',
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
            'configuracion-logs',
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
        'Alertas de Video (Dominios y Personas)' => [
            'menu-alertas-video',
            'alerta-dominio',
            'alerta-persona',
        ],
        'Personal' => [
            'personal',
            'datos-personales-personal',
            'menu-armeria',
            'personal-secciones',
            'personal-seccion-nota',
            'menu-personal-secciones',
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
            'infraestructura-grabador',
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

    // Construir el árbol: cada grupo tiene una lista de items que son
    // hojas sueltas (un solo permiso) o ramas de módulo (2+ permisos, con su propio checkbox maestro)
    $tree = [];
    foreach ($groupedPermissions as $groupName => $modules) {
        $groupTotal = 0;
        $groupSelected = 0;
        $items = [];

        if ($groupName === 'Otros') {
            foreach ($modules as $perm) {
                $groupTotal++;
                if (in_array($perm->id, $rolePermissions)) {
                    $groupSelected++;
                }
                $items[] = [
                    'type' => 'leaf',
                    'perm' => $perm,
                    'action' => null,
                    'module' => null,
                    'label' => $perm->name,
                ];
            }
        } else {
            foreach ($modules as $module => $actions) {
                $moduleLeaves = [];
                $moduleTotal = 0;
                $moduleSelected = 0;
                foreach ($actions as $action => $permissions) {
                    foreach ($permissions as $perm) {
                        $moduleTotal++;
                        $groupTotal++;
                        if (in_array($perm->id, $rolePermissions)) {
                            $moduleSelected++;
                            $groupSelected++;
                        }
                        $moduleLeaves[] = [
                            'perm' => $perm,
                            'action' => $action,
                            'label' => ucfirst($action) . ' - ' . ucfirst(str_replace('-', ' ', $module)),
                        ];
                    }
                }

                if (count($moduleLeaves) <= 1) {
                    // Un solo permiso: se muestra como hoja directa, sin rama intermedia redundante
                    foreach ($moduleLeaves as $leaf) {
                        $items[] = array_merge(['type' => 'leaf', 'module' => $module], $leaf);
                    }
                } else {
                    $items[] = [
                        'type' => 'branch',
                        'module' => $module,
                        'stats' => ['total' => $moduleTotal, 'selected' => $moduleSelected],
                        'leaves' => $moduleLeaves,
                    ];
                }
            }
        }

        $tree[$groupName] = [
            'stats' => ['total' => $groupTotal, 'selected' => $groupSelected],
            'items' => $items,
        ];
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

    <div class="perm-tree" role="tree">
        @foreach ($tree as $groupName => $group)
            @php $groupExpanded = false; @endphp
            <div class="perm-node perm-node--group" data-default-expanded="{{ $groupExpanded ? 1 : 0 }}">
                <div class="perm-node-row">
                    <label class="perm-checkbox-wrap" title="Activar/desactivar todo el grupo">
                        <input type="checkbox" class="tri-checkbox">
                        <span class="perm-check-visual">
                            <i class="fas fa-check icon-check"></i>
                            <i class="fas fa-minus icon-dash"></i>
                        </span>
                    </label>
                    <div class="perm-node-hit" role="treeitem" tabindex="0" aria-expanded="{{ $groupExpanded ? 'true' : 'false' }}">
                        <i class="fas fa-chevron-right perm-caret-icon"></i>
                        <i class="fas fa-folder-open perm-node-icon"></i>
                        <span class="perm-node-label">{{ $groupName }}</span>
                        <span class="perm-node-count">{{ $group['stats']['selected'] }}/{{ $group['stats']['total'] }}</span>
                    </div>
                </div>
                <div class="perm-node-children {{ $groupExpanded ? '' : 'is-collapsed' }}" role="group">
                    @foreach ($group['items'] as $item)
                        @if ($item['type'] === 'leaf')
                            @include('roles.partials.permiso-leaf', ['item' => $item, 'rolePermissions' => $rolePermissions, 'groupName' => $groupName])
                        @else
                            @php $moduleExpanded = false; @endphp
                            <div class="perm-node perm-node--module" data-default-expanded="{{ $moduleExpanded ? 1 : 0 }}">
                                <div class="perm-node-row">
                                    <label class="perm-checkbox-wrap" title="Activar/desactivar todo el módulo">
                                        <input type="checkbox" class="tri-checkbox">
                                        <span class="perm-check-visual">
                                            <i class="fas fa-check icon-check"></i>
                                            <i class="fas fa-minus icon-dash"></i>
                                        </span>
                                    </label>
                                    <div class="perm-node-hit" role="treeitem" tabindex="0" aria-expanded="{{ $moduleExpanded ? 'true' : 'false' }}">
                                        <i class="fas fa-chevron-right perm-caret-icon"></i>
                                        <i class="fa fa-certificate perm-node-icon"></i>
                                        <span class="perm-node-label">{{ ucfirst(str_replace('-', ' ', $item['module'])) }}</span>
                                        <span class="perm-node-count">{{ $item['stats']['selected'] }}/{{ $item['stats']['total'] }}</span>
                                    </div>
                                </div>
                                <div class="perm-node-children {{ $moduleExpanded ? '' : 'is-collapsed' }}" role="group">
                                    @foreach ($item['leaves'] as $leaf)
                                        @include('roles.partials.permiso-leaf', [
                                            'item' => array_merge(['type' => 'leaf', 'module' => $item['module']], $leaf),
                                            'rolePermissions' => $rolePermissions,
                                            'groupName' => $groupName,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .permissions-toolbar {
        z-index: 10;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 15px;
    }

    /* --- Árbol --- */
    .perm-tree {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 6px 10px;
        background: var(--card-bg);
    }
    .perm-node-children {
        margin-left: 22px;
        padding-left: 14px;
        border-left: 2px solid var(--border-color);
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 0 12px;
        align-items: start;
    }
    .perm-node-children.is-collapsed {
        display: none;
    }
    /* Los módulos (con sus propios hijos) ocupan todo el ancho de la grilla */
    .perm-node--module {
        grid-column: 1 / -1;
    }
    .perm-node--group > .perm-node-children {
        margin-bottom: 4px;
    }
    .perm-node--group + .perm-node--group {
        border-top: 1px solid var(--border-color);
    }

    .perm-node-row {
        display: flex;
        align-items: center;
        padding: 6px 4px;
        border-radius: 6px;
    }
    .perm-node--group > .perm-node-row .perm-node-label {
        font-weight: 700;
        color: var(--text-primary);
    }
    .perm-node--module > .perm-node-row .perm-node-label {
        font-weight: 600;
        color: var(--text-secondary);
    }

    .perm-node-hit {
        display: flex;
        align-items: center;
        flex: 1 1 auto;
        min-width: 0;
        cursor: pointer;
        user-select: none;
        border-radius: 6px;
        padding: 2px 6px;
    }
    .perm-node-hit:hover {
        background-color: var(--bg-tertiary);
    }
    .perm-node-hit:focus-visible {
        outline: 2px solid #86b7fe;
        outline-offset: 1px;
    }
    .perm-caret-icon {
        font-size: 0.72rem;
        color: var(--text-secondary);
        margin-right: 8px;
        transition: transform 0.15s ease;
        flex: 0 0 auto;
    }
    .perm-node-hit[aria-expanded="true"] .perm-caret-icon {
        transform: rotate(90deg);
    }
    .perm-node-icon {
        margin-right: 8px;
        color: var(--text-secondary);
        flex: 0 0 auto;
    }
    .perm-node-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-right: 10px;
    }
    .perm-node-count {
        margin-left: auto;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: var(--bg-tertiary);
        border-radius: 10px;
        padding: 1px 8px;
        flex: 0 0 auto;
    }

    /* --- Checkbox tri-estado (padres) --- */
    .perm-checkbox-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin: 0 8px 0 2px;
        flex: 0 0 auto;
        cursor: pointer;
    }
    .perm-checkbox-wrap .tri-checkbox {
        position: absolute;
        inset: 0;
        margin: 0;
        opacity: 0;
        cursor: pointer;
    }
    .perm-check-visual {
        width: 19px;
        height: 19px;
        border-radius: 5px;
        border: 2px solid var(--input-border);
        background: var(--input-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: background-color 0.12s ease, border-color 0.12s ease;
    }
    .perm-check-visual .icon-check,
    .perm-check-visual .icon-dash {
        display: none;
        font-size: 0.65rem;
    }
    .tri-checkbox:checked ~ .perm-check-visual {
        background-color: #2eb85c;
        border-color: #2eb85c;
    }
    .tri-checkbox:checked ~ .perm-check-visual .icon-check {
        display: block;
    }
    .tri-checkbox:indeterminate ~ .perm-check-visual {
        background-color: #f9b115;
        border-color: #f9b115;
    }
    .tri-checkbox:indeterminate ~ .perm-check-visual .icon-dash {
        display: block;
    }
    .tri-checkbox:focus-visible ~ .perm-check-visual {
        outline: 2px solid #86b7fe;
        outline-offset: 1px;
    }

    /* --- Hojas (permisos individuales) --- */
    .perm-leaf-row {
        padding: 3px 4px;
        min-width: 0;
    }
    .perm-leaf-switch {
        min-height: 0;
        padding-left: 2.5rem;
    }
    .perm-leaf-switch .custom-control-label {
        font-weight: 500;
        color: var(--text-primary);
        line-height: 1.6rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        max-width: 100%;
    }
    .perm-leaf-switch .custom-control-label .perm-leaf-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }
    .perm-leaf-switch .custom-control-label::before,
    .perm-leaf-switch .custom-control-label::after {
        top: 0.05rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const permTree = document.querySelector('.perm-tree');
        if (!permTree) return;

        const permissionCheckboxes = () => permTree.querySelectorAll('.permission-checkbox');

        function updateSelectedCount() {
            const total = permTree.querySelectorAll('.permission-checkbox:checked').length;
            document.getElementById('permissions-selected-count').textContent = total + ' permisos seleccionados';
        }

        function leavesOf(node) {
            const container = node.querySelector(':scope > .perm-node-children');
            return container ? Array.from(container.querySelectorAll('.permission-checkbox')) : [];
        }

        function refreshNode(node) {
            const master = node.querySelector(':scope > .perm-node-row > .perm-checkbox-wrap > .tri-checkbox');
            if (!master) return;
            const leaves = leavesOf(node);
            const total = leaves.length;
            const checked = leaves.filter(cb => cb.checked).length;
            master.checked = total > 0 && checked === total;
            master.indeterminate = checked > 0 && checked < total;
            const badge = node.querySelector(':scope > .perm-node-row .perm-node-count');
            if (badge) badge.textContent = checked + '/' + total;
        }

        function refreshAllNodes() {
            permTree.querySelectorAll('.perm-node').forEach(refreshNode);
        }

        function refreshAncestors(el) {
            let node = el.closest('.perm-node');
            while (node) {
                refreshNode(node);
                node = node.parentElement ? node.parentElement.closest('.perm-node') : null;
            }
        }

        function setExpanded(node, expanded) {
            const children = node.querySelector(':scope > .perm-node-children');
            const hit = node.querySelector(':scope > .perm-node-row > .perm-node-hit');
            if (!children || !hit) return;
            children.classList.toggle('is-collapsed', !expanded);
            hit.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        // Expandir/colapsar al hacer click (o Enter/Espacio) sobre la etiqueta del nodo
        permTree.querySelectorAll('.perm-node-hit').forEach(hit => {
            function toggle() {
                const node = hit.closest('.perm-node');
                const children = node.querySelector(':scope > .perm-node-children');
                if (!children) return;
                setExpanded(node, children.classList.contains('is-collapsed'));
            }
            hit.addEventListener('click', toggle);
            hit.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });

        // Checkbox maestro (grupo/módulo): activa/desactiva todos sus hijos
        permTree.querySelectorAll('.tri-checkbox').forEach(master => {
            master.addEventListener('change', function() {
                const node = master.closest('.perm-node');
                leavesOf(node).forEach(cb => { cb.checked = master.checked; });
                // Refresca también los módulos hijos (su propio checkbox/badge) antes de subir a los ancestros
                node.querySelectorAll('.perm-node').forEach(refreshNode);
                refreshAncestors(node);
                updateSelectedCount();
            });
        });

        // Checkbox hoja: recalcula el estado de sus ancestros (módulo y grupo)
        permissionCheckboxes().forEach(cb => {
            cb.addEventListener('change', function() {
                refreshAncestors(this);
                updateSelectedCount();
            });
        });

        // Controles globales
        document.getElementById('select-all-permissions').addEventListener('click', function() {
            permissionCheckboxes().forEach(checkbox => { checkbox.checked = true; });
            refreshAllNodes();
            updateSelectedCount();
        });

        document.getElementById('deselect-all-permissions').addEventListener('click', function() {
            permissionCheckboxes().forEach(checkbox => { checkbox.checked = false; });
            refreshAllNodes();
            updateSelectedCount();
        });

        // Búsqueda en vivo
        const searchInput = document.getElementById('permission-search');
        const emptyMessage = document.getElementById('permission-search-empty');
        const allNodesDeepFirst = Array.from(permTree.querySelectorAll('.perm-node')).reverse();

        function restoreDefaultState() {
            permTree.querySelectorAll('.perm-node, .perm-leaf-row').forEach(el => el.classList.remove('d-none'));
            permTree.querySelectorAll('.perm-node').forEach(node => {
                setExpanded(node, node.getAttribute('data-default-expanded') === '1');
            });
            emptyMessage.classList.add('d-none');
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();

            if (query === '') {
                restoreDefaultState();
                return;
            }

            permTree.querySelectorAll('.perm-leaf-row').forEach(row => {
                row.classList.toggle('d-none', !row.getAttribute('data-search').includes(query));
            });

            // De más profundo a más superficial: un nodo es visible si alguno de sus hijos directos lo es
            allNodesDeepFirst.forEach(node => {
                const children = node.querySelector(':scope > .perm-node-children');
                let hasVisible = false;
                if (children) {
                    hasVisible = Array.from(children.children).some(child => !child.classList.contains('d-none'));
                }
                node.classList.toggle('d-none', !hasVisible);
                if (hasVisible) setExpanded(node, true);
            });

            const anyVisible = permTree.querySelectorAll('.perm-node--group:not(.d-none)').length > 0;
            emptyMessage.classList.toggle('d-none', anyVisible);
        });

        document.getElementById('clear-permission-search').addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });

        refreshAllNodes();
        updateSelectedCount();
    });
</script>
