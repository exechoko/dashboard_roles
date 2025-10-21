@extends('layouts.app')

@section('title', isset($passwordVault) ? 'Editar Contraseña' : 'Nueva Contraseña')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ isset($passwordVault) ? 'Editar Contraseña' : 'Nueva Contraseña' }}</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-8 col-lg-8">
                    <div class="card">
                        <form action="{{ isset($passwordVault) ? route('password-vault.update', $passwordVault) : route('password-vault.store') }}"
                              method="POST">
                            @csrf
                            @if(isset($passwordVault))
                                @method('PUT')
                            @endif

                            <div class="card-header">
                                <h4>Información de la Contraseña</h4>
                                {{-- Mostrar si es una contraseña compartida --}}
                                @if(isset($passwordVault) && $passwordVault->user_id !== Auth::id())
                                    <div class="badge badge-info ml-auto">
                                        <i class="fas fa-share-alt"></i> Contraseña Compartida (Solo lectura/edición según permisos)
                                    </div>
                                @endif
                            </div>

                            <div class="card-body">
                                {{-- Nombre del Sistema --}}
                                <div class="form-group">
                                    <label>Nombre del Sistema <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="system_name"
                                           class="form-control @error('system_name') is-invalid @enderror"
                                           value="{{ old('system_name', $passwordVault->system_name ?? '') }}"
                                           placeholder="Ej: Gmail Empresa, VPN Oficina, etc."
                                           required>
                                    @error('system_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Tipo de Sistema --}}
                                <div class="form-group">
                                    <label>Tipo de Sistema <span class="text-danger">*</span></label>
                                    <select name="system_type"
                                            id="system_type"
                                            class="form-control @error('system_type') is-invalid @enderror"
                                            required>
                                        <option value="">Selecciona un tipo...</option>
                                        @foreach($systemTypes as $key => $type)
                                            <option value="{{ $key }}"
                                                    {{ old('system_type', $passwordVault->system_type ?? '') == $key ? 'selected' : '' }}>
                                                {{ $type['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('system_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Campos específicos de VPN (ocultos por defecto) --}}
                                <div id="vpn_fields" style="display: none;">
                                    {{-- Tipo de VPN --}}
                                    <div class="form-group">
                                        <label>Tipo de VPN</label>
                                        <select name="vpn_type"
                                                id="vpn_type"
                                                class="form-control @error('vpn_type') is-invalid @enderror">
                                            <option value="">Selecciona un tipo de VPN...</option>
                                            @foreach($vpnTypes as $key => $label)
                                                <option value="{{ $key }}"
                                                        {{ old('vpn_type', $passwordVault->vpn_type ?? '') == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('vpn_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Host / Dirección del Servidor VPN --}}
                                    <div class="form-group">
                                        <label>Host / Dirección del Servidor VPN</label>
                                        <input type="text" name="vpn_host" class="form-control @error('vpn_host') is-invalid @enderror"
                                            value="{{ old('vpn_host', $passwordVault->vpn_host ?? '') }}" placeholder="Ej: vpn.empresa.com o 190.12.34.56">
                                        @error('vpn_host')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Clave Previamente Compartida --}}
                                    <div class="form-group">
                                        <label>Clave Previamente Compartida (PSK)</label>
                                        <div class="input-group">
                                            <input type="password"
                                                   name="vpn_preshared_key"
                                                   id="vpn_preshared_key"
                                                   class="form-control @error('vpn_preshared_key') is-invalid @enderror"
                                                   placeholder="{{ isset($passwordVault) ? 'Dejar en blanco para mantener la actual' : 'Ingresa la PSK si aplica' }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="togglePSK">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" type="button" id="generatePSK">
                                                    <i class="fas fa-random"></i> Generar
                                                </button>
                                            </div>
                                        </div>
                                        @error('vpn_preshared_key')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @if(isset($passwordVault))
                                            <small class="form-text text-muted">Dejar vacío para mantener la PSK actual</small>
                                        @endif
                                    </div>
                                </div>

                                {{-- URL --}}
                                <div class="form-group">
                                    <label>URL / Dirección</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="fas fa-link"></i>
                                            </div>
                                        </div>
                                        <input type="text"
                                               name="url"
                                               class="form-control @error('url') is-invalid @enderror"
                                               value="{{ old('url', $passwordVault->url ?? '') }}"
                                               placeholder="https://ejemplo.com">
                                    </div>
                                    @error('url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Username --}}
                                <div class="form-group">
                                    <label>Usuario <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <input type="text"
                                               name="username"
                                               class="form-control @error('username') is-invalid @enderror"
                                               value="{{ old('username', $passwordVault->username ?? '') }}"
                                               placeholder="usuario123"
                                               required>
                                    </div>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Contraseña --}}
                                <div class="form-group">
                                    <label>Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password"
                                               name="password"
                                               id="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="{{ isset($passwordVault) ? 'Dejar en blanco para mantener la actual' : 'Ingresa una contraseña segura' }}"
                                               {{ isset($passwordVault) ? '' : 'required' }}>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" type="button" id="generatePassword">
                                                <i class="fas fa-random"></i> Generar
                                            </button>
                                        </div>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @if(isset($passwordVault))
                                        <small class="form-text text-muted">Dejar vacío para mantener la contraseña actual</small>
                                    @endif
                                </div>

                                {{-- Medidor de seguridad de contraseña --}}
                                <div class="form-group">
                                    <label>Seguridad de la Contraseña</label>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar"
                                             style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                            <span id="strengthText"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Notas --}}
                                <div class="form-group">
                                    <label>Notas Adicionales</label>
                                    <textarea name="notes"
                                              class="form-control @error('notes') is-invalid @enderror"
                                              rows="4"
                                              placeholder="Información adicional, instrucciones de acceso, etc.">{{ old('notes', $passwordVault->notes ?? '') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Favorito --}}
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="favorite"
                                               name="favorite"
                                               value="1"
                                               {{ old('favorite', $passwordVault->favorite ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="favorite">
                                            <i class="fas fa-star text-warning"></i> Marcar como favorito
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-right">
                                <a href="{{ route('password-vault.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                @if(isset($passwordVault))
                                    @can('editar-clave')
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar
                                        </button>
                                    @endcan
                                @else
                                    @can('crear-clave')
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Panel de ayuda --}}
                <div class="col-12 col-md-4 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-info-circle"></i> Consejos de Seguridad</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check text-success"></i>
                                    <strong>Usa contraseñas únicas</strong>
                                    <p class="text-muted mb-0 small">No reutilices contraseñas entre sistemas</p>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check text-success"></i>
                                    <strong>Longitud mínima de 12 caracteres</strong>
                                    <p class="text-muted mb-0 small">Combina letras, números y símbolos</p>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check text-success"></i>
                                    <strong>Evita información personal</strong>
                                    <p class="text-muted mb-0 small">No uses nombres, fechas o datos obvios</p>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check text-success"></i>
                                    <strong>Actualiza regularmente</strong>
                                    <p class="text-muted mb-0 small">Cambia contraseñas importantes cada 3-6 meses</p>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-shield-alt"></i> Seguridad</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-0">
                                <i class="fas fa-lock text-success"></i>
                                Todas las contraseñas se almacenan encriptadas usando
                                encriptación AES-256. Solo tú puedes acceder a tus contraseñas.
                            </p>
                        </div>
                    </div>

                    {{-- Información de contraseña compartida --}}
                    @if(isset($passwordVault) && $passwordVault->user_id !== Auth::id())
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h4><i class="fas fa-share-alt"></i> Contraseña Compartida</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Esta contraseña ha sido compartida contigo.
                                </p>
                                <p class="text-muted small mb-0">
                                    <strong>Propietario:</strong> {{ $passwordVault->owner->name ?? 'Usuario' }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Mostrar/ocultar campos VPN según el tipo de sistema seleccionado
        function toggleVpnFields() {
            if ($('#system_type').val() === 'vpn') {
                $('#vpn_fields').slideDown();
            } else {
                $('#vpn_fields').slideUp();
                $('#vpn_type').val('');
                $('#vpn_host').val('');
                $('#vpn_preshared_key').val('');
            }
        }

        // Ejecutar al cargar la página
        toggleVpnFields();

        // Ejecutar al cambiar el tipo de sistema
        $('#system_type').change(function() {
            toggleVpnFields();
        });

        // Toggle mostrar/ocultar contraseña
        $('#togglePassword').click(function() {
            let input = $('#password');
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
        $('#togglePSK').click(function() {
            let input = $('#vpn_preshared_key');
            let icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Generar contraseña segura
        $('#generatePassword').click(function() {
            $.ajax({
                url: '{{ route("password-vault.generate") }}',
                method: 'GET',
                success: function(response) {
                    $('#password').val(response.password).attr('type', 'text');
                    $('#togglePassword i').removeClass('fa-eye').addClass('fa-eye-slash');
                    checkPasswordStrength(response.password);

                    iziToast.success({
                        title: 'Generada',
                        message: 'Contraseña segura generada',
                        position: 'topRight'
                    });
                }
            });
        });

        // Generar PSK segura
        $('#generatePSK').click(function() {
            $.ajax({
                url: '{{ route("password-vault.generate") }}',
                method: 'GET',
                success: function(response) {
                    $('#vpn_preshared_key').val(response.password).attr('type', 'text');
                    $('#togglePSK i').removeClass('fa-eye').addClass('fa-eye-slash');

                    iziToast.success({
                        title: 'Generada',
                        message: 'Clave PSK segura generada',
                        position: 'topRight'
                    });
                }
            });
        });

        // Verificar seguridad de la contraseña
        $('#password').on('input', function() {
            checkPasswordStrength($(this).val());
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            let text = '';
            let color = '';

            if (password.length === 0) {
                $('#passwordStrength').css('width', '0%').removeClass().addClass('progress-bar');
                $('#strengthText').text('');
                return;
            }

            // Longitud
            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 20;
            if (password.length >= 16) strength += 10;

            // Minúsculas
            if (/[a-z]/.test(password)) strength += 10;

            // Mayúsculas
            if (/[A-Z]/.test(password)) strength += 10;

            // Números
            if (/[0-9]/.test(password)) strength += 15;

            // Caracteres especiales
            if (/[^A-Za-z0-9]/.test(password)) strength += 15;

            // Determinar nivel
            if (strength < 30) {
                text = 'Muy Débil';
                color = 'bg-danger';
            } else if (strength < 50) {
                text = 'Débil';
                color = 'bg-warning';
            } else if (strength < 70) {
                text = 'Aceptable';
                color = 'bg-info';
            } else if (strength < 90) {
                text = 'Fuerte';
                color = 'bg-success';
            } else {
                text = 'Muy Fuerte';
                color = 'bg-success';
            }

            $('#passwordStrength')
                .css('width', strength + '%')
                .removeClass()
                .addClass('progress-bar ' + color)
                .attr('aria-valuenow', strength);

            $('#strengthText').text(text);
        }

        // Verificar contraseña inicial si existe
        @if(!isset($passwordVault))
            checkPasswordStrength($('#password').val());
        @endif
    });
</script>
@endpush
