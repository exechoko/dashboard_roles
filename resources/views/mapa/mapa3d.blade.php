{{-- mapa/mapa3d.blade.php --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/maplibre/maplibre-gl.css') }}">
@include('mapa.partials.styles')
@include('mapa.partials.styles-3d')
<style>
    /* Forzar pantalla completa, igual que en la vista 2D */
    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
        height: 100vh;
    }

    .main-content {
        padding: 0 !important;
        margin: 0 !important;
        height: 100vh;
    }

    .section {
        padding: 0 !important;
        margin: 0 !important;
        height: 100vh !important;
        width: 100vw !important;
        position: relative;
    }

    #map-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
        max-height: 60vh;
        overflow-y: auto;
        transform: translateY(-100%);
    }

    [data-theme="dark"] #map-header {
        background: var(--card-bg, #1e1e1e);
        color: var(--text-primary, #ffffff);
    }

    #map-header.show {
        transform: translateY(0);
    }

    #map-header .header-content {
        padding: 15px;
    }

    #header-toggle {
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10000;
        background: white;
        border: 2px solid #007bff;
        border-radius: 20px;
        padding: 8px 20px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }

    [data-theme="dark"] #header-toggle {
        background: var(--card-bg, #1e1e1e);
        color: var(--text-primary, #ffffff);
        border-color: #007bff;
    }

    #header-toggle:hover {
        transform: translateX(-50%) scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    #header-toggle.header-visible {
        top: auto;
        bottom: 10px;
    }

    .select2-container {
        z-index: 10001 !important;
    }

    .select2-dropdown {
        z-index: 10002 !important;
    }

    .custom-layer-control {
        top: 70px !important;
        z-index: 10000 !important;
    }
</style>
@endsection

@section('content')
    {{-- Botón para mostrar/ocultar header --}}
    <button id="header-toggle" onclick="toggleMapHeader()">
        <span id="toggle-text">Ver Información</span>
    </button>

    {{-- Header con estadísticas y controles - INICIA OCULTO --}}
    @include('mapa.partials.header', [
        'camaras' => $camaras,
        'fijas' => $fijas,
        'fijasFR' => $fijasFR,
        'fijasLPR' => $fijasLPR,
        'domos' => $domos,
        'domosDuales' => $domosDuales,
        'bde' => $bde,
        'total' => $total,
        'canales' => $canales,
        'camarasParana' => $camarasParana,
        'camarasCniaAvellaneda' => $camarasCniaAvellaneda,
        'camarasSanBenito' => $camarasSanBenito,
        'camarasOroVerde' => $camarasOroVerde,
        'sitiosParana' => $sitiosParana,
        'sitiosCniaAvellaneda' => $sitiosCniaAvellaneda,
        'sitiosSanBenito' => $sitiosSanBenito,
        'sitiosOroVerde' => $sitiosOroVerde,
        'cantidadSitios' => $cantidadSitios
    ])

    {{-- Panel flotante de visualización en vivo --}}
    @can('ver-stream-camara')
    <div id="mapaStreamPanel" style="display:none; position:fixed; bottom:20px; left:50%; transform:translateX(-50%);
        z-index:20000; background:#111; border:2px solid #28a745; border-radius:8px; padding:8px;
        flex-direction:column; align-items:stretch; width:calc(100% - 40px); max-width:480px;
        box-shadow:0 4px 20px rgba(0,0,0,0.7);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span id="mapaStreamTitle" style="color:#fff; font-weight:600; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Vista en Vivo</span>
            <button onclick="closeCameraStream()" style="background:none; border:none; color:#aaa; font-size:20px; cursor:pointer; line-height:1; flex-shrink:0; margin-left:8px;">&times;</button>
        </div>
        <div id="mapaStreamContainer"></div>
        <small style="color:#555; margin-top:5px; font-size:10px; text-align:center;">
            <i class="fas fa-circle" style="color:#28a745; font-size:7px;"></i> MJPEG en vivo
        </small>
    </div>
    @endcan

    {{-- Botón para volver a la vista 2D --}}
    <a href="{{ route('mapa.index') }}" id="btn-vista-2d" class="btn btn-primary" title="Vista 2D">
        <i class="fas fa-map"></i> Vista 2D
    </a>

    {{-- Mapa 3D en pantalla completa --}}
    <div id="map3d">
        @include('mapa.partials.layer-control-3d')

        <div id="map3d-loader" class="loading-overlay">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/maplibre/maplibre-gl.js') }}"></script>
@include('mapa.partials.acciones-camara')
@include('mapa.partials.scripts-3d')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initMapHeader();
        initHeaderToggle();
    });

    function initMapHeader() {
        const header = document.getElementById('map-header');
        const toggle = document.getElementById('header-toggle');
        const toggleText = document.getElementById('toggle-text');

        header.classList.remove('show');
        toggle.classList.remove('header-visible');
        toggleText.innerHTML = '<i class="fas fa-info-circle"></i> Ver Información';
    }

    function initHeaderToggle() {
        window.toggleMapHeader = function() {
            const header = document.getElementById('map-header');
            const toggle = document.getElementById('header-toggle');
            const toggleText = document.getElementById('toggle-text');

            if (header.classList.contains('show')) {
                header.classList.remove('show');
                toggle.classList.remove('header-visible');
                toggleText.innerHTML = '<i class="fas fa-info-circle"></i> Ver Información';
            } else {
                header.classList.add('show');
                toggle.classList.add('header-visible');
                toggleText.innerHTML = '<i class="fas fa-times"></i> Ocultar';
            }
        };

        document.addEventListener('click', function(e) {
            const header = document.getElementById('map-header');
            const toggle = document.getElementById('header-toggle');

            if (header.classList.contains('show') &&
                !header.contains(e.target) &&
                !toggle.contains(e.target)) {
                toggleMapHeader();
            }
        });
    }

    $(document).ready(function() {
        $('#camara_select').select2({ width: '100%', placeholder: 'Buscar cámara...', allowClear: true, dropdownParent: $('#map-header') })
            .on('select2:open', function() {
                setTimeout(function() {
                    var field = document.querySelector('.select2-container--open .select2-search__field');
                    if (field) field.focus();
                }, 0);
            })
            .on('change', function() {
                var selected = $(this).find(':selected');
                var lat = parseFloat(selected.data('lat'));
                var lng = parseFloat(selected.data('lng'));
                if (lat && lng) {
                    volarACamara3D(lat, lng);
                }
            });
    });
</script>
@endsection
