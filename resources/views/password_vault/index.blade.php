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
    /* Ajustar Select2 para el modal */
    .select2-users + .select2-container {
        min-height: 120px;
    }

    .select2-container--bootstrap .select2-selection--multiple {
        min-height: 120px !important;
    }

    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice {
        background-color: #6777ef !important;
        border-color: #6777ef !important;
        color: white !important;
        padding: 5px 10px !important;
        font-size: 14px !important;
        margin: 3px !important;
    }

    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice__remove {
        color: white !important;
        margin-right: 5px !important;
        font-weight: bold;
    }

    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ff6b6b !important;
    }

    /* Z-index para modales */
    .select2-container--open {
        z-index: 9999;
    }

    .select2-dropdown {
        z-index: 10000;
    }

    /* Estilo para usuarios compartidos */
    .shared-user-card {
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    .shared-user-card:hover {
        background-color: #f8f9fa;
        border-left-color: #6777ef;
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">
                    <i class="fas fa-share-alt"></i> Compartir Contraseña
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="shareForm" class="ajax-share-form" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="password_vault_id" id="share_password_vault_id">

                    <div class="form-group">
                        <label for="shared_with_users">
                            <i class="fas fa-users"></i> Seleccionar Usuarios para Compartir
                        </label>
                        <select name="shared_with_users[]" id="shared_with_users" class="form-control select2" multiple="multiple" style="width: 100%;">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" data-email="{{ $user->email }}">
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Puedes buscar por nombre o email. Selecciona múltiples usuarios.
                        </small>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="hidden" name="can_edit" value="0">
                        <input type="checkbox" class="custom-control-input" id="can_edit" name="can_edit" value="1">
                        <label class="custom-control-label" for="can_edit">
                            <i class="fas fa-edit"></i> Permitir edición a los usuarios seleccionados
                        </label>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-user-friends"></i> <strong>Usuarios con acceso actual:</strong>
                        </p>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshSharesList">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div id="currentSharesList">
                        <p class="text-center text-muted small">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="submit" class="btn btn-primary" id="shareSubmitBtn">
                        <i class="fas fa-share-alt"></i> Compartir con Seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            let select2UsersInstance = null;

            // ========== FUNCIONALIDAD DE CONTRASEÑAS ==========

            // Toggle para mostrar/ocultar contraseña
            $('.toggle-password').on('click', function () {
                const button = $(this);
                const input = button.closest('.input-group').find('.password-field');
                const icon = button.find('i');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Copiar contraseña al portapapeles
            $('.copy-password').on('click', function () {
                const button = $(this);
                const password = button.data('password');

                // Crear elemento temporal para copiar
                const tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(password).select();
                document.execCommand('copy');
                tempInput.remove();

                // Feedback visual
                const originalIcon = button.find('i').attr('class');
                button.find('i').removeClass().addClass('fas fa-check text-success');

                iziToast.success({
                    title: 'Copiado',
                    message: 'Contraseña copiada al portapapeles',
                    position: 'topRight',
                    timeout: 2000
                });

                // Restaurar icono después de 2 segundos
                setTimeout(() => {
                    button.find('i').removeClass().addClass(originalIcon);
                }, 2000);
            });

            // Toggle de favoritos
            $('.toggle-favorite').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const button = $(this);
                const passwordId = button.data('id');
                const icon = button.find('i');

                $.ajax({
                    url: `/password-vault/${passwordId}/favorite`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.favorite) {
                            icon.removeClass('text-muted').addClass('favorite-star');
                            iziToast.success({
                                title: 'Favorito',
                                message: 'Añadido a favoritos',
                                position: 'topRight',
                                timeout: 2000
                            });
                        } else {
                            icon.removeClass('favorite-star').addClass('text-muted');
                            iziToast.info({
                                title: 'Favorito',
                                message: 'Eliminado de favoritos',
                                position: 'topRight',
                                timeout: 2000
                            });
                        }
                    },
                    error: function () {
                        iziToast.error({
                            title: 'Error',
                            message: 'No se pudo actualizar el favorito',
                            position: 'topRight'
                        });
                    }
                });
            });

            // Confirmación para eliminar contraseña
            $('.delete-form').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);

                Swal.fire({
                    title: '¿Eliminar contraseña?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit();
                    }
                });
            });

            // ========== FUNCIONALIDAD DE COMPARTIR ==========

            // Inicializar Select2 cuando se abre el modal
            $('#shareModal').on('shown.bs.modal', function () {
                if (!select2UsersInstance) {
                    select2UsersInstance = $('.select2-users').select2({
                        theme: 'bootstrap',
                        language: 'es',
                        placeholder: 'Seleccione uno o varios usuarios...',
                        allowClear: true,
                        width: '100%',
                        closeOnSelect: false,
                        dropdownParent: $('#shareModal'), // Importante para modales
                        templateResult: formatUserOption,
                        templateSelection: formatUserSelection
                    });

                    // Manejo del foco (igual que en tu proyecto)
                    $('.select2-users').on('select2:open', function (e) {
                        setTimeout(() => {
                            const $dropdown = $('.select2-container--open');
                            const searchField = $dropdown.find('.select2-search__field');
                            if (searchField.length > 0) {
                                searchField[0].focus();
                            }
                        }, 50);
                    });
                }
            });

            // Formato personalizado para las opciones del dropdown
            function formatUserOption(user) {
                if (!user.id) {
                    return user.text;
                }

                var $user = $(
                    '<div class="d-flex align-items-center">' +
                    '<i class="fas fa-user-circle fa-lg mr-2 text-primary"></i>' +
                    '<div>' +
                    '<div><strong>' + user.text.split('(')[0].trim() + '</strong></div>' +
                    '<small class="text-muted">' + $(user.element).data('email') + '</small>' +
                    '</div>' +
                    '</div>'
                );
                return $user;
            }

            // Formato para los items seleccionados (chips)
            function formatUserSelection(user) {
                if (!user.id) {
                    return user.text;
                }
                return user.text.split('(')[0].trim();
            }

            // Destruir Select2 cuando se cierra el modal
            $('#shareModal').on('hidden.bs.modal', function () {
                if (select2UsersInstance) {
                    $('.select2-users').select2('destroy');
                    select2UsersInstance = null;
                }
                $('#shareForm').trigger('reset');
            });

            // Envío del formulario de compartir
            $('#shareForm').submit(function (e) {
                e.preventDefault();
                const form = $(this);
                const passwordId = $('#share_password_vault_id').val();
                const selectedUsers = $('#shared_with_users').val();
                const canEdit = $('#can_edit').is(':checked');

                if (!selectedUsers || selectedUsers.length === 0) {
                    iziToast.warning({
                        title: 'Atención',
                        message: 'Debes seleccionar al menos un usuario.',
                        position: 'topRight'
                    });
                    return;
                }

                // Deshabilitar el botón de enviar
                const submitBtn = $('#shareSubmitBtn');
                const originalBtnText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Compartiendo...');

                // Enviar solicitudes para cada usuario seleccionado
                const promises = selectedUsers.map(userId => {
                    return $.ajax({
                        url: `/password-vault/${passwordId}/share`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            shared_with_user_id: userId,
                            can_edit: canEdit ? 1 : 0
                        }
                    });
                });

                // Esperar a que todas las solicitudes terminen
                Promise.allSettled(promises).then(results => {
                    const successful = results.filter(r => r.status === 'fulfilled').length;
                    const failed = results.filter(r => r.status === 'rejected').length;
                    const alreadyShared = results.filter(r =>
                        r.status === 'rejected' &&
                        r.reason.status === 422
                    ).length;

                    // Resetear el botón
                    submitBtn.prop('disabled', false).html(originalBtnText);

                    // Mostrar mensajes según resultados
                    if (successful > 0) {
                        iziToast.success({
                            title: '¡Éxito!',
                            message: `Contraseña compartida con ${successful} usuario(s) exitosamente.`,
                            position: 'topRight',
                            timeout: 3000
                        });
                    }

                    if (alreadyShared > 0) {
                        iziToast.info({
                            title: 'Información',
                            message: `${alreadyShared} usuario(s) ya tenían acceso a esta contraseña.`,
                            position: 'topRight',
                            timeout: 3000
                        });
                    }

                    if (failed > alreadyShared) {
                        iziToast.error({
                            title: 'Error',
                            message: `No se pudo compartir con ${failed - alreadyShared} usuario(s).`,
                            position: 'topRight',
                            timeout: 3000
                        });
                    }

                    // Limpiar selección y actualizar lista
                    if (select2UsersInstance) {
                        $('.select2-users').val(null).trigger('change');
                    }
                    loadCurrentShares(passwordId);

                    // Si todo fue exitoso, cerrar modal y recargar
                    if (failed === 0 || failed === alreadyShared) {
                        setTimeout(() => {
                            $('#shareModal').modal('hide');
                            window.location.reload();
                        }, 2000);
                    }
                });
            });

            // Revocar acceso
            $(document).on('click', '.remove-share', function () {
                const shareId = $(this).data('share-id');
                const userName = $(this).data('user-name');

                Swal.fire({
                    title: '¿Revocar acceso?',
                    html: `El usuario <strong>${userName}</strong> perderá inmediatamente el acceso a esta contraseña.`,
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

            // Botón para actualizar la lista de compartidos
            $('#refreshSharesList').click(function () {
                const passwordId = $('#share_password_vault_id').val();
                if (passwordId) {
                    loadCurrentShares(passwordId);
                    iziToast.info({
                        title: 'Actualizando',
                        message: 'Recargando lista de usuarios...',
                        position: 'topRight',
                        timeout: 1500
                    });
                }
            });

            // Configurar modal de compartir
            $('.share-password-btn').click(function () {
                const passwordId = $(this).data('id');
                $('#share_password_vault_id').val(passwordId);
                loadCurrentShares(passwordId);
            });

            // Función para cargar la lista de usuarios compartidos
            function loadCurrentShares(passwordId) {
                const listContainer = $('#currentSharesList');
                listContainer.html('<p class="text-center text-muted small"><i class="fas fa-sync fa-spin"></i> Cargando...</p>');

                $.ajax({
                    url: `/password-vault/${passwordId}/shares`,
                    method: 'GET',
                    success: function (response) {
                        if (response.shares && response.shares.length > 0) {
                            let html = '<div class="list-group list-group-flush">';
                            response.shares.forEach(function (share) {
                                const canEditBadge = share.can_edit ?
                                    '<span class="badge badge-success ml-2"><i class="fas fa-edit"></i> Puede Editar</span>' :
                                    '<span class="badge badge-info ml-2"><i class="fas fa-eye"></i> Solo Ver</span>';

                                html += `
                                <div class="list-group-item shared-user-card px-2 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div class="mr-3">
                                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <strong>${share.shared_with_name}</strong>
                                                ${canEditBadge}
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope"></i> ${share.shared_with_email}
                                                </small>
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-danger remove-share"
                                                data-share-id="${share.id}"
                                                data-user-name="${share.shared_with_name}"
                                                title="Revocar acceso">
                                            <i class="fas fa-times"></i> Revocar
                                        </button>
                                    </div>
                                </div>
                            `;
                            });
                            html += '</div>';
                            listContainer.html(html);
                        } else {
                            listContainer.html(`
                            <div class="alert alert-light text-center mb-0">
                                <i class="fas fa-info-circle text-muted"></i>
                                <p class="mb-0 mt-2">Esta contraseña no está compartida con nadie.</p>
                            </div>
                        `);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 403) {
                            listContainer.html(`
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                No tienes permiso para ver los compartidos.
                            </div>
                        `);
                        } else {
                            listContainer.html(`
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle"></i>
                                Error al cargar compartidos.
                            </div>
                        `);
                        }
                    }
                });
            }
        });
    </script>
@endpush
