@extends('layouts.app')

@section('css')

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="{{ asset('/plugins/JQueryUi/jquery-ui.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/leafletjs/geocoder/geocoder.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-dialog/bootstrap-dialog.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/bootstrap-toggle/bootstrap-toggle.min.css') }}" rel="stylesheet">


    <link href="{{ asset('/plugins/leafletjs/lib/leaflet-dist/leaflet.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/leafletjs/src/Icon.Label.css') }}" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.4.2/leaflet.draw.css"/> -->

    <!-- Link de respuesta LOCAL -->
    <link href="{{ asset('/plugins/bootstrap-table/bootstrap-table.css') }}" rel="stylesheet">
    <link href="{{ asset('/plugins/view-mapas-plugins/css/css-dispositivos.css') }}" rel="stylesheet" type="text/css" />

    <link href="{{ asset('/plugins/cluster/MarkerCluster.css') }}" rel="stylesheet" />
    <link href="{{ asset('/plugins/cluster/MarkerCluster.Default.css') }}" rel="stylesheet" />
    <link href="{{ asset('/plugins/view-mapas-plugins/css/label.css') }}" rel="stylesheet" type="text/css" />
    <!-- Bootstrap DatePicker -->
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <!-- <link href="{{ asset('/plugins/bootstrap-table/bootstrap-table-reorder-rows.css') }}" rel="stylesheet"> -->
    <link href="{{ asset('/css/jquery.modalLink-1.0.0.css') }}" rel="stylesheet">

    <style>
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
        <div class="section-header">
            <h3 class="page__heading">Mapa</h3>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div id="map" style="height: 600px;"></div>
            </div>
        </div>
    </section>

    <script>
        var zoom = 17;
        var mymap = L.map('map').setView(new L.LatLng(-31.74275, -60.51827), zoom);

        /*var Icono = L.icon({
            //iconUrl: "https://w7.pngwing.com/pngs/825/135/png-transparent-red-location-icon-google-maps-pin-google-map-maker-google-s-heart-map-location.png",
            iconUrl: "/img/marker.png",
            iconSize: [40, 40],
            iconAnchor: [15, 40],
            shadowSize: [35, 50],
            shadowAnchor: [0, 55],
            popupAnchor: [0, -40]
        });*/

        var capa1 = L.layerGroup();
        var capa2 = L.geoJSON();


        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
            //'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZW1kZXYiLCJhIjoiY2xlajRsbWpiMDdhNDNvbno1dmQzNW5xbSJ9.WOvCqAED6IUBsKukVJTJkg', {
            //attribution: 'Map data © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> ',
            //maxZoom: 18,
            //id: 'mapbox/streets-v11',
            //id: 'mapbox/outdoors-v11',
            //id: 'mapbox/light-v10',
            //id: 'mapbox/dark-v10',
            //id: 'mapbox/satellite-v9',
            //id: 'mapbox/satellite-streets-v11',
        }).addTo(mymap);

        @foreach ($comisarias as $marcador)
            var numero = "{{ $marcador['numero'] }}";
            console.log("marcador", numero);

            var markerIcon = L.divIcon({
                className: 'transparent',
                labelAnchor: [0, 0],
                popupAnchor: [0, 0],
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                html: '<div class="marker-comprador">' + numero + '</div>'
            });
            L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
                    icon: markerIcon
                    //}).addTo(mymap)
                }).addTo(capa1)
                .bindPopup("{{ $marcador['titulo'] }}");
        @endforeach

        @foreach ($camaras as $marcador)
            var numero = "{{ $marcador['numero'] }}";
            console.log("marcador", numero);

            var markerIcon = L.divIcon({
                className: 'transparent',
                labelAnchor: [0, 0],
                popupAnchor: [0, 0],
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                html: '<div class="marker-camara">' + numero + '</div>'
            });
            L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
                    icon: markerIcon
                    //}).addTo(mymap)
                }).addTo(capa2)
                .bindPopup("{{ $marcador['titulo'] }}");
        @endforeach

        // Crear el control de capas
        var controlCapas = L.control.layers({
            'Comisarias': capa1,
            'Camaras': capa2
        }).addTo(mymap);

        // Crear botón para ocultar/mostrar capa
        var botonCapa2 = L.easyButton('fa-eye-slash', function() {
            if (mymap.hasLayer(capa2)) {
                mymap.removeLayer(capa2);
            } else {
                mymap.addLayer(capa2);
            }
        }).addTo(mymap);


        /*for (var i = 0; i < markers.length; i++) {
            marker = new L.marker([markers[i][1], markers[i][2]], {icon: Icono})
                .bindPopup(markers[i][0])
                .addTo(mymap);
        }*/

        /*var marker = L.marker([-31.733611, -60.520276],{
            title: "Exe",
            icon: Icono
        }).addTo(mymap);*/
        //mymap.setView(new L.LatLng(-31.736579, -60.524606), 13);
    </script>
@endsection
