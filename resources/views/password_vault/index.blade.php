@extends('layouts.app')

@section('title', 'Gestor de Contraseñas')

@push('style')
<style>
    .password-card {
        transition: all 0.3s;
        cursor: pointer;
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
</style>
@endpush

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-lock"></i> Gestor de Contraseñas</h1>
        <div class="section-header-button">
            <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Contraseña
            </a>
        </div>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('password-vault.index') }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Buscar</label>
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Buscar por nombre, usuario o URL..."
                                               value="{{ request('search') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
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
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="favorites" name="favorites"
                                                   {{ request()->has('favorites') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="favorites">
                                                Solo Favoritos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
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
        <div class="row">
            @forelse($passwords as $password)
            <div class="col-md-6 col-lg-4">
                <div class="card password-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="{{ $systemTypes[$password->system_type]['icon'] }} fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $password->system_name }}</h6>
                                    <small class="text-muted">{{ $systemTypes[$password->system_type]['label'] }}</small>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-link p-0 toggle-favorite"
                                        data-id="{{ $password->id }}">
                                    <i class="fas fa-star {{ $password->favorite ? 'favorite-star' : 'text-muted' }}"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Usuario</small>
                            <strong>{{ $password->username }}</strong>
                        </div>

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

                        @if($password->url)
                        <div class="mb-2">
                            <small class="text-muted d-block">URL</small>
                            <a href="{{ $password->url }}" target="_blank" class="text-truncate d-block">
                                {{ $password->url }}
                            </a>
                        </div>
                        @endif

                        <div class="mt-2 pt-2 border-top"> {{-- Reducir margen y padding superior --}}
                            <div class="d-flex justify-content-between">
                                <div class="btn-group"> {{-- Agrupar botones para compactar --}}
                                    <a href="{{ route('password-vault.show', $password) }}"
                                       class="btn btn-sm btn-info" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('password-vault.edit', $password) }}"
                                       class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- INICIO: NUEVA FUNCIONALIDAD DE COMPARTIR --}}
                                    <button type="button"
                                            class="btn btn-sm btn-primary share-password-btn"
                                            data-toggle="modal"
                                            data-target="#shareModal"
                                            data-id="{{ $password->id }}"
                                            title="Compartir contraseña">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    {{-- FIN: NUEVA FUNCIONALIDAD DE COMPARTIR --}}

                                    <form action="{{ route('password-vault.destroy', $password) }}"
                                            method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger rounded-left-0" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if($password->last_accessed_at)
                        <div class="mt-2">
                            <small class="text-muted">
                                Último acceso: {{ $password->last_accessed_at->diffForHumans() }}
                            </small>
                        </div>
                        @endif
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
                        <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Contraseña
                        </a>
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
@endsection

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
                        {{-- Aquí usarías un select2 o un campo de autocompletar para buscar usuarios --}}
                        <select name="shared_with_user_id" id="shared_with_user_id" class="form-control" required>
                            <option value="">Buscar y seleccionar un usuario...</option>
                            {{-- La lista de usuarios disponibles se cargaría aquí --}}
                            {{-- Ejemplo (asumiendo que tienes $users disponibles): --}}
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
                        {{-- Aquí se listarán los usuarios que ya tienen acceso (cargado con JS/AJAX) --}}
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

                // 1. Intentar usar la API moderna (asíncrona y recomendada)
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(password)
                        .then(function () {
                            iziToast.success({
                                title: 'Copiado',
                                message: 'Contraseña copiada al portapapeles (Modo moderno).',
                                position: 'topRight'
                            });
                        })
                        .catch(function (err) {
                            // Fallback si falla la escritura (ej. permisos)
                            copyFallback(password);
                        });
                } else {
                    // 2. Si la API moderna no está disponible, usar el método antiguo
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
                    }
                });
            });

            // Confirmar eliminación
            $('.delete-form').submit(function(e) {
                e.preventDefault();
                let form = this;

                swal({
                    title: '¿Estás seguro?',
                    text: 'Esta contraseña será eliminada permanentemente',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            });

            // **********************************************
            // ** LÓGICA DE ENVÍO DEL FORMULARIO DE COMPARTIR **
            // **********************************************
            $('#shareForm').submit(function (e) {
                e.preventDefault(); // Detener el envío de formulario tradicional
                const form = $(this);
                const url = form.attr('action'); // La URL ya está configurada al abrir el modal

                $.ajax({
                    url: url,
                    method: 'POST', // Esto coincide con la ruta en Laravel
                    data: form.serialize(), // Enviar todos los campos del formulario
                    success: function (response) {
                        // Éxito: Cierra el modal, limpia el formulario, y muestra toast
                        $('#shareModal').modal('hide');
                        form.trigger('reset'); // Limpia los campos del formulario

                        iziToast.success({
                            title: 'Éxito',
                            message: 'Contraseña compartida exitosamente.',
                            position: 'topRight'
                        });

                        // Opcional: Recargar la lista de compartidos en el modal si fuera necesario
                        // O simplemente recargar la página para ver el cambio (más fácil)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function (xhr) {
                        // Error: Manejo de errores de Policy (403) o Validación (422)
                        let message = 'Error al intentar compartir la contraseña.';

                        if (xhr.status === 403) {
                            message = 'No tienes permiso para compartir esta contraseña.';
                        } else if (xhr.status === 422 && xhr.responseJSON.errors) {
                            // Errores de validación de Laravel (Rule::unique, Rule::notIn)
                            const errors = xhr.responseJSON.errors;
                            // Mostrar solo el primer error relevante
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

            // Función de respaldo para copiar
            function copyFallback(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    iziToast.success({
                        title: 'Copiado',
                        message: 'Contraseña copiada al portapapeles (Modo antiguo).',
                        position: 'topRight'
                    });
                } catch (err) {
                    iziToast.error({
                        title: 'Error',
                        message: 'No se pudo copiar automáticamente. Intenta manualmente.',
                        position: 'topRight'
                    });
                }
                document.body.removeChild(textarea);
            }

            // Configurar el modal de compartir
            $('.share-password-btn').click(function () {
                const passwordId = $(this).data('id');
                $('#share_password_vault_id').val(passwordId);

                // 1. Configurar la URL del formulario de envío
                // Asegúrate de crear esta ruta en Laravel, por ejemplo:
                // Route::post('password-vault/{id}/share', 'PasswordVaultController@share')->name('password-vault.share');
                $('#shareForm').attr('action', `/password-vault/${passwordId}/share`);

                // 2. (Opcional pero Recomendado) Cargar la lista de usuarios compartidos actualmente
                loadCurrentShares(passwordId);
            });

            // **********************************************
            // ** LÓGICA PARA REVOCAR EL ACCESO (AJAX) **
            // **********************************************
            $(document).on('click', '.remove-share', function () {
                const shareId = $(this).data('share-id');
                const btn = $(this);

                swal({
                    title: '¿Revocar acceso?',
                    text: 'El usuario perderá inmediatamente el acceso a esta contraseña.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }, function(willDelete) {
                    if (willDelete) {
                        // IMPORTANTE: Usar POST con _method: DELETE para compatibilidad con Laravel
                        $.ajax({
                            url: `/password-shares/${shareId}/revoke`,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'  // ← Esto es CRUCIAL para Laravel
                            },
                            success: function (response) {
                                swal({
                                    title: "¡Revocado!",
                                    text: "Acceso revocado exitosamente.",
                                    icon: "success",
                                    timer: 2000,
                                    buttons: false
                                });

                                // Recargar la lista de compartidos en el modal
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

                                swal({
                                    title: "Error",
                                    text: message,
                                    icon: "error"
                                });
                            }
                        });
                    }
                });
            });

            function loadCurrentShares(passwordId) {
                const listContainer = $('#currentSharesList');
                listContainer.html('<p class="text-center text-muted small"><i class="fas fa-sync fa-spin"></i> Cargando...</p>');

                // Asegúrate de crear esta ruta GET en Laravel para obtener los usuarios que ya tienen acceso
                $.ajax({
                    url: `/password-vault/${passwordId}/shares`, // Ejemplo de ruta GET
                    method: 'GET',
                    success: function (response) {
                        if (response.shares && response.shares.length > 0) {
                            let html = '<ul>';
                            response.shares.forEach(function (share) {
                                const canEdit = share.can_edit ? ' (Puede Editar)' : '';
                                // Nota: Aquí necesitarías el nombre/email del usuario compartido, no solo el ID
                                html += `<li>${share.shared_with_name}${canEdit} <button type="button" class="btn btn-sm btn-danger ml-2 remove-share" data-share-id="${share.id}">Revocar</button></li>`;
                            });
                            html += '</ul>';
                            listContainer.html(html);
                        } else {
                            listContainer.html('<p class="text-muted small mb-0">Esta contraseña no está compartida con nadie.</p>');
                        }
                    },
                    error: function () {
                        listContainer.html('<p class="text-danger small mb-0">Error al cargar compartidos.</p>');
                    }
                });
            }
        });
    </script>
@endpush
