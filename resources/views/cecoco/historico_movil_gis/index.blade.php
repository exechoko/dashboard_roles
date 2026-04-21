@extends('layouts.app')

@section('css')
<style>
    .form-card { border-radius: 16px; box-shadow: 0 4px 24px rgba(103,119,239,.15); }
    .form-card .card-header { background:linear-gradient(135deg,#6777ef,#35199a); color:#fff; border-radius:16px 16px 0 0; padding:12px 20px; }

    .speed-input-group .input-group-text { background: #6777ef; color: #fff; border-color: #6777ef; }

    .leyenda-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 600; }
    .leyenda-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }

    #tabla-resultado thead th {
        background: linear-gradient(45deg, #6777ef, #35199a); color: #fff;
        font-size: .85rem; white-space: nowrap; vertical-align: middle; border: none;
    }
    #tabla-resultado tbody tr { transition: background .15s; }
    #tabla-resultado tbody tr:hover { filter: brightness(.95); }
    #tabla-resultado td { vertical-align: middle; font-size: .85rem; }

    .pill-detenido { background:#0070c0; color:#fff; padding:3px 10px; border-radius:12px; white-space:nowrap; font-size:.8rem; }
    .pill-movimiento { background:#00c41c; color:#fff; padding:3px 10px; border-radius:12px; white-space:nowrap; font-size:.8rem; }

    #tabla-resultado tbody td.td-yellow { background:#ffff00 !important; color:#000 !important; }
    #tabla-resultado tbody td.td-orange { background:#ffa500 !important; color:#000 !important; }
    #tabla-resultado tbody td.td-red    { background:#ff0000 !important; color:#fff !important; }

    .badge-exceso { background:#ff0000; color:#fff; font-weight:700; padding:3px 10px; border-radius:12px; font-size:.8rem; }

    .metadata-banner { background:linear-gradient(135deg,#6777ef,#35199a); color:#fff; border-radius:12px; padding:16px 24px; }
    .metadata-banner .meta-item { font-size:.85rem; opacity:.9; }
    .metadata-banner .meta-val  { font-weight:700; font-size:1rem; }

    .btn-export-excel { background:#1d6f42; color:#fff; border:none; border-radius:8px; }
    .btn-export-excel:hover { background:#155233; color:#fff; }

    #processing-overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999;
        align-items:center; justify-content:center; flex-direction:column; gap:16px;
    }
    #processing-overlay.show { display:flex; }
    #processing-overlay .spinner-border { width:3.5rem; height:3.5rem; color:#6777ef; }
    #processing-overlay p { color:#fff; font-size:1.1rem; margin:0; }

    .historial-card { border-radius:12px; overflow:hidden; }
    .historial-card .card-header { background:linear-gradient(135deg,#2c3e50,#1a252f); color:#fff; padding:10px 18px; }

    /* ═══ MAPA MODAL ═══ */
    #recorrido-map { height: 480px; width: 100%; border-radius: 0 0 8px 8px; }
    .player-panel { background:linear-gradient(135deg,#1a1a2e,#16213e); border-radius:12px 12px 0 0; padding:14px 18px; display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .player-btn { width:46px; height:46px; border-radius:50%; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition:transform .15s, box-shadow .15s; }
    .player-btn:hover { transform:scale(1.1); box-shadow:0 4px 12px rgba(0,0,0,.4); }
    .player-btn:active { transform:scale(.95); }
    .btn-play  { background:linear-gradient(135deg,#00c41c,#007a13); color:#fff; }
    .btn-pause { background:linear-gradient(135deg,#ffa500,#cc7a00); color:#fff; }
    .btn-step  { background:linear-gradient(135deg,#6777ef,#35199a); color:#fff; }
    .btn-stop  { background:linear-gradient(135deg,#e74c3c,#922b21); color:#fff; }
    .btn-prev  { background:linear-gradient(135deg,#8e44ad,#5b2c6f); color:#fff; }
    .player-info { background:rgba(255,255,255,.08); border-radius:8px; padding:6px 12px; color:#fff; font-size:.78rem; line-height:1.5; min-width:200px; flex:1; }
    .player-info .pi-fecha  { font-weight:700; font-size:.85rem; }
    .player-info .pi-vel    { color:#7ecff7; font-weight:600; }
    .player-info .pi-dir    { color:#b0b8d1; font-size:.75rem; }
    .player-info .pi-estado { font-size:.75rem; }
    .speed-slider-wrap { display:flex; align-items:center; gap:8px; color:#b0b8d1; font-size:.78rem; }
    .speed-slider-wrap input[type=range] { accent-color:#6777ef; width:90px; }
    .progress-wrap { display:flex; align-items:center; gap:6px; color:#b0b8d1; font-size:.78rem; width:100%; }
    .progress-bar-custom { flex:1; height:5px; background:rgba(255,255,255,.15); border-radius:4px; overflow:hidden; cursor:pointer; }
    .progress-bar-fill   { height:100%; background:linear-gradient(90deg,#6777ef,#35199a); border-radius:4px; width:0%; transition:width .15s; }
    .car-marker { background:transparent; border:none; }
    .car-dot { width:18px; height:18px; border-radius:50%; background:radial-gradient(circle at 40% 40%, #fff 10%, #6777ef 55%, #35199a 100%); border:2px solid #fff; box-shadow:0 0 8px rgba(103,119,239,.8), 0 0 0 3px rgba(103,119,239,.3); }
    .leaflet-popup-content { min-width:200px; font-size:.82rem; }
    #tabla-body tr.fila-activa { background:linear-gradient(90deg,rgba(103,119,239,.25),rgba(53,25,154,.15)) !important; outline:2px solid #6777ef; }

    /* ── Estado del recurso validado ── */
    .recurso-ok-badge { display:inline-block; font-size:.7rem; padding:2px 8px; border-radius:10px; background:#00a832; color:#fff; margin-left:6px; }
    #btn-consultar:disabled { opacity:.55; cursor:not-allowed; }
</style>
@stop

@section('content')
<div id="processing-overlay">
    <div class="spinner-border" role="status"></div>
    <p id="overlay-msg">Consultando GIS, por favor espere...</p>
</div>

<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-satellite-dish mr-2" style="color:#6777ef"></i>Histórico Móvil GIS</h3>
    </div>

    <div class="section-body">

        {{-- Historial --}}
        <div class="row no-print mb-3">
            <div class="col-lg-12">
                <div class="card historial-card">
                    <div class="card-header d-flex align-items-center justify-content-between" style="gap:8px;flex-wrap:wrap">
                        <span style="font-size:.9rem">
                            <i class="fas fa-history mr-1"></i>Consultas previas
                            <span class="badge badge-light ml-1" id="hist-count">—</span>
                        </span>
                        <div style="display:flex;height:30px">
                            <span style="display:flex;align-items:center;padding:0 8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-right:none;border-radius:4px 0 0 4px;color:#fff;font-size:.82rem">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="hist-busqueda" placeholder="Recurso o archivo..."
                                   style="height:30px;width:220px;padding:0 8px;font-size:.8rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:0 4px 4px 0;color:#fff;outline:none">
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:240px;overflow-y:auto">
                        <table class="table table-sm table-hover mb-0">
                            <thead style="position:sticky;top:0;z-index:1;background:#2c3e50;color:#fff">
                                <tr>
                                    <th>Recurso</th>
                                    <th class="text-center">Período</th>
                                    <th class="text-center">Pos.</th>
                                    <th class="text-center">Consultado por</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="hist-body">
                                <tr><td colspan="6" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i> Cargando historial...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario de consulta --}}
        <div class="row no-print">
            <div class="col-lg-12">
                <div class="card form-card">
                    <div class="card-header">
                        <h4 class="card-title mb-0"><i class="fas fa-search mr-2"></i>Consultar histórico desde GIS viewer</h4>
                    </div>
                    <div class="card-body">
                        <form id="form-consulta">
                            @csrf
                            {{-- Fila 1: Desde · Hasta · Recurso · Vel. máxima --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="far fa-calendar-alt mr-1" style="color:#6777ef"></i>Desde
                                        </label>
                                        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="far fa-calendar-alt mr-1" style="color:#6777ef"></i>Hasta
                                        </label>
                                        <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-car mr-1" style="color:#6777ef"></i>Recurso
                                            <span id="recurso-ok" class="recurso-ok-badge" style="display:none"><i class="fas fa-check mr-1"></i>Validado</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" id="recurso" name="recurso" class="form-control" placeholder="Ej: 916, Cria, Pb..." required autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="button" id="btn-buscar-recurso" class="btn btn-outline-primary" title="Buscar recursos en el GIS">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Primero completá fechas y <strong>buscá el recurso</strong> con <i class="fas fa-search"></i>.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-tachometer-alt mr-1" style="color:#6777ef"></i>Vel. máxima
                                        </label>
                                        <div class="input-group speed-input-group">
                                            <input type="number" id="velocidad_maxima" name="velocidad_maxima" class="form-control" min="0" step="1" value="45">
                                            <div class="input-group-append">
                                                <span class="input-group-text">km/h</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Fila 2: Umbral naranja · Umbral rojo · Botón consultar --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1"><i class="fas fa-clock mr-1" style="color:#ffa500"></i>Umbral naranja</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" style="background:#ffa500;color:#000;border-color:#ffa500">Naranja</span>
                                            </div>
                                            <input type="number" id="umbral_naranja" name="umbral_naranja" class="form-control" min="1" step="1" value="30">
                                            <div class="input-group-append"><span class="input-group-text">min</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1"><i class="fas fa-clock mr-1" style="color:#ff0000"></i>Umbral rojo</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" style="background:#ff0000;color:#fff;border-color:#ff0000">Rojo</span>
                                            </div>
                                            <input type="number" id="umbral_rojo" name="umbral_rojo" class="form-control" min="1" step="1" value="45">
                                            <div class="input-group-append"><span class="input-group-text">min</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" id="btn-consultar" class="btn btn-primary btn-block btn-lg" disabled title="Primero buscá y validá el recurso">
                                        <i class="fas fa-satellite-dish mr-1"></i> Consultar histórico
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resultado --}}
        <div id="resultado-wrap" class="row mt-3" style="display:none">
            <div class="col-lg-12">
                <div class="metadata-banner mb-3 d-flex align-items-center justify-content-between flex-wrap" style="gap:16px">
                    <div>
                        <div class="meta-item">Recurso</div>
                        <div class="meta-val" id="meta-recurso">—</div>
                    </div>
                    <div>
                        <div class="meta-item">Período</div>
                        <div class="meta-val"><span id="meta-desde">—</span> — <span id="meta-hasta">—</span></div>
                    </div>
                    <div>
                        <div class="meta-item">Posiciones</div>
                        <div class="meta-val" id="meta-pos">—</div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <button type="button" id="btn-ver-recorrido" class="btn btn-lg" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:8px">
                            <i class="fas fa-route mr-1"></i> Ver Recorrido
                        </button>
                        <button type="button" id="btn-exportar" class="btn btn-export-excel">
                            <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="mb-2 d-flex align-items-center" style="gap:10px;flex-wrap:wrap">
                    <span class="leyenda-badge" style="background:#e8f4ff;color:#000"><span class="leyenda-dot" style="background:#0070c0"></span> Detenido</span>
                    <span class="leyenda-badge" style="background:#e8ffe8;color:#000"><span class="leyenda-dot" style="background:#00c41c"></span> En movimiento</span>
                    <span class="leyenda-badge" style="background:#ffffcc;color:#000"><span class="leyenda-dot" style="background:#ffff00"></span> Detenido < <span id="ley-naranja">30</span> min</span>
                    <span class="leyenda-badge" style="background:#ffe5b8;color:#000"><span class="leyenda-dot" style="background:#ffa500"></span> Detenido <span id="ley-rango">30–45</span> min</span>
                    <span class="leyenda-badge" style="background:#ffcccc;color:#000"><span class="leyenda-dot" style="background:#ff0000"></span> Detenido > <span id="ley-rojo">45</span> min</span>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="tabla-resultado" class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Velocidad</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Mapa</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Tiempo detenido</th>
                                    <th class="text-center" id="th-exceso" style="display:none">Exceso</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Form auxiliar para exportar Excel --}}
<form id="form-excel" method="POST" action="{{ route('cecoco.historico-movil-gis.exportar-excel') }}" style="display:none">
    @csrf
    <input type="hidden" id="export-data" name="data">
</form>

{{-- ═══ MODAL RECORRIDO ═══ --}}
<div class="modal fade" id="recorridoModal" tabindex="-1" role="dialog" data-backdrop="false"
     style="background:rgba(0,0,0,.6)" aria-labelledby="recorridoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width:92vw" role="document">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;border:none">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:none;padding:12px 18px">
                <h5 class="modal-title text-white" id="recorridoModalLabel">
                    <i class="fas fa-route mr-2" style="color:#6777ef"></i>Recorrido del Móvil
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="player-panel no-print">
                <button class="player-btn btn-stop" id="btn-stop" title="Detener y volver al inicio"><i class="fas fa-stop"></i></button>
                <button class="player-btn btn-prev" id="btn-prev" title="Punto anterior"><i class="fas fa-step-backward"></i></button>
                <button class="player-btn btn-play" id="btn-play" title="Reproducir"><i class="fas fa-play"></i></button>
                <button class="player-btn btn-step" id="btn-step" title="Avanzar un punto"><i class="fas fa-step-forward"></i></button>
                <div class="player-info" id="player-info">
                    <div class="pi-fecha">— Seleccioná un punto —</div>
                    <div class="pi-vel"></div>
                    <div class="pi-dir"></div>
                    <div class="pi-estado"></div>
                </div>
                <div class="speed-slider-wrap">
                    <i class="fas fa-rabbit text-white" style="font-size:.8rem"></i>
                    <input type="range" id="speed-slider" min="1" max="10" value="5" title="Velocidad de reproducción">
                    <i class="fas fa-horse-head text-white" style="font-size:.8rem"></i>
                    <span id="speed-label" class="text-white" style="font-size:.75rem">5x</span>
                </div>
                <div class="progress-wrap">
                    <span id="prog-current">0</span>
                    <div class="progress-bar-custom" id="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progress-bar-fill"></div>
                    </div>
                    <span id="prog-total">0</span>
                </div>
            </div>

            <div id="recorrido-map"></div>

            <div class="modal-footer" style="background:#f8f9ff;border-top:1px solid #dee2e6;padding:8px 18px">
                <small class="text-muted">
                    <i class="fas fa-circle mr-1" style="color:#6777ef"></i>En movimiento &nbsp;
                    <i class="fas fa-circle mr-1" style="color:#0070c0"></i>Detenido &nbsp;
                    <i class="fas fa-exclamation-circle mr-1 text-danger"></i>Exceso de velocidad
                </small>
                <button type="button" class="btn btn-secondary btn-sm ml-auto" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: selección de recurso cuando hay múltiples coincidencias --}}
<div class="modal fade" id="modal-recursos" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#6777ef,#35199a);color:#fff">
                <h5 class="modal-title"><i class="fas fa-search mr-2"></i>Seleccioná un recurso</h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:0">
                <div id="modal-recursos-info" class="p-3 text-muted" style="font-size:.85rem;border-bottom:1px solid #dee2e6">—</div>
                <ul class="list-group list-group-flush" id="modal-recursos-lista"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let resultadoData = null;
    let histBusquedaTimer = null;
    let registros = [];             // Set desde renderizar() — fuente de verdad para el mapa
    let recursoValidado = false;    // Solo se habilita Consultar cuando el usuario eligió un recurso

    // Estado del mapa/player
    let recorridoMap = null;
    let mapInitialized = false;
    let routePolyline = null, trailPolyline = null, carMarker = null;
    let staticMarkers = [];
    let playbackIndex = 0, isPlaying = false, playTimer = null;

    const overlay    = document.getElementById('processing-overlay');
    const overlayMsg = document.getElementById('overlay-msg');
    const showOverlay = (msg) => { overlayMsg.textContent = msg || 'Procesando...'; overlay.classList.add('show'); };
    const hideOverlay = () => overlay.classList.remove('show');

    const btnConsultar = document.getElementById('btn-consultar');
    const badgeOk      = document.getElementById('recurso-ok');

    function setRecursoValidado(ok) {
        recursoValidado = !!ok;
        btnConsultar.disabled = !ok;
        badgeOk.style.display = ok ? 'inline-block' : 'none';
        btnConsultar.title = ok ? '' : 'Primero buscá y validá el recurso';
    }

    // Si el usuario edita el input manualmente, invalidar
    document.getElementById('recurso').addEventListener('input', () => setRecursoValidado(false));

    /* ═══ BUSCADOR DE RECURSOS ═══ */
    document.getElementById('btn-buscar-recurso').addEventListener('click', function() {
        const q = document.getElementById('recurso').value.trim();
        const desde = document.getElementById('fecha_inicio').value;
        const hasta = document.getElementById('fecha_fin').value;
        if (!q) { swal('Atención', 'Escribí parte del nombre/ID del recurso.', 'warning'); return; }
        if (!desde || !hasta) { swal('Atención', 'Completá el rango de fechas antes de buscar.', 'warning'); return; }

        showOverlay('Buscando recursos en el GIS...');
        const params = new URLSearchParams({ q, fecha_inicio: desde, fecha_fin: hasta });

        fetch('{{ route("cecoco.historico-movil-gis.buscar-recurso") }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
        .then(data => {
            hideOverlay();
            const items = data.items || [];
            if (items.length === 0) {
                swal('Sin resultados', `No hay recursos con GPS que coincidan con "${q}" en el rango indicado.`, 'info');
                return;
            }
            if (items.length === 1) {
                document.getElementById('recurso').value = items[0].resourceName;
                setRecursoValidado(true);
                swal({ title: 'Recurso seleccionado', text: items[0].resourceName, icon: 'success', timer: 1500 });
                return;
            }
            abrirModalRecursos(items, q);
        })
        .catch(err => {
            hideOverlay();
            swal('Error', err.message || 'Error al buscar recursos.', 'error');
        });
    });

    function abrirModalRecursos(items, query) {
        const info = document.getElementById('modal-recursos-info');
        const lista = document.getElementById('modal-recursos-lista');
        info.innerHTML = `<strong>${items.length}</strong> recursos con datos GPS coinciden con "<strong>${query}</strong>". Elegí uno:`;
        lista.innerHTML = '';
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            const alias = item.alias ? ` <small class="text-muted">(${item.alias})</small>` : '';
            li.innerHTML = `<i class="fas fa-car mr-2" style="color:#6777ef"></i><strong>${item.resourceName}</strong>${alias}`;
            li.addEventListener('click', () => {
                document.getElementById('recurso').value = item.resourceName;
                setRecursoValidado(true);
                $('#modal-recursos').modal('hide');
            });
            lista.appendChild(li);
        });
        $('#modal-recursos').modal('show');
    }

    // Atajo: Enter en el campo recurso dispara la búsqueda (no el submit del form)
    document.getElementById('recurso').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-buscar-recurso').click();
        }
    });

    /* ═══ FORM SUBMIT ═══ */
    document.getElementById('form-consulta').addEventListener('submit', function(e) {
        e.preventDefault();
        const recurso = document.getElementById('recurso').value.trim();
        const desde   = document.getElementById('fecha_inicio').value;
        const hasta   = document.getElementById('fecha_fin').value;
        if (!recurso || !desde || !hasta) {
            swal('Atención', 'Recurso, desde y hasta son obligatorios.', 'warning');
            return;
        }
        if (!recursoValidado) {
            swal('Atención', 'Primero hacé click en <i class="fas fa-search"></i> para buscar y elegir el recurso. Así evitamos timeouts por nombres inexactos.', 'warning');
            return;
        }

        const fd = new FormData(this);
        showOverlay('Consultando GIS, por favor espere...');

        fetch('{{ route("cecoco.historico-movil-gis.consultar") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: fd
        })
        .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
        .then(data => {
            hideOverlay();
            resultadoData = data;
            renderizar(data);
            cargarHistorial();
        })
        .catch(err => {
            hideOverlay();
            const msg = err.message || (err.errors ? Object.values(err.errors).flat().join('<br>') : 'Error al consultar GIS.');
            swal('Error', msg, 'error');
        });
    });

    /* ═══ RENDER TABLA ═══ */
    function renderizar(data) {
        const { metadata, registros: regs, velocidad_maxima, umbral_naranja, umbral_rojo } = data;
        registros = regs || [];

        document.getElementById('resultado-wrap').style.display = '';
        document.getElementById('meta-recurso').textContent = metadata.recurso || '—';
        document.getElementById('meta-desde').textContent   = metadata.fecha_inicio || '—';
        document.getElementById('meta-hasta').textContent   = metadata.fecha_fin || '—';
        document.getElementById('meta-pos').textContent     = metadata.posiciones ?? registros.length;

        const un = umbral_naranja ?? 30, ur = umbral_rojo ?? 45;
        document.getElementById('ley-naranja').textContent = un;
        document.getElementById('ley-rango').textContent   = un + '–' + ur;
        document.getElementById('ley-rojo').textContent    = ur;

        const thExceso = document.getElementById('th-exceso');
        if (velocidad_maxima > 0) { thExceso.style.display=''; thExceso.textContent='Exceso (>'+velocidad_maxima+' km/h)'; }
        else thExceso.style.display='none';

        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        registros.forEach((reg, idx) => {
            const tr = document.createElement('tr');
            tr.dataset.index = idx;
            if (reg.color_estado === 'detenido') tr.style.background = '#e8f4ff';

            td(tr, reg.id, 'text-center font-weight-bold');
            td(tr, reg.fecha, '');
            td(tr, reg.velocidad + ' km/h', reg.exceso_velocidad ? 'text-center text-danger font-weight-bold' : 'text-center');
            td(tr, reg.direccion || '—', '');

            const tdM = document.createElement('td'); tdM.className = 'text-center';
            if (reg.enlace) {
                const a = document.createElement('a'); a.href=reg.enlace; a.target='_blank';
                a.className='btn btn-sm btn-dark'; a.innerHTML='<i class="fas fa-map-marker-alt"></i>';
                tdM.appendChild(a);
            }
            tr.appendChild(tdM);

            const tdE = document.createElement('td'); tdE.className='text-center';
            const pill = document.createElement('span');
            pill.className = reg.color_estado==='detenido' ? 'pill-detenido' : 'pill-movimiento';
            pill.textContent = reg.estado;
            tdE.appendChild(pill);
            tr.appendChild(tdE);

            const tdT = document.createElement('td'); tdT.className='text-center';
            if (reg.tiempo_detenido) {
                tdT.textContent = reg.tiempo_detenido;
                tdT.classList.add('td-' + (reg.color_tiempo || 'yellow'));
            } else tdT.textContent = '—';
            tr.appendChild(tdT);

            if (velocidad_maxima > 0) {
                const tdX = document.createElement('td'); tdX.className='text-center';
                if (reg.exceso_velocidad) tdX.innerHTML = '<span class="badge-exceso">EXCESO</span>';
                tr.appendChild(tdX);
            }

            // Click en fila → ir al punto en mapa (si el modal está abierto)
            tr.addEventListener('click', () => {
                if ($('#recorridoModal').hasClass('show')) irAPunto(idx);
            });

            tbody.appendChild(tr);
        });

        document.getElementById('resultado-wrap').scrollIntoView({behavior:'smooth', block:'start'});
    }

    function td(tr, val, cls) {
        const el = document.createElement('td');
        el.textContent = val ?? '';
        if (cls) el.className = cls;
        tr.appendChild(el);
    }

    /* ═══ MAPA / PLAYER ═══ */
    function initMapaRecorrido() {
        if (!mapInitialized) {
            recorridoMap = L.map('recorrido-map', { zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(recorridoMap);
            mapInitialized = true;
        }
        limpiarMapa();
        setTimeout(() => { cargarRecorrido(); recorridoMap.invalidateSize(); }, 350);
    }

    function limpiarMapa() {
        if (routePolyline) { recorridoMap.removeLayer(routePolyline); routePolyline = null; }
        if (trailPolyline) { recorridoMap.removeLayer(trailPolyline); trailPolyline = null; }
        if (carMarker)     { recorridoMap.removeLayer(carMarker);     carMarker = null; }
        staticMarkers.forEach(m => recorridoMap.removeLayer(m));
        staticMarkers = [];
        resetPlayback();
    }

    function cargarRecorrido() {
        if (!registros.length) return;
        const puntosValidos = registros.filter(r => r.lat !== null && r.lng !== null);
        if (!puntosValidos.length) return;

        const coords = puntosValidos.map(r => [r.lat, r.lng]);
        routePolyline = L.polyline(coords, { color:'#adb5bd', weight:3, opacity:.5, dashArray:'4 4' }).addTo(recorridoMap);
        trailPolyline = L.polyline([], { color:'#6777ef', weight:4, opacity:.85 }).addTo(recorridoMap);

        const iconInicio = L.icon({ iconUrl: '/img/flag_init.svg',   iconSize:[32,32], iconAnchor:[8,32], popupAnchor:[0,-34] });
        const iconFin    = L.icon({ iconUrl: '/img/flag_finish.svg', iconSize:[32,32], iconAnchor:[8,32], popupAnchor:[0,-34] });

        const mInicio = L.marker(coords[0], { icon: iconInicio })
            .bindPopup(`<b>Inicio</b><br>${registros[0]?.fecha ?? ''}<br>${registros[0]?.direccion ?? ''}`)
            .addTo(recorridoMap);
        const ultimo = puntosValidos[puntosValidos.length - 1];
        const mFin = L.marker(coords[coords.length - 1], { icon: iconFin })
            .bindPopup(`<b>Fin</b><br>${ultimo?.fecha ?? ''}<br>${ultimo?.direccion ?? ''}`)
            .addTo(recorridoMap);
        staticMarkers.push(mInicio, mFin);

        registros.forEach(r => {
            if (r.lat === null) return;
            if (r.segundos_detenido && r.segundos_detenido >= 2700) {
                const m = L.circleMarker([r.lat, r.lng], {
                    radius: 8, color:'#ff0000', fillColor:'#ff0000', fillOpacity:.7, weight:2
                }).bindPopup(`<b>Parada larga</b><br>${r.fecha}<br>${r.tiempo_detenido}`).addTo(recorridoMap);
                staticMarkers.push(m);
            }
        });

        const carIcon = L.divIcon({ className:'car-marker', html:'<div class="car-dot"></div>', iconSize:[18,18], iconAnchor:[9,9] });
        carMarker = L.marker(coords[0], { icon: carIcon, zIndexOffset: 1000 })
            .bindPopup(popupContent(registros[0])).addTo(recorridoMap);

        recorridoMap.fitBounds(routePolyline.getBounds(), { padding:[30,30] });
        playbackIndex = 0;
        actualizarProgress();
        actualizarPlayerInfo(registros[0]);
    }

    function resetPlayback() {
        isPlaying = false;
        if (playTimer) { clearInterval(playTimer); playTimer = null; }
        playbackIndex = 0;
        actualizarBtnPlay();
    }

    function irAPunto(idx) {
        if (!carMarker) return;
        const reg = registros[idx];
        if (!reg || reg.lat === null) return;

        document.querySelectorAll('#tabla-body tr').forEach(r => r.classList.remove('fila-activa'));
        const fila = document.querySelector(`#tabla-body tr[data-index="${idx}"]`);
        if (fila) fila.classList.add('fila-activa');

        const trail = registros.slice(0, idx + 1).filter(r => r.lat !== null).map(r => [r.lat, r.lng]);
        trailPolyline.setLatLngs(trail);

        carMarker.setLatLng([reg.lat, reg.lng]);
        carMarker.setPopupContent(popupContent(reg));

        const dot = carMarker.getElement()?.querySelector('.car-dot');
        if (dot) {
            if (reg.exceso_velocidad)                dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #ff0000 55%, #990000 100%)';
            else if (reg.color_estado === 'detenido') dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #0070c0 55%, #004a80 100%)';
            else                                      dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #6777ef 55%, #35199a 100%)';
        }

        playbackIndex = idx;
        actualizarProgress();
        actualizarPlayerInfo(reg);
        recorridoMap.panTo([reg.lat, reg.lng], { animate: true, duration: .3 });
    }

    function avanzar() {
        if (playbackIndex >= registros.length - 1) { pausar(); return; }
        playbackIndex++;
        while (playbackIndex < registros.length - 1 && registros[playbackIndex].lat === null) playbackIndex++;
        irAPunto(playbackIndex);
    }

    function retroceder() {
        if (playbackIndex <= 0) return;
        playbackIndex--;
        while (playbackIndex > 0 && registros[playbackIndex].lat === null) playbackIndex--;
        irAPunto(playbackIndex);
    }

    function reproducir() {
        if (isPlaying) return;
        if (playbackIndex >= registros.length - 1) playbackIndex = 0;
        isPlaying = true;
        actualizarBtnPlay();
        const intervaloMs = () => Math.max(50, 600 - (parseInt(document.getElementById('speed-slider').value) - 1) * 60);
        playTimer = setInterval(() => { avanzar(); if (playbackIndex >= registros.length - 1) pausar(); }, intervaloMs());
    }

    function pausar() {
        isPlaying = false;
        if (playTimer) { clearInterval(playTimer); playTimer = null; }
        actualizarBtnPlay();
    }

    function detener() {
        pausar();
        playbackIndex = 0;
        if (carMarker && registros.length && registros[0].lat !== null) {
            trailPolyline.setLatLngs([]);
            carMarker.setLatLng([registros[0].lat, registros[0].lng]);
            actualizarProgress();
            actualizarPlayerInfo(registros[0]);
        }
        document.querySelectorAll('#tabla-body tr').forEach(r => r.classList.remove('fila-activa'));
    }

    function actualizarBtnPlay() {
        const btn = document.getElementById('btn-play');
        btn.innerHTML = isPlaying ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
        btn.className = isPlaying ? 'player-btn btn-pause' : 'player-btn btn-play';
        btn.title = isPlaying ? 'Pausar' : 'Reproducir';
    }

    function actualizarProgress() {
        const total = registros.length;
        document.getElementById('prog-current').textContent = playbackIndex + 1;
        document.getElementById('prog-total').textContent   = total;
        document.getElementById('progress-bar-fill').style.width = (((playbackIndex + 1) / total) * 100) + '%';
    }

    function actualizarPlayerInfo(reg) {
        if (!reg) return;
        const pi = document.getElementById('player-info');
        pi.querySelector('.pi-fecha').textContent  = reg.fecha;
        pi.querySelector('.pi-vel').textContent    = reg.velocidad + ' km/h' + (reg.exceso_velocidad ? ' ⚠ EXCESO' : '');
        pi.querySelector('.pi-dir').textContent    = reg.direccion || '';
        pi.querySelector('.pi-estado').textContent = reg.estado + (reg.tiempo_detenido ? ' · ' + reg.tiempo_detenido : '');
        pi.querySelector('.pi-vel').style.color    = reg.exceso_velocidad ? '#ff7675' : '#7ecff7';
    }

    function popupContent(reg) {
        if (!reg) return '';
        return `<div style="font-size:.82rem;min-width:180px">
            <b>${reg.fecha}</b><br>
            <span style="color:#0070c0"><b>${reg.velocidad} km/h</b></span>
            ${reg.exceso_velocidad ? '<span style="color:red"> ⚠ EXCESO</span>' : ''}<br>
            ${reg.direccion || ''}<br>
            <span style="color:${reg.color_estado === 'detenido' ? '#0070c0' : '#00a832'}">${reg.estado}</span>
            ${reg.tiempo_detenido ? '<br><b>Detenido: ' + reg.tiempo_detenido + '</b>' : ''}
        </div>`;
    }

    // Eventos controles
    document.getElementById('btn-play').addEventListener('click', () => isPlaying ? pausar() : reproducir());
    document.getElementById('btn-step').addEventListener('click', () => { pausar(); avanzar(); });
    document.getElementById('btn-prev').addEventListener('click', () => { pausar(); retroceder(); });
    document.getElementById('btn-stop').addEventListener('click', detener);
    document.getElementById('speed-slider').addEventListener('input', function() {
        document.getElementById('speed-label').textContent = this.value + 'x';
        if (isPlaying) { // reiniciar timer con nueva velocidad
            clearInterval(playTimer);
            const intervaloMs = Math.max(50, 600 - (parseInt(this.value) - 1) * 60);
            playTimer = setInterval(() => { avanzar(); if (playbackIndex >= registros.length - 1) pausar(); }, intervaloMs);
        }
    });
    document.getElementById('progress-bar-wrap').addEventListener('click', function(e) {
        if (!registros.length) return;
        const pct = e.offsetX / this.offsetWidth;
        const idx = Math.round(pct * (registros.length - 1));
        pausar();
        irAPunto(Math.max(0, Math.min(idx, registros.length - 1)));
    });

    // Abrir/cerrar modal
    document.getElementById('btn-ver-recorrido').addEventListener('click', function() {
        if (!resultadoData || !registros.length) { swal('Atención', 'Primero consultá el histórico.', 'warning'); return; }
        const meta = resultadoData.metadata;
        document.getElementById('recorridoModalLabel').innerHTML =
            `<i class="fas fa-route mr-2" style="color:#6777ef"></i>Recorrido — ${meta.recurso} (${meta.fecha_inicio} → ${meta.fecha_fin})`;
        document.getElementById('prog-total').textContent = registros.length;
        $('#recorridoModal').modal('show');
    });
    $('#recorridoModal').on('shown.bs.modal', () => initMapaRecorrido());
    $('#recorridoModal').on('hidden.bs.modal', () => pausar());

    /* ═══ EXPORT EXCEL ═══ */
    document.getElementById('btn-exportar').addEventListener('click', function() {
        if (!resultadoData) return;
        document.getElementById('export-data').value = JSON.stringify(resultadoData);
        document.getElementById('form-excel').submit();
    });

    /* ═══ HISTORIAL ═══ */
    function cargarHistorial() {
        const q = document.getElementById('hist-busqueda').value;
        const url = '{{ route("cecoco.historico-movil-gis.buscar") }}?q=' + encodeURIComponent(q);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('hist-body');
                document.getElementById('hist-count').textContent = data.total ?? 0;
                if (!data.items || !data.items.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Sin consultas registradas.</td></tr>';
                    return;
                }
                tbody.innerHTML = '';
                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.style.cursor = 'pointer';
                    tr.innerHTML = `
                        <td><strong>${item.recurso || '—'}</strong></td>
                        <td class="text-center">${item.fecha_inicio || ''} → ${item.fecha_fin || ''}</td>
                        <td class="text-center">${item.posiciones}</td>
                        <td class="text-center">${item.procesado_por}</td>
                        <td class="text-center">${item.procesado_el}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-action="cargar" data-id="${item.id}" title="Cargar"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-danger" data-action="eliminar" data-id="${item.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>`;
                    tbody.appendChild(tr);
                });
            })
            .catch(() => {
                document.getElementById('hist-body').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al cargar historial.</td></tr>';
            });
    }

    document.getElementById('hist-busqueda').addEventListener('input', function() {
        clearTimeout(histBusquedaTimer);
        histBusquedaTimer = setTimeout(cargarHistorial, 300);
    });

    document.getElementById('hist-body').addEventListener('click', function(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const id = btn.dataset.id;
        if (btn.dataset.action === 'cargar') {
            showOverlay('Cargando consulta previa...');
            fetch('/cecoco/historico-movil-gis/' + id + '/cargar', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { hideOverlay(); resultadoData = data; renderizar(data); })
                .catch(() => { hideOverlay(); swal('Error', 'No se pudo cargar la consulta.', 'error'); });
        } else if (btn.dataset.action === 'eliminar') {
            swal({ title: '¿Eliminar?', text: 'Se borrará esta consulta del historial.', icon: 'warning', buttons: ['Cancelar', 'Eliminar'], dangerMode: true })
                .then(ok => {
                    if (!ok) return;
                    fetch('/cecoco/historico-movil-gis/' + id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    }).then(() => cargarHistorial());
                });
        }
    });

    /* ═══ INIT ═══ */
    // Default: rango de hoy
    const hoy = new Date();
    const pad = n => String(n).padStart(2, '0');
    const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const ini = new Date(hoy); ini.setHours(0,0,0,0);
    document.getElementById('fecha_inicio').value = fmt(ini);
    document.getElementById('fecha_fin').value    = fmt(hoy);

    cargarHistorial();
})();
</script>
@stop
