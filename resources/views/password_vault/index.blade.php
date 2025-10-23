@extends('layouts.app')

@section('title', 'Gestor de Contraseñas')

@push('style')
<style>
    /* Hacer que las columnas tengan la misma altura */
    .password-cards-row {
        display: flex;
        flex-wrap: wrap;
    }

    .password-card-col {
        display: flex;
        margin-bottom: 30px;
    }

    /* Card con flexbox para altura completa */
    .password-card {
        transition: all 0.3s;
        cursor: pointer;
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    .password-card .card-body {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .password-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .password-hidden {
        filter: blur(5px);
        user-select: none;
    }

    .favorite-star {
        color: #ffc107;
    }

    /* Contenido crece para llenar el espacio */
    .password-card-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Acciones siempre al final */
    .password-card-actions {
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid #e9ecef;
    }

    /* Altura mínima para último acceso */
    .last-access-container {
        min-height: 24px;
    }
</style>
@endpush

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-lock"></i> Gestor de Contraseñas</h1>
        <div class="section-header-button">
            @can('crear-clave')
                <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Contraseña
                </a>
            @endcan
        </div>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('password-vault.index') }}" id="searchForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-md-0">
                                        <label>Buscar</label>
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Buscar por nombre, usuario o URL..."
                                               value="{{ request('search') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-md-0">
                                        <label>Tipo de Sistema</label>
                                        <select name="type" class="form-control">
                                            <option value="">Todos</option>
                                            @foreach($systemTypes as $key => $type)
                                                <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                                    {{ $type['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-md-0">
                                        <label class="d-block">&nbsp;</label>
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="favorites" name="favorites" value="1"
                                                   {{ request('favorites') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="favorites">
                                                Solo Favoritos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-md-0">
                                        <label class="d-block">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                            <a href="{{ route('password-vault.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjetas de contraseñas --}}
        <div class="row password-cards-row">
            @forelse($passwords as $password)
            <div class="col-md-6 col-lg-4 password-card-col">
                <div class="card password-card">
                    <div class="card-body">
                        <div class="password-card-content">
                            {{-- Header con icono y favorito --}}
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <i class="{{ $systemTypes[$password->system_type]['icon'] }} fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $password->system_name }}</h6>
                                        <small class="text-muted">{{ $systemTypes[$password->system_type]['label'] }}</small>
                                        {{-- Indicador de contraseña compartida --}}
                                        @if($password->user_id !== Auth::id())
                                            <br><span class="badge badge-info badge-sm"><i class="fas fa-share-alt"></i> Compartida</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    @can('editar-clave')
                                        <button class="btn btn-sm btn-link p-0 toggle-favorite"
                                                data-id="{{ $password->id }}">
                                            <i class="fas fa-star {{ $password->favorite ? 'favorite-star' : 'text-muted' }}"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>

                            {{-- Usuario y URL en la misma fila --}}
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Usuario</small>
                                    <strong class="d-block text-truncate" title="{{ $password->username }}">{{ $password->username }}</strong>
                                </div>
                                <div class="col-md-6">
                                    @if($password->url)
                                        <small class="text-muted d-block">URL</small>
                                        <a href="{{ $password->url }}" target="_blank" class="d-block text-truncate" title="{{ $password->url }}">
                                            {{ $password->url }}
                                        </a>
                                    @else
                                        <small class="text-muted d-block">&nbsp;</small>
                                        <span class="d-block">&nbsp;</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Contraseña --}}
                            <div class="mb-2">
                                <small class="text-muted d-block">Contraseña</small>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-sm password-field"
                                           value="{{ $password->password }}" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-sm btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary copy-password"
                                                type="button" data-password="{{ $password->password }}">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Botones de acción (siempre al final) --}}
                        <div class="password-card-actions">
                            <div class="d-flex justify-content-between">
                                <div class="btn-group">
                                    @can('ver-clave')
                                        <a href="{{ route('password-vault.show', $password) }}"
                                           class="btn btn-sm btn-info" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan

                                    @can('editar-clave')
                                        <a href="{{ route('password-vault.edit', $password) }}"
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    {{-- Solo el dueño puede compartir --}}
                                    @can('compartir-clave')
                                        @if($password->user_id === Auth::id())
                                            <button type="button"
                                                    class="btn btn-sm btn-primary share-password-btn"
                                                    data-toggle="modal"
                                                    data-target="#shareModal"
                                                    data-id="{{ $password->id }}"
                                                    title="Compartir contraseña">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        @endif
                                    @endcan

                                    {{-- Solo el dueño puede eliminar --}}
                                    @can('borrar-clave')
                                        @if($password->user_id === Auth::id())
                                            <form action="{{ route('password-vault.destroy', $password) }}"
                                                    method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger rounded-left-0" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h5>No hay contraseñas guardadas</h5>
                        <p class="text-muted">Comienza agregando tu primera contraseña</p>
                        @can('crear-clave')
                            <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Contraseña
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        @if($passwords->hasPages())
        <div class="row">
            <div class="col-12">
                {{ $passwords->links() }}
            </div>
        </div>
        @endif
    </div>
</section>

{{-- Modal de compartir (solo para dueños con permiso) --}}
@can('compartir-clave')
<div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Compartir Contraseña</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="shareForm" class="ajax-share-form" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="password_vault_id" id="share_password_vault_id">

                    <div class="form-group">
                        <label for="shared_with_user_id">Seleccionar Usuario para Compartir</label>
                        <select name="shared_with_user_id" id="shared_with_user_id" class="form-control" required>
                            <option value="">Buscar y seleccionar un usuario...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="can_edit" value="0">
                        <input type="checkbox" class="custom-control-input" id="can_edit" name="can_edit" value="1">
                        <label class="custom-control-label" for="can_edit">Permitir edición al usuario</label>
                    </div>

                    <hr>
                    <p class="text-muted small">Usuarios con acceso actual:</p>
                    <div id="currentSharesList">
                        <p class="text-center text-muted small">Cargando...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Compartir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Toggle mostrar/ocultar contraseña
            $('.toggle-password').click(function() {
                let input = $(this).closest('.input-group').find('.password-field');
                let icon = $(this).find('i');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Copiar contraseña
            $('.copy-password').click(function () {
                let password = $(this).data('password');

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(password)
                        .then(function () {
                            iziToast.success({
                                title: 'Copiado',
                                message: 'Contraseña copiada al portapapeles.',
                                position: 'topRight'
                            });
                        })
                        .catch(function (err) {
                            copyFallback(password);
                        });
                } else {
                    copyFallback(password);
                }
            });

            // Toggle favorito
            $('.toggle-favorite').click(function() {
                let btn = $(this);
                let id = btn.data('id');

                $.ajax({
                    url: `/password-vault/${id}/toggle-favorite`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        let star = btn.find('i');
                        if (response.favorite) {
                            star.addClass('favorite-star').removeClass('text-muted');
                        } else {
                            star.removeClass('favorite-star').addClass('text-muted');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            iziToast.error({
                                title: 'Error',
                                message: 'No tienes permiso para modificar favoritos.',
                                position: 'topRight'
                            });
                        }
                    }
                });
            });

            // Confirmar eliminación
            $('.delete-form').submit(function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta contraseña será eliminada permanentemente',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Envío del formulario de compartir
            $('#shareForm').submit(function (e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: form.serialize(),
                    success: function (response) {
                        $('#shareModal').modal('hide');
                        form.trigger('reset');

                        iziToast.success({
                            title: 'Éxito',
                            message: 'Contraseña compartida exitosamente.',
                            position: 'topRight'
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function (xhr) {
                        let message = 'Error al intentar compartir la contraseña.';

                        if (xhr.status === 403) {
                            message = 'No tienes permiso para compartir esta contraseña.';
                        } else if (xhr.status === 422 && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            message = errors[Object.keys(errors)[0]][0];
                        }

                        iziToast.error({
                            title: 'Error',
                            message: message,
                            position: 'topRight'
                        });
                    }
                });
            });

            // Revocar acceso
            $(document).on('click', '.remove-share', function () {
                const shareId = $(this).data('share-id');
                const btn = $(this);

                Swal.fire({
                    title: '¿Revocar acceso?',
                    text: 'El usuario perderá inmediatamente el acceso a esta contraseña.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, revocar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/password-shares/${shareId}/revoke`,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            },
                            success: function (response) {
                                iziToast.success({
                                    title: 'Revocado',
                                    message: 'Acceso revocado exitosamente.',
                                    position: 'topRight'
                                });

                                const passwordId = $('#share_password_vault_id').val();
                                loadCurrentShares(passwordId);
                            },
                            error: function (xhr) {
                                let message = 'Error al revocar el acceso.';

                                if (xhr.status === 403) {
                                    message = 'No tienes permiso para revocar este acceso.';
                                } else if (xhr.status === 404) {
                                    message = 'El registro de compartido no fue encontrado.';
                                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                iziToast.error({
                                    title: 'Error',
                                    message: message,
                                    position: 'topRight'
                                });
                            }
                        });
                    }
                });
            });

            // Configurar modal de compartir
            $('.share-password-btn').click(function () {
                const passwordId = $(this).data('id');
                $('#share_password_vault_id').val(passwordId);
                $('#shareForm').attr('action', `/password-vault/${passwordId}/share`);
                loadCurrentShares(passwordId);
            });

            function copyFallback(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    iziToast.success({
                        title: 'Copiado',
                        message: 'Contraseña copiada al portapapeles.',
                        position: 'topRight'
                    });
                } catch (err) {
                    iziToast.error({
                        title: 'Error',
                        message: 'No se pudo copiar automáticamente.',
                        position: 'topRight'
                    });
                }
                document.body.removeChild(textarea);
            }

            function loadCurrentShares(passwordId) {
                const listContainer = $('#currentSharesList');
                listContainer.html('<p class="text-center text-muted small"><i class="fas fa-sync fa-spin"></i> Cargando...</p>');

                $.ajax({
                    url: `/password-vault/${passwordId}/shares`,
                    method: 'GET',
                    success: function (response) {
                        if (response.shares && response.shares.length > 0) {
                            let html = '<ul class="list-unstyled mb-0">';
                            response.shares.forEach(function (share) {
                                const canEdit = share.can_edit ? ' <span class="badge badge-info">Puede Editar</span>' : '';
                                html += `<li class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>${share.shared_with_name}${canEdit}</span>
                                        <button type="button" class="btn btn-sm btn-danger remove-share" data-share-id="${share.id}">
                                            <i class="fas fa-times"></i> Revocar
                                        </button>
                                    </div>
                                </li>`;
                            });
                            html += '</ul>';
                            listContainer.html(html);
                        } else {
                            listContainer.html('<p class="text-muted small mb-0">Esta contraseña no está compartida con nadie.</p>');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 403) {
                            listContainer.html('<p class="text-danger small mb-0">No tienes permiso para ver los compartidos.</p>');
                        } else {
                            listContainer.html('<p class="text-danger small mb-0">Error al cargar compartidos.</p>');
                        }
                    }
                });
            }
        });
    </script>
@endpush
