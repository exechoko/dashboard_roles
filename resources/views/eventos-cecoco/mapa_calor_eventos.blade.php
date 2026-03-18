@extends('layouts.app')

@section('css')
    <style>
        #map-calor-eventos {
            height: 650px;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color, #dee2e6);
        }

        .filtros-mapa {
            background: var(--bs-body-bg, #fff);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .stats-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        #loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            border-radius: 8px;
            justify-content: center;
            align-items: center;
        }

        #loading-overlay .spinner-content {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] #loading-overlay .spinner-content {
            background: #1e1e2d;
            color: #e4e6fc;
        }
    </style>
@endsection

@section('content')
    <div class="mb-4">
        <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="mb-0"><i class="bi bi-fire"></i> Mapa de Calor — Eventos CECOCO</h4>
        </div>
        <div class="card-body">

            {{-- Filtros --}}
            <div class="filtros-mapa">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="tipo_servicio" class="form-label fw-bold">Tipo de Servicio</label>
                        <select id="tipo_servicio" class="form-select">
                            <option value="">— Todos —</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo }}">{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_desde" class="form-label fw-bold">Fecha Desde</label>
                        <input type="datetime-local" id="fecha_desde" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_hasta" class="form-label fw-bold">Fecha Hasta</label>
                        <input type="datetime-local" id="fecha_hasta" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button id="btn-buscar" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div id="stats-bar" class="mb-3" style="display:none;">
                <span class="stats-badge bg-primary text-white" id="stat-eventos">0 eventos</span>
                <span class="stats-badge bg-success text-white" id="stat-geocod">0 geocodificados</span>
                <span class="stats-badge bg-warning text-dark" id="stat-sin-geocod">0 sin ubicación</span>
                <span class="stats-badge bg-info text-white" id="stat-direcciones">0 direcciones únicas</span>
            </div>

            {{-- Mapa --}}
            <div style="position:relative;">
                <div id="map-calor-eventos"></div>
                <div id="loading-overlay">
                    <div class="spinner-content">
                        <div class="spinner-border text-primary mb-2" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <div id="loading-text">Geocodificando direcciones...</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Inicializar mapa centrado en Paraná
        var map = L.map('map-calor-eventos').setView([-31.7413, -60.5115], 13);
        var heatLayer = null;

        L.tileLayer('https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey={{ env("API_KEY_THUNDER_FOREST_MAP") }}', {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Geocoder search
        var searchControl = new L.esri.Controls.Geosearch({ position: 'topleft' }).addTo(map);
        var markerBusqueda = null;
        searchControl.on('results', function (data) {
            if (markerBusqueda) map.removeLayer(markerBusqueda);
            if (data.results.length > 0) {
                markerBusqueda = L.marker(data.results[0].latlng).addTo(map);
                markerBusqueda.bindPopup(data.results[0].text);
            }
        });

        // Buscar
        document.getElementById('btn-buscar').addEventListener('click', function () {
            var fechaDesde = document.getElementById('fecha_desde').value;
            var fechaHasta = document.getElementById('fecha_hasta').value;

            if (!fechaDesde || !fechaHasta) {
                alert('Debe seleccionar fecha desde y hasta');
                return;
            }

            var tipoServicio = document.getElementById('tipo_servicio').value;

            // Mostrar loading
            document.getElementById('loading-overlay').style.display = 'flex';
            document.getElementById('loading-text').textContent = 'Consultando eventos y geocodificando direcciones...';

            var params = new URLSearchParams({
                fecha_desde: fechaDesde.replace('T', ' '),
                fecha_hasta: fechaHasta.replace('T', ' '),
            });
            if (tipoServicio) params.append('tipo_servicio', tipoServicio);

            fetch('{{ route("cecoco.mapa-calor.datos") }}?' + params.toString())
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('loading-overlay').style.display = 'none';

                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }

                    // Actualizar stats
                    document.getElementById('stats-bar').style.display = 'block';
                    document.getElementById('stat-eventos').textContent = data.total_eventos.toLocaleString() + ' eventos';
                    document.getElementById('stat-geocod').textContent = data.geocodificados + ' geocodificados';
                    document.getElementById('stat-sin-geocod').textContent = data.sin_geocodificar + ' sin ubicación';
                    document.getElementById('stat-direcciones').textContent = data.total_direcciones + ' direcciones únicas';

                    // Limpiar capa anterior
                    if (heatLayer) {
                        map.removeLayer(heatLayer);
                    }

                    if (!data.heat_data || data.heat_data.length === 0) {
                        alert('No se encontraron eventos con ubicación para los filtros seleccionados.');
                        return;
                    }

                    // Construir datos para heatmap
                    var heatData = [];
                    var bounds = [];
                    data.heat_data.forEach(function (punto) {
                        heatData.push([punto.lat, punto.lng, punto.peso]);
                        bounds.push([punto.lat, punto.lng]);
                    });

                    // Crear capa de calor
                    heatLayer = L.heatLayer(heatData, {
                        radius: 20,
                        blur: 15,
                        maxZoom: 17
                    }).addTo(map);

                    // Ajustar vista al área de datos
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [30, 30] });
                    }
                })
                .catch(function (err) {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert('Error de conexión: ' + err.message);
                });
        });
    </script>
@endsection