@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Flota</h3>
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

                            <form action="{{ route('flota.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Tipo de movimiento</label>
                                            <select name="tipo_movimiento" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar tipo de movimiento</option>
                                                @foreach ($tipos_movimiento as $tipo_movimiento)
                                                    <option value="{{ $tipo_movimiento->id }}">
                                                        {{ $tipo_movimiento->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Equipos</label>
                                            <select name="equipo" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Equipo</option>
                                                @foreach ($equipos as $equipo)
                                                    <option value="{{ $equipo->id }}">
                                                        {{ $equipo->tipo_terminal->tipo_uso->uso . ' ' . $equipo->issi . ' - ' . $equipo->tei . ' - '. $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!--div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Recurso</label>
                                            <select name="recurso" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Recurso</option>
                                                @foreach ($recursos as $recurso)
                                                    <option value="{{ $recurso->id }}">
                                                        @if (is_null($recurso->vehiculo))
                                                        {{ $recurso->nombre }}
                                                        @else
                                                        {{ $recurso->nombre . ' - ' . $recurso->vehiculo->tipo_vehiculo }}
                                                        @endif
                                                        </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div-->
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Dependencia</label>
                                            <select name="dependencia" id="dependencia" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Dependencia</option>
                                                @foreach ($dependencias as $dependencia)
                                                    <option value="{{ $dependencia->id }}">
                                                        {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Recurso</label>
                                            <select class="form-control select2" name="recurso" id="recurso"></select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="ticket_per">Ticket PER</label>
                                            <input type="text" name="ticket_per" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12" id="label_fecha_asignacion">
                                        <div class="form-group">
                                            <label for="fecha_asignacion">Fecha de asignación</label>
                                            {!! Form::date('fecha_asignacion', \Carbon\Carbon::now()) !!}
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

            $('#dependencia').on('change', function() {
                var dependenciaId = this.value;
                $('#recurso').html('');
                $.ajax({
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '{{ route('getRecursosJSON') }}?destino_id=' + dependenciaId,
                    type: 'get',
                    success: function(res) {
                        $('#recurso').html('<option value="">Seleccionar Recurso</option>');
                        $.each(res, function(key, value) {
                            $('#recurso').append('<option value="' + value
                                .id + '">' + value.nombre + '</option>');
                        });
                    }
                });
            });
        });
    </script>
@endsection
