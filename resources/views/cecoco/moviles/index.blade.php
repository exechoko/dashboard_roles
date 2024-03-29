@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('/plugins/JQueryUi/jquery-ui.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-dialog/bootstrap-dialog.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-toggle/bootstrap-toggle.min.css') }}" rel="stylesheet">

    <style>
        /* redondear modal
                                                                                        #mapModal .modal-dialog {
                                                                                            border-radius: 100% !important;
                                                                                            overflow: hidden;
                                                                                        }*/

        #map-modal {
            height: 400px;
            width: 100%;
        }

        #recorrido-modal {
            height: 600px;
            width: 100%;
        }

        .circulo-rojo {
            background-color: red;
            border-radius: 50%;
            width: 10px;
            height: 10px;
            text-align: center;
            line-height: 10px;
            color: white;
        }
    </style>
@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Móviles</h3>
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
                                <div class="col-xs-12 col-sm-12 col-md-4">
                                    <div class="form-group">
                                        <label for="recurso">Recursos desde CeCoCo</label>
                                        <select name="recurso" id="recurso" class="form-control select2">
                                            <option value="">Seleccionar recurso</option>
                                            @foreach ($recursos as $recurso)
                                                <option value="{{ trim($recurso) }}">
                                                    {{ trim($recurso) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                                    <div id="selectedResources" style="margin-bottom: 10px;">
                                        <!-- Aquí se agregarán los badges -->
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
                            <div class="form-group col-lg-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        @can('buscar-moviles-recorridos')
                                            <button style="margin-top:25px;" id="buscarMoviles" type="button"
                                                class="btn btn-primary">Buscar</button>
                                        @endcan
                                    </div>
                                    <div class="col-md-6">
                                        <div class="col-md-12">
                                            @can('buscar-moviles-parados')
                                                <button id="buscarMovilesParados" type="button" class="btn btn-danger">Buscar
                                                    tiempo detenido</button>
                                                <input id="tiempo_permitido" type="text" name="tiempo_permitido"
                                                    class="form-control" placeholder="Tiempo permitido parar (minutos)"
                                                    value="">
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        @can('buscar-moviles-parados')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div id="control-moviles-parados" class="col-lg-12" style="margin-top: 20px;">
                            <div class="row">
                                <div class="col-lg-12" style="text-align: center; color: rgb(0, 0, 0)">
                                    <h3 class="vertical-space">Control de Móviles parados</h3>
                                </div>
                                <div id="loading-indicator" style="display: none;">
                                    <i class="fa fa-spinner fa-spin"></i> Cargando...
                                </div>
                                <div id="box-table_moviles" class="col-lg-12">
                                    <table table id="table_moviles_parados" class="table-condensed" data-toggle="table"
                                        data-height="100%" style="background-color:#FFF; width: 100%;" data-sort-name="id"
                                        data-sort-order="asc" data-query-params="queryParams"
                                        data-content-type="application/x-www-form-urlencoded" data-pagination="true"
                                        data-page-size="100" data-page-list="[10,20,50,100,200]" data-unique-id="id"
                                        data-resizable="true" data-method="post">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-visible="false">ID</th>
                                                <th data-field="recurso">Recurso</th>
                                                <th data-field="inicio_parado">Inicio</th>
                                                <th data-field="fin_parado">Fin</th>
                                                <th data-field="tiempo_parado">Tiempo parado</th>
                                                <th data-field="direccion">Dirección</th>
                                                <th data-field="mapa"></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('buscar-moviles-recorridos')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div id="control-moviles" class="col-lg-12" style="margin-top: 20px;">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-6">
                                    <div class="form-group">
                                        <label for="filtroRecursos">Filtro por recurso</label>
                                        <select name="filtroRecursos" id="filtroRecursos" class="form-control select2">
                                            <!-- Las opciones se cargarán dinámicamente después -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 text-right">
                                    <div class="form-group">
                                        <button id="verRecorrido" class="btn btn-warning">Recorrido</button>
                                    </div>
                                    <div class="form-group">
                                        <button id="exportarExcel" class="btn btn-success">Exportar a Excel</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="text-align: center; color: rgb(0, 0, 0)">
                                    <h3 class="vertical-space">Control de Móviles</h3>
                                </div>
                            </div>
                            <div id="loading-indicator" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Cargando...
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
                                            <th data-field="mapa"></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

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



    <script src="https://cdnjs.cloudflare.com/ajax/libs/spin.js/2.3.2/spin.min.js"></script>
    <script>
        var map;
        var recorridoMap;
        var markerResultado;
        var polyline;
        var MARKERS = [];

        function initMap() {
            if (map == null) {
                // Inicializa el mapa una vez al cargar la página
                map = L.map('map-modal', {
                    editable: true
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                /*L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);*/
            }

            setTimeout(() => {
                var latitud = parseFloat($('#mapModal').data('latitud'))
                var longitud = parseFloat($('#mapModal').data('longitud'))
                if (markerResultado) {
                    map.removeLayer(markerResultado);
                }
                markerResultado = L.marker([latitud, longitud]).addTo(map);
                // Centra el mapa en las coordenadas del marcador
                map.setView([latitud, longitud], 17);

            }, 2000);

        }

        function initMapRecorrido(info) {
            if (recorridoMap == null) {
                // Inicializa el mapa una vez al cargar la página
                recorridoMap = L.map('recorrido-modal', {
                    editable: true
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(recorridoMap);
            }

            setTimeout(() => {

                for (var m = 0; m < MARKERS.length; m++) {
                    console.log('marker', MARKERS);
                    recorridoMap.removeLayer(MARKERS[m])
                }
                MARKERS = [];

                if (polyline) {
                    recorridoMap.removeLayer(polyline);
                }

                // Crear la polyline y ajustar el mapa al recorrido
                var coordenadas = info.map(function(data) {
                    if (data.latitud !== 0 || data.longitud !== 0) {
                        return [data.latitud, data.longitud];
                    }
                }).filter(function(coordenada) {
                    return coordenada !== undefined;
                });

                polyline = L.polyline(coordenadas, {
                    color: 'red'
                }).addTo(recorridoMap);

                // Agrega los marcadores de cada coordenada
                info.forEach(function(data, index) {
                    if (data.latitud !== 0 || data.longitud !== 0) {
                        var markerIcon;

                        if (index === 0) {
                            // Primer marcador con bandera verde
                            markerIcon = L.icon({
                                iconUrl: "/img/flag_init.svg",
                                iconSize: [32, 32], // Ajusta el tamaño según tu necesidad
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            });
                        } else if (index === info.length - 1) {
                            // Último marcador con bandera de llegada (a cuadros)
                            markerIcon = L.icon({
                                iconUrl: "/img/flag_finish.svg",
                                iconSize: [32, 32], // Ajusta el tamaño según tu necesidad
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            });
                        } else {
                            // Círculo intermedio en rojo (creado con CSS)
                            markerIcon = L.divIcon({
                                className: 'circulo-rojo',
                                iconSize: [10, 10],
                                iconAnchor: [5, 5]
                            });
                        }

                        var marker = L.marker([data.latitud, data.longitud], {
                            icon: markerIcon
                        });

                        var popupContent = "Fecha: " + data.fecha + "<br>Dirección: " + data.direccion +
                            "<br>Velocidad: " + data.velocidad;
                        marker.bindPopup(popupContent);
                        MARKERS.push(marker);
                        marker.addTo(recorridoMap);
                    }
                });

                //centra el mapa en el recorrido completo
                recorridoMap.fitBounds(polyline.getBounds());
            }, 2000);
        }

        function initMapRecorridoAnimado(info) {
            console.log('animado', info)
            if (recorridoMap == null) {
                // Inicializa el mapa una vez al cargar la página
                recorridoMap = L.map('recorrido-modal', {
                    editable: true
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(recorridoMap);
            }

            setTimeout(() => {

                for (var m = 0; m < MARKERS.length; m++) {
                    recorridoMap.removeLayer(MARKERS[m]);
                }
                MARKERS = [];

                if (polyline) {
                    recorridoMap.removeLayer(polyline);
                }

                // Crear la polyline y ajustar el mapa al recorrido
                var coordenadas = info.map(function(data) {
                    if (data.latitud !== 0 || data.longitud !== 0) {
                        return [data.latitud, data.longitud];
                    }
                }).filter(function(coordenada) {
                    return coordenada !== undefined;
                });

                polyline = L.polyline([], {
                    color: 'red'
                }).addTo(recorridoMap);

                // Función para agregar un marcador con un retraso
                function agregarMarcadorConRetraso(data, index) {
                    setTimeout(function() {
                        var markerIcon;

                        if (index === 0) {
                            // Primer marcador con bandera verde
                            markerIcon = L.icon({
                                iconUrl: "/img/flag_init.svg",
                                iconSize: [32, 32], // Ajusta el tamaño según tu necesidad
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            });
                        } else if (index === info.length - 1) {
                            // Último marcador con bandera de llegada (a cuadros)
                            markerIcon = L.icon({
                                iconUrl: "/img/flag_finish.svg",
                                iconSize: [32, 32], // Ajusta el tamaño según tu necesidad
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            });
                        } else {
                            // Círculo intermedio en rojo (creado con CSS)
                            markerIcon = L.divIcon({
                                className: 'circulo-rojo',
                                iconSize: [10, 10],
                                iconAnchor: [5, 5]
                            });
                        }

                        var marker = L.marker([data.latitud, data.longitud], {
                            icon: markerIcon
                        });

                        var popupContent = "Fecha: " + data.fecha + "<br>Dirección: " + data.direccion +
                            "<br>Velocidad: " + data.velocidad;
                        marker.bindPopup(popupContent);
                        MARKERS.push(marker);
                        marker.addTo(recorridoMap);

                        // Agregar coordenadas a la lista para la polyline
                        polyline.addLatLng([data.latitud, data.longitud]);

                        // Ajustar el mapa al recorrido completo
                        recorridoMap.fitBounds(polyline.getBounds());
                    }, index * 250); // Ajusta el valor (en milisegundos) según la velocidad deseada
                }

                // Agrega los marcadores de cada coordenada con retraso
                info.forEach(function(data, index) {
                    if (data.latitud !== 0 || data.longitud !== 0) {
                        agregarMarcadorConRetraso(data, index);
                    }
                    // Ajustar el mapa al recorrido completo
                    //recorridoMap.fitBounds(polyline.getBounds());
                });
            }, 2000);
        }

        $(document).ready(function() {
            var $table_moviles = $('#table_moviles');
            var $table_moviles_parados = $('#table_moviles_parados');
            var $loadingIndicator = $('#loading-indicator');

            $('#mapModal').on('show.bs.modal', function() {
                initMap()
            })

            // Inicializa la tabla
            $table_moviles_parados.bootstrapTable({
                columns: [{
                        field: 'id',
                        title: 'ID'
                    },
                    {
                        field: 'recurso',
                        title: 'Recurso'
                    },
                    {
                        field: 'inicio_parado',
                        title: 'Inicio'
                    },
                    {
                        field: 'fin_parado',
                        title: 'Fin'
                    },
                    {
                        field: 'tiempo_parado',
                        title: 'Tiempo parado'
                    },
                    {
                        field: 'lugar',
                        title: 'Lugar'
                    }, {
                        field: 'mapa',
                        title: 'Mapa',
                        formatter: function(value, row) {
                            return '<button class="btn btn-dark btn-sm mapa-btn" data-latitud="' +
                                row.latitud + '" data-longitud="' + row.longitud +
                                '"><i class="fa fa-globe"></i></button>';
                        }
                    }
                ]
            });
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
                    },
                    {
                        field: 'mapa',
                        title: 'Mapa',
                        formatter: function(value, row) {
                            return '<button class="btn btn-dark btn-sm mapa-btn" data-latitud="' +
                                row.latitud + '" data-longitud="' + row.longitud +
                                '"><i class="fa fa-globe"></i></button>';
                        }
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
                console.log('texto', e.params.data.id);
                var selectedResource = e.params.data.id;

                // Verifica si el recurso ya está seleccionado
                if (!selectedResources.includes(selectedResource) || selectedResource != '') {
                    // Agrega el recurso a la lista de recursos seleccionados
                    selectedResources.push(selectedResource);
                    // Agrega el badge al contenedor
                    var badgeItem = $('<span class="badge badge-info badge-pill m-2">' +
                        selectedResource +
                        '<i class="fa fa-times ml-2 cursor-pointer"></i>' +
                        '</span>')
                    badgeItem.find('i').click(function(event) {
                        // Obtén el índice correcto antes de eliminar el elemento
                        var index = selectedResources.indexOf(selectedResource);
                        console.log('index', index)
                        selectedResources.splice(index, 1)
                        $(this).closest('.badge').remove()
                        console.log('rec_select', selectedResources);
                    });

                    $('#selectedResources').append(badgeItem);
                }
                console.log('rec_select', selectedResources);
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
                if (selectedResources.length == 0 || !$('#fecha_desde').val() || !$('#fecha_hasta').val()) {
                    swal('¡ATENCION!', 'Todos los campos son requeridos', 'warning');
                    return;
                }
                $loadingIndicator.show();
                buscarMoviles(selectedResources);
            });

            // Agrega un evento de clic al botón "Buscar"
            $("#buscarMovilesParados").click(function() {
                if (selectedResources.length == 0 || !$('#fecha_desde').val() || !$('#fecha_hasta').val() ||
                    !$('#tiempo_permitido').val()) {
                    swal('¡ATENCION!', 'Todos los campos son requeridos', 'warning');
                    return;
                }
                $loadingIndicator.show();
                buscarMovilesParados(selectedResources);
            });

            // Agrega un evento de clic al botón "Mapa"
            $('#table_moviles').on('click', '.mapa-btn', function() {
                var latitud = $(this).data('latitud');
                var longitud = $(this).data('longitud');
                console.log('coord', latitud + '-' + longitud);
                abrirModalMapa(latitud, longitud);
            });
            // Agrega un evento de clic al botón "Mapa"
            $('#table_moviles_parados').on('click', '.mapa-btn', function() {
                var latitud = $(this).data('latitud');
                var longitud = $(this).data('longitud');
                console.log('coord', latitud + '-' + longitud);
                abrirModalMapa(latitud, longitud);
            });

            function buscarMoviles(recursos) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-moviles') }}",
                    data: {
                        recursos: JSON.stringify(recursos) /*$('#recurso').val()*/ ,
                        fecha_desde: $('#fecha_desde').val(),
                        fecha_hasta: $('#fecha_hasta').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Data', data);
                        // Verificar si hay al menos un grupo de recursos
                        if (data.moviles.length > 0) {
                            // Cargar automáticamente los datos del primer grupo en la tabla
                            var movil = data.moviles[0].datos;
                            $table_moviles.bootstrapTable('load', movil);
                            console.log('movil', movil);
                            actualizarSelectFiltro(data.moviles);
                        } else {
                            // Manejar el caso en que no hay grupos de recursos
                            console.log('No hay datos de moviles disponibles.');
                        }
                    },
                    error: function(data) {
                        console.log('DataError para ' + recurso, data);
                    },
                    complete: function() {
                        // Oculta el indicador de carga después de completar la solicitud
                        $loadingIndicator.hide();
                    }
                });
            }


            function buscarMovilesParados(recursos) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('get-moviles-parados') }}",
                    data: {
                        recursos: JSON.stringify(recursos) /*$('#recurso').val()*/ ,
                        fecha_desde: $('#fecha_desde').val(),
                        fecha_hasta: $('#fecha_hasta').val(),
                        tiempo_permitido: $('#tiempo_permitido').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Data', data);
                        var movil = data.intervalos_parado;
                        $table_moviles_parados.bootstrapTable('load', movil);
                    },
                    error: function(data) {
                        console.log('DataError para ' + recurso, data);
                        swal('Error!', 'Vuelva a intentar', 'warning');
                    },
                    complete: function() {
                        // Oculta el indicador de carga después de completar la solicitud
                        $loadingIndicator.hide();
                    }
                });
            }

            // Función para actualizar el select2 de filtroRecursos
            function actualizarSelectFiltro(recursos) {
                // Limpiar opciones actuales
                $('#filtroRecursos').empty();

                // Agregar una opción por cada recurso
                console.log('recursos', recursos);
                recursos.forEach(function(recurso) {
                    console.log('recurso', recurso);
                    var option = new Option(recurso.recurso, recurso.recurso, true, true);
                    $('#filtroRecursos').append(option).trigger('change');
                });

                // Inicializar Select2
                $('#filtroRecursos').select2({
                    placeholder: 'Filtrar por Recurso',
                    allowClear: false
                });

                // Manejar el evento de cambio en el select2 de filtroRecursos
                $('#filtroRecursos').on('change', function() {
                    var recursoSeleccionado = $(this).val();
                    console.log('recurso_selec', recursoSeleccionado);

                    // Filtrar y cargar datos en la tabla según el recurso seleccionado
                    var datosFiltrados = filtrarDatosPorRecurso(recursos, recursoSeleccionado);
                    console.log('datos_filtrados', datosFiltrados);
                    $table_moviles.bootstrapTable('load', datosFiltrados);
                });

                $("#verRecorrido").click(function() {
                    // Obtiene el recurso seleccionado en el filtro
                    var recursoSeleccionado = $('#filtroRecursos').val();
                    var fechaDesde = $('#fecha_desde').val();
                    var fechaHasta = $('#fecha_hasta').val();

                    // Filtra los datos según el recurso seleccionado
                    var datosFiltrados = filtrarDatosPorRecurso(recursos, recursoSeleccionado);
                    if (datosFiltrados.length > 0) {
                        console.log('datos_para_recorrido', datosFiltrados);
                        initMapRecorrido(datosFiltrados);
                        // Agrega el nombre del recurso al título de la modal
                        $('#recorridoModalLabel').html('Recorrido - ' + recursoSeleccionado);
                        $('#recorridoModal').modal('show');
                    }
                    /*else {
                                           // Muestra un mensaje si no hay datos para exportar
                                           swal('¡ATENCIÓN!', 'No hay recorrido para mostrar.', 'warning');
                                       }*/

                });

                // Agrega un evento de clic al botón "Exportar a Excel"
                $("#exportarExcel").click(function() {
                    // Obtiene el recurso seleccionado en el filtro
                    var recursoSeleccionado = $('#filtroRecursos').val();
                    var fechaDesde = $('#fecha_desde').val();
                    var fechaHasta = $('#fecha_hasta').val();

                    // Filtra los datos según el recurso seleccionado
                    var datosFiltrados = filtrarDatosPorRecurso(recursos, recursoSeleccionado);

                    // Verifica si hay datos para exportar
                    if (datosFiltrados.length > 0) {
                        // Convierte los datos a un formato compatible con xlsx
                        var data = datosFiltrados.map(function(item) {
                            return {
                                //ID: item.id,
                                //Recurso: item.recurso,
                                Latitud: item.latitud,
                                Longitud: item.longitud,
                                Direccion: item.direccion,
                                Velocidad: item.velocidad,
                                Fecha: item.fecha
                            };
                        });

                        // Crea un objeto de trabajo de libro de Excel
                        var ws = XLSX.utils.json_to_sheet(data);

                        // Crea un libro y agrega la hoja de trabajo
                        var wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, 'Historico');
                        /* fix headers */
                        XLSX.utils.sheet_add_aoa(ws, [
                            ["Latitud", "Longitud", "Dirección", "Velocidad",
                                "Fecha"
                            ]
                        ], {
                            origin: "A1",
                            bold: true
                        });

                        // Guarda el archivo Excel con un nombre específico
                        XLSX.writeFile(wb, 'historico_' +
                            recursoSeleccionado +
                            '_' +
                            fechaDesde.replace('T', '_') +
                            '__' +
                            fechaHasta.replace('T', '_') +
                            '.xlsx');
                    } else {
                        // Muestra un mensaje si no hay datos para exportar
                        swal('¡ATENCIÓN!', 'No hay datos para exportar.', 'warning');
                    }
                });

                $('#recrearRecorridoBtn').click(function() {
                    var recursoSeleccionado = $('#filtroRecursos').val();
                    var fechaDesde = $('#fecha_desde').val();
                    var fechaHasta = $('#fecha_hasta').val();

                    // Filtra los datos según el recurso seleccionado
                    var datosFiltrados = filtrarDatosPorRecurso(recursos, recursoSeleccionado);
                    if (datosFiltrados.length > 0) {
                        console.log('datos_para_recorrido', datosFiltrados);
                        initMapRecorridoAnimado(datosFiltrados);
                    }
                });
            }

            function filtrarDatosPorRecurso(datos, recurso) {
                var datosFiltrados = datos.find(function(item) {
                    return item.recurso === recurso;
                });

                // Verifica si se encontraron datos
                if (datosFiltrados) {
                    return datosFiltrados.datos;
                } else {
                    // Si no se encontraron datos, puedes devolver un array vacío o manejarlo según tus necesidades
                    return [];
                }
            }

            // Función para abrir la modal y mostrar el mapa
            function abrirModalMapa(latitud, longitud) {

                $('#mapModal').data({
                    latitud: latitud,
                    longitud: longitud
                });

                $('#mapModal').modal('show');
            }
        });
    </script>
@endsection
