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

                                <div id="selectedResources" style="margin-bottom: 10px;">
                                    <!-- Aquí se agregarán los badges -->
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
                                    <button style="margin-top:25px;" id="buscarMoviles" type="button"
                                        class="btn btn-primary">Buscar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div id="control-moviles" class="col-lg-12" style="margin-top: 20px;">
                            <div class="row">
                                <div class="col-lg-12" style="text-align: center; color: rgb(0, 0, 0)">
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
                                            <th data-field="id" data-visible="false">ID</th>
                                            <th data-field="recurso">Recurso</th>
                                            <th data-field="latitud">Latitud</th>
                                            <th data-field="longitud">Longitud</th>
                                            <th data-field="velocidad">Velocidad</th>
                                            <th data-field="fecha">Fecha</th>
                                            <th data-field="direccion">Direccion</th>
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

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
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
                    },
                    {
                        field: 'direccion',
                        title: 'Direccion'
                    }
                ]
            });

            var selectedResources = [];

            // Inicializa Select2 en el select
            $('#recurso').select2({
                placeholder: 'Seleccionar recurso',
                allowClear: true
            });

            // Maneja el evento de selección en el select2
            $('#recurso').on('select2:select', function(e) {
                var selectedResource = e.params.data.text;

                // Verifica si el recurso ya está seleccionado
                if (!selectedResources.includes(selectedResource)) {
                    // Agrega el badge al contenedor
                    $('#selectedResources').append('<span class="badge badge-primary badge-pill">' +
                        selectedResource +
                        '<i class="fa fa-times ml-2 cursor-pointer" onclick="removeBadge(\'' +
                        selectedResource + '\')"></i>' +
                        '</span>');

                    // Agrega el recurso a la lista de recursos seleccionados
                    selectedResources.push(selectedResource);
                }
            });

            // Maneja el evento de deselección en el select2
            $('#recurso').on('select2:unselect', function(e) {
                var unselectedResource = e.params.data.text;

                // Remueve el badge del contenedor
                $('#selectedResources .badge:contains("' + unselectedResource + '")').remove();

                // Remueve el recurso de la lista de recursos seleccionados
                selectedResources = selectedResources.filter(function(resource) {
                    return resource !== unselectedResource;
                });
            });

            // Agrega un evento de clic al botón "Buscar"
            $("#buscarMoviles").click(function() {
                // Realiza consultas para cada recurso seleccionado
                selectedResources.forEach(function(resource) {
                    // Realiza la consulta para el recurso actual
                    buscarMoviles(resource);
                });
            });

            // Agrega un evento de clic al botón "Buscar"
            /*$("#buscarMoviles").click(function() {
                buscarMoviles();
            });*/

            function obtenerDireccion(coordinates) {
                console.log('es la l', L);
                var geocoder = L.Control.Geocoder.nominatim();
                console.log(geocoder);
                var direcciones = [];
                $.each(coordinates, function(i, coordenada) {
                    var latlng = L.latLng(coordenada.latitud, coordenada.longitud);

                    geocoder.reverse(latlng, map.options.crs.scale(map.getZoom()), function(results) {
                        var address = results[0].name;
                        direcciones.push(address);
                        //var listItem = document.createElement("li");
                        //listItem.textContent = address;
                        //addressesList.appendChild(listItem);
                    });
                });
                console.log('direcciones', direcciones);
            }

            function buscarMoviles(recurso) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-moviles') }}",
                    data: {
                        recurso: recurso /*$('#recurso').val()*/ ,
                        fecha_desde: $('#fecha_desde').val(),
                        fecha_hasta: $('#fecha_hasta').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Data', data);
                        $table_moviles.bootstrapTable('load', data.moviles);
                        //obtenerDireccion(data.moviles);
                    },
                    error: function(data) {
                        console.log('DataError para ' + recurso, data);
                    }
                });
            }

            // Función para remover un badge
            function removeBadge(resource) {
                // Remueve el badge del contenedor
                $('#selectedResources .badge[data-resource="' + resource + '"]').remove();

                // Remueve el recurso de la lista de recursos seleccionados
                selectedResources = selectedResources.filter(function(selectedResource) {
                    return selectedResource !== resource;
                });

                // Deselecciona el recurso en el select2
                $('#recurso option:contains("' + resource + '")').prop('selected', false);
                $('#recurso').trigger('change');
            }
        });
    </script>
@endsection
