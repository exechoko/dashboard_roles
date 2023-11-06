@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('/plugins/JQueryUi/jquery-ui.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-dialog/bootstrap-dialog.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-toggle/bootstrap-toggle.min.css') }}" rel="stylesheet">
@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Moviles</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="recurso">Móvil</label>
                                        <select name="recurso" id="recurso" class="form-control select2">
                                            <option value="">Seleccionar recurso</option>
                                            @foreach ($recursos as $recurso)
                                                <option value="{{ $recurso }}">
                                                    {{ $recurso }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-12 col-md-3" id="label_fecha_desde">
                                    <div class="form-group">
                                        <label for="fecha_desde">Desde</label>
                                        {!! Form::datetimeLocal('fecha_desde', '') !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3" id="label_fecha_hasta">
                                    <div class="form-group">
                                        <label for="fecha_hasta">Hasta</label>
                                        {!! Form::datetimeLocal('fecha_hasta', '') !!}
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-1">
                                <div class="form-group">
                                    <button style="margin-top:25px;" id="buscarMoviles" type="button"
                                        class="btn btn-primary">Buscar</button>
                                </div>
                            </div>




                            <!--div class="table-responsive">
                                                                    <table class="table table-striped mt-2">
                                                                        <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                                                            <th style="display: none;">ID</th>
                                                                            <th style="color:#fff;">Recurso</th>
                                                                            <th style="color:#fff;">Latitud</th>
                                                                            <th style="color:#fff;">Longitud</th>
                                                                            <th style="color:#fff;">Velocidad</th>
                                                                            <th style="color: #fff;">Fecha</th>
                                                                        </thead>
                                                                        <tbody>
                                                                            {{-- @foreach ($results as $movil)
                                            <tr>
                                                <td style="display: none;">{{ $movil->id }}</td>
                                                <td>{{ $movil->recurso }}</td>
                                                <td>{{ $movil->latitud }}</td>
                                                <td>{{ $movil->longitud }}</td>
                                                <td>{{ $movil->velocidad }}</td>
                                                <td>{{ $movil->fecha }}</td>
                                            </tr>
                                        @endforeach --}}
                                                                        </tbody>
                                                                    </table>
                                                                </div-->
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div id="control-moviles" class="col-lg-12" style="margin-top: 20px;">
                            <div class="row">
                                <div class="col-lg-12" style="text-align: center; color: red">
                                    <h3 class="vertical-space">Control de Móviles</h3>
                                </div>
                            </div>
                            <div id="box-table_moviles">
                                <table table id="table_moviles" class="table-condensed" data-toggle="table"
                                    data-height="100%" style="background-color:#FFF;" data-sort-name="id"
                                    data-sort-order="asc" data-query-params="queryParams"
                                    data-content-type="application/x-www-form-urlencoded" data-pagination="true"
                                    data-page-size="100" data-page-list="[10,20,50,100,200]" data-unique-id="id"
                                    data-resizable="true" data-method="post">
                                    <thead>
                                        <tr>
                                            <th data-field="id">ID</th>
                                            <th data-field="recurso">Recurso</th>
                                            <th data-field="latitud">Latitud</th>
                                            <th data-field="longitud">Longitud</th>
                                            <th data-field="velocidad">Velocidad</th>
                                            <th data-field="fecha">Fecha</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            var $table_moviles = $('#table_moviles');

            // Inicializa la tabla
            $table_moviles.bootstrapTable({
                columns: [{
                        field: 'id',
                        title: 'ID'
                    },
                    {
                        field: 'recurso',
                        title: 'Recurso'
                    },
                    {
                        field: 'latitud',
                        title: 'Latitud'
                    },
                    {
                        field: 'longitud',
                        title: 'Longitud'
                    },
                    {
                        field: 'velocidad',
                        title: 'Velocidad'
                    },
                    {
                        field: 'fecha',
                        title: 'Fecha'
                    }
                ]
            });

            // Agrega un evento de clic al botón "Buscar"
            $("#buscarMoviles").click(function() {
                buscarMoviles();
            });

            function buscarMoviles() {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-moviles') }}",
                    data: {
                        recurso: $('#recurso').val(),
                        fecha_desde: $('#fecha_desde').val(),
                        fecha_hasta: $('#fecha_hasta').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Data', data);
                        $table_moviles.bootstrapTable('load', data.moviles);
                    },
                    error: function(data) {
                        console.log('DataError', data);
                    }
                });
            }
        });
    </script>
@endsection
