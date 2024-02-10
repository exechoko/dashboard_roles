@extends('layouts.app')

@section('css')

    <style>
        #fullscreen-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background-color: rgb(255, 255, 255);
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        html {
            --border-m: darkgreen;
            --border-t: red;
            --border-all: darkblue;
            --fondo-m: lightgreen;
            --fondo-t: gold;
            --fondo-all: deepskyblue;
        }

        section {
            padding: 0px;
            margin: 0px;
        }

        .content {
            padding: 0px;
            margin: 0px;
        }

        .content-header {
            padding: 0px;
            margin: 0px;
        }

        .my_class {
            position: absolute;
            top: npx;
            /* distancia superior */
            left: npx;
            /* distancia izquierda */
            right: npx
                /* distancia derecha */
                bottom:npx;
            /* distancia inferior */
            z-index: N(valor numerico) nivel de elevacion (simil elevation de android)
        }

        .underline-on-hover:hover {
            text-decoration: underline;
            cursor: pointer;
        }

        .flex-row-between-center {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: justify;
            justify-content: space-between;
            -ms-flex-align: center;
            align-items: center;
        }

        .flex-row-around-center {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: distribute;
            justify-content: space-around;
            -ms-flex-align: center;
            align-items: center;
        }

        .flex-row-start-start {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: start;
            justify-content: flex-start;
            -ms-flex-align: start;
            align-items: flex-start;
        }

        .flex-row-start-center {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: start;
            justify-content: flex-start;
            -ms-flex-align: center;
            align-items: center;
        }

        .flex-col-start-start {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: column;
            flex-direction: column;
            -ms-flex-pack: start;
            justify-content: flex-start;
            -ms-flex-align: start;
            align-items: flex-start;
        }

        .legend {
            line-height: 18px;
            color: #555;
        }

        .legend i {
            width: 18px;
            height: 18px;
            float: left;
            margin-right: 8px;
            opacity: 0.7;
        }

        .info {
            /*padding: 6px 8px;*/
            /*font: 14px/16px Arial, Helvetica, sans-serif;*/
            background: white;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        .info h4 {
            margin: 0 0 5px;
            color: #777;
        }

        #btn-reset {
            position: fixed;
            right: 20px;
            bottom: 20px;
        }

        #box-zoom {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: 20px;
        }

        #box-zoom i {
            color: black !important
        }

        .pieza {
            padding: 10px;
            border: solid thin #00c0ef;
            border-radius: 5px;
            margin-bottom: 10px
        }

        .lbl-result {
            padding: 5px 10px;
        }

        .li-success {
            padding: 2px 10px;
            border: solid thin green;
            border-radius: 5px;
            margin-bottom: 10px
        }

        .li-danger {
            padding: 2px 10px;
            border: solid thin red;
            border-radius: 5px;
            margin-bottom: 10px
        }

        #modal-result ul {
            list-style: none;
            height: 250px;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 10px
        }

        #lbl-area {
            background: #00c0ef;
            padding: 10px;
            color: white;
            margin: 0 10px !important;
            font-size: 20px;
            font-weight: bold
        }

        #no-guias {
            padding: 10px;
            margin: 10px !important;

        }

        #btn-route:before {
            content: '';
        }

        .marker-icon::before {
            content: '';
            display: block;
            width: 20px;
            height: 30px;
            background-color: red;
            border-radius: 50% 50% 0 0;
            transform: rotate(-45deg);
            margin-left: -10px;
            margin-top: -30px;
        }



        .marker-comprador {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: center;
            justify-content: center;
            -ms-flex-align: center;
            align-items: center;
            width: 30px;
            height: 30px;
            left: 0px;
            top: 0px;
            position: relative;
            border-radius: 100%;
            border: thick solid #424EB0;
            font-size: 16px;
            font-weight: bold;
            color: rgb(196, 56, 13);
            box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);
            background-color: white !important;

        }

        .marker-camara {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: center;
            justify-content: center;
            -ms-flex-align: center;
            align-items: center;
            width: 30px;
            height: 30px;
            left: 0px;
            top: 0px;
            position: relative;
            border-radius: 100%;
            border: thick solid #b06a42;
            font-size: 16px;
            font-weight: bold;
            color: rgb(13, 196, 89);
            box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);
            background-color: white !important;

        }

        .comprador-matutino {
            background-color: var(--fondo-m) !important;
            border-color: var(--border-m) !important;
        }

        .comprador-vespertino {
            background-color: var(--fondo-t) !important;
            border-color: var(--border-t) !important;
        }

        .comprador-corrido {
            background-color: var(--fondo-all) !important;
            border-color: var(--border-all) !important;
        }

        #modal-divide {
            display: none;
            position: fixed;
            background: white;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }


        .marker-route {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-pack: center;
            justify-content: center;
            -ms-flex-align: center;
            align-items: center;
            background-color: white !important;
            width: 3rem;
            height: 3rem;
            left: -1.5rem;
            top: -1.5rem;
            position: relative;
            border-radius: 100%;
            border: thick solid green;
            font-size: 16px;
            font-weight: bold;
            color: green;
            box-shadow: 2px 2px 4px rgba(0, 0, 0, .3)
        }

        .marker-polygon-edit {
            width: 1.5rem;
            height: 1.5rem;
            left: -0.75rem;
            top: -0.75rem;
            position: relative;
            border: thin solid black;
            color: black;
            background: rgba(0, 0, 0, .5);

        }

        .origin {
            border: thick solid red;
            color: red;
        }

        .destino {
            border: thick solid #FFD700 !important;
            color: #FFD700 !important;
            background-color: black !important;
            z-index: 100000;
        }

        .route-matutino {
            background-color: white;
            /*var(--fondo-m) !important;*/
            border-color: var(--fondo-m) !important;
            color: var(--border-m) !important;
        }

        .route-vespertino {
            background-color: white;
            /*var(--fondo-t) !important;*/
            border-color: orange !important;
            color: var(--border-t) !important;
        }

        .route-corrido {
            background-color: white;
            /*var(--fondo-all) !important;*/
            border-color: var(--fondo-all) !important;
            color: var(--border-all) !important;
        }

        #retiros_table>tbody>tr:hover {
            cursor: move;
            background: #EAF1F8
        }

        #box-top-center {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 50;
        }

        #count-retiros {

            padding: 5px 10px;
            font-size: 20px;
            color: white;
            background: #A60000;
            border-radius: 10px;
        }

        #max-count {
            width: 70px !important;
            height: 25px !important;
            margin-left: 10px;
            font-weight: bold
        }

        #print {
            padding: 5px 10px;
            font-size: 20px;
            color: white;
            border-radius: 10px;
            margin-left: 5px
        }

        #retiro-limit {

            padding: 5px 10px;
            font-size: 16px;
            color: white;
            background: #A60000;
            border-radius: 10px;
            margin-left: 5px
        }

        #travel {
            padding: 5px 10px;
            position: fixed;
            top: 120px;
            right: 20px;
            z-index: 50;
            background: white;
            border-radius: 5px;
            border: solid thin gray;
        }

        #travel span {
            margin: 0 5px;
        }

        #travel .route {
            width: 70px;
            height: 0;

        }

        .auto {
            border: solid 2px #0015FF
        }

        .bici {
            border: dashed 2px #86007B
        }

        .walk {
            border: dashed 2px #FF0000
        }

        .i-auto {
            color: #0015FF
        }

        .i-bici {
            color: #86007B
        }

        .i-walk {
            color: #FF0000
        }

        #franjas {
            padding: 5px 10px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 50;
            background: white;
            border-radius: 5px;
            border: solid thin gray;
        }

        .franja-horaria {
            border: solid medium;
            border-radius: 5px;
            font-size: 14px !important;
            /*font-weight: bold;*/
            color: black;
            padding: 1px 2px;
            margin: 2px;
        }

        #info-recorrido .alert {
            padding: 5px 10px;
            /*margin-left: 5px*/
        }



        .lbl-suc {
            position: absolute;
            left: 70px;
            top: 2px;
            z-index: 10
        }

        #modal-nogeo .fixed-table-loading {
            height: 430px;
        }

        #modal-nogeo .fixed-table-body {
            height: 350px;
        }

        #export-nogeo {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100
        }

        .info-lbl {
            padding: 5px !important;
            margin: 5px;
            font-size: 15px !important;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }


        .bootstrap-table table>thead>tr>th,
        .bootstrap-table table>tbody>tr>td {
            /*ajusta col al contenido*/
            white-space: nowrap;
        }

        #btn-independizar:before {
            content: ''
        }



        #loading-route {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10000000;
            border-radius: 20px;
            background: #D0FF00;
            box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);
            padding: 10px;
            font-size: 20px;
            color: black !important;
            display: none;
        }

        #loading-route span {
            margin-left: 10px;
        }

        #loading-route i {
            animation-name: loading;
            animation-iteration-count: infinite;
            animation-direction: normal;
            animation-duration: 1s;
        }

        .pac-container {
            z-index: 100000000 !important;
            /*permite visualizar la lista de resultados de busqueda de google al reasignar origen*/
        }

        .modal-polygon {
            min-width: 96%;
        }

        .swal-big {
            width: 1000px;
        }

        .transparent {
            background: transparent !important;
        }



        @media (min-width: 1150px) {
            .modal-xl {
                width: 1140px;
            }

            .modal-full {
                width: 98%;
            }
        }
    </style>

@stop

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <div class="">
                <div class="form-group">

                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div id="map" style="height: 725px;"></div>
        </div>
    </section>

    <script>
        function getRandomColor() { //funcion obtiene color aleatorio
            var letters = '0123456789ABCDEF';
            var color = '#';
            var ok = false;
            color += letters[Math.floor(Math.random() * 10)];
            while (!ok) {
                for (var i = 0; i < 5; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }
                if (!inArray(color, colores)) {
                    break;
                }
            }
            colores.push(color); //array para controlar que un color no se repita
            return color;
        }

        function getColor(d) {
            return d > 1000 ? '#800026' :
                d > 500 ? '#BD0026' :
                d > 200 ? '#E31A1C' :
                d > 100 ? '#FC4E2A' :
                d > 50 ? '#FD8D3C' :
                d > 20 ? '#FEB24C' :
                d > 10 ? '#FED976' :
                '#FFEDA0';
        }

        var zoom = 13;
        var mymap = L.map('map', {
            editable: true,
            zoomControl: false
        }).setView(new L.LatLng(-31.75899, -60.47825), zoom);


        //atlas: https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey={{ env('API_KEY_THUNDER_FOREST_MAP') }}
        //dark: https://tile.thunderforest.com/transport-dark/{z}/{x}/{y}.png?apikey={{ env('API_KEY_THUNDER_FOREST_MAP') }}
        L.tileLayer('https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey=' +
            '{{ env('API_KEY_THUNDER_FOREST_MAP') }}', {
                attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mymap);
        var markersGroup = L.layerGroup().addTo(mymap);

        searchControl = new L.esri.Controls.Geosearch({
            position: 'topleft'
        }).addTo(mymap);
        var geocodeService = new L.esri.Services.Geocoding();
        var markerResultado = null;

        searchControl.on('results', function(data) {
            if (markerResultado) {
                mymap.removeLayer(markerResultado);
            }
            var result = data.results[0];
            var location = result.latlng;
            markerResultado = L.marker(location).addTo(mymap);
            markerResultado.bindPopup(result.text);
        });

        mymap.on('click', function(event) {
            if (markerResultado) {
                mymap.removeLayer(markerResultado);
            }
        });

        var fullscreenButton = L.control({
            position: 'bottomright'
        });
        var isFullscreen = false;

        fullscreenButton.onAdd = function(map) {
            var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<div id="fullscreen-button"><i class="fas fa-expand"></i></div>';
            div.firstChild.addEventListener('click', function() {
                var element = map.getContainer(); // Obtener el contenedor del mapa
                if (!isFullscreen) {
                    if (element.requestFullscreen) {
                        element.requestFullscreen();
                    } else if (element.mozRequestFullScreen) {
                        element.mozRequestFullScreen();
                    } else if (element.webkitRequestFullscreen) {
                        element.webkitRequestFullscreen();
                    } else if (element.msRequestFullscreen) {
                        element.msRequestFullscreen();
                    }
                    isFullscreen = true;
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                    isFullscreen = false;
                    div.firstChild.innerHTML = '<i class="fas fa-expand"></i>'; // Cambiar el texto del botón
                }
            });
            return div;
        };
        fullscreenButton.addTo(mymap);

        function actualizarLeyenda(cantidadServicios) {
            // Actualizar el contenido del elemento con ID 'leyenda'
            var leyendaElement = document.getElementById('leyenda');
            leyendaElement.innerHTML = '<strong> Se encontraron ' + cantidadServicios + ' servicios.</strong>';
        }

        function cargarRecursos() {
            // Realizar una solicitud AJAX al servidor para obtener los recursos actualizados
            $.ajax({
                    url: '/getRecursosCecoco',
                    method: 'GET',
                    success: function(data) {
                        console.log('recursos', data);
                        // Limpiar los marcadores existentes en el mapa
                        markersGroup.clearLayers();

                        // Iterar sobre los nuevos recursos y agregar marcadores al mapa
                        data.recursos.forEach(function(recurso) {
                            var latitud = recurso.latitud_decimal;
                            var longitud = recurso.longitud_decimal;
                            var icono;
                            switch (recurso.tipo_recurso.toLowerCase()) {
                                case 'portatil seguridad banc':
                                case 'portatil':
                                    icono = '<i class="fas fa-male"></i>';
                                    break;
                                case 'auto cria':
                                case 'auto':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-car"></i>';
                                    break;
                                case 'moto comando patrulla':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-motorcycle"></i>';
                                    break;
                                case 'camioneta':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-truck-pickup"></i>';
                                    break;
                                default:
                                    icono =
                                        '<i class="fas fa-question"></i>'; // Ícono predeterminado para casos no especificados
                            }
                            var marker = L.marker([latitud, longitud]).addTo(markersGroup);
                            // Puedes personalizar el marcador según tus necesidades
                            marker.setIcon(L.divIcon({
                                className: 'custom-marker', // Puedes agregar tus propias clases CSS
                                html: '<div class="marker-label">' +
                                    icono + // Utiliza el ícono seleccionado
                                    '<div class="marker-name" style="color: #ff0000;">' +
                                    recurso.nombre + '</div>' +
                                    '</div>',
                                iconSize: [50, 50],
                                iconAnchor: [15, 50],
                                popupAnchor: [0, -50]
                            }));
                            marker.bindPopup('Móvil: ' + recurso.nombre + '<br> ISSI:' + recurso
                                .medios_nombre);

                        });
                        data.servicios.forEach(function(servicio) {
                            var latitud = servicio.latitud;
                            var longitud = servicio.longitud;
                            switch (servicio.tipo_servicio.toLowerCase()) {
                                case 'robo calificado en proceso':
                                case 'robo en proceso':
                                    icono = '<i class="fas fa-bomb"></i>';
                                    break;
                                case 'accidente':
                                case 'accidente de transito':
                                case 'accidente de transito con fallecido':
                                case 'accidente de transito con lesionados':
                                case 'accidente de transito sin lesionados':
                                case 'accidente domestico':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-car-crash"></i>';
                                    break;
                                case 'moto comando patrulla':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-motorcycle"></i>';
                                    break;
                                case 'camioneta':
                                    // Puedes agregar más casos según los tipos de recurso que tengas
                                    icono = '<i class="fas fa-truck-pickup"></i>';
                                    break;
                                default:
                                    icono =
                                        '<i class="fas fa-exclamation-triangle"></i>'; // Ícono predeterminado para casos no especificados
                            }
                            var marker = L.marker([latitud, longitud]).addTo(markersGroup);
                            // Puedes personalizar el marcador según tus necesidades
                            marker.setIcon(L.divIcon({
                                className: 'custom-marker', // Puedes agregar tus propias clases CSS
                                html: '<div class="marker-label">' +
                                    icono + // Utiliza el ícono seleccionado
                                    '<div class="marker-name" style="color: #ff0000;">' +
                                    servicio.tipo_servicio + '</div>' +
                                    '</div>',
                                iconSize: [50, 50],
                                iconAnchor: [15, 50],
                                popupAnchor: [0, -50]
                            }));
                            marker.bindPopup('Servicio: ' + servicio.tipo_servicio);
                        });
                },
                error: function(error) {
                    console.error('Error al cargar recursos:', error);
                },
                complete: function() {
                    // Programar la próxima carga después de 10 segundos
                    setTimeout(cargarRecursos, 10000);
                }
            });
        }

        // Iniciar la carga de recursos al cargar la página
        cargarRecursos();
    </script>
@endsection
