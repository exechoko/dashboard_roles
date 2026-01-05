<style>
    /* ========================================
   VARIABLES CSS PARA COLORES DE FRANJAS HORARIAS
   ======================================== */
    html {
        --border-m: darkgreen;
        --border-t: red;
        --border-all: darkblue;
        --fondo-m: lightgreen;
        --fondo-t: gold;
        --fondo-all: deepskyblue;
    }

    /* ========================================
   RESET DE MÁRGENES Y PADDING
   ======================================== */
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

    /* ========================================
   BOTÓN DE PANTALLA COMPLETA
   ======================================== */
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

    [data-theme="dark"] #fullscreen-button {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
   CLASE DE UTILIDAD PARA POSICIONAMIENTO
   ======================================== */
    .my_class {
        position: absolute;
        top: npx;
        left: npx;
        right: npx;
        bottom: npx;
        z-index: N;
    }

    /* ========================================
   HOVER EFFECTS
   ======================================== */
    .underline-on-hover:hover {
        text-decoration: underline;
        cursor: pointer;
    }

    /* ========================================
   UTILIDADES FLEXBOX
   ======================================== */
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

    .flex-wrap {
        flex-wrap: wrap;
    }

    /* ========================================
   ESTILOS PARA LEYENDA DEL MAPA
   ======================================== */
    .legend {
        line-height: 18px;
        color: #555;
    }

    [data-theme="dark"] .legend {
        color: var(--text-primary, #ffffff) !important;
    }

    .legend i {
        width: 18px;
        height: 18px;
        float: left;
        margin-right: 8px;
        opacity: 0.7;
    }

    .info {
        background: white;
        background: rgba(255, 255, 255, 0.8);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
    }

    [data-theme="dark"] .info {
        background: rgba(30, 30, 30, 0.9) !important;
        color: var(--text-primary, #ffffff) !important;
        border: 1px solid var(--border-color, #333333) !important;
    }

    .info h4 {
        margin: 0 0 5px;
        color: #777;
    }

    [data-theme="dark"] .info h4 {
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
   BOTONES DE CONTROL DEL MAPA
   ======================================== */
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
        color: black !important;
    }

    /* ========================================
   ESTILOS PARA ELEMENTOS DE INFORMACIÓN
   ======================================== */
    .pieza {
        padding: 10px;
        border: solid thin #00c0ef;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    [data-theme="dark"] .pieza {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
    }

    .lbl-result {
        padding: 5px 10px;
    }

    .li-success {
        padding: 2px 10px;
        border: solid thin green;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .li-danger {
        padding: 2px 10px;
        border: solid thin red;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    /* ========================================
   MODAL DE RESULTADOS
   ======================================== */
    #modal-result ul {
        list-style: none;
        height: 250px;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 10px;
    }

    #lbl-area {
        background: #00c0ef;
        padding: 10px;
        color: white;
        margin: 0 10px !important;
        font-size: 20px;
        font-weight: bold;
    }

    #no-guias {
        padding: 10px;
        margin: 10px !important;
    }

    /* ========================================
   MARCADORES PERSONALIZADOS
   ======================================== */
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

    /* ========================================
   ESTILOS PARA DIFERENTES TURNOS
   ======================================== */
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

    /* ========================================
   MODAL DIVISIÓN
   ======================================== */
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

    [data-theme="dark"] #modal-divide {
        background: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
   MARCADORES DE RUTAS
   ======================================== */
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
        box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);
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
        border-color: var(--fondo-m) !important;
        color: var(--border-m) !important;
    }

    .route-vespertino {
        background-color: white;
        border-color: orange !important;
        color: var(--border-t) !important;
    }

    .route-corrido {
        background-color: white;
        border-color: var(--fondo-all) !important;
        color: var(--border-all) !important;
    }

    /* ========================================
   TABLA DE RETIROS
   ======================================== */
    #retiros_table>tbody>tr:hover {
        cursor: move;
        background: #EAF1F8;
    }

    /* ========================================
   CONTROLES SUPERIORES
   ======================================== */
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
        font-weight: bold;
    }

    #print {
        padding: 5px 10px;
        font-size: 20px;
        color: white;
        border-radius: 10px;
        margin-left: 5px;
    }

    #retiro-limit {
        padding: 5px 10px;
        font-size: 16px;
        color: white;
        background: #A60000;
        border-radius: 10px;
        margin-left: 5px;
    }

    /* ========================================
   PANEL DE VIAJE
   ======================================== */
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

    [data-theme="dark"] #travel {
        background: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
    }

    #travel span {
        margin: 0 5px;
    }

    #travel .route {
        width: 70px;
        height: 0;
    }

    /* ========================================
   TIPOS DE TRANSPORTE
   ======================================== */
    .auto {
        border: solid 2px #0015FF;
    }

    .bici {
        border: dashed 2px #86007B;
    }

    .walk {
        border: dashed 2px #FF0000;
    }

    .i-auto {
        color: #0015FF;
    }

    .i-bici {
        color: #86007B;
    }

    .i-walk {
        color: #FF0000;
    }

    /* ========================================
   PANEL DE FRANJAS
   ======================================== */
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

    [data-theme="dark"] #franjas {
        background: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
    }

    .franja-horaria {
        border: solid medium;
        border-radius: 5px;
        font-size: 14px !important;
        color: black;
        padding: 1px 2px;
        margin: 2px;
    }

    #info-recorrido .alert {
        padding: 5px 10px;
    }

    .lbl-suc {
        position: absolute;
        left: 70px;
        top: 2px;
        z-index: 10;
    }

    /* ========================================
   MODAL SIN GEOLOCALIZACIÓN
   ======================================== */
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
        z-index: 100;
    }

    .info-lbl {
        padding: 5px !important;
        margin: 5px;
        font-size: 15px !important;
    }

    /* ========================================
   BOOTSTRAP TABLE AJUSTES
   ======================================== */
    .bootstrap-table table>thead>tr>th,
    .bootstrap-table table>tbody>tr>td {
        white-space: nowrap;
    }

    /* ========================================
   LOADING DE RUTAS
   ======================================== */
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

    /* ========================================
   GOOGLE PLACES AUTOCOMPLETE
   ======================================== */
    .pac-container {
        z-index: 100000000 !important;
    }

    [data-theme="dark"] .pac-container {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
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

    /* ========================================
   CONTROL PERSONALIZADO DE CAPAS - LIGHT
   ======================================== */
    .custom-layer-control {
        position: absolute !important;
        top: 10px;
        right: 10px;
        z-index: 10000 !important;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        font-size: 12px;
        max-width: 250px;
        font-family: Arial, sans-serif;
        border: 1px solid #ccc;
        pointer-events: auto;
    }

    /* Ajuste para el control de búsqueda de Esri */
    .leaflet-top.leaflet-right .leaflet-control.geocoder-control {
        margin-top: 90px; /* Aumentar el margen superior */
        margin-right: 250px; 
        z-index: 10001 !important; /* Asegurar que esté por encima de otros controles */
        background: #fff; /* Fondo blanco para visibilidad en tema oscuro */
        border-radius: 5px; /* Bordes redondeados para estética */
        box-shadow: 0 1px 5px rgba(0,0,0,0.65); /* Sombra para destacar */
    }

    .custom-layer-control h6 {
        margin: 0 0 10px 0;
        font-weight: bold;
        color: #333;
    }

    #map {
        position: relative !important;
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

    /* ========================================
   SWITCH/TOGGLE LIGHT THEME
   ======================================== */
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

    /* ========================================
   CONTROL COLAPSIBLE LIGHT
   ======================================== */
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
        transition: max-height 0.1s ease-out;
    }

    .layer-control-content.hidden {
        max-height: 0;
        overflow: hidden;
    }

    /* ========================================
   ESTILOS PARA SISTEMA DE SELECCIÓN POR POLÍGONO
   ======================================== */

    /* Cursor personalizado durante el dibujo */
    #map.drawing-mode {
        cursor: crosshair !important;
    }

    /* Estilos para el modal de cámaras */
    #camerasPolygonModal .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    [data-theme="dark"] #camerasPolygonModal .modal-content {
        background-color: var(--card-bg, #1e1e1e);
        color: var(--text-primary, #ffffff);
    }

    #camerasPolygonModal .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    [data-theme="dark"] #camerasPolygonModal .modal-header {
        background: linear-gradient(135deg, #434343 0%, #000000 100%);
    }

    #camerasPolygonModal .modal-title {
        font-weight: 600;
    }

    #camerasPolygonModal .table {
        margin-bottom: 0;
    }

    #camerasPolygonModal .table thead th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    [data-theme="dark"] #camerasPolygonModal .table thead th {
        background-color: var(--bg-secondary, #2d2d2d);
        color: var(--text-primary, #ffffff);
        border-color: var(--border-color, #333333);
    }

    [data-theme="dark"] #camerasPolygonModal .table {
        color: var(--text-primary, #ffffff);
    }

    [data-theme="dark"] #camerasPolygonModal .table tbody tr {
        border-color: var(--border-color, #333333);
    }

    [data-theme="dark"] #camerasPolygonModal .table tbody tr:hover {
        background-color: var(--bg-secondary, #2d2d2d);
    }

    #camerasPolygonModal .table tbody tr {
        transition: background-color 0.2s ease;
    }

    #camerasPolygonModal .table tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    /* Estilos para el campo de búsqueda */
    #searchCameraInList {
        border-radius: 20px;
        padding-left: 15px;
    }

    [data-theme="dark"] #searchCameraInList {
        background-color: var(--input-bg, #2d2d2d);
        color: var(--text-primary, #ffffff);
        border-color: var(--input-border, #444444);
    }

    /* Botones de exportación */
    #camerasPolygonModal .btn {
        border-radius: 20px;
        padding: 6px 15px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #camerasPolygonModal .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Estilos para el polígono seleccionado */
    .leaflet-interactive.polygon-selected {
        stroke: #3388ff;
        stroke-width: 3;
        fill: #3388ff;
        fill-opacity: 0.2;
    }

    /* Marcadores de vértices del polígono */
    .polygon-vertex-marker {
        background-color: #ff0000;
        border: 2px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    /* Animación para botón de selección */
    #togglePolygonDraw {
        transition: all 0.3s ease;
    }

    #togglePolygonDraw:hover {
        transform: scale(1.05);
    }

    #togglePolygonDraw.active {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        50% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
    }

    /* Controles de polígono (lista y limpiar) */
    .polygon-controls {
        background: white;
        padding: 8px;
        border-radius: 4px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    [data-theme="dark"] .polygon-controls {
        background-color: var(--card-bg, #1e1e1e);
    }

    /* Tabla responsive */
    .table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }

    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    [data-theme="dark"] .table-responsive::-webkit-scrollbar-track {
        background: var(--bg-secondary, #2d2d2d);
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Estilos para cámaras resaltadas */
    .camera-marker-highlighted {
        animation: markerPulse 1s infinite;
    }

    @keyframes markerPulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    /* Badge de conteo */
    .camera-count-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-left: 10px;
    }

    /* Mensaje cuando no hay cámaras */
    .no-cameras-message {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    [data-theme="dark"] .no-cameras-message {
        color: var(--text-secondary, #aaaaaa);
    }

    .no-cameras-message i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Tooltips personalizados */
    .camera-tooltip {
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #camerasPolygonModal .modal-xl {
            max-width: 95%;
            margin: 10px auto;
        }

        #camerasPolygonModal .modal-body {
            padding: 10px;
        }

        #camerasPolygonModal .table {
            font-size: 0.85rem;
        }

        #camerasPolygonModal .btn {
            padding: 4px 10px;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .table-responsive {
            max-height: 400px;
        }
    }

    @media (max-width: 576px) {
        #togglePolygonDraw {
            font-size: 0.85rem;
            padding: 6px 10px;
        }

        #camerasPolygonModal .modal-title {
            font-size: 1rem;
        }

        .table-responsive {
            max-height: 300px;
        }
    }

    /* Estilos para el estado de carga */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
    }

    .loading-spinner {
        background: white;
        padding: 20px 30px;
        border-radius: 8px;
        text-align: center;
    }

    [data-theme="dark"] .loading-spinner {
        background-color: var(--card-bg, #1e1e1e);
        color: var(--text-primary, #ffffff);
    }

    .loading-spinner i {
        font-size: 2rem;
        color: #667eea;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* ========================================
   CONTROL PERSONALIZADO DE CAPAS - DARK THEME
   ======================================== */
    [data-theme="dark"] .custom-layer-control {
        background-color: var(--card-bg, #1e1e1e) !important;
        border: 1px solid var(--border-color, #333333) !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5) !important;
    }

    [data-theme="dark"] .custom-layer-control h6 {
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .layer-control-header {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        color: var(--text-primary, #ffffff) !important;
        border-bottom-color: var(--border-color, #333333) !important;
    }

    [data-theme="dark"] .layer-control-toggle {
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .layer-control-content {
        background-color: var(--card-bg, #1e1e1e) !important;
    }

    [data-theme="dark"] .layer-item {
        border-radius: 4px !important;
        transition: background-color 0.2s ease !important;
    }

    [data-theme="dark"] .layer-item:hover {
        background-color: var(--bg-secondary, #2d2d2d) !important;
    }

    [data-theme="dark"] .layer-label {
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .layer-label:hover {
        color: #fff !important;
    }

    /* ========================================
   SWITCH DARK THEME
   ======================================== */
    [data-theme="dark"] .slider {
        background-color: var(--input-border, #444444) !important;
    }

    [data-theme="dark"] input:checked+.slider {
        background-color: #007bff !important;
    }

    [data-theme="dark"] input:checked+.slider.comisarias {
        background-color: #28a745 !important;
    }

    [data-theme="dark"] input:checked+.slider.camaras {
        background-color: #007bff !important;
    }

    [data-theme="dark"] input:checked+.slider.camaras-tipo {
        background-color: #fd7e14 !important;
    }

    [data-theme="dark"] input:checked+.slider.antenas {
        background-color: #6f42c1 !important;
    }

    [data-theme="dark"] input:checked+.slider.sitios {
        background-color: #dc3545 !important;
    }

    /* ========================================
   FULLSCREEN ADJUSTMENT
   ======================================== */
    #map:fullscreen .custom-layer-control {
        position: absolute !important;
        top: 10px;
        right: 10px;
        z-index: 10000 !important;
    }

    .leaflet-tile {
        border: none !important;
    }

    /* ========================================
   MODAL EN PANTALLA COMPLETA - ESTILOS CRÍTICOS
   ======================================== */

    /* Forzar modal por encima de todo en fullscreen */
    :fullscreen .modal,
    :-webkit-full-screen .modal,
    :-moz-full-screen .modal,
    :-ms-fullscreen .modal {
        z-index: 10001 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Backdrop para fullscreen */
    :fullscreen .modal-backdrop,
    :-webkit-full-screen .modal-backdrop,
    :-moz-full-screen .modal-backdrop,
    :-ms-fullscreen .modal-backdrop {
        z-index: 10000 !important;
        background-color: rgba(0, 0, 0, 0.9) !important;
    }

    /* Modal dialog en fullscreen */
    :fullscreen .modal-dialog,
    :-webkit-full-screen .modal-dialog,
    :-moz-full-screen .modal-dialog,
    :-ms-fullscreen .modal-dialog {
        margin: 20px auto !important;
        max-height: 90vh !important;
        max-width: 95vw !important;
    }

    /* Modal content en fullscreen */
    :fullscreen .modal-content,
    :-webkit-full-screen .modal-content,
    :-moz-full-screen .modal-content,
    :-ms-fullscreen .modal-content {
        max-height: 90vh !important;
        overflow: hidden !important;
    }

    /* Asegurar que el body no tenga overflow en fullscreen */
    :fullscreen body,
    :-webkit-full-screen body,
    :-moz-full-screen body,
    :-ms-fullscreen body {
        overflow: hidden !important;
    }

    /* Estilos específicos para el contenedor del mapa en fullscreen */
    #map:fullscreen,
    #map:-webkit-full-screen,
    #map:-moz-full-screen,
    #map:-ms-fullscreen {
        position: relative !important;
        z-index: auto !important;
    }

    /* ========================================
   ARREGLOS ESPECÍFICOS PARA BOOTSTRAP MODAL
   ======================================== */
    .modal.fade.show {
        display: block !important;
        padding-right: 17px;
        /* Compensar scrollbar */
    }

    .modal-open {
        overflow: hidden !important;
    }

    /* Backdrop fijo */
    .modal-backdrop.fade.show {
        opacity: 0.5;
    }

    /* ========================================
   RESPONSIVE PARA MODAL EN FULLSCREEN
   ======================================== */
    @media (max-width: 768px) {

        :fullscreen .modal-dialog,
        :-webkit-full-screen .modal-dialog,
        :-moz-full-screen .modal-dialog,
        :-ms-fullscreen .modal-dialog {
            margin: 10px auto !important;
            max-height: 95vh !important;
            max-width: 98vw !important;
        }

        :fullscreen .modal-body,
        :-webkit-full-screen .modal-body,
        :-moz-full-screen .modal-body,
        :-msfullscreen .modal-body {
            padding: 15px !important;
        }
    }

    /* ========================================
   EVITAR CONFLICTOS CON Z-INDEX DE LEAFLET
   ======================================== */
    .leaflet-container {
        z-index: 1 !important;
    }

    .leaflet-top,
    .leaflet-bottom {
        z-index: 1000 !important;
    }

    /* ========================================
   BOTÓN FULLSCREEN MEJORADO
   ======================================== */
    #fullscreen-button {
        z-index: 1000 !important;
        position: absolute !important;
        bottom: 20px !important;
        right: 20px !important;
    }

    /* ========================================
   ANIMACIÓN SUAVE PARA MODAL
   ======================================== */
    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
    }

    /* ========================================
   SCROLLBAR PERSONALIZADO PARA MODAL
   ======================================== */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Estilo para marcadores individuales en captura */
    .simple-marker-icon {
        background: transparent !important;
        border: none !important;
    }

    .pdf-camera-marker {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .pdf-camera-marker svg {
        filter: drop-shadow(2px 4px 6px rgba(0, 0, 0, 0.3));
    }


    /* ========================================
   MEDIA QUERIES RESPONSIVO
   ======================================== */
    @media (min-width: 1150px) {
        .modal-xl {
            width: 1140px;
        }

        .modal-full {
            width: 98%;
        }
    }

    @media (max-width: 768px) {
        .custom-layer-control {
            max-width: 280px !important;
            font-size: 11px !important;
        }

        #map {
            height: 400px !important;
        }
    }

    @media (max-width: 576px) {
        .custom-layer-control {
            max-width: 260px !important;
            font-size: 10px !important;
        }

        #map {
            height: 300px !important;
        }
    }
</style>
