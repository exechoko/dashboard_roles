@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Crear Dependencia</h3>
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

                            <form action="{{ route('dependencias.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Dirección</label>
                                            <select name="direccion" id="direccion" class="form-control"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Dirección</option>
                                                @foreach ($direcciones as $direccion)
                                                    <option value="{{ $direccion->id }}">
                                                        {{ $direccion->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="">Dirección</label>
                                            <select name="departamental" id="departamental" class="form-control"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Departamental</option>
                                                @foreach ($departamentales as $departamental)
                                                    <option value="{{ $departamental->id }}">
                                                        {{ $departamental->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="">División</label>
                                            <select class="form-control" name="division" id="division"></select>
                                        </div>
                                        <div class="form-group">
                                            <label for="">Comisaría</label>
                                            <select class="form-control" name="comisaria" id="comisaria"></select>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre de la dependencia</label>
                                            <input type="text" name="nombre" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Tipo de dependencia</label>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="tipoDependencia"
                                                    id="esDestacamento" value="destacamento">
                                                <label class="form-check-label font-weight-bold text-dark" for="esDestacamento">
                                                    DESTACAMENTO
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="tipoDependencia"
                                                    id="esSeccion" checked value="seccion">
                                                <label class="form-check-label font-weight-bold text-dark" for="esSeccion">
                                                    SECCIÓN
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="telefono">Telefono</label>
                                            <input type="text" name="telefono" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-8">
                                        <div class="form-group">
                                            <label for="">Ubicación</label>
                                            <input type="text" name="ubicacion" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
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
            $('#direccion').on('change', function() {
                /*var direccionId = this.value;
                $('#departamental').html('');
                $.ajax({
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '{{ route('getDepartamentales') }}?direccion_id=' + direccionId,
                    type: 'get',
                    success: function(res) {
                        $('#departamental').html(
                            '<option value="">Seleccionar Departamental</option>');
                        $.each(res, function(key, value) {
                            $('#departamental').append('<option value="' + value
                                .id + '">' + value.nombre + '</option>');
                        });
                        //$('#divisiones').html('<option value="">Seleccionar División</option>');
                    }
                });*/

                var direccionId = this.value;
                $('#division').html('');
                $.ajax({
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '{{ route('getDivisiones') }}?direccion_id=' + direccionId,
                    type: 'get',
                    success: function(res) {
                        $('#division').html(
                            '<option value="">Seleccionar Division</option>');
                        $.each(res, function(key, value) {
                            $('#division').append('<option value="' + value
                                .id + '">' + value.nombre + '</option>');
                        });
                        //$('#divisiones').html('<option value="">Seleccionar División</option>');
                    }

                });
            });

            $('#departamental').on('change', function() {
                var departamentalId = this.value;
                $('#division').html('');
                $.ajax({
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '{{ route('getDivisiones') }}?departamental_id=' + departamentalId,
                    type: 'get',
                    success: function(res) {
                        $('#division').html('<option value="">Seleccionar División</option>');
                        $.each(res, function(key, value) {
                            $('#division').append('<option value="' + value
                                .id + '">' + value.nombre + '</option>');
                        });
                    }
                });

                var departamentalId = this.value;
                $('#comisaria').html('');
                $.ajax({
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '{{ route('getComisarias') }}?departamental_id=' + departamentalId,
                    type: 'get',
                    success: function(res) {
                        $('#comisaria').html('<option value="">Seleccionar Comisaria</option>');
                        $.each(res, function(key, value) {
                            $('#comisaria').append('<option value="' + value
                                .id + '">' + value.nombre + '</option>');
                        });
                    }
                });
            });
        });
    </script>
@endsection
