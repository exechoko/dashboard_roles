@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Alta de usuarios</h3>
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

                            {!! Form::open(['route' => 'usuarios.store', 'method' => 'POST']) !!}
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group">
                                        <label for="name">Nombre <span class="text-danger">*</span></label>
                                        {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el nombre']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group">
                                        <label for="apellido">Apellido <span class="text-danger">*</span></label>
                                        {!! Form::text('apellido', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el apellido']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group">
                                        <label for="lp">L.P. <span class="text-danger">*</span></label>
                                        {!! Form::number('lp', null, ['class' => 'form-control', 'placeholder' => 'Número de LP']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group">
                                        <label for="dni">D.N.I. <span class="text-danger">*</span></label>
                                        {!! Form::number('dni', null, ['class' => 'form-control', 'placeholder' => 'Número de DNI']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-4">
                                    <div class="form-group">
                                        <label for="email">E-mail <span class="text-danger">*</span></label>
                                        {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => 'ejemplo@correo.com']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-4">
                                    <div class="form-group">
                                        <label for="password">Password <span class="text-danger">*</span></label>
                                        {!! Form::password('password', ['class' => 'form-control', 'placeholder' => 'Ingrese la contraseña']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-4">
                                    <div class="form-group">
                                        <label for="confirm-password">Confirmar Password <span class="text-danger">*</span></label>
                                        {!! Form::password('confirm-password', ['class' => 'form-control', 'placeholder' => 'Confirme la contraseña']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="roles">Roles <span class="text-danger">*</span></label>
                                        <select name="roles[]" id="roles" class="form-control select2" style="width: 100%;">
                                            <option value="">Seleccione un rol</option>
                                            @foreach($roles as $role)
                                                @php
                                                    $roleModel = \Spatie\Permission\Models\Role::where('name', $role)->first();
                                                    $color = $roleModel ? $roleModel->color : '#6c757d';
                                                @endphp
                                                <option value="{{ $role }}"
                                                    {{ old('roles.0') == $role ? 'selected' : '' }}
                                                    data-color="{{ $color }}">
                                                    {{ $role }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Crear Usuario
                                        </button>
                                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            {!! Form::close() !!}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

    <style>
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.125rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .select2-container--bootstrap4 .select2-results__option {
            padding: 8px 12px;
        }

        .role-option {
            display: flex;
            align-items: center;
        }

        .role-color-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            padding-left: 12px;
            padding-right: 20px;
        }
    </style>
@endpush

@push('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2
            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });

            $('#roles').select2({
                theme: 'bootstrap4',
                placeholder: "Seleccione el rol",
                allowClear: false,
                width: '100%',
                templateResult: function(option) {
                    if (!option.id) {
                        return option.text;
                    }

                    var color = $(option.element).data('color') || '#6c757d';

                    var $result = $(
                        '<div class="role-option">' +
                            '<div class="role-color-indicator" style="background-color: ' + color + ';"></div>' +
                            '<span>' + option.text + '</span>' +
                        '</div>'
                    );

                    return $result;
                },
                templateSelection: function(option) {
                    if (!option.id) {
                        return option.text;
                    }

                    var color = $(option.element).data('color') || '#6c757d';

                    var $selection = $(
                        '<span style="color: ' + color + '; font-weight: 600;">' +
                            '<i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>' +
                            option.text +
                        '</span>'
                    );

                    return $selection;
                }
            });

            // Validación de formulario
            $('form').on('submit', function(e) {
                var rolesSelected = $('#roles').val();

                if (!rolesSelected) {
                    e.preventDefault();
                    alert('Debe seleccionar un rol para el usuario.');
                    $('#roles').focus();
                    return false;
                }

                var password = $('input[name="password"]').val();
                var confirmPassword = $('input[name="confirm-password"]').val();

                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden.');
                    $('input[name="confirm-password"]').focus();
                    return false;
                }

                if (password === '' || confirmPassword === '') {
                    e.preventDefault();
                    alert('La contraseña es obligatoria.');
                    $('input[name="password"]').focus();
                    return false;
                }
            });

            // Validación en tiempo real de contraseñas
            $('input[name="confirm-password"]').on('keyup', function() {
                var password = $('input[name="password"]').val();
                var confirmPassword = $(this).val();

                if (password !== '' && confirmPassword !== '') {
                    if (password === confirmPassword) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            // Limpiar validación de confirm-password cuando se borra password
            $('input[name="password"]').on('keyup', function() {
                var password = $(this).val();
                var confirmPasswordField = $('input[name="confirm-password"]');

                if (password === '') {
                    confirmPasswordField.removeClass('is-valid is-invalid');
                }
            });
        });
    </script>
@endpush
