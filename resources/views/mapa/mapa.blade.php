@extends('layouts.app')

@section('css')

<!-- <link href="{{ asset('/plugins/bootstrap-table/bootstrap-table-reorder-rows.css') }}" rel="stylesheet"> -->

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

    /*.etiqueta {
                                                                                                                                                        position: absolute;
                                                                                                                                                        top: 50px;

                                                                                                                                                        left: 50%;
                                                                                                                                                        transform: translateX(-50%);
                                                                                                                                                    }*/




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

    /* Estilos para el control personalizado de capas */
    .custom-layer-control {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        font-size: 12px;
        max-width: 250px;
        font-family: Arial, sans-serif;
    }

    .custom-layer-control h6 {
        margin: 0 0 10px 0;
        font-weight: bold;
        color: #333;
    }

    .layer-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        padding: 5px 0;
    }

    .layer-item:last-child {
        margin-bottom: 0;
    }

    /* Switch CSS */
    .switch {
        position: relative;
        display: inline-block;
        width: 34px;
        height: 18px;
        margin-right: 8px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 12px;
        width: 12px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #007bff;
    }

    input:checked+.slider:before {
        transform: translateX(16px);
    }

    .layer-label {
        font-size: 12px;
        color: #333;
        cursor: pointer;
        user-select: none;
    }

    /* Diferentes colores para diferentes tipos de capas */
    .switch input:checked+.slider.comisarias {
        background-color: #28a745;
    }

    .switch input:checked+.slider.camaras {
        background-color: #007bff;
    }

    .switch input:checked+.slider.camaras-tipo {
        background-color: #fd7e14;
    }

    .switch input:checked+.slider.antenas {
        background-color: #6f42c1;
    }

    .switch input:checked+.slider.sitios {
        background-color: #dc3545;
    }

    /* Hacer el control colapsible */
    .layer-control-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .layer-control-toggle {
        font-size: 18px;
        transition: transform 0.3s;
    }

    .layer-control-toggle.collapsed {
        transform: rotate(-90deg);
    }

    .layer-control-content {
        max-height: 400px;
        overflow-y: auto;
        transition: max-height 0.3s ease-out;
    }

    .layer-control-content.hidden {
        max-height: 0;
        overflow: hidden;
    }

    #map:fullscreen .custom-layer-control {
        position: absolute !important;
        top: 10px;
        right: 10px;
        z-index: 10000 !important;
    }
</style>

@stop

@section('content')
    <section class="section">
        <div
            class="section-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div class="w-100">
                <div class="form-group">
                    <select name="camara_select" id="camara_select" class="form-control select2 mb-3">
                        <option value="">Buscar cámara</option>
                        @foreach ($camaras as $camara)
                            <option value="{{ $camara['numero'] }}" data-lat="{{ $camara['latitud'] }}"
                                data-lng="{{ $camara['longitud'] }}">
                                {{ $camara['titulo'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Línea 1: Cámaras por tipo -->
                <div class="mt-2">
                    <h6>Cámaras por tipo</h6>
                    <div class="d-flex flex-wrap">
                        <span class="badge badge-info p-2 m-1">Fijas {{ $fijas }}</span>
                        <span class="badge badge-warning p-2 m-1">Fijas FR {{ $fijasFR }}</span>
                        <span class="badge badge-danger p-2 m-1">Fijas LPR {{ $fijasLPR }}</span>
                        <span class="badge badge-success p-2 m-1">Domos {{ $domos }}</span>
                        <span class="badge badge-primary p-2 m-1">Domos Duales {{ $domosDuales }}</span>
                        <span class="badge badge-dark p-2 m-1">Cámaras {{ $total }}</span>
                        <span class="badge badge-dark p-2 m-1">Canales {{ $canales }}</span>
                    </div>
                </div>
                <!-- Línea 2: Cámaras por ciudad -->
                <div class="mt-2">
                    <h6>Cámaras por ciudad</h6>
                    <div class="d-flex flex-wrap">
                        <span class="badge badge-info p-2 m-1">Paraná: {{ $camarasParana }}</span>
                        <span class="badge badge-warning p-2 m-1">Cnia. Avellaneda: {{ $camarasCniaAvellaneda }}</span>
                        <span class="badge badge-danger p-2 m-1">San Benito: {{ $camarasSanBenito }}</span>
                        <span class="badge badge-success p-2 m-1">Oro Verde: {{ $camarasOroVerde }}</span>
                    </div>
                </div>
                <!-- Línea 3: Sitios por ciudad -->
                <div class="mt-2">
                    <h6>Sitios</h6>
                    <div class="d-flex flex-wrap">
                        <span class="badge badge-info p-2 m-1">Paraná: {{ $sitiosParana }}</span>
                        <span class="badge badge-warning p-2 m-1">Cnia. Avellaneda: {{ $sitiosCniaAvellaneda }}</span>
                        <span class="badge badge-danger p-2 m-1">San Benito: {{ $sitiosSanBenito }}</span>
                        <span class="badge badge-success p-2 m-1">Oro Verde: {{ $sitiosOroVerde }}</span>
                        <span class="badge badge-dark p-2 m-1">Total sitios: {{ $cantidadSitios }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <form action="{{ route('mapa.exportar') }}" method="GET" style="display: inline;">
                        <button type="submit" class="btn btn-primary">Exportar Listado Cámaras</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- El mapa -->
        <div class="col-lg-12 mt-3">
            <div id="map" style="height: 625px; position: relative;">
                <!-- Agregar este HTML después del div del mapa -->
                <div class="custom-layer-control" id="customLayerControl">
                    <div class="layer-control-header" onclick="toggleLayerControl()">
                        <h6>Control de Capas</h6>
                        <span class="layer-control-toggle" id="layerControlToggle">▼</span>
                    </div>
                    <div class="layer-control-content" id="layerControlContent">
                        <!-- Cámaras (todas) -->
                        @can('ver-camara')
                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras" checked
                                        onchange="toggleLayer('camaras', this.checked)">
                                    <span class="slider camaras"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras')">Todas las Cámaras</span>
                            </div>
                        @endcan

                        <!-- Comisarías -->
                        @can('ver-dependencia')
                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-comisarias"
                                        onchange="toggleLayer('comisarias', this.checked)">
                                    <span class="slider comisarias"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-comisarias')">Comisarías</span>
                            </div>
                        @endcan

                        <!-- Cámaras por tipo -->
                        @can('ver-camara')
                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras-fijas"
                                        onchange="toggleLayer('camaras-fijas', this.checked)">
                                    <span class="slider camaras-tipo"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras-fijas')">Cámaras Fijas</span>
                            </div>

                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras-fr"
                                        onchange="toggleLayer('camaras-fr', this.checked)">
                                    <span class="slider camaras-tipo"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras-fr')">Cámaras FR</span>
                            </div>

                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras-lpr"
                                        onchange="toggleLayer('camaras-lpr', this.checked)">
                                    <span class="slider camaras-tipo"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras-lpr')">Cámaras LPR</span>
                            </div>

                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras-domos"
                                        onchange="toggleLayer('camaras-domos', this.checked)">
                                    <span class="slider camaras-tipo"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras-domos')">Cámaras Domos</span>
                            </div>

                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-camaras-domos-duales"
                                        onchange="toggleLayer('camaras-domos-duales', this.checked)">
                                    <span class="slider camaras-tipo"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-camaras-domos-duales')">Cámaras Domos
                                    Duales</span>
                            </div>
                        @endcan

                        <!-- Sitios Inactivos -->
                        <div class="layer-item">
                            <label class="switch">
                                <input type="checkbox" id="switch-sitios" onchange="toggleLayer('sitios', this.checked)">
                                <span class="slider sitios"></span>
                            </label>
                            <span class="layer-label" onclick="toggleSwitch('switch-sitios')">Sitios Inactivos</span>
                        </div>

                        <!-- Antenas -->
                        @can('ver-dependencia')
                            <div class="layer-item">
                                <label class="switch">
                                    <input type="checkbox" id="switch-antenas" onchange="toggleLayer('antenas', this.checked)">
                                    <span class="slider antenas"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch('switch-antenas')">Antenas</span>
                            </div>
                        @endcan

                        <!-- Botón para limpiar -->
                        <div class="layer-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <button class="btn btn-sm btn-secondary" onclick="clearAllLayers()" style="width: 100%;">
                                <i class="fas fa-eraser"></i> Limpiar Todo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <script>
        // Función para redirigir a la ruta de edición
        function editCamera(camaraId) {
            @can('editar-camara')
                window.location.href = '/camaras/' + camaraId + '/edit';
            @endcan
                                }

        function openGoogleMaps(latitud, longitud) {
            // Abre Google Maps en una nueva pestaña con la ubicación especificada
            window.open(`https://www.google.com/maps?q=${latitud},${longitud}`, '_blank');
        }

        function openStreetView(latitud, longitud) {
            // Abre Google Maps en una nueva pestaña con el enlace directo a Street View
            window.open(`https://www.google.com/maps?q=&layer=c&cbll=${latitud},${longitud}`, '_blank');
        }

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

        // Maneja el evento de cambio en el elemento select
        $(document).ready(function () {
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
            $('#camara_select').on('change', function () {
                // Obtén el elemento select y la opción seleccionada
                var selectElement = this;
                var selectedOption = selectElement.options[selectElement.selectedIndex];

                // Imprime la información de la opción seleccionada en la consola
                console.log('item_select', selectedOption);

                // Obtiene las coordenadas de los atributos de datos del elemento seleccionado
                var lat = parseFloat(selectedOption.getAttribute('data-lat'));
                var lng = parseFloat(selectedOption.getAttribute('data-lng'));

                // Centra y hace zoom en el mapa a las coordenadas seleccionadas
                mymap.setView([lat, lng], 20);
            });
        });

        var marcadores = L.markerClusterGroup();
        var markersCamarasLPR = L.markerClusterGroup();
        var markersCamarasFR = L.markerClusterGroup();
        var markersCamarasFijas = L.markerClusterGroup();
        var markersCamarasDomos = L.markerClusterGroup();
        var markersCamarasDomosDuales = L.markerClusterGroup();
        var marcadoresSitios = L.markerClusterGroup();
        var capaSitios = L.layerGroup();
        var capa1 = L.layerGroup();
        var capa2 = L.geoJSON();
        var capa3 = L.geoJSON();
        var capa4 = L.layerGroup();
        var capa5 = L.layerGroup();
        var capaLPR = L.layerGroup();
        var capaFR = L.layerGroup();
        var capaFija = L.layerGroup();
        var capaDomo = L.layerGroup();
        var capaDomoDual = L.layerGroup();

        // Define las capas para el mapa común y el híbrido
        var mapaComun = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        });

        var mapaHibrido = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        });
        // Añade el mapa común por defecto
        mymap.addLayer(mapaComun);
        // Estado inicial del mapa
        var esHibrido = false;
        // Crear el botón y posicionarlo en la parte inferior izquierda
        var toggleControl = L.control({
            position: 'bottomleft'
        });

        toggleControl.onAdd = function () {
            var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
            div.innerHTML = '<button id="toggleMapBtn" class="btn btn-primary">Mapa Satelital</button>';
            div.style.backgroundColor = 'white';
            div.style.padding = '5px';
            // Previene que el mapa se desplace cuando haces clic en el botón
            L.DomEvent.disableClickPropagation(div);
            return div;
        };

        // Añadir el control al mapa
        toggleControl.addTo(mymap);
        // Maneja el evento de clic en el botón toggle
        document.getElementById('toggleMapBtn').addEventListener('click', function () {
            if (esHibrido) {
                // Cambia al mapa común
                mymap.removeLayer(mapaHibrido);
                mymap.addLayer(mapaComun);
                this.textContent = 'Mapa Satelital'; // Cambia el texto del botón
            } else {
                // Cambia al mapa híbrido
                mymap.removeLayer(mapaComun);
                mymap.addLayer(mapaHibrido);
                this.textContent = 'Mapa Común'; // Cambia el texto del botón
            }
            esHibrido = !esHibrido; // Alterna el estado
        });

        var etiquetaControl = L.control({
            position: 'topright' /*'topright'*/ /*'bottomright'*/
        });

        searchControl = new L.esri.Controls.Geosearch({
            position: 'topleft'
        }).addTo(mymap);
        var geocodeService = new L.esri.Services.Geocoding();
        var markerResultado = null;

        // Manejar el evento de resultado de búsqueda
        searchControl.on('results', function (data) {
            if (markerResultado) {
                mymap.removeLayer(markerResultado);
            }
            var result = data.results[0];
            var location = result.latlng;
            markerResultado = L.marker(location).addTo(mymap);
            markerResultado.bindPopup(result.text);
        });

        // Manejar el evento de clic en el mapa
        mymap.on('click', function (event) {
            if (markerResultado) {
                mymap.removeLayer(markerResultado);
            }
        });

        etiquetaControl.onAdd = function (mymap) {
            /*var div = L.DomUtil.create('div', 'etiqueta');
            div.innerHTML = 'Texto de la etiqueta';

            return div;*/
            var div = L.DomUtil.create('div', 'info legend'),
                grades = [0, 10, 20, 50, 100, 200, 500, 1000],
                labels = [];

            // loop through our density intervals and generate a label with a colored square for each interval
            for (var i = 0; i < grades.length; i++) {
                div.innerHTML +=
                    '<i style="background:' + getColor(grades[i] + 1) + '"></i> ' +
                    grades[i] + (grades[i + 1] ? '&ndash;' + grades[i + 1] + '<br>' : '+');
            }

            return div;
        };

        //etiquetaControl.addTo(mymap);

        @foreach ($comisarias as $marcador)
            var numero = "{{ $marcador['numero'] }}";
            console.log("comisaria", numero);

            var markerIcon = L.divIcon({
                className: 'transparent',
                labelAnchor: [0, 0],
                popupAnchor: [0, 0],
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                html: '<div class="marker-comprador">' + numero + '</div>'
            });
            var marker = L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
                icon: markerIcon
            }).addTo(capa1).addTo(capa5)
                .bindPopup("{{ $marcador['titulo'] }}");
            //marcadores.addLayer(marker);
        @endforeach

        var colores = ['black', 'red', 'blue', 'purple', 'brown', 'orange', 'yellow']
        //var colores = []
        @foreach ($jurisdicciones as $jurisdiccion)
            var indiceAleatorio = Math.floor(Math.random() * colores.length);
            var colorAleatorio = colores[indiceAleatorio];
            var coordenadas =
                @if ($jurisdiccion)
                    {!! json_encode($jurisdiccion) !!}
                @else
                []
            @endif ;
            console.log("jur", coordenadas)
            var objeto = JSON.parse(coordenadas.jurisdiccion)
            console.log("jurJSON", objeto)

            var polygonCoords = [];
            for (let i = 0; i < objeto.length; i++) {
                let coord = [objeto[i].lat, objeto[i].lng]; // Creamos un arreglo [latitud, longitud]
                polygonCoords.push(coord); // Agregamos el arreglo al arreglo de coordenadas del polígono
                console.log("coordenada", objeto[i].lat);

                //Para mostrar las coordenadas en cada punto del poligono
                /*var lat = objeto[i].lat;
                var lng = objeto[i].lng;
                var markerPunto = L.marker([objeto[i].lat, objeto[i].lng], {

                }) //.addTo(capa2)
                .bindPopup(lat + "," + lng);
                markerPunto.addTo(mymap);
                -------------------------------------------------------------*/

            }
            console.log("coord", polygonCoords);
            var polygon = L.polygon(polygonCoords).setStyle({
                fillColor: colorAleatorio,
                fillOpacity: 0.15,
                color: 'black',
                weight: 2
            })
                .addTo(capa1).addTo(capa5);
            //.addTo(mymap);

            //------------- Para mostrar poligono editable -----------------------------------------------------------
            /*var poligonoEditable = L.polygon(polygonCoords).setStyle({
                    color: 'black',
                    weight: 1
                })
                //.addTo(capa1).addTo(capa5);
                .addTo(mymap);
            poligonoEditable.enableEdit();
            poligonoEditable.on('editable:editing', function(e) {
                var editedPolygon = e.layer;
                //var newCoords = editedPolygon.getLatLngs()[0]; // Obtener las nuevas coordenadas
                var polygonCoords = editedPolygon.getLatLngs()[0];
                var newCoords = [];
                for (var i = 0; i < polygonCoords.length; i++) {
                    var coord = {
                        lat: polygonCoords[i].lat,
                        lng: polygonCoords[i].lng
                    };
                    newCoords.push(coord);
                }
                var jsonCoords = JSON.stringify(newCoords);
                console.log('nuevas coord', jsonCoords);
                // Aquí puedes hacer una llamada AJAX o enviar las coordenadas al backend para guardar los cambios
                // Ejemplo de una llamada AJAX usando jQuery:
                $.ajax({
                    url: '/guardar-coordenadas',
                    method: 'POST',
                    data: {
                        coordinates: newCoords
                    },
                    success: function(response) {
                        console.log('Cambios guardados con éxito');
                    },
                    error: function(error) {
                        console.error('Error al guardar los cambios:', error);
                    }
                });
            });*/
            //console("poligon", polygon)
            //-----------------------------------------------------------------------------------------------------------------------
        @endforeach

        @foreach ($camaras as $marcador)
            var numero = "{{ $marcador['numero'] }}";
            var latitud = "{{ $marcador['latitud'] }}";
            var longitud = "{{ $marcador['longitud'] }}";
            console.log("camaras", numero);
            var cameraIcon = L.icon({
                iconUrl: "{{ $marcador['imagen'] }}", //(tipo.includes("Fija")) ? "/img/cctv_icon.png" : "/img/domo_icon.png",
                iconSize: [50, 50],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            });
            var marker = L.marker([latitud, longitud], {
                icon: cameraIcon
            }).bindPopup(`
                                                                                        <div>
                                                                                            <img src='{{ $marcador['imagen'] }}' alt="" style="max-width: 200px;">
                                                                                            <h5>{{ $marcador['titulo'] }}</h5>
                                                                                            Tipo: <b>{{ $marcador['tipo_camara'] }}</b><br>
                                                                                            Sitio: <b>{{ $marcador['sitio'] }}</b><br>
                                                                                            Señalizado: <b>{{ $marcador['cartel'] ? 'SI' : 'NO' }}</b><br>
                                                                                            Dependencia: <b>{{ $marcador['dependencia'] }}</b><br>
                                                                                            Etapa: <b>{{ $marcador['etapa'] }}</b><br>
                                                                                            Instalación: <b>{{ $marcador['fecha_instalacion'] }}</b><br>
                                                                                            Inteligencia: <b>{{ $marcador['inteligencia'] }}</b><br>
                                                                                            Marca: <b>{{ $marcador['marca'] }}</b> - Mod.: <b>{{ $marcador['modelo'] }}</b><br>
                                                                                            Nº serie: <b>{{ $marcador['nro_serie'] }}</b><br>
                                                                                            <div class="btn-group" role="group">
                                                                                                <button class="btn btn-icon btn-primary" title="Editar cámara" onclick="editCamera(${numero})"><i class="fas fa-edit"></i></button>
                                                                                                <button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(${latitud}, ${longitud})"><i class="fas fa-globe-americas"></i></button>
                                                                                                <button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(${latitud}, ${longitud})"><i class="fas fa-street-view"></i></button>
                                                                                            </div>
                                                                                        </div>
                                                                                    `);
            //.bindPopup("{{ $marcador['titulo'] }}<br>{{ $marcador['tipo_camara'] }}<br>{{ $marcador['inteligencia'] }}");
            marcadores.addLayer(marker);
            var tipo_camara = "{{ $marcador['tipo_camara'] }}";
            if (tipo_camara === "Fija - LPR" || tipo_camara === "Fija - LPR AV" || tipo_camara === "Fija - LPR NV") {
                markersCamarasLPR.addLayer(marker)
            } else if (tipo_camara === "Fija - FR") {
                markersCamarasFR.addLayer(marker)
            } else if (tipo_camara === "Fija") {
                markersCamarasFijas.addLayer(marker)
            } else if (tipo_camara === "Domo") {
                markersCamarasDomos.addLayer(marker)
            } else if (tipo_camara === "Domo Dual") {
                markersCamarasDomosDuales.addLayer(marker)
            }
        @endforeach
        marcadores.addTo(capa2).addTo(capa5);
        markersCamarasLPR.addTo(capaLPR);
        markersCamarasFR.addTo(capaFR);
        markersCamarasFijas.addTo(capaFija);
        markersCamarasDomos.addTo(capaDomo);
        markersCamarasDomosDuales.addTo(capaDomoDual);

        @foreach ($antenas as $marcador)
            var numero = "{{ $marcador['numero'] }}";
            console.log("antenas", numero);

            var antenaIcon = L.icon({
                iconUrl: "/img/antena_icon.png",
                iconSize: [40, 40],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            });
            var marker = L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
                icon: antenaIcon
                //}).addTo(mymap)
            }).addTo(capa3) //.addTo(capa5)
                .bindPopup("{{ $marcador['titulo'] }}");
            //marcadores.addLayer(marker);
        @endforeach

        @foreach ($sitios as $sitio)
            @if($sitio['activo'] == 0) //Solo mostrar sitios inactivos
                var numeroSitio = "{{ $sitio['numero'] }}";
                var latitudSitio = "{{ $sitio['latitud'] }}";
                var longitudSitio = "{{ $sitio['longitud'] }}";
                console.log("sitio inactivo", numeroSitio);

                // Crear icono personalizado para sitio inactivo (círculo rojo con X)
                var sitioInactivoIcon = L.divIcon({
                    className: 'transparent',
                    labelAnchor: [0, 0],
                    popupAnchor: [0, -15],
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                    html: '<div style="width: 30px; height: 30px; background-color: #dc3545; border: 2px solid #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-times" style="color: white; font-size: 16px;"></i></div>'
                });

                var markerSitio = L.marker([latitudSitio, longitudSitio], {
                    icon: sitioInactivoIcon
                }).bindPopup(`
                                                                                            <div>
                                                                                                <h5>{{ $sitio['titulo'] }}</h5>
                                                                                                <strong>Estado:</strong> <span style="color: #dc3545;">INACTIVO</span><br>
                                                                                                @if(isset($sitio['cartel']))
                                                                                                    <strong>Cartel:</strong> <b>{{ $sitio['cartel'] ? 'SI' : 'NO' }}</b><br>
                                                                                                @endif
                                                                                                <div class="btn-group" role="group">
                                                                                                <button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(${latitudSitio}, ${longitudSitio})"><i class="fas fa-globe-americas"></i></button>
                                                                                                <button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(${latitudSitio}, ${longitudSitio})"><i class="fas fa-street-view"></i></button>
                                                                                            </div>
                                                                                            </div>
                                                                                        `);

                marcadoresSitios.addLayer(markerSitio);
            @endif
        @endforeach
        marcadoresSitios.addTo(capaSitios);

        // Estado de las capas
        let layerStates = {
            comisarias: false,
            camaras: true, // Iniciamos con cámaras activas
            'camaras-fijas': false,
            'camaras-fr': false,
            'camaras-lpr': false,
            'camaras-domos': false,
            'camaras-domos-duales': false,
            antenas: false,
            sitios: false,
            'camaras-comisarias': false
        };

        // Función para alternar el control de capas
        function toggleLayerControl() {
            const content = document.getElementById('layerControlContent');
            const toggle = document.getElementById('layerControlToggle');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                toggle.classList.remove('collapsed');
                toggle.textContent = '▼';
            } else {
                content.classList.add('hidden');
                toggle.classList.add('collapsed');
                toggle.textContent = '▶';
            }
        }

        // Función para alternar un switch al hacer clic en el label
        function toggleSwitch(switchId) {
            const switchElement = document.getElementById(switchId);
            switchElement.checked = !switchElement.checked;
            switchElement.dispatchEvent(new Event('change'));
        }

        // Función principal para manejar las capas
        function toggleLayer(layerType, isChecked) {
            // Actualizar el estado
            layerStates[layerType] = isChecked;

            // Lógica especial para las cámaras
            if (layerType === 'camaras') {
                // Si activamos "Todas las Cámaras", desactivar las específicas
                if (isChecked) {
                    deactivateCameraSpecificLayers();
                    mymap.addLayer(capa2);
                } else {
                    mymap.removeLayer(capa2);
                }
            } else if (layerType.startsWith('camaras-') && layerType !== 'camaras-comisarias') {
                // Si activamos una cámara específica, desactivar "Todas las Cámaras"
                if (isChecked) {
                    deactivateGeneralCameraLayer();
                    activateSpecificCameraLayer(layerType);
                } else {
                    deactivateSpecificCameraLayer(layerType);
                }
            } else {
                // Para otras capas (no cámaras)
                switch (layerType) {
                    case 'comisarias':
                        if (isChecked) {
                            mymap.addLayer(capa1);
                        } else {
                            mymap.removeLayer(capa1);
                        }
                        break;
                    case 'antenas':
                        if (isChecked) {
                            mymap.addLayer(capa3);
                        } else {
                            mymap.removeLayer(capa3);
                        }
                        break;
                    case 'sitios':
                        if (isChecked) {
                            mymap.addLayer(capaSitios);
                        } else {
                            mymap.removeLayer(capaSitios);
                        }
                        break;
                    case 'camaras-comisarias':
                        if (isChecked) {
                            // Desactivar otras capas de cámaras
                            deactivateAllCameraLayers();
                            mymap.addLayer(capa5);
                        } else {
                            mymap.removeLayer(capa5);
                        }
                        break;
                }
            }
        }

        // Función para desactivar la capa general de cámaras
        function deactivateGeneralCameraLayer() {
            if (layerStates.camaras) {
                layerStates.camaras = false;
                document.getElementById('switch-camaras').checked = false;
                mymap.removeLayer(capa2);
            }
        }

        // Función para desactivar capas específicas de cámaras
        function deactivateCameraSpecificLayers() {
            const cameraTypes = ['camaras-fijas', 'camaras-fr', 'camaras-lpr', 'camaras-domos', 'camaras-domos-duales'];

            cameraTypes.forEach(type => {
                if (layerStates[type]) {
                    layerStates[type] = false;
                    document.getElementById(`switch-${type}`).checked = false;
                    deactivateSpecificCameraLayer(type);
                }
            });
        }

        // Función para activar una capa específica de cámaras
        function activateSpecificCameraLayer(layerType) {
            switch (layerType) {
                case 'camaras-fijas':
                    mymap.addLayer(capaFija);
                    break;
                case 'camaras-fr':
                    mymap.addLayer(capaFR);
                    break;
                case 'camaras-lpr':
                    mymap.addLayer(capaLPR);
                    break;
                case 'camaras-domos':
                    mymap.addLayer(capaDomo);
                    break;
                case 'camaras-domos-duales':
                    mymap.addLayer(capaDomoDual);
                    break;
            }
        }

        // Función para desactivar una capa específica de cámaras
        function deactivateSpecificCameraLayer(layerType) {
            switch (layerType) {
                case 'camaras-fijas':
                    mymap.removeLayer(capaFija);
                    break;
                case 'camaras-fr':
                    mymap.removeLayer(capaFR);
                    break;
                case 'camaras-lpr':
                    mymap.removeLayer(capaLPR);
                    break;
                case 'camaras-domos':
                    mymap.removeLayer(capaDomo);
                    break;
                case 'camaras-domos-duales':
                    mymap.removeLayer(capaDomoDual);
                    break;
            }
        }

        // Función para desactivar todas las capas de cámaras
        function deactivateAllCameraLayers() {
            // Desactivar capa general
            deactivateGeneralCameraLayer();

            // Desactivar capas específicas
            deactivateCameraSpecificLayers();

            // Desactivar cámaras y comisarías
            if (layerStates['camaras-comisarias']) {
                layerStates['camaras-comisarias'] = false;
                document.getElementById('switch-camaras-comisarias').checked = false;
                mymap.removeLayer(capa5);
            }
        }

        // Función para limpiar todas las capas
        function clearAllLayers() {
            // Desactivar todos los switches
            Object.keys(layerStates).forEach(layerType => {
                layerStates[layerType] = false;
                const switchElement = document.getElementById(`switch-${layerType}`);
                if (switchElement) {
                    switchElement.checked = false;
                }
            });

            // Remover todas las capas del mapa
            mymap.removeLayer(capa1);
            mymap.removeLayer(capa2);
            mymap.removeLayer(capa3);
            mymap.removeLayer(capa5);
            mymap.removeLayer(capaFija);
            mymap.removeLayer(capaFR);
            mymap.removeLayer(capaLPR);
            mymap.removeLayer(capaDomo);
            mymap.removeLayer(capaDomoDual);
            mymap.removeLayer(capaSitios);
        }

        // Agregar botón personalizado para pantalla completa
        var fullscreenButton = L.control({
            position: 'bottomright'
        });
        var isFullscreen = false; // Variable para rastrear el estado de pantalla completa

        fullscreenButton.onAdd = function (map) {
            var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<div id="fullscreen-button"><i class="fas fa-expand"></i></div>';
            div.firstChild.addEventListener('click', function () {
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
                    //div.firstChild.innerHTML = 'Salir'; // Cambiar el texto del botón
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
        mymap.addLayer(capa2);
        fullscreenButton.addTo(mymap);
    </script>
@endsection
