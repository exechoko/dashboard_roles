@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Rol</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-dark alert-dismissible fade show" role="alert">
                                    <strong>¡Revise los campos!</strong>
                                    @foreach ($errors->all() as $error)
                                        <span class="badge badge-danger">{{ $error }}</span>
                                    @endforeach
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            {!! Form::model($role, ['method' => 'PATCH', 'route' => ['roles.update', $role->id]]) !!}
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="">Nombre del Rol:</label>
                                        {!! Form::text('name', null, ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <div class="permissions-tree">
                                            <!-- Controles globales -->
                                            <div class="mt-4 text-center">
                                                <button type="button" class="btn btn-success" id="select-all-permissions">
                                                    <i class="fas fa-check-double"></i> Seleccionar Todos
                                                </button>
                                                <button type="button" class="btn btn-secondary ml-2" id="deselect-all-permissions">
                                                    <i class="fas fa-times"></i> Deseleccionar Todos
                                                </button>
                                            </div>
                                            @php
                                                // Agrupar permisos por categorías principales
                                                $mainGroups = [
                                                    'Menús' => [
                                                        'menu-dashboard',
                                                        'menu-equipamientos',
                                                        'menu-camaras',
                                                        'menu-dependencias',
                                                        'menu-mapa',
                                                        'menu-usuarios',
                                                        'menu-auditoria',
                                                        'menu-cecoco',
                                                        'menu-entregas',
                                                        'menu-documentacion',
                                                        'menu-transcripcion',
                                                        'menu-transcripcion-aws'
                                                    ],
                                                    'Administración' => [
                                                        'rol',
                                                        'usuario',
                                                        'auditoria'
                                                    ],
                                                    'Equipamientos' => [
                                                        'equipo',
                                                        'terminal',
                                                        'dependencia',
                                                        'recurso'
                                                    ],
                                                    'Vehículos' => [
                                                        'vehiculo',
                                                        'historico',
                                                        'flota'
                                                    ],
                                                    'Cámaras' => [
                                                        'camara',
                                                        'tipo-camara',
                                                        'sitio'
                                                    ],
                                                    'Cecoco' => [
                                                        'llamadas-cecoco',
                                                        'moviles-cecoco',
                                                        'eventos-cecoco',
                                                        'mapa-calor-servicios-cecoco',
                                                        'mapa-cecoco-en-vivo'
                                                    ],
                                                    'Entregas' => [
                                                        'entrega-equipos',
                                                        'entrega-bodycams'
                                                    ],
                                                    'Operaciones' => [
                                                        'buscar-moviles-parados',
                                                        'buscar-moviles-recorridos',
                                                        'reiniciar-camara'
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
                                            @endphp

                                            @foreach ($groupedPermissions as $groupName => $modules)
                                                <div class="group-section mb-4">
                                                    <h5 class="text-primary mb-3">
                                                        <i class="fas fa-folder-open"></i> {{ $groupName }}
                                                    </h5>
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
                                                                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                                                                        <div class="custom-control custom-switch mb-3">
                                                                                            {{ Form::hidden('permission[]', 0) }}
                                                                                            {{ Form::checkbox('permission[]', $perm->id, in_array($perm->id, $rolePermissions), [
                                                                                                'class' => 'custom-control-input',
                                                                                                'id' => 'permission_'.$perm->id
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
                                                                                            'reiniciar' => 'fas fa-sync-alt text-primary'
                                                                                        ];
                                                                                    @endphp

                                                                                    @foreach ($actions as $action => $permissions)
                                                                                        @foreach ($permissions as $perm)
                                                                                            <div class="col-12">
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
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 text-right mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                </div>

                            </div>
                            {!! Form::close() !!}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .permissions-tree .card {
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .permissions-tree .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .group-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
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
            // Controles globales
            document.getElementById('select-all-permissions').addEventListener('click', function() {
                document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                });
            });

            document.getElementById('deselect-all-permissions').addEventListener('click', function() {
                document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });

            // Controles por grupo
            document.querySelectorAll('.select-all-group').forEach(button => {
                button.addEventListener('click', function() {
                    const group = this.getAttribute('data-group');
                    document.querySelectorAll(`[data-group="${group}"]`).forEach(checkbox => {
                        checkbox.checked = true;
                    });
                });
            });

            document.querySelectorAll('.deselect-all-group').forEach(button => {
                button.addEventListener('click', function() {
                    const group = this.getAttribute('data-group');
                    document.querySelectorAll(`[data-group="${group}"]`).forEach(checkbox => {
                        checkbox.checked = false;
                    });
                });
            });
        });
    </script>
@endsection
