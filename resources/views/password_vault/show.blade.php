@extends('layouts.app')

@section('title', 'Detalle de Contraseña')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-lock"></i> Detalle de Contraseña</h1>
        <div class="section-header-button">
            <a href="{{ route('password-vault.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('password-vault.edit', $passwordVault) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            <i class="{{ $passwordVault->getSystemTypes()[$passwordVault->system_type]['icon'] }} mr-2"></i>
                            {{ $passwordVault->system_name }}
                        </h4>
                        <div class="card-header-action">
                            @if($passwordVault->favorite)
                                <span class="badge badge-warning">
                                    <i class="fas fa-star"></i> Favorito
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Tipo de Sistema --}}
                        <div class="form-group">
                            <label class="text-muted mb-1">Tipo de Sistema</label>
                            <p class="mb-0">
                                <strong>{{ $passwordVault->getSystemTypes()[$passwordVault->system_type]['label'] }}</strong>
                            </p>
                        </div>

                        {{-- Información específica de VPN --}}
                        @if($passwordVault->system_type === 'vpn')
                            <hr>
                            <h6 class="text-primary mb-3"><i class="fas fa-shield-alt"></i> Información VPN</h6>

                            @if($passwordVault->vpn_type)
                                <div class="form-group">
                                    <label class="text-muted mb-1">Tipo de VPN</label>
                                    <p class="mb-0">
                                        <strong>{{ $passwordVault->getVpnTypes()[$passwordVault->vpn_type] ?? $passwordVault->vpn_type }}</strong>
                                    </p>
                                </div>
                            @endif

                            @if($passwordVault->vpn_preshared_key)
                                <div class="form-group">
                                    <label class="text-muted mb-1">Clave Previamente Compartida (PSK)</label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control psk-field"
                                               value="{{ $passwordVault->vpn_preshared_key }}"
                                               readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-psk" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary copy-psk"
                                                    type="button"
                                                    data-psk="{{ $passwordVault->vpn_preshared_key }}">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <hr>
                        @endif

                        {{-- URL --}}
                        @if($passwordVault->url)
                            <div class="form-group">
                                <label class="text-muted mb-1">URL / Dirección</label>
                                <p class="mb-0">
                                    <a href="{{ $passwordVault->url }}" target="_blank">
                                        {{ $passwordVault->url }}
                                        <i class="fas fa-external-link-alt ml-1"></i>
                                    </a>
                                </p>
                            </div>
                        @endif

                        {{-- Usuario --}}
                        <div class="form-group">
                            <label class="text-muted mb-1">Usuario</label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       value="{{ $passwordVault->username }}"
                                       readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary copy-username"
                                            type="button"
                                            data-username="{{ $passwordVault->username }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Contraseña --}}
                        <div class="form-group">
                            <label class="text-muted mb-1">Contraseña</label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control password-field"
                                       value="{{ $passwordVault->password }}"
                                       readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary copy-password"
                                            type="button"
                                            data-password="{{ $passwordVault->password }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Notas --}}
                        @if($passwordVault->notes)
                            <div class="form-group">
                                <label class="text-muted mb-1">Notas Adicionales</label>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        {{ $passwordVault->notes }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Panel lateral --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> Información</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Creado</small>
                            <strong>{{ $passwordVault->created_at->format('d/m/Y H:i') }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Última modificación</small>
                            <strong>{{ $passwordVault->updated_at->format('d/m/Y H:i') }}</strong>
                        </div>

                        @if($passwordVault->last_accessed_at)
                            <div class="mb-3">
                                <small class="text-muted d-block">Último acceso</small>
                                <strong>{{ $passwordVault->last_accessed_at->diffForHumans() }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-shield-alt"></i> Seguridad</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-lock text-success"></i>
                            Esta contraseña está encriptada con AES-256.
                            Se registra cada acceso para fines de auditoría.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-cog"></i> Acciones</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('password-vault.destroy', $passwordVault) }}"
                              method="POST"
                              class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-trash"></i> Eliminar Contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle mostrar/ocultar contraseña
        $('.toggle-password').click(function() {
            let input = $('.password-field');
            let icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Toggle mostrar/ocultar PSK
        $('.toggle-psk').click(function() {
            let input = $('.psk-field');
            let icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Copiar usuario
        $('.copy-username').click(function() {
            let username = $(this).data('username');
            navigator.clipboard.writeText(username).then(function() {
                iziToast.success({
                    title: 'Copiado',
                    message: 'Usuario copiado al portapapeles',
                    position: 'topRight'
                });
            });
        });

        // Copiar contraseña
        $('.copy-password').click(function() {
            let password = $(this).data('password');
            navigator.clipboard.writeText(password).then(function() {
                iziToast.success({
                    title: 'Copiado',
                    message: 'Contraseña copiada al portapapeles',
                    position: 'topRight'
                });
            });
        });

        // Copiar PSK
        $('.copy-psk').click(function() {
            let psk = $(this).data('psk');
            navigator.clipboard.writeText(psk).then(function() {
                iziToast.success({
                    title: 'Copiado',
                    message: 'PSK copiada al portapapeles',
                    position: 'topRight'
                });
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
    });
</script>
@endpush
