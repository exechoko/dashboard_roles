@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Llamadas</h3>
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

                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-5">
                                    <div class="form-group">
                                        <label for="protocolo">Protocolos</label>
                                        <select name="protocolo" id="protocolo" class="form-control select2">
                                            <option value="">Seleccionar protocolo</option>
                                            @foreach ($protocolos as $protocolo)
                                                <option value="{{ $protocolo->id }}">
                                                    {{ $protocolo->descripcion }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-12 col-md-5">
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input id="telefono" type="text" name="telefono" class="form-control" value="">
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-12 col-md-3" id="label_fecha_desde">
                                    <div class="form-group">
                                        <label for="fecha_desde">Desde</label>
                                        {!! Form::datetimeLocal('fecha_desde', null, ['id' => 'fecha_desde', 'name' => 'fecha_desde']) !!}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3" id="label_fecha_hasta">
                                    <div class="form-group">
                                        <label for="fecha_hasta">Hasta</label>
                                        {!! Form::datetimeLocal('fecha_hasta', null, ['id' => 'fecha_hasta', 'name' => 'fecha_hasta']) !!}
                                    </div>
                                </div>

                            </div>

                            <div class="col-lg-1">
                                <div class="form-group">
                                    <button style="margin-top:25px;" id="buscarLlamadas" type="button"
                                        class="btn btn-primary">Buscar</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="text-align: left; color: rgb(0, 0, 0)">
                                    <h4 class="vertical-space">Llamadas</h4>
                                </div>
                            </div>
                            <div id="loading-indicator" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Cargando...
                            </div>
                            <div id="box-table_llmadas">
                                <table table id="table_llamadas" class="table-condensed" data-toggle="table"
                                    data-height="100%" style="background-color:#FFF;" data-sort-name="fechaInicio"
                                    data-sort-order="asc" data-query-params="queryParams"
                                    data-content-type="application/x-www-form-urlencoded" data-pagination="true"
                                    data-page-size="100" data-page-list="[10,20,50,100,200]" data-unique-id="id"
                                    data-resizable="true" data-method="post">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-visible="false">ID</th>
                                            <th data-field="numero">Número</th>
                                            <th data-field="fechaInicio">Inicio</th>
                                            <th data-field="fechaFin">Fin</th>
                                            <th data-field="duracion">Duración (seg.)</th>
                                            <th data-field="nombre">Nombre/Obs.</th>
                                            <th data-field="reproducir"></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para mostrar el mapa -->
        <div id="mapModal" class="modal fade" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-secondary">
                        <h5 class="modal-title" id="mapModalLabel">Ubicación</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="map-modal"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para mostrar el recorrido en el mapa -->
        <div id="recorridoModal" class="modal fade" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-labelledby="recorridoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-secondary">
                        <h5 class="modal-title" id="recorridoModalLabel">Recorrido</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="recorrido-modal"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="recrearRecorridoBtn">Recrear
                            Recorrido</button>
                        <button type="button" class="btn btn-success" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        var map;
        var recorridoMap;
        var markerResultado;
        var polyline;
        var MARKERS = [];

        $(document).ready(function() {
            var $table_llamadas = $('#table_llamadas');
            var $loadingIndicator = $('#loading-indicator');

            // Inicializa la tabla
            $table_llamadas.bootstrapTable({
                columns: [{
                        field: 'id',
                        title: 'ID'
                    },
                    {
                        field: 'numero',
                        title: 'Número'
                    },
                    {
                        field: 'fechaInicio',
                        title: 'Inicio'
                    },
                    {
                        field: 'fechaFin',
                        title: 'Fin'
                    },
                    {
                        field: 'duracion',
                        title: 'Duración'
                    },
                    {
                        field: 'nombre',
                        title: 'Nombre/Obs.'
                    },
                    {
                        field: 'reproducir',
                        title: '',
                        formatter: function(value, row) {
                            return '<button class="btn btn-dark btn-sm reproducir-btn"><i class="fa fa-play"></i></button>';
                        }
                    }
                ]
            });

            // Inicializa Select2 en el select
            $('#recurso').select2({
                placeholder: 'Seleccionar recurso',
                allowClear: true
            });

            // Agrega un evento de clic al botón "Buscar"
            $("#buscarLlamadas").click(function() {
                if (!$('#protocolo').val() || !$('#fecha_desde').val() || !$('#fecha_hasta').val() || !$('#telefono').val()) {
                    swal('¡ATENCION!', 'Todos los campos son requeridos', 'error');
                    return;
                }
                $loadingIndicator.show();
                buscarLlamadas();
            });

            // Agrega un evento de clic al botón "Mapa"
            $('#table_llamadas').on('click', '.reproducir-btn', function() {
                var latitud = $(this).data('latitud');
                var longitud = $(this).data('longitud');
                console.log('coord', latitud + '-' + longitud);
                //abrirModalMapa(latitud, longitud);
            });

            function buscarLlamadas() {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-llamadas') }}",
                    data: {
                        protocolo: $('#protocolo').val() ,
                        fecha_desde: $('#fecha_desde').val(),
                        fecha_hasta: $('#fecha_hasta').val(),
                        telefono: $('#telefono').val() ,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Data', data);
                        $table_llamadas.bootstrapTable('load', data.llamadas);
                    },
                    error: function(data) {
                        console.log('DataError ', data);
                    },
                    complete: function() {
                        // Oculta el indicador de carga después de completar la solicitud
                        $loadingIndicator.hide();
                    }
                });
            }

        });
    </script>
@endsection
