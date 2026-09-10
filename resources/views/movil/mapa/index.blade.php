@extends('layouts.movil')

@section('title', 'Mapa')
@section('back', route('movil.index'))

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
        integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.Default.css">
@endsection

@section('content')
    <div class="m-map-wrap">
        <div id="m-map"></div>

        <button type="button" class="m-map-filters-btn" id="mMapFiltersBtn" aria-label="Filtros del mapa">
            <i class="fas fa-filter"></i>
        </button>

        <div class="m-map-filters" id="mMapFilters" hidden>
            <div class="m-map-filters__header">
                <span>Cámaras por tipo</span>
                <button type="button" class="m-map-filters__close" id="mMapFiltersClose" aria-label="Cerrar">&times;</button>
            </div>

            <div class="m-map-filters__chips" id="mMapTipoChips">
                <button type="button" class="m-chip m-chip--filter is-active" data-tipo="todas">Todas</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="Fija">Fijas</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="Fija - FR">FR</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="lpr">LPR</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="Domo">Domos</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="Domo Dual">Domos Duales</button>
                <button type="button" class="m-chip m-chip--filter" data-tipo="BDE (Totem)">BDE</button>
            </div>

            <div class="m-map-filters__header" style="margin-top:.6rem;">
                <span>Otras capas</span>
            </div>

            @if ($puedeVerDependencias)
                <label class="m-map-filters__toggle">
                    <input type="checkbox" id="mMapToggleDependencias">
                    <span>Dependencias</span>
                </label>
            @endif

            <label class="m-map-filters__toggle">
                <input type="checkbox" id="mMapToggleSitios">
                <span>Sitios inactivos</span>
            </label>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
        integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/leaflet.markercluster.js"></script>
    <script>
        (function () {
            function escapeHtml(texto) {
                return String(texto ?? '').replace(/[&<>"']/g, function (c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
                });
            }

            var mapa = L.map('m-map').setView([-31.75899, -60.47825], 13);

            // Mismos tiles que el mapa de escritorio: claro (OSM) u oscuro
            // (Stadia Maps, nativo, sin filtros CSS) según el tema activo.
            var tileClaro = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });
            var tileOscuro = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; Stadia Maps &copy; OpenMapTiles &copy; OpenStreetMap',
                maxZoom: 20,
                tileSize: 256,
                detectRetina: false,
                crossOrigin: true
            });

            var tileActual = null;
            function aplicarTileSegunTema() {
                var esOscuro = document.documentElement.getAttribute('data-theme') === 'dark';
                var nuevoTile = esOscuro ? tileOscuro : tileClaro;
                if (nuevoTile === tileActual) {
                    return;
                }
                if (tileActual) {
                    mapa.removeLayer(tileActual);
                }
                tileActual = nuevoTile;
                mapa.addLayer(tileActual);
            }
            aplicarTileSegunTema();

            new MutationObserver(aplicarTileSegunTema).observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme']
            });

            // Cámaras: agrupadas en clusters, filtrables por tipo.
            var clusters = L.markerClusterGroup();
            var camarasData = [];

            function tipoCoincide(filtro, tipoCamara) {
                tipoCamara = tipoCamara || '';
                if (filtro === 'todas') {
                    return true;
                }
                if (filtro === 'lpr') {
                    return tipoCamara.indexOf('LPR') !== -1;
                }
                return tipoCamara === filtro;
            }

            function aplicarFiltroCamaras(filtro) {
                clusters.clearLayers();
                camarasData.forEach(function (c) {
                    if (tipoCoincide(filtro, c.tipo)) {
                        clusters.addLayer(c.marker);
                    }
                });
                if (!mapa.hasLayer(clusters)) {
                    mapa.addLayer(clusters);
                }
            }

            fetch('{{ route('movil.mapa.camaras-json') }}')
                .then(function (r) { return r.json(); })
                .then(function (geojson) {
                    (geojson.features || []).forEach(function (feature) {
                        var p = feature.properties || {};
                        var coords = feature.geometry.coordinates;
                        var marker = L.marker([coords[1], coords[0]]);
                        var detalleUrl = '{{ url('/movil/camaras') }}/' + p.id;
                        marker.bindPopup(
                            '<strong>' + escapeHtml(p.titulo) + '</strong><br>' +
                            escapeHtml(p.tipo_camara) + '<br>' +
                            escapeHtml(p.sitio) +
                            '<br><a href="' + detalleUrl + '">Ver ficha</a>'
                        );
                        camarasData.push({ marker: marker, tipo: p.tipo_camara || '' });
                    });
                    aplicarFiltroCamaras('todas');
                });

            var chips = document.querySelectorAll('#mMapTipoChips .m-chip--filter');
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.classList.remove('is-active'); });
                    chip.classList.add('is-active');
                    aplicarFiltroCamaras(chip.dataset.tipo);
                });
            });

            // Dependencias (comisarías): capa aparte, se carga recién al activarla.
            var comisariasLayer = L.layerGroup();
            var comisariasCargadas = false;

            function cargarDependencias() {
                if (comisariasCargadas) {
                    return;
                }
                comisariasCargadas = true;
                fetch('{{ route('movil.mapa.dependencias-json') }}')
                    .then(function (r) { return r.json(); })
                    .then(function (geojson) {
                        (geojson.features || []).forEach(function (feature) {
                            var p = feature.properties || {};
                            var coords = feature.geometry.coordinates;
                            var icon = L.divIcon({
                                className: 'm-map-marker m-map-marker--comisaria',
                                html: '<span>' + escapeHtml(p.numero) + '</span>',
                                iconSize: [26, 26],
                                iconAnchor: [13, 13]
                            });
                            var marker = L.marker([coords[1], coords[0]], { icon: icon })
                                .bindPopup(escapeHtml(p.titulo));
                            comisariasLayer.addLayer(marker);
                        });
                    });
            }

            var toggleDependencias = document.getElementById('mMapToggleDependencias');
            if (toggleDependencias) {
                toggleDependencias.addEventListener('change', function (e) {
                    if (e.target.checked) {
                        cargarDependencias();
                        mapa.addLayer(comisariasLayer);
                    } else {
                        mapa.removeLayer(comisariasLayer);
                    }
                });
            }

            // Sitios inactivos: idem, capa aparte cargada a demanda.
            var sitiosLayer = L.layerGroup();
            var sitiosCargados = false;

            function cargarSitios() {
                if (sitiosCargados) {
                    return;
                }
                sitiosCargados = true;
                fetch('{{ route('movil.mapa.sitios-json') }}')
                    .then(function (r) { return r.json(); })
                    .then(function (geojson) {
                        (geojson.features || []).forEach(function (feature) {
                            var p = feature.properties || {};
                            var coords = feature.geometry.coordinates;
                            var icon = L.divIcon({
                                className: 'm-map-marker m-map-marker--sitio-inactivo',
                                html: '<i class="fas fa-times"></i>',
                                iconSize: [26, 26],
                                iconAnchor: [13, 13]
                            });
                            var marker = L.marker([coords[1], coords[0]], { icon: icon })
                                .bindPopup(
                                    '<strong>' + escapeHtml(p.titulo) + '</strong><br>' +
                                    '<span style="color:#dc3545;">INACTIVO</span>' +
                                    (p.observaciones ? '<br>' + escapeHtml(p.observaciones) : '')
                                );
                            sitiosLayer.addLayer(marker);
                        });
                    });
            }

            var toggleSitios = document.getElementById('mMapToggleSitios');
            if (toggleSitios) {
                toggleSitios.addEventListener('change', function (e) {
                    if (e.target.checked) {
                        cargarSitios();
                        mapa.addLayer(sitiosLayer);
                    } else {
                        mapa.removeLayer(sitiosLayer);
                    }
                });
            }

            // Panel de filtros: se muestra/oculta con el botón flotante.
            var filtersBtn = document.getElementById('mMapFiltersBtn');
            var filtersPanel = document.getElementById('mMapFilters');
            var filtersClose = document.getElementById('mMapFiltersClose');

            filtersBtn.addEventListener('click', function () {
                filtersPanel.hidden = !filtersPanel.hidden;
            });
            filtersClose.addEventListener('click', function () {
                filtersPanel.hidden = true;
            });
        })();
    </script>
@endsection
