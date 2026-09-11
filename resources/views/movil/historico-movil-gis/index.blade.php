@extends('layouts.movil')

@section('title', 'Histórico Móvil GIS')
@section('back', route('movil.index'))

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
        integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="">
@endsection

@section('content')
    <div class="m-section-title">Consultar recorrido</div>

    <form id="hgisForm" class="m-filters">
        <div class="m-filters__row">
            <div class="m-field">
                <label for="hgisDesde">Desde</label>
                <input type="datetime-local" id="hgisDesde" required>
            </div>
            <div class="m-field">
                <label for="hgisHasta">Hasta</label>
                <input type="datetime-local" id="hgisHasta" required>
            </div>
        </div>

        <div class="m-field">
            <label for="hgisRecurso">Móvil / recurso</label>
            <div class="m-filters__row">
                <input type="text" id="hgisRecurso" placeholder="Ej: Cria 916" autocomplete="off" required>
                <button type="button" id="hgisBtnValidar" class="m-btn m-btn--outline" style="flex:0 0 auto;">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div id="hgisRecursoOk" class="m-hgis-ok" hidden><i class="fas fa-check-circle"></i> Recurso validado</div>
        </div>

        <div id="hgisSugerencias" class="m-list" hidden></div>

        <details class="m-detail" style="padding:.6rem 1rem;">
            <summary style="cursor:pointer; color:var(--m-muted); font-size:.85rem;">Opciones avanzadas</summary>
            <div class="m-filters__row" style="margin-top:.6rem;">
                <div class="m-field">
                    <label for="hgisVelMax">Vel. máxima (km/h)</label>
                    <input type="number" id="hgisVelMax" value="45" min="0">
                </div>
            </div>
            <div class="m-filters__row">
                <div class="m-field">
                    <label for="hgisUmbralNaranja">Detenido "naranja" (min)</label>
                    <input type="number" id="hgisUmbralNaranja" value="30" min="1">
                </div>
                <div class="m-field">
                    <label for="hgisUmbralRojo">Detenido "rojo" (min)</label>
                    <input type="number" id="hgisUmbralRojo" value="45" min="1">
                </div>
            </div>
        </details>

        <button type="submit" id="hgisBtnConsultar" class="m-btn" disabled>
            <i class="fas fa-route"></i> Consultar
        </button>
    </form>

    <div id="hgisAlert" class="m-alert m-alert--danger" hidden></div>

    <div class="m-section-title" style="display:flex; align-items:center; justify-content:space-between;">
        <span>Consultas previas</span>
        <button type="button" id="hgisTogglePrevias" class="m-chip m-chip--filter"><i class="fas fa-history"></i></button>
    </div>
    <div id="hgisPrevias" hidden>
        <div class="m-field">
            <input type="text" id="hgisPreviasBuscar" placeholder="Buscar por recurso...">
        </div>
        <div id="hgisPreviasLista" class="m-list"></div>
        <button type="button" id="hgisPreviasMas" class="m-btn m-btn--outline" style="width:100%; margin-top:.6rem;" hidden>Cargar más</button>
        <div id="hgisPreviasEmpty" class="m-empty" hidden><p>No hay consultas previas.</p></div>
    </div>

    <div id="hgisResultados" hidden>
        <div class="m-section-title">Resultado</div>
        <div id="hgisMetaCard" class="m-card"></div>

        <div class="m-card__meta" style="margin: .6rem 0 1rem; flex-wrap:wrap; gap:.5rem;">
            <button type="button" id="hgisBtnMapa" class="m-chip m-chip--action"><i class="fas fa-map-marked-alt"></i> Ver recorrido</button>
            @if($puedeCompartir)
                <button type="button" id="hgisBtnPdf" class="m-chip m-chip--action"><i class="fas fa-file-pdf"></i> Compartir PDF</button>
                <button type="button" id="hgisBtnRecorridoCompartir" class="m-chip m-chip--action"><i class="fas fa-share-alt"></i> Compartir recorrido</button>
            @endif
        </div>

        <div id="hgisMapaWrap" class="m-hgis-map-wrap" hidden>
            <div id="hgisMap"></div>
            <div class="m-hgis-player">
                <button type="button" id="hgisPlayerPrev" class="m-hgis-player__btn"><i class="fas fa-step-backward"></i></button>
                <button type="button" id="hgisPlayerPlay" class="m-hgis-player__btn"><i class="fas fa-play"></i></button>
                <button type="button" id="hgisPlayerNext" class="m-hgis-player__btn"><i class="fas fa-step-forward"></i></button>
                <input type="range" id="hgisPlayerRange" min="0" max="0" value="0" style="flex:1;">
            </div>
            <div id="hgisPlayerInfo" class="m-hgis-player__info">— Tocá play o un punto de la lista —</div>
        </div>

        <div id="hgisRegistros" class="m-list"></div>
        <button type="button" id="hgisRegistrosMas" class="m-btn m-btn--outline" style="width:100%; margin: .6rem 0 1rem;" hidden>Mostrar más</button>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
        integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <script>
        (function () {
            'use strict';

            function escapeHtml(texto) {
                return String(texto ?? '').replace(/[&<>"']/g, function (c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
                });
            }

            var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function fetchJson(url, options) {
                options = options || {};
                options.headers = Object.assign({
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                }, options.headers || {});
                return fetch(url, options).then(function (r) {
                    return r.json().then(function (data) {
                        if (!r.ok) {
                            throw new Error(data.message || ('HTTP ' + r.status));
                        }
                        return data;
                    });
                });
            }

            function mostrarError(mensaje) {
                var alerta = document.getElementById('hgisAlert');
                alerta.textContent = mensaje;
                alerta.hidden = false;
            }
            function ocultarError() {
                document.getElementById('hgisAlert').hidden = true;
            }

            /* ═══ Validación de recurso ═══ */
            var inputRecurso = document.getElementById('hgisRecurso');
            var btnValidar = document.getElementById('hgisBtnValidar');
            var btnConsultar = document.getElementById('hgisBtnConsultar');
            var recursoOk = document.getElementById('hgisRecursoOk');
            var sugerencias = document.getElementById('hgisSugerencias');

            function setRecursoValidado(ok) {
                btnConsultar.disabled = !ok;
                recursoOk.hidden = !ok;
            }

            inputRecurso.addEventListener('input', function () {
                setRecursoValidado(false);
                sugerencias.hidden = true;
            });

            btnValidar.addEventListener('click', function () {
                var q = inputRecurso.value.trim();
                var desde = document.getElementById('hgisDesde').value;
                var hasta = document.getElementById('hgisHasta').value;

                if (!q || !desde || !hasta) {
                    mostrarError('Completá desde, hasta y el recurso antes de validar.');
                    return;
                }
                ocultarError();

                var icon = btnValidar.querySelector('i');
                icon.className = 'fas fa-spinner fa-spin';

                var params = new URLSearchParams({ q: q, fecha_inicio: desde, fecha_fin: hasta });
                fetchJson('{{ route('movil.historico-movil-gis.buscar-recurso') }}?' + params.toString())
                    .then(function (data) {
                        var items = data.items || [];
                        if (items.length === 0) {
                            mostrarError('No se encontró ningún recurso que coincida con "' + q + '" en ese rango.');
                            setRecursoValidado(false);
                        } else if (items.length === 1) {
                            inputRecurso.value = items[0].resourceName;
                            setRecursoValidado(true);
                            sugerencias.hidden = true;
                        } else {
                            sugerencias.innerHTML = items.map(function (it) {
                                return '<button type="button" class="m-card hgis-sugerencia" data-nombre="' + escapeHtml(it.resourceName) + '" style="text-align:left; width:100%; border:none;">' +
                                    '<div class="m-card__title">' + escapeHtml(it.resourceName) + '</div>' +
                                    (it.alias ? '<div class="m-card__subtitle">' + escapeHtml(it.alias) + '</div>' : '') +
                                    '</button>';
                            }).join('');
                            sugerencias.hidden = false;
                            sugerencias.querySelectorAll('.hgis-sugerencia').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    inputRecurso.value = btn.dataset.nombre;
                                    setRecursoValidado(true);
                                    sugerencias.hidden = true;
                                });
                            });
                        }
                    })
                    .catch(function (e) {
                        mostrarError('Error al buscar el recurso: ' + e.message);
                        setRecursoValidado(false);
                    })
                    .finally(function () {
                        icon.className = 'fas fa-search';
                    });
            });

            /* ═══ Consultar ═══ */
            var formConsulta = document.getElementById('hgisForm');
            var resultados = document.getElementById('hgisResultados');
            var currentHistorialId = null;
            var currentRegistros = [];

            formConsulta.addEventListener('submit', function (e) {
                e.preventDefault();
                ocultarError();

                var btnIcon = btnConsultar.querySelector('i');
                var claseOriginal = btnIcon.className;
                btnIcon.className = 'fas fa-spinner fa-spin';
                btnConsultar.disabled = true;

                fetchJson('{{ route('movil.historico-movil-gis.consultar') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recurso: inputRecurso.value.trim(),
                        fecha_inicio: document.getElementById('hgisDesde').value,
                        fecha_fin: document.getElementById('hgisHasta').value,
                        velocidad_maxima: document.getElementById('hgisVelMax').value,
                        umbral_naranja: document.getElementById('hgisUmbralNaranja').value,
                        umbral_rojo: document.getElementById('hgisUmbralRojo').value,
                    }),
                })
                    .then(mostrarResultado)
                    .then(cargarPrevias)
                    .catch(function (e) { mostrarError('Error al consultar el GIS: ' + e.message); })
                    .finally(function () {
                        btnIcon.className = claseOriginal;
                        btnConsultar.disabled = false;
                    });
            });

            var registrosMostrados = 0;
            var TANDA = 100;

            function renderizarRegistrosTanda() {
                var cont = document.getElementById('hgisRegistros');
                var hasta = Math.min(registrosMostrados + TANDA, currentRegistros.length);
                var html = '';
                for (var i = registrosMostrados; i < hasta; i++) {
                    var r = currentRegistros[i];
                    var claseEstado = r.color_estado === 'detenido' ? 'm-hgis-badge--detenido' : 'm-hgis-badge--movimiento';
                    html += '<div class="m-card hgis-registro" data-idx="' + i + '">' +
                        '<div class="m-card__title">' + escapeHtml(r.fecha) +
                        (r.exceso_velocidad ? ' <span class="m-hgis-badge m-hgis-badge--exceso">EXCESO</span>' : '') +
                        '</div>' +
                        '<div class="m-card__subtitle">' + escapeHtml(r.direccion || 'Sin dirección') + '</div>' +
                        '<div class="m-card__meta">' +
                        '<span class="m-hgis-badge ' + claseEstado + '">' + escapeHtml(r.estado) + '</span>' +
                        '<span>' + r.velocidad + ' km/h</span>' +
                        (r.tiempo_detenido ? '<span>Detenido ' + escapeHtml(r.tiempo_detenido) + '</span>' : '') +
                        '</div></div>';
                }
                cont.insertAdjacentHTML('beforeend', html);
                cont.querySelectorAll('.hgis-registro').forEach(function (card) {
                    if (card.dataset.wired) return;
                    card.dataset.wired = '1';
                    card.addEventListener('click', function () {
                        irAPunto(parseInt(card.dataset.idx, 10));
                    });
                });
                registrosMostrados = hasta;
                document.getElementById('hgisRegistrosMas').hidden = registrosMostrados >= currentRegistros.length;
            }

            document.getElementById('hgisRegistrosMas').addEventListener('click', renderizarRegistrosTanda);

            function mostrarResultado(data) {
                currentHistorialId = data.historial_id;
                currentRegistros = data.registros || [];
                registrosMostrados = 0;

                var meta = data.metadata || {};
                document.getElementById('hgisMetaCard').innerHTML =
                    '<div class="m-card__title">' + escapeHtml(meta.recurso || '') + '</div>' +
                    '<div class="m-card__subtitle">' + escapeHtml(meta.fecha_inicio || '') + ' — ' + escapeHtml(meta.fecha_fin || '') + '</div>' +
                    '<div class="m-card__meta"><span>' + (meta.posiciones ?? currentRegistros.length) + ' posiciones</span></div>';

                document.getElementById('hgisRegistros').innerHTML = '';
                renderizarRegistrosTanda();

                document.getElementById('hgisMapaWrap').hidden = true;
                resultados.hidden = false;
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (mapaInicializado) {
                    dibujarRecorridoEnMapa();
                }

                return data;
            }

            /* ═══ Consultas previas ═══ */
            var previasWrap = document.getElementById('hgisPrevias');
            var previasPagina = 1;

            document.getElementById('hgisTogglePrevias').addEventListener('click', function () {
                var mostrar = previasWrap.hidden;
                previasWrap.hidden = !mostrar;
                if (mostrar && document.getElementById('hgisPreviasLista').children.length === 0) {
                    cargarPrevias();
                }
            });

            function cargarPrevias(pagina) {
                pagina = pagina || 1;
                var q = document.getElementById('hgisPreviasBuscar').value.trim();
                var params = new URLSearchParams({ q: q, page: pagina });

                return fetchJson('{{ route('movil.historico-movil-gis.buscar') }}?' + params.toString())
                    .then(function (data) {
                        var lista = document.getElementById('hgisPreviasLista');
                        if (pagina === 1) {
                            lista.innerHTML = '';
                        }
                        document.getElementById('hgisPreviasEmpty').hidden = data.total > 0;

                        var html = data.items.map(function (item) {
                            return '<div class="m-card">' +
                                '<div class="m-card__title">' + escapeHtml(item.recurso) + '</div>' +
                                '<div class="m-card__subtitle">' + escapeHtml(item.fecha_inicio) + ' — ' + escapeHtml(item.fecha_fin) + '</div>' +
                                '<div class="m-card__meta">' +
                                '<span>' + (item.posiciones ?? 0) + ' pos. · ' + escapeHtml(item.procesado_por) + ' · ' + escapeHtml(item.procesado_el) + '</span>' +
                                '</div>' +
                                '<div class="m-card__meta" style="margin-top:.4rem;">' +
                                '<button type="button" class="m-chip m-chip--action hgis-previa-cargar" data-id="' + item.id + '"><i class="fas fa-eye"></i> Ver</button>' +
                                '<button type="button" class="m-chip m-chip--action hgis-previa-eliminar" data-id="' + item.id + '"><i class="fas fa-trash"></i></button>' +
                                '</div></div>';
                        }).join('');
                        lista.insertAdjacentHTML('beforeend', html);

                        lista.querySelectorAll('.hgis-previa-cargar').forEach(function (btn) {
                            if (btn.dataset.wired) return;
                            btn.dataset.wired = '1';
                            btn.addEventListener('click', function () {
                                fetchJson('{{ url('movil/historico-movil-gis') }}/' + btn.dataset.id + '/cargar')
                                    .then(mostrarResultado)
                                    .catch(function (e) { mostrarError('No se pudo cargar la consulta: ' + e.message); });
                            });
                        });
                        lista.querySelectorAll('.hgis-previa-eliminar').forEach(function (btn) {
                            if (btn.dataset.wired) return;
                            btn.dataset.wired = '1';
                            btn.addEventListener('click', function () {
                                if (!confirm('¿Eliminar esta consulta?')) return;
                                fetchJson('{{ url('movil/historico-movil-gis') }}/' + btn.dataset.id, { method: 'DELETE' })
                                    .then(function () {
                                        previasPagina = 1;
                                        cargarPrevias(1);
                                    });
                            });
                        });

                        previasPagina = data.pagina_actual;
                        document.getElementById('hgisPreviasMas').hidden = data.pagina_actual >= data.ultima_pagina;
                    });
            }

            document.getElementById('hgisPreviasMas').addEventListener('click', function () {
                cargarPrevias(previasPagina + 1);
            });

            var previasBuscarTimer = null;
            document.getElementById('hgisPreviasBuscar').addEventListener('input', function () {
                clearTimeout(previasBuscarTimer);
                previasBuscarTimer = setTimeout(function () { cargarPrevias(1); }, 400);
            });

            /* ═══ Compartir PDF / recorrido ═══ */
            function descargarBlob(blob, nombreArchivo) {
                var urlObjeto = URL.createObjectURL(blob);
                var enlace = document.createElement('a');
                enlace.href = urlObjeto;
                enlace.download = nombreArchivo;
                document.body.appendChild(enlace);
                enlace.click();
                document.body.removeChild(enlace);
                URL.revokeObjectURL(urlObjeto);
            }

            async function compartirArchivo(url, nombreArchivo, tipoMime, boton) {
                var icono = boton.querySelector('i');
                var claseOriginal = icono.className;
                icono.className = 'fas fa-spinner fa-spin';
                boton.disabled = true;

                try {
                    var respuesta = await fetch(url, { credentials: 'same-origin' });
                    if (!respuesta.ok) {
                        throw new Error('HTTP ' + respuesta.status);
                    }
                    var blob = await respuesta.blob();
                    var archivo = new File([blob], nombreArchivo, { type: blob.type || tipoMime });

                    if (navigator.canShare && navigator.canShare({ files: [archivo] })) {
                        try {
                            await navigator.share({ files: [archivo] });
                            return;
                        } catch (e) {
                            if (e.name === 'AbortError') {
                                return; // el usuario cerró el panel de compartir
                            }
                            // El navegador puede rechazar el share si tardó
                            // demasiado en generarse/descargarse el archivo y
                            // se perdió el "gesto de usuario" (común con
                            // recorridos largos). Se descarga en su lugar para
                            // que el usuario lo adjunte a mano.
                            descargarBlob(blob, nombreArchivo);
                            return;
                        }
                    }

                    descargarBlob(blob, nombreArchivo);
                } catch (e) {
                    alert('No se pudo generar el archivo: ' + e.message);
                } finally {
                    icono.className = claseOriginal;
                    boton.disabled = false;
                }
            }

            var btnPdf = document.getElementById('hgisBtnPdf');
            if (btnPdf) {
                btnPdf.addEventListener('click', function () {
                    if (!currentHistorialId) return;
                    var url = '{{ url('movil/historico-movil-gis') }}/' + currentHistorialId + '/pdf';
                    compartirArchivo(url, 'HistoricoMovil.pdf', 'application/pdf', btnPdf);
                });
            }

            var btnRecorridoCompartir = document.getElementById('hgisBtnRecorridoCompartir');
            if (btnRecorridoCompartir) {
                btnRecorridoCompartir.addEventListener('click', function () {
                    if (!currentHistorialId) return;
                    var url = '{{ url('movil/historico-movil-gis') }}/' + currentHistorialId + '/recorrido';
                    compartirArchivo(url, 'Recorrido.html', 'text/html', btnRecorridoCompartir);
                });
            }

            /* ═══ Mapa del recorrido ═══ */
            var mapaInicializado = false;
            var mapa, routePolyline, trailPolyline, carMarker, paradaMarkers = [];
            var latlngsValidos = []; // [{idx, lat, lng}]
            var playbackPos = 0, playing = false, playTimer = null;

            function colorAccent() {
                return getComputedStyle(document.documentElement).getPropertyValue('--m-accent').trim() || '#0d6efd';
            }

            function inicializarMapa() {
                if (mapaInicializado) return;
                mapaInicializado = true;

                mapa = L.map('hgisMap');
                var tileClaro = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);

                routePolyline = L.polyline([], { color: '#888', dashArray: '6,6', weight: 3 }).addTo(mapa);
                trailPolyline = L.polyline([], { color: colorAccent(), weight: 4 }).addTo(mapa);

                var carIcon = L.divIcon({
                    className: 'm-map-marker m-hgis-car',
                    html: '<i class="fas fa-car"></i>',
                    iconSize: [26, 26],
                    iconAnchor: [13, 13]
                });
                carMarker = L.marker([0, 0], { icon: carIcon, zIndexOffset: 1000 });

                document.getElementById('hgisPlayerPlay').addEventListener('click', togglePlay);
                document.getElementById('hgisPlayerPrev').addEventListener('click', function () { pausar(); irAIndicePlayback(playbackPos - 1); });
                document.getElementById('hgisPlayerNext').addEventListener('click', function () { pausar(); irAIndicePlayback(playbackPos + 1); });
                document.getElementById('hgisPlayerRange').addEventListener('input', function (e) {
                    pausar();
                    irAIndicePlayback(parseInt(e.target.value, 10));
                });
            }

            function dibujarRecorridoEnMapa() {
                pausar();
                latlngsValidos = [];
                currentRegistros.forEach(function (r, idx) {
                    if (r.lat !== null && r.lng !== null && r.lat !== undefined && r.lng !== undefined) {
                        latlngsValidos.push({ idx: idx, lat: r.lat, lng: r.lng });
                    }
                });

                var puntos = latlngsValidos.map(function (p) { return [p.lat, p.lng]; });
                routePolyline.setLatLngs(puntos);
                trailPolyline.setLatLngs([]);

                paradaMarkers.forEach(function (m) { mapa.removeLayer(m); });
                paradaMarkers = [];
                var coloresParada = { yellow: '#e6b800', orange: '#e07800', red: '#d9302f' };
                currentRegistros.forEach(function (r) {
                    if (r.tiempo_detenido && r.lat !== null && r.lng !== null) {
                        var circ = L.circleMarker([r.lat, r.lng], {
                            radius: 6,
                            color: coloresParada[r.color_tiempo] || '#e6b800',
                            fillColor: coloresParada[r.color_tiempo] || '#e6b800',
                            fillOpacity: .8,
                            weight: 1
                        }).bindPopup('Detenido ' + escapeHtml(r.tiempo_detenido));
                        circ.addTo(mapa);
                        paradaMarkers.push(circ);
                    }
                });

                if (puntos.length > 0) {
                    if (!mapa.hasLayer(carMarker)) {
                        carMarker.addTo(mapa);
                    }
                    mapa.fitBounds(puntos, { padding: [24, 24] });
                }

                document.getElementById('hgisPlayerRange').max = Math.max(latlngsValidos.length - 1, 0);
                playbackPos = 0;
                if (latlngsValidos.length > 0) {
                    irAIndicePlayback(0);
                }
            }

            function irAIndicePlayback(pos) {
                if (latlngsValidos.length === 0) return;
                pos = Math.max(0, Math.min(pos, latlngsValidos.length - 1));
                playbackPos = pos;

                var puntos = latlngsValidos.slice(0, pos + 1).map(function (p) { return [p.lat, p.lng]; });
                trailPolyline.setLatLngs(puntos);
                var actual = latlngsValidos[pos];
                carMarker.setLatLng([actual.lat, actual.lng]);
                mapa.panTo([actual.lat, actual.lng]);

                document.getElementById('hgisPlayerRange').value = pos;

                var reg = currentRegistros[actual.idx];
                document.getElementById('hgisPlayerInfo').innerHTML =
                    escapeHtml(reg.fecha) + ' · ' + reg.velocidad + ' km/h · ' + escapeHtml(reg.direccion || '') +
                    ' · <strong>' + escapeHtml(reg.estado) + '</strong>';
            }

            function irAPunto(idxRegistro) {
                document.getElementById('hgisMapaWrap').hidden = false;
                inicializarMapa();
                if (latlngsValidos.length === 0) {
                    dibujarRecorridoEnMapa();
                }
                var posEnLatlngs = latlngsValidos.findIndex(function (p) { return p.idx === idxRegistro; });
                if (posEnLatlngs >= 0) {
                    pausar();
                    setTimeout(function () { mapa.invalidateSize(); irAIndicePlayback(posEnLatlngs); }, 50);
                }
            }

            function togglePlay() {
                if (playing) { pausar(); return; }
                if (latlngsValidos.length === 0) return;
                playing = true;
                document.getElementById('hgisPlayerPlay').innerHTML = '<i class="fas fa-pause"></i>';
                playTimer = setInterval(function () {
                    if (playbackPos >= latlngsValidos.length - 1) {
                        pausar();
                        return;
                    }
                    irAIndicePlayback(playbackPos + 1);
                }, 350);
            }

            function pausar() {
                playing = false;
                document.getElementById('hgisPlayerPlay').innerHTML = '<i class="fas fa-play"></i>';
                if (playTimer) {
                    clearInterval(playTimer);
                    playTimer = null;
                }
            }

            document.getElementById('hgisBtnMapa').addEventListener('click', function () {
                var wrap = document.getElementById('hgisMapaWrap');
                wrap.hidden = !wrap.hidden;
                if (!wrap.hidden) {
                    inicializarMapa();
                    setTimeout(function () {
                        mapa.invalidateSize();
                        dibujarRecorridoEnMapa();
                    }, 50);
                } else {
                    pausar();
                }
            });
        })();
    </script>
@endsection
