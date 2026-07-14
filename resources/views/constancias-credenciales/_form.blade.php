<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-shield"></i> Datos del Receptor</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="usuario_busqueda">Buscar Usuario del Sistema</label>
                    <select class="form-control select2-usuario" id="usuario_busqueda" style="width: 100%;">
                        <option value="">Buscar por nombre, apellido, DNI o email...</option>
                    </select>
                    <input type="hidden" name="user_id" id="user_id" value="{{ old('user_id', isset($constancia) ? $constancia->user_id : '') }}">
                    <small class="text-muted">Seleccione un usuario para autocompletar los datos, o complete manualmente.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="nombre_apellido">Nombre y Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nombre_apellido') is-invalid @enderror" id="nombre_apellido" name="nombre_apellido" value="{{ old('nombre_apellido', isset($constancia) ? $constancia->nombre_apellido : '') }}" maxlength="255" required>
                    @error('nombre_apellido')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="dni">DNI <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('dni') is-invalid @enderror" id="dni" name="dni" value="{{ old('dni', isset($constancia) ? $constancia->dni : '') }}" maxlength="20" required>
                    @error('dni')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror" id="fecha_entrega" name="fecha_entrega" value="{{ old('fecha_entrega', isset($constancia) ? $constancia->fecha_entrega->format('Y-m-d') : date('Y-m-d')) }}" required>
                    @error('fecha_entrega')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-key"></i> Credenciales de Acceso</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="email">Correo Electrónico Registrado <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', isset($constancia) ? $constancia->email : '') }}" maxlength="255" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="contrasena">Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" value="{{ old('contrasena', isset($constancia) ? $constancia->contrasena : '') }}" maxlength="255" {{ isset($constancia) ? '' : 'required' }}>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="contrasena">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    @error('contrasena')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @if(isset($constancia))
                        <small class="text-muted">Dejar en blanco para mantener la contraseña actual.</small>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="lugar">Lugar</label>
                    <input type="text" class="form-control @error('lugar') is-invalid @enderror" id="lugar" name="lugar" value="{{ old('lugar', isset($constancia) ? $constancia->lugar : 'Paraná, Entre Ríos') }}" maxlength="255">
                    @error('lugar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-info-circle"></i> Información adicional</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="3">{{ old('observaciones', isset($constancia) ? $constancia->observaciones : '') }}</textarea>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('.select2-usuario').select2({
                placeholder: 'Buscar por nombre, apellido, DNI o email...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("constancias-credenciales.buscar-usuarios") }}',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { term: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });

            $('.select2-usuario').on('select2:select', function (e) {
                var data = e.params.data;
                $('#user_id').val(data.id);
                $('#nombre_apellido').val(data.nombre);
                $('#dni').val(data.dni);
                $('#email').val(data.email);
            });

            $('.select2-usuario').on('select2:clear', function () {
                $('#user_id').val('');
                $('#nombre_apellido').val('');
                $('#dni').val('');
                $('#email').val('');
            });

            $('.toggle-password').on('click', function () {
                var target = $('#' + $(this).data('target'));
                var icon = $(this).find('i');
                if (target.attr('type') === 'password') {
                    target.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    target.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
@endpush
