@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Crear Dependencia</h3>
            <div class="alert alert-info ml-3">
                Nota: Sólo se pueden crear Secciones y Destacamentos.
            </div>
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

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('dependencias.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Dirección</label>
                                            <select name="direccion" id="direccion" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Dirección</option>
                                                @foreach ($direcciones as $direccion)
                                                    <option value="{{ $direccion->id }}" {{ old('direccion') == $direccion->id ? 'selected' : '' }}>
                                                        {{ $direccion->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="">Departamental</label>
                                            <select name="departamental" id="departamental" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Departamental</option>
                                                @foreach ($departamentales as $departamental)
                                                    <option value="{{ $departamental->id }}" {{ old('departamental') == $departamental->id ? 'selected' : '' }}>
                                                        {{ $departamental->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="">División</label>
                                            <select class="form-control select2" name="division" id="division">
                                                <option value="">Seleccionar División</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="">Comisaría</label>
                                            <select class="form-control select2" name="comisaria" id="comisaria">
                                                <option value="">Seleccionar Comisaría</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre de la dependencia</label>
                                            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
                                            <small class="form-text text-muted">
                                                El prefijo "Sección" o "Destacamento" se agregará automáticamente según el tipo seleccionado.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Tipo de dependencia</label>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="tipoDependencia"
                                                    id="esDestacamento" value="destacamento" {{ old('tipoDependencia') == 'destacamento' ? 'checked' : '' }}>
                                                <label class="form-check-label font-weight-bold text-dark" for="esDestacamento">
                                                    DESTACAMENTO
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="tipoDependencia"
                                                    id="esSeccion" value="seccion" {{ old('tipoDependencia', 'seccion') == 'seccion' ? 'checked' : '' }}>
                                                <label class="form-check-label font-weight-bold text-dark" for="esSeccion">
                                                    SECCIÓN
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono</label>
                                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-8">
                                        <div class="form-group">
                                            <label for="">Ubicación</label>
                                            <input type="text" name="ubicacion" class="form-control" value="{{ old('ubicacion') }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ old('observaciones') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                        <a href="{{ route('dependencias.index') }}" class="btn btn-secondary">Cancelar</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });

            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });

            // Al cambiar la dirección, cargar departamentales y divisiones
            $('#direccion').on('change', function() {
                var direccionId = this.value;

                // Limpiar dependencias inferiores
                $('#departamental').html('<option value="">Seleccionar Departamental</option>');
                $('#division').html('<option value="">Seleccionar División</option>');
                $('#comisaria').html('<option value="">Seleccionar Comisaría</option>');

                if (direccionId) {
                    // Cargar departamentales de esta dirección
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        url: '{{ route('getDepartamentales') }}?direccion_id=' + direccionId,
                        type: 'get',
                        success: function(res) {
                            $.each(res, function(key, value) {
                                $('#departamental').append('<option value="' + value.id + '">' + value.nombre + '</option>');
                            });
                        }
                    });

                    // Cargar divisiones directas de esta dirección
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        url: '{{ route('getDivisiones') }}?direccion_id=' + direccionId,
                        type: 'get',
                        success: function(res) {
                            $.each(res, function(key, value) {
                                $('#division').append('<option value="' + value.id + '">' + value.nombre + '</option>');
                            });
                        }
                    });
                }
            });

            // Al cambiar la departamental, cargar divisiones y comisarías
            $('#departamental').on('change', function() {
                var departamentalId = this.value;

                // Limpiar dependencias inferiores
                $('#division').html('<option value="">Seleccionar División</option>');
                $('#comisaria').html('<option value="">Seleccionar Comisaría</option>');

                if (departamentalId) {
                    // Cargar divisiones de esta departamental
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        url: '{{ route('getDivisiones') }}?departamental_id=' + departamentalId,
                        type: 'get',
                        success: function(res) {
                            $.each(res, function(key, value) {
                                $('#division').append('<option value="' + value.id + '">' + value.nombre + '</option>');
                            });
                        }
                    });

                    // Cargar comisarías de esta departamental
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        url: '{{ route('getComisarias') }}?departamental_id=' + departamentalId,
                        type: 'get',
                        success: function(res) {
                            $.each(res, function(key, value) {
                                $('#comisaria').append('<option value="' + value.id + '">' + value.nombre + '</option>');
                            });
                        }
                    });
                }
            });

            // Al cambiar la división, limpiar comisarías (las divisiones no tienen comisarías hijas directas)
            $('#division').on('change', function() {
                $('#comisaria').html('<option value="">Seleccionar Comisaría</option>');
            });
        });
    </script>
@endsection
