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

        #panel-sin-geocod .motivo-badge {
            font-size: 0.75em;
        }

        #panel-sin-geocod tr.geocodificado td {
            opacity: 0.55;
        }

        #map-ubicar-modal {
            height: 420px;
            border-radius: 6px;
        }

        .descripcion-evento {
            font-size: 0.8em;
            color: var(--bs-secondary-color, #6c757d);
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .acciones-geocod {
            display: flex;
            gap: 4px;
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
                        <select id="tipo_servicio" class="form-select select2" style="width: 100%;">
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

    {{-- Panel de eventos sin geocodificar --}}
    <div id="panel-sin-geocod" class="card mt-3" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-geo-alt-fill text-warning"></i>
                Direcciones sin ubicar
                <span class="badge bg-warning text-dark ms-2" id="badge-sin-geocod">0</span>
            </h5>
            <small class="text-muted">Escribí una dirección corregida o usá el mapa para ubicar manualmente</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="tabla-sin-geocod">
                    <thead class="table-light">
                        <tr>
                            <th>Dirección original / Descripción</th>
                            <th>Expediente</th>
                            <th class="text-center" style="width:80px;">Eventos</th>
                            <th class="text-center" style="width:120px;">Motivo</th>
                            <th>Corrección manual</th>
                            <th style="width:220px;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-sin-geocod"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: ubicar en mapa --}}
    <div class="modal fade" id="modalUbicarMapa" tabindex="-1" aria-labelledby="modalUbicarMapaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUbicarMapaLabel">
                        <i class="bi bi-map me-1"></i> Ubicar dirección en el mapa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-2">
                    <div class="alert alert-info py-2 mb-2" style="font-size:0.88em;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hacé clic en el mapa para marcar la ubicación correcta del evento.
                        Podés mover el marcador arrastrándolo.
                    </div>
                    <p class="mb-1"><strong>Dirección:</strong> <span id="modal-dir-texto" class="font-monospace"></span></p>
                    <p class="mb-2"><strong>Descripción:</strong> <span id="modal-desc-texto" class="text-muted"></span></p>
                    <div id="map-ubicar-modal"></div>
                    <div class="mt-2 text-muted" id="modal-coords-texto" style="font-size:0.85em;">
                        Sin ubicación seleccionada
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-confirmar-ubicacion" disabled>
                        <i class="bi bi-check-circle me-1"></i> Confirmar ubicación
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            $('#tipo_servicio').select2({
                placeholder: '— Todos —',
                allowClear: true,
                width: '100%'
            });
        });

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

                    // Panel de sin geocodificar
                    renderizarSinGeocodificar(data.sin_geocodificar_datos || []);

                    // Limpiar capa anterior
                    if (heatLayer) {
                        map.removeLayer(heatLayer);
                    }

                    if (!data.heat_data || data.heat_data.length === 0) {
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

        function renderizarSinGeocodificar(lista) {
            var panel = document.getElementById('panel-sin-geocod');
            var tbody = document.getElementById('tbody-sin-geocod');
            var badge = document.getElementById('badge-sin-geocod');

            if (!lista || lista.length === 0) {
                panel.style.display = 'none';
                return;
            }

            badge.textContent = lista.length;
            tbody.innerHTML = '';

            lista.forEach(function (item) {
                var motivoLabel = item.motivo === 'invalida'
                    ? '<span class="badge bg-danger motivo-badge">dirección inválida</span>'
                    : '<span class="badge bg-secondary motivo-badge">no encontrada</span>';

                var tr = document.createElement('tr');
                tr.dataset.direccionOriginal = item.direccion;
                tr.dataset.nroExpediente = item.nro_expediente || '';
                tr.dataset.descripcion = item.descripcion || '';

                var expLink = item.nro_expediente
                    ? '<span class="font-monospace">' + escHtml(item.nro_expediente) + '</span>'
                    : '<span class="text-muted">—</span>';

                var descHtml = item.descripcion
                    ? '<div class="descripcion-evento" title="' + escHtml(item.descripcion) + '">' + escHtml(item.descripcion) + '</div>'
                    : '';

                tr.innerHTML =
                    '<td><span class="font-monospace">' + escHtml(item.direccion) + '</span>' + descHtml + '</td>' +
                    '<td>' + expLink + '</td>' +
                    '<td class="text-center">' + item.total + '</td>' +
                    '<td class="text-center">' + motivoLabel + '</td>' +
                    '<td><input type="text" class="form-control form-control-sm input-correccion" placeholder="Ej: San Martín 1234 o Urquiza y Corrientes"></td>' +
                    '<td>' +
                        '<div class="acciones-geocod">' +
                            '<button class="btn btn-sm btn-primary flex-fill btn-geocod-manual" onclick="geocodificarManual(this)"><i class="bi bi-geo-fill me-1"></i>Asignar</button>' +
                            '<button class="btn btn-sm btn-outline-info btn-ubicar-mapa" onclick="abrirModalUbicar(this)"><i class="bi bi-pin-map-fill me-1"></i>Mapa</button>' +
                        '</div>' +
                    '</td>';
                tbody.appendChild(tr);
            });

            panel.style.display = 'block';
        }

        function geocodificarManual(btn) {
            var tr = btn.closest('tr');
            var direccionOriginal = tr.dataset.direccionOriginal;
            var input = tr.querySelector('.input-correccion');
            var corregida = input.value.trim();

            if (!corregida) {
                input.classList.add('is-invalid');
                input.focus();
                return;
            }
            input.classList.remove('is-invalid');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('{{ route("cecoco.mapa-calor.geocodificar-manual") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ direccion_original: direccionOriginal, direccion_corregida: corregida, nro_expediente: tr.dataset.nroExpediente || null })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-geo-fill me-1"></i>Asignar';
                    input.classList.add('is-invalid');
                    input.title = data.error;
                    // Show feedback under input
                    var fb = tr.querySelector('.invalid-feedback') || document.createElement('div');
                    fb.className = 'invalid-feedback d-block';
                    fb.textContent = data.error;
                    input.parentNode.appendChild(fb);
                    return;
                }

                // Marcar fila como geocodificada
                tr.classList.add('geocodificado', 'table-success');
                tr.querySelector('td:last-child').innerHTML =
                    '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Guardado</span>';

                // Actualizar badge
                var badge = document.getElementById('badge-sin-geocod');
                var pendientes = document.querySelectorAll('#tbody-sin-geocod tr:not(.geocodificado)').length;
                badge.textContent = pendientes;
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-fill me-1"></i>Asignar';
                alert('Error de conexión: ' + err.message);
            });
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // ── Modal: ubicar en mapa ────────────────────────────────────────────
        var mapUbicar = null;
        var markerUbicar = null;
        var filaUbicarActiva = null;

        function abrirModalUbicar(btn) {
            filaUbicarActiva = btn.closest('tr');
            var dir  = filaUbicarActiva.dataset.direccionOriginal;
            var desc = filaUbicarActiva.dataset.descripcion;

            document.getElementById('modal-dir-texto').textContent  = dir;
            document.getElementById('modal-desc-texto').textContent = desc || '(sin descripción)';
            document.getElementById('modal-coords-texto').textContent = 'Sin ubicación seleccionada';
            document.getElementById('btn-confirmar-ubicacion').disabled = true;

            var modal = new bootstrap.Modal(document.getElementById('modalUbicarMapa'));
            modal.show();

            document.getElementById('modalUbicarMapa').addEventListener('shown.bs.modal', function iniciarMapa() {
                if (!mapUbicar) {
                    mapUbicar = L.map('map-ubicar-modal').setView([-31.7413, -60.5115], 13);
                    L.tileLayer('https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey={{ env("API_KEY_THUNDER_FOREST_MAP") }}', {
                        attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(mapUbicar);

                    mapUbicar.on('click', function (e) {
                        colocarMarcador(e.latlng);
                    });
                } else {
                    mapUbicar.invalidateSize();
                    if (markerUbicar) {
                        mapUbicar.removeLayer(markerUbicar);
                        markerUbicar = null;
                    }
                    document.getElementById('modal-coords-texto').textContent = 'Sin ubicación seleccionada';
                    document.getElementById('btn-confirmar-ubicacion').disabled = true;
                }
                // remover listener para no acumularlo en próximas aperturas
                document.getElementById('modalUbicarMapa').removeEventListener('shown.bs.modal', iniciarMapa);
            }, { once: true });
        }

        function colocarMarcador(latlng) {
            if (markerUbicar) {
                markerUbicar.setLatLng(latlng);
            } else {
                markerUbicar = L.marker(latlng, { draggable: true }).addTo(mapUbicar);
                markerUbicar.on('dragend', function () {
                    actualizarCoordsModal(markerUbicar.getLatLng());
                });
            }
            actualizarCoordsModal(latlng);
        }

        function actualizarCoordsModal(latlng) {
            document.getElementById('modal-coords-texto').textContent =
                'Lat: ' + latlng.lat.toFixed(6) + '  |  Lng: ' + latlng.lng.toFixed(6);
            document.getElementById('btn-confirmar-ubicacion').disabled = false;
        }

        document.getElementById('btn-confirmar-ubicacion').addEventListener('click', function () {
            if (!markerUbicar || !filaUbicarActiva) return;

            var latlng = markerUbicar.getLatLng();
            var btnConfirmar = this;
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('{{ route("cecoco.mapa-calor.geocodificar-coordenadas") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    direccion_original: filaUbicarActiva.dataset.direccionOriginal,
                    lat: latlng.lat,
                    lng: latlng.lng,
                    nro_expediente: filaUbicarActiva.dataset.nroExpediente || null
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                bootstrap.Modal.getInstance(document.getElementById('modalUbicarMapa')).hide();
                btnConfirmar.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar ubicación';

                // Marcar fila como geocodificada
                filaUbicarActiva.classList.add('geocodificado', 'table-success');
                filaUbicarActiva.querySelector('td:last-child').innerHTML =
                    '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Guardado</span>';

                // Actualizar badge
                var badge = document.getElementById('badge-sin-geocod');
                var pendientes = document.querySelectorAll('#tbody-sin-geocod tr:not(.geocodificado)').length;
                badge.textContent = pendientes;
            })
            .catch(function (err) {
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar ubicación';
                alert('Error de conexión: ' + err.message);
            });
        });
    </script>
@endsection