@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Crear Dependencia</h3>
            <div class="alert alert-info ml-3">
                Puede crear cualquier tipo de dependencia respetando la jerarquía organizacional.
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

                            <form action="{{ route('dependencias.store-general') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="tipo">Tipo de Dependencia *</label>
                                            <select name="tipo" id="tipo" class="form-control select2" required>
                                                <option value="">Seleccionar Tipo</option>
                                                <option value="direccion" {{ old('tipo') == 'direccion' ? 'selected' : '' }}>Dirección</option>
                                                <option value="departamental" {{ old('tipo') == 'departamental' ? 'selected' : '' }}>Departamental</option>
                                                <option value="division" {{ old('tipo') == 'division' ? 'selected' : '' }}>División</option>
                                                <option value="comisaria" {{ old('tipo') == 'comisaria' ? 'selected' : '' }}>Comisaría</option>
                                                <option value="seccion" {{ old('tipo') == 'seccion' ? 'selected' : '' }}>Sección</option>
                                                <option value="destacamento" {{ old('tipo') == 'destacamento' ? 'selected' : '' }}>Destacamento</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="parent_id">Depende de</label>
                                            <select name="parent_id" id="parent_id" class="form-control select2">
                                                <option value="">Seleccionar Dependencia Padre</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Las opciones disponibles dependen del tipo seleccionado.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre de la Dependencia *</label>
                                            <input type="text" name="nombre" id="nombre" class="form-control"
                                                   value="{{ old('nombre') }}" required>
                                            <small class="form-text text-muted">
                                                El prefijo correspondiente al tipo se agregará automáticamente.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono</label>
                                            <input type="text" name="telefono" id="telefono" class="form-control"
                                                   value="{{ old('telefono') }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="ubicacion">Ubicación</label>
                                            <input type="text" name="ubicacion" id="ubicacion" class="form-control"
                                                   value="{{ old('ubicacion') }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" id="observaciones"
                                                      style="height: 100px">{{ old('observaciones') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                        <a href="{{ route('dependencias.index') }}" class="btn btn-secondary">Cancelar</a>
                                    </div>
                                </div>
                            </form>

                            <!-- Información de jerarquía -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h5 class="mb-0">Jerarquía Organizacional</h5>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><strong>Jefatura:</strong> No depende de nadie (nivel superior)</li>
                                                <li><strong>Dirección:</strong> Depende de la Jefatura</li>
                                                <li><strong>Departamental:</strong> Puede depender de Jefatura o Dirección</li>
                                                <li><strong>División:</strong> Puede depender de Jefatura, Dirección o Departamental</li>
                                                <li><strong>Comisaría:</strong> Depende de una Departamental</li>
                                                <li><strong>Sección:</strong> Puede depender de Dirección, Departamental, División o Comisaría</li>
                                                <li><strong>Destacamento:</strong> Puede depender de Departamental, División o Comisaría</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

            // Cuando cambia el tipo de dependencia, cargar los posibles padres
            $('#tipo').on('change', function() {
                var tipo = this.value;
                $('#parent_id').html('<option value="">Seleccionar Dependencia Padre</option>');

                if (tipo) {
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        url: '{{ route("getPosiblesPadres") }}',
                        type: 'GET',
                        data: { tipo: tipo },
                        success: function(res) {
                            if (res.length > 0) {
                                $.each(res, function(key, value) {
                                    var selected = '';
                                    @if(old('parent_id'))
                                        if (value.id == {{ old('parent_id') }}) {
                                            selected = 'selected';
                                        }
                                    @endif
                                    $('#parent_id').append('<option value="' + value.id + '" ' + selected + '>' + value.nombre + '</option>');
                                });
                            } else {
                                $('#parent_id').append('<option value="">No hay dependencias padre disponibles</option>');
                            }
                        },
                        error: function() {
                            alert('Error al cargar las dependencias padre');
                        }
                    });
                }
            });

            // Si hay un tipo seleccionado al cargar la página (por old input), cargar los padres
            @if(old('tipo'))
                $('#tipo').trigger('change');
            @endif
        });
    </script>
@endsection
