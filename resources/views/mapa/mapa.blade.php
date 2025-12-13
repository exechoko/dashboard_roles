{{-- mapa/mapa.blade.php --}}
@extends('layouts.app')

@section('css')
@include('mapa.partials.styles')
<style>
    /* Forzar pantalla completa desde el inicio */
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

    /* Header colapsable flotante - INICIA COLAPSADO */
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
        transform: translateY(-100%); /* INICIA OCULTO */
    }

    [data-theme="dark"] #map-header {
        background: var(--card-bg, #1e1e1e);
        color: var(--text-primary, #ffffff);
    }

    #map-header.show {
        transform: translateY(0); /* MUESTRA EL HEADER */
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

    /* Mapa en toda la pantalla */
    #map {
        height: 100vh !important;
        width: 100vw !important;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1;
    }

    /* Asegurar que Select2 se muestre correctamente */
    .select2-container {
        z-index: 10001 !important;
    }

    .select2-dropdown {
        z-index: 10002 !important;
    }

    /* Ajustar control de capas */
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

    {{-- Mapa en pantalla completa --}}
    <div id="map">
        {{-- Control personalizado de capas --}}
        @include('mapa.partials.layer-control')
    </div>

    {{-- Scripts del mapa --}}
    @include('mapa.partials.scripts')
    @include('mapa.partials.polygon-selection')
    @include('mapa.partials.clustering-control')
@endsection

@section('scripts')
<script>
    // ========================================
    // INICIALIZACIÓN DE SELECT2 Y HEADER
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2 con configuración mejorada
        initSelect2();

        // Inicializar estado del header (colapsado por defecto)
        initMapHeader();

        // Inicializar funciones del mapa
        initMapFunctions();
    });

    function initSelect2() {
        // Destruir Select2 si ya existe
        if ($('#camara_select').hasClass('select2-hidden-accessible')) {
            $('#camara_select').select2('destroy');
        }

        // Inicializar Select2
        $('#camara_select').select2({
            width: '100%',
            placeholder: 'Buscar cámara...',
            allowClear: true,
            dropdownParent: $('#map-header') // Asegurar que el dropdown esté dentro del header
        }).on('select2:open', function() {
            // Forzar el foco en el campo de búsqueda
            setTimeout(function() {
                document.querySelector('.select2-search__field').focus();
            }, 0);
        }).on('change', function() {
            var selectedOption = $(this).find(':selected');
            var lat = selectedOption.data('lat');
            var lng = selectedOption.data('lng');

            if (lat && lng && window.mymap) {
                window.mymap.setView([lat, lng], 20);
            }
        });
    }

    function initMapHeader() {
        const header = document.getElementById('map-header');
        const toggle = document.getElementById('header-toggle');
        const toggleText = document.getElementById('toggle-text');

        // Asegurar que el header esté oculto al inicio
        header.classList.remove('show');
        toggle.classList.remove('header-visible');
        toggleText.innerHTML = '<i class="fas fa-info-circle"></i> Ver Información';
    }

    function initMapFunctions() {
        // Función para alternar el header
        window.toggleMapHeader = function() {
            const header = document.getElementById('map-header');
            const toggle = document.getElementById('header-toggle');
            const toggleText = document.getElementById('toggle-text');

            if (header.classList.contains('show')) {
                // Ocultar header
                header.classList.remove('show');
                toggle.classList.remove('header-visible');
                toggleText.innerHTML = '<i class="fas fa-info-circle"></i> Ver Información';

                // Re-inicializar Select2 para evitar problemas de posición
                setTimeout(initSelect2, 300);
            } else {
                // Mostrar header
                header.classList.add('show');
                toggle.classList.add('header-visible');
                toggleText.innerHTML = '<i class="fas fa-times"></i> Ocultar';

                // Re-inicializar Select2 para asegurar funcionalidad
                setTimeout(initSelect2, 300);
            }
        };

        // Cerrar header al hacer clic fuera
        $(document).on('click', function(e) {
            const header = document.getElementById('map-header');
            const toggle = document.getElementById('header-toggle');

            if (header.classList.contains('show') &&
                !header.contains(e.target) &&
                !toggle.contains(e.target)) {
                toggleMapHeader();
            }
        });

        // Ajustar mapa al cambiar tamaño de ventana
        $(window).on('resize', function() {
            if (window.mymap) {
                window.mymap.invalidateSize();
            }
        });
    }
</script>
@endsection
