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
