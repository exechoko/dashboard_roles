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
                                        <label for="">Permisos para este Rol:</label>

                                        <div class="permissions-tree">
                                            @php
                                                // Agrupar permisos por categorías
                                                $groupedPermissions = [];
                                                foreach ($permission as $perm) {
                                                    $parts = explode('-', $perm->name);
                                                    $action = $parts[0]; // ver, crear, editar, borrar
                                                    $module = implode('-', array_slice($parts, 1)); // resto del nombre

                                                    // Mover permisos de auditoría al grupo 'menu-auditoria'
                                                    if ($module === 'auditoria') {
                                                        $module = 'menu-auditoria';
                                                    }

                                                    if (!isset($groupedPermissions[$module])) {
                                                        $groupedPermissions[$module] = [];
                                                    }
                                                    $groupedPermissions[$module][$action] = $perm;
                                                }

                                                // Ordenar las categorías
                                                ksort($groupedPermissions);
                                            @endphp

                                            <div class="row">
                                                @foreach ($groupedPermissions as $module => $actions)
                                                    <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 mb-3">
                                                        <div class="permission-group h-100">
                                                            <div class="card border-left-primary h-100">
                                                                <div class="card-header bg-light">
                                                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                                        <h6 class="mb-0 text-primary">
                                                                            <i class="fas fa-folder"></i>
                                                                            {{ ucfirst(str_replace('-', ' ', $module)) }}
                                                                        </h6>
                                                                        <div class="group-controls mt-1">
                                                                            <button type="button" class="btn btn-sm btn-outline-success select-all-group" data-group="{{ $module }}">
                                                                                <i class="fas fa-check-double"></i> <span class="d-none d-lg-inline">Seleccionar Todo</span>
                                                                            </button>
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-group" data-group="{{ $module }}">
                                                                                <i class="fas fa-times"></i> <span class="d-none d-lg-inline">Deseleccionar Todo</span>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        @php
                                                                            $actionOrder = ['ver', 'crear', 'editar', 'borrar'];
                                                                            $actionIcons = [
                                                                                'ver' => 'fas fa-eye text-info',
                                                                                'crear' => 'fas fa-plus text-success',
                                                                                'editar' => 'fas fa-edit text-warning',
                                                                                'borrar' => 'fas fa-trash text-danger'
                                                                            ];
                                                                        @endphp

                                                                        @foreach ($actionOrder as $action)
                                                                            @if (isset($actions[$action]))
                                                                                @php $perm = $actions[$action]; @endphp
                                                                                <div class="col-lg-6 col-md-12 col-sm-12">
                                                                                    <div class="custom-control custom-switch mb-2">
                                                                                        {{ Form::hidden('permission[]', 0) }}
                                                                                        {{ Form::checkbox('permission[]', $perm->id, in_array($perm->id, $rolePermissions), [
                                                                                            'class' => 'custom-control-input permission-checkbox',
                                                                                            'id' => 'permission_'.$perm->id,
                                                                                            'data-group' => $module
                                                                                        ]) }}
                                                                                        <label class="custom-control-label" for="permission_{{ $perm->id }}">
                                                                                            <i class="{{ $actionIcons[$action] ?? 'fas fa-cog' }}"></i>
                                                                                            {{ ucfirst($action) }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Permisos que no siguen el patrón estándar -->
                                            @php
                                                $specialPermissions = [];
                                                foreach ($permission as $perm) {
                                                    $parts = explode('-', $perm->name);
                                                    if (count($parts) < 2 || !in_array($parts[0], ['ver', 'crear', 'editar', 'borrar'])) {
                                                        $specialPermissions[] = $perm;
                                                    }
                                                }
                                            @endphp

                                            @if (count($specialPermissions) > 0)
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="permission-group mb-3">
                                                            <div class="card border-left-info">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="mb-0 text-info">
                                                                        <i class="fas fa-cogs"></i> Permisos Especiales
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        @foreach ($specialPermissions as $perm)
                                                                            <div class="col-lg-6 col-md-12 col-sm-12">
                                                                                <div class="custom-control custom-switch mb-2">
                                                                                    {{ Form::hidden('permission[]', 0) }}
                                                                                    {{ Form::checkbox('permission[]', $perm->id, in_array($perm->id, $rolePermissions), [
                                                                                        'class' => 'custom-control-input',
                                                                                        'id' => 'permission_'.$perm->id
                                                                                    ]) }}
                                                                                    <label class="custom-control-label" for="permission_{{ $perm->id }}">
                                                                                        <i class="fas fa-key text-info"></i>
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
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Controles globales -->
                                        <div class="mt-3 mb-3">
                                            <button type="button" class="btn btn-success" id="select-all-permissions">
                                                <i class="fas fa-check-double"></i> Seleccionar Todos los Permisos
                                            </button>
                                            <button type="button" class="btn btn-secondary ml-2" id="deselect-all-permissions">
                                                <i class="fas fa-times"></i> Deseleccionar Todos los Permisos
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
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
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }

        .border-left-primary {
            border-left: 3px solid #007bff !important;
        }

        .border-left-info {
            border-left: 3px solid #17a2b8 !important;
        }

        .custom-control-label {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .group-controls .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .permission-group .card-header {
            padding: 0.75rem 1rem;
        }

        .permission-group .card-body {
            padding: 1rem;
        }

        .permission-group .h-100 {
            height: 100% !important;
        }

        @media (max-width: 991px) {
            .group-controls .btn span {
                display: none !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionar todos los permisos
            document.getElementById('select-all-permissions').addEventListener('click', function() {
                document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                });
            });

            // Deseleccionar todos los permisos
            document.getElementById('deselect-all-permissions').addEventListener('click', function() {
                document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });

            // Seleccionar todos los permisos de un grupo
            document.querySelectorAll('.select-all-group').forEach(button => {
                button.addEventListener('click', function() {
                    const group = this.getAttribute('data-group');
                    document.querySelectorAll(`[data-group="${group}"]`).forEach(checkbox => {
                        checkbox.checked = true;
                    });
                });
            });

            // Deseleccionar todos los permisos de un grupo
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
