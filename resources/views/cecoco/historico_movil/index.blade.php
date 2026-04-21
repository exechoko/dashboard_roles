@extends('layouts.app')

@section('css')
<style>
    /* ── Upload card ── */
    .upload-card { border-radius: 16px; box-shadow: 0 4px 24px rgba(103,119,239,.15); }
    .upload-zone {
        border: 2.5px dashed #6777ef; border-radius: 12px; padding: 40px 20px;
        text-align: center; cursor: pointer; transition: background .2s, border-color .2s; background: #f8f9ff;
    }
    .upload-zone:hover, .upload-zone.dragover { background: #eef0ff; border-color: #3c4fb8; }
    .upload-zone i { font-size: 2.5rem; color: #6777ef; }
    .upload-zone p { margin: 10px 0 0; color: #6c757d; }

    /* ── Speed input ── */
    .speed-input-group .input-group-text { background: #6777ef; color: #fff; border-color: #6777ef; }

    /* ── Leyenda ── */
    .leyenda-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 600;
    }
    .leyenda-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }

    /* ── Tabla resultado ── */
    #tabla-resultado thead th {
        background: linear-gradient(45deg, #6777ef, #35199a); color: #fff;
        font-size: .85rem; white-space: nowrap; vertical-align: middle; border: none;
    }
    #tabla-resultado tbody tr { transition: background .15s; }
    #tabla-resultado tbody tr:hover { filter: brightness(.95); }
    #tabla-resultado td { vertical-align: middle; font-size: .85rem; }
    tr.fila-activa { outline: 2px solid #6777ef !important; }

    /* ── Estado pills ── */
    .pill-detenido { background:#0070c0; color:#fff; padding:3px 10px; border-radius:12px; white-space:nowrap; font-size:.8rem; }
    .pill-movimiento { background:#00c41c; color:#fff; padding:3px 10px; border-radius:12px; white-space:nowrap; font-size:.8rem; }

    /* ── Tiempo detenido — negro forzado en ambos temas ── */
    #tabla-resultado tbody td.td-yellow,
    body.dark-theme #tabla-resultado tbody td.td-yellow,
    body #tabla-resultado tbody td.td-yellow { background:#ffff00 !important; color:#000000 !important; }

    #tabla-resultado tbody td.td-orange,
    body.dark-theme #tabla-resultado tbody td.td-orange,
    body #tabla-resultado tbody td.td-orange { background:#ffa500 !important; color:#000000 !important; }

    #tabla-resultado tbody td.td-red,
    body.dark-theme #tabla-resultado tbody td.td-red,
    body #tabla-resultado tbody td.td-red    { background:#ff0000 !important; color:#ffffff !important; }

    /* ── Exceso ── */
    .badge-exceso { background:#ff0000; color:#fff; font-weight:700; padding:3px 10px; border-radius:12px; font-size:.8rem; }

    /* ── Metadata banner ── */
    .metadata-banner { background:linear-gradient(135deg,#6777ef,#35199a); color:#fff; border-radius:12px; padding:16px 24px; }
    .metadata-banner .meta-item { font-size:.85rem; opacity:.9; }
    .metadata-banner .meta-val  { font-weight:700; font-size:1rem; }

    /* ── Botones export ── */
    .btn-export-excel { background:#1d6f42; color:#fff; border:none; border-radius:8px; }
    .btn-export-excel:hover { background:#155233; color:#fff; }
    .btn-export-pdf   { background:#c0392b; color:#fff; border:none; border-radius:8px; }
    .btn-export-pdf:hover { background:#922b21; color:#fff; }

    /* ── Spinner overlay ── */
    #processing-overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999;
        align-items:center; justify-content:center; flex-direction:column; gap:16px;
    }
    #processing-overlay.show { display:flex; }
    #processing-overlay .spinner-border { width:3.5rem; height:3.5rem; color:#6777ef; }
    #processing-overlay p { color:#fff; font-size:1.1rem; margin:0; }

    /* ── Alerta atención ── */
    .alert-atencion { background:#fff3cd; border-left:5px solid #ffc107; border-radius:8px; padding:12px 16px; font-size:.85rem; color:#856404; }

    /* ═══ MAPA MODAL ════════════════════════════════════════════════════ */
    #recorrido-map { height: 480px; width: 100%; border-radius: 0 0 8px 8px; }

    /* ── Panel de controles ── */
    .player-panel {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px 12px 0 0; padding: 14px 18px;
        display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
    }
    .player-btn {
        width: 46px; height: 46px; border-radius: 50%; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        transition: transform .15s, box-shadow .15s;
    }
    .player-btn:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(0,0,0,.4); }
    .player-btn:active { transform: scale(.95); }
    .btn-play  { background: linear-gradient(135deg, #00c41c, #007a13); color: #fff; }
    .btn-pause { background: linear-gradient(135deg, #ffa500, #cc7a00); color: #fff; }
    .btn-step  { background: linear-gradient(135deg, #6777ef, #35199a); color: #fff; }
    .btn-stop  { background: linear-gradient(135deg, #e74c3c, #922b21); color: #fff; }
    .btn-prev  { background: linear-gradient(135deg, #8e44ad, #5b2c6f); color: #fff; }

    .player-info {
        background: rgba(255,255,255,.08); border-radius: 8px;
        padding: 6px 12px; color: #fff; font-size: .78rem; line-height: 1.5;
        min-width: 200px; flex: 1;
    }
    .player-info .pi-fecha  { font-weight: 700; font-size: .85rem; }
    .player-info .pi-vel    { color: #7ecff7; font-weight: 600; }
    .player-info .pi-dir    { color: #b0b8d1; font-size: .75rem; }
    .player-info .pi-estado { font-size: .75rem; }

    .speed-slider-wrap { display: flex; align-items: center; gap: 8px; color: #b0b8d1; font-size: .78rem; }
    .speed-slider-wrap input[type=range] { accent-color: #6777ef; width: 90px; }

    .progress-wrap { display: flex; align-items: center; gap: 6px; color: #b0b8d1; font-size: .78rem; width: 100%; }
    .progress-bar-custom { flex: 1; height: 5px; background: rgba(255,255,255,.15); border-radius: 4px; overflow: hidden; cursor: pointer; }
    .progress-bar-fill   { height: 100%; background: linear-gradient(90deg, #6777ef, #35199a); border-radius: 4px; width: 0%; transition: width .15s; }

    /* Marker carro animado */
    .car-marker {
        background: transparent; border: none;
    }
    .car-dot {
        width: 18px; height: 18px; border-radius: 50%;
        background: radial-gradient(circle at 40% 40%, #fff 10%, #6777ef 55%, #35199a 100%);
        border: 2px solid #fff; box-shadow: 0 0 8px rgba(103,119,239,.8), 0 0 0 3px rgba(103,119,239,.3);
    }

    /* Popup de marcador */
    .leaflet-popup-content { min-width: 200px; font-size: .82rem; }

    /* ── Historial ── */
    .historial-card { border-radius:12px; overflow:hidden; }
    .historial-card .card-header { background:linear-gradient(135deg,#2c3e50,#1a252f); color:#fff; padding:10px 18px; }
    .historial-item {
        display:flex; align-items:center; gap:10px; padding:10px 14px;
        border-bottom:1px solid rgba(0,0,0,.07); cursor:pointer;
        transition:background .15s;
    }
    .historial-item:hover { background:rgba(103,119,239,.08); }
    .historial-item:last-child { border-bottom:none; }
    .hist-icon { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#6777ef,#35199a); display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; font-size:.9rem; }
    .hist-info { flex:1; min-width:0; }
    .hist-recurso { font-weight:700; font-size:.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hist-meta    { font-size:.75rem; color:#6c757d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hist-badges  { display:flex; flex-direction:column; gap:3px; align-items:flex-end; flex-shrink:0; }
    .hist-badge   { font-size:.7rem; padding:2px 7px; border-radius:10px; white-space:nowrap; }
    .btn-hist-del { background:none; border:none; color:#dc3545; padding:4px 6px; border-radius:6px; cursor:pointer; opacity:.6; }
    .btn-hist-del:hover { opacity:1; background:rgba(220,53,69,.1); }
    .hist-empty   { text-align:center; color:#adb5bd; padding:30px 16px; font-size:.88rem; }

    /* ── Toggle modo Individual / Lote ── */
    .modo-toggle { display:flex; border-radius:8px; overflow:hidden; border:1px solid rgba(255,255,255,.3); }
    .modo-btn {
        background: rgba(255,255,255,.12); color: rgba(255,255,255,.8);
        border: none; padding: 6px 16px; font-size: .82rem; cursor: pointer;
        transition: background .2s, color .2s; display:flex; align-items:center;
    }
    .modo-btn.activo { background: rgba(255,255,255,.28); color: #fff; font-weight: 600; }
    .modo-btn:hover:not(.activo) { background: rgba(255,255,255,.2); }

    /* ── Filas de estado en lote ── */
    .lote-estado-espera   { color:#6c757d; font-size:.78rem; }
    .lote-estado-proceso  { color:#6777ef; font-size:.78rem; font-weight:600; }
    .lote-estado-ok       { color:#00a832; font-size:.78rem; font-weight:600; }
    .lote-estado-error    { color:#dc3545; font-size:.78rem; font-weight:600; }

    /* ── Atajos de período ── */
    .btn-atajo {
        height: 30px; line-height: 1;
        background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25);
        border-radius: 4px; padding: 0 10px; font-size: .75rem; cursor: pointer;
        transition: background .15s, border-color .15s;
        display: inline-flex; align-items: center;
    }
    .btn-atajo:hover  { background: rgba(103,119,239,.5); border-color: #6777ef; }
    .btn-atajo.activo { background: #6777ef; border-color: #6777ef; font-weight: 600; }

    /* Inputs dentro del card-header oscuro */
    #hist-busqueda::placeholder,
    #hist-desde::placeholder,
    #hist-hasta::placeholder { color: rgba(255,255,255,.45); }
    #hist-busqueda:focus, #hist-desde:focus, #hist-hasta:focus {
        background: rgba(255,255,255,.18) !important;
        border-color: #6777ef !important;
        box-shadow: none;
    }

    /* Icono del calendario en tema oscuro */
    #hist-desde::-webkit-calendar-picker-indicator,
    #hist-hasta::-webkit-calendar-picker-indicator { filter: invert(1); opacity: .7; cursor: pointer; }

    @media print { .no-print { display:none !important; } .card { box-shadow:none !important; border:1px solid #ddd !important; } }
</style>
@stop

@section('content')
{{-- Overlay de procesamiento --}}
<div id="processing-overlay">
    <div class="spinner-border" role="status"></div>
    <p>Procesando archivo, por favor espere...</p>
</div>

<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-file-import mr-2" style="color:#6777ef"></i>Procesar Histórico Móvil</h3>
    </div>

    <div class="section-body">

        {{-- ── Historial de procesados ── --}}
        <div class="row no-print mb-3">
            <div class="col-lg-12">
                <div class="card historial-card">
                    <div class="card-header" style="padding:10px 16px">

                        {{-- ── Fila principal ── --}}
                        <div class="d-flex align-items-center justify-content-between" style="gap:8px;flex-wrap:wrap">

                            <span style="white-space:nowrap;font-size:.9rem">
                                <i class="fas fa-history mr-1"></i>Historial
                                <span class="badge badge-light ml-1" id="hist-count" style="font-size:.78rem">—</span>
                            </span>

                            {{-- Controles alineados en una sola fila --}}
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">

                                {{-- Buscador --}}
                                <div style="display:flex;height:30px">
                                    <span style="display:flex;align-items:center;padding:0 8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-right:none;border-radius:4px 0 0 4px;color:#fff;font-size:.82rem">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="hist-busqueda"
                                           placeholder="Recurso o archivo..."
                                           style="height:30px;width:200px;padding:0 8px;font-size:.8rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:0 4px 4px 0;color:#fff;outline:none">
                                </div>

                                {{-- Toggle Período --}}
                                <button id="btn-toggle-filtros" title="Filtrar por período de procesamiento"
                                        style="height:30px;padding:0 10px;font-size:.8rem;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:4px;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px">
                                    <i class="fas fa-calendar-alt"></i>Período
                                    <i class="fas fa-chevron-down" id="icon-toggle-filtros" style="font-size:.65rem"></i>
                                </button>

                                {{-- Limpiar --}}
                                <button id="btn-limpiar-filtros" title="Limpiar todos los filtros"
                                        style="display:none;height:30px;padding:0 10px;font-size:.8rem;background:rgba(220,53,69,.3);color:#fff;border:1px solid rgba(220,53,69,.5);border-radius:4px;cursor:pointer;white-space:nowrap;display:none;align-items:center;gap:5px">
                                    <i class="fas fa-times"></i>Limpiar
                                </button>
                            </div>
                        </div>

                        {{-- ── Panel de fechas (colapsable) ── --}}
                        <div id="hist-filtros-fecha" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.15)">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">

                                <span style="color:rgba(255,255,255,.7);font-size:.78rem;white-space:nowrap">
                                    <i class="fas fa-clock mr-1"></i>Procesado:
                                </span>

                                {{-- Desde --}}
                                <div style="display:flex;height:30px">
                                    <span style="display:flex;align-items:center;padding:0 8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-right:none;border-radius:4px 0 0 4px;color:rgba(255,255,255,.8);font-size:.75rem;white-space:nowrap">
                                        Desde
                                    </span>
                                    <input type="date" id="hist-desde"
                                           style="height:30px;padding:0 6px;font-size:.78rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:0 4px 4px 0;color:#fff;outline:none;width:140px">
                                </div>

                                {{-- Hasta --}}
                                <div style="display:flex;height:30px">
                                    <span style="display:flex;align-items:center;padding:0 8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-right:none;border-radius:4px 0 0 4px;color:rgba(255,255,255,.8);font-size:.75rem;white-space:nowrap">
                                        Hasta
                                    </span>
                                    <input type="date" id="hist-hasta"
                                           style="height:30px;padding:0 6px;font-size:.78rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:0 4px 4px 0;color:#fff;outline:none;width:140px">
                                </div>

                                {{-- Separador visual --}}
                                <span style="width:1px;height:20px;background:rgba(255,255,255,.2);display:inline-block"></span>

                                {{-- Atajos rápidos --}}
                                <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                                    <button class="btn-atajo" data-rango="hoy">Hoy</button>
                                    <button class="btn-atajo" data-rango="semana">Esta semana</button>
                                    <button class="btn-atajo" data-rango="mes">Este mes</button>
                                    <button class="btn-atajo" data-rango="mes_ant">Mes anterior</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla --}}
                    <div class="table-responsive" style="max-height:280px;overflow-y:auto">
                        <table class="table table-sm table-hover mb-0" id="hist-tabla">
                            <thead style="position:sticky;top:0;z-index:1;background:#2c3e50;color:#fff">
                                <tr>
                                    <th style="width:24px"></th>
                                    <th>Recurso / Archivo</th>
                                    <th class="text-center">Período</th>
                                    <th class="text-center">Pos.</th>
                                    <th class="text-center">Vel.máx</th>
                                    <th class="text-center">Umbrales</th>
                                    <th class="text-center">Procesado por</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="hist-body">
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Cargando historial...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    <div class="card-footer d-flex align-items-center justify-content-between py-2 px-3" id="hist-paginacion" style="background:#f8f9fa">
                        <span class="text-muted" style="font-size:.8rem" id="hist-info-pag">—</span>
                        <div class="d-flex align-items-center" style="gap:.4rem">
                            <button class="btn btn-sm btn-outline-secondary" id="hist-prev" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span style="font-size:.82rem" id="hist-pag-num">—</span>
                            <button class="btn btn-sm btn-outline-secondary" id="hist-next" disabled>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Panel de carga ── --}}
        <div class="row no-print">
            <div class="col-lg-12">
                <div class="card upload-card">

                    {{-- Header con toggle de modo --}}
                    <div class="card-header d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#6777ef,#35199a);border-radius:16px 16px 0 0;padding:12px 20px">
                        <h4 class="card-title mb-0" style="color:#fff">
                            <i class="fas fa-upload mr-2"></i>Importar archivo GPS (.xls / .xlsx)
                        </h4>
                        {{-- Toggle Individual / Lote --}}
                        <div class="modo-toggle" role="group">
                            <button type="button" id="btn-modo-individual" class="modo-btn activo">
                                <i class="fas fa-file mr-1"></i>Individual
                            </button>
                            <button type="button" id="btn-modo-lote" class="modo-btn">
                                <i class="fas fa-layer-group mr-1"></i>Por lotes
                                <span class="badge badge-light ml-1" style="font-size:.65rem">hasta 10</span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- ═══ MODO INDIVIDUAL ═══ --}}
                        <div id="seccion-individual">
                            <form id="form-upload" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="upload-zone" id="upload-zone">
                                            <i class="fas fa-file-excel"></i>
                                            <p id="upload-label">Arrastrá aquí el archivo o <strong>hacé clic</strong> para seleccionarlo</p>
                                            <p id="upload-filename" class="text-primary font-weight-bold" style="display:none"></p>
                                            <input type="file" id="archivo" name="archivo" accept=".xls,.xlsx" style="display:none" required>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-2">
                                            <label class="font-weight-bold mb-1">
                                                <i class="fas fa-tachometer-alt mr-1" style="color:#6777ef"></i>
                                                Velocidad máxima permitida
                                            </label>
                                            <div class="input-group speed-input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-car"></i></span>
                                                </div>
                                                <input type="number" id="velocidad_maxima" name="velocidad_maxima"
                                                       class="form-control" placeholder="Ej: 45" min="0" step="1" value="45">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">km/h</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="font-weight-bold mb-1">
                                                <i class="fas fa-clock mr-1" style="color:#ffa500"></i>
                                                Umbrales de tiempo detenido
                                            </label>
                                            <div class="row no-gutters">
                                                <div class="col-6 pr-1">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" style="background:#ffa500;color:#000;border-color:#ffa500;font-size:.75rem">Naranja</span>
                                                        </div>
                                                        <input type="number" id="umbral_naranja" name="umbral_naranja"
                                                               class="form-control" min="1" step="1" value="30"
                                                               title="Minutos desde los cuales se pinta naranja">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" style="font-size:.75rem">min</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6 pl-1">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" style="background:#ff0000;color:#fff;border-color:#ff0000;font-size:.75rem">Rojo</span>
                                                        </div>
                                                        <input type="number" id="umbral_rojo" name="umbral_rojo"
                                                               class="form-control" min="1" step="1" value="45"
                                                               title="Minutos desde los cuales se pinta rojo">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" style="font-size:.75rem">min</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted">Tiempo detenido a partir del cual cambia el color de alerta.</small>
                                        </div>
                                        <div class="alert-atencion mt-2">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <strong>ATENCIÓN:</strong> Más de 45 min detenido puede indicar que el GPS fue apagado y encendido en otro punto.
                                        </div>
                                        <button type="submit" id="btn-procesar" class="btn btn-primary btn-block btn-lg mt-3">
                                            <i class="fas fa-cogs mr-1"></i> Procesar Archivo
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- ═══ MODO LOTE ═══ --}}
                        <div id="seccion-lote" style="display:none">
                            <div class="row">

                                {{-- Columna izquierda: zona drop + lista --}}
                                <div class="col-md-7">
                                    {{-- Zona drop múltiple --}}
                                    <div class="upload-zone" id="upload-zone-lote">
                                        <i class="fas fa-layer-group" style="color:#6777ef"></i>
                                        <p id="lote-label">Arrastrá hasta <strong>10 archivos</strong> o <strong>hacé clic</strong> para seleccionarlos</p>
                                        <input type="file" id="archivos-lote" accept=".xls,.xlsx" multiple style="display:none">
                                    </div>

                                    {{-- Lista de archivos en cola --}}
                                    <div id="lote-lista-wrap" style="display:none;margin-top:12px">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span style="font-size:.82rem;font-weight:600">
                                                Archivos en cola: <span id="lote-count">0</span> / 10
                                            </span>
                                            <button id="btn-lote-limpiar-lista" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;padding:2px 10px">
                                                <i class="fas fa-trash-alt mr-1"></i>Vaciar lista
                                            </button>
                                        </div>
                                        <div style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;border-radius:8px">
                                            <table class="table table-sm mb-0" id="lote-tabla-cola">
                                                <thead style="background:#f8f9fa;font-size:.78rem">
                                                    <tr>
                                                        <th style="width:28px">#</th>
                                                        <th>Archivo</th>
                                                        <th style="width:70px" class="text-center">Tamaño</th>
                                                        <th style="width:110px" class="text-center">Estado</th>
                                                        <th style="width:32px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lote-cola-body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Columna derecha: configuración + botón --}}
                                <div class="col-md-5">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-tachometer-alt mr-1" style="color:#6777ef"></i>
                                            Velocidad máxima permitida
                                        </label>
                                        <div class="input-group speed-input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-car"></i></span>
                                            </div>
                                            <input type="number" id="lote-vel" class="form-control" placeholder="Ej: 45" min="0" step="1" value="45">
                                            <div class="input-group-append">
                                                <span class="input-group-text">km/h</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-clock mr-1" style="color:#ffa500"></i>
                                            Umbrales de tiempo detenido
                                        </label>
                                        <div class="row no-gutters">
                                            <div class="col-6 pr-1">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" style="background:#ffa500;color:#000;border-color:#ffa500;font-size:.75rem">Naranja</span>
                                                    </div>
                                                    <input type="number" id="lote-naranja" class="form-control" min="1" step="1" value="30">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" style="font-size:.75rem">min</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 pl-1">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" style="background:#ff0000;color:#fff;border-color:#ff0000;font-size:.75rem">Rojo</span>
                                                    </div>
                                                    <input type="number" id="lote-rojo" class="form-control" min="1" step="1" value="45">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" style="font-size:.75rem">min</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted">Se aplican a todos los archivos del lote.</small>
                                    </div>

                                    <div class="alert-atencion mt-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        La misma configuración se aplica a todos los archivos del lote.
                                    </div>

                                    <button id="btn-procesar-lote" class="btn btn-primary btn-block btn-lg mt-3" disabled>
                                        <i class="fas fa-cogs mr-1"></i> Procesar lote
                                        <span id="lote-btn-count" class="badge badge-light ml-1">0</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Resultados del lote --}}
                            <div id="lote-resultados" style="display:none;margin-top:20px">
                                <hr>
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-check-circle mr-1" style="color:#00c41c"></i>
                                    Resultado del procesamiento
                                    <span id="lote-res-ok" class="badge badge-success ml-1">0 OK</span>
                                    <span id="lote-res-err" class="badge badge-danger ml-1" style="display:none">0 con error</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" style="font-size:.83rem">
                                        <thead style="background:#f0f0f0">
                                            <tr>
                                                <th>#</th>
                                                <th>Archivo</th>
                                                <th class="text-center">Recurso</th>
                                                <th class="text-center">Posiciones</th>
                                                <th class="text-center">Resultado</th>
                                                <th class="text-center">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lote-res-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ── Resultado ── --}}
        <div id="resultado-section" style="display:none">

            {{-- Metadata --}}
            <div class="row mb-3 no-print">
                <div class="col-12">
                    <div class="metadata-banner">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="meta-item">Recurso</div>
                                <div class="meta-val" id="meta-recurso">—</div>
                            </div>
                            <div class="col-md-3">
                                <div class="meta-item">Desde</div>
                                <div class="meta-val" id="meta-desde">—</div>
                            </div>
                            <div class="col-md-3">
                                <div class="meta-item">Hasta</div>
                                <div class="meta-val" id="meta-hasta">—</div>
                            </div>
                            <div class="col-md-3">
                                <div class="meta-item">Posiciones</div>
                                <div class="meta-val" id="meta-pos">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Leyenda + Botones --}}
            <div class="row mb-3 no-print">
                <div class="col-md-6">
                    <div class="card" style="border-radius:10px">
                        <div class="card-body py-2">
                            <strong class="mr-2">Tiempo detenido:</strong>
                            <span class="leyenda-badge" style="background:#ffff00;color:#000"><span class="leyenda-dot" style="background:#cccc00"></span>&lt; <span id="ley-naranja">30</span> min</span>
                            <span class="leyenda-badge ml-2" style="background:#ffa500;color:#000"><span class="leyenda-dot" style="background:#cc7a00"></span><span id="ley-rango">30–45</span> min</span>
                            <span class="leyenda-badge ml-2" style="background:#ff0000;color:#fff"><span class="leyenda-dot" style="background:#990000"></span>&gt; <span id="ley-rojo">45</span> min</span>
                            <span class="leyenda-badge ml-2" style="background:#0070c0;color:#fff"><span class="leyenda-dot" style="background:#004a80"></span>Detenido</span>
                            <span class="leyenda-badge ml-2" style="background:#00c41c;color:#fff"><span class="leyenda-dot" style="background:#009914"></span>Mov.</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button id="btn-ver-recorrido" class="btn btn-lg mr-2" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:8px">
                        <i class="fas fa-route mr-1"></i> Ver Recorrido
                    </button>
                    <button id="btn-export-excel" class="btn btn-export-excel btn-lg mr-2">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button id="btn-export-pdf" class="btn btn-export-pdf btn-lg">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="row">
                <div class="col-12">
                    <div class="card" style="border-radius:12px;overflow:hidden">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="tabla-resultado" class="table table-hover table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Fecha / Hora</th>
                                            <th class="text-center">Velocidad</th>
                                            <th>Dirección</th>
                                            <th class="text-center">Mapa</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Tiempo Detenido</th>
                                            <th class="text-center" id="th-exceso" style="display:none">Exceso Vel.</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ═══ MODAL RECORRIDO ════════════════════════════════════════════════ --}}
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

            {{-- Panel de controles del reproductor --}}
            <div class="player-panel no-print">
                {{-- Botones de control --}}
                <button class="player-btn btn-stop" id="btn-stop" title="Detener y volver al inicio">
                    <i class="fas fa-stop"></i>
                </button>
                <button class="player-btn btn-prev" id="btn-prev" title="Punto anterior">
                    <i class="fas fa-step-backward"></i>
                </button>
                <button class="player-btn btn-play" id="btn-play" title="Reproducir">
                    <i class="fas fa-play"></i>
                </button>
                <button class="player-btn btn-step" id="btn-step" title="Avanzar un punto">
                    <i class="fas fa-step-forward"></i>
                </button>

                {{-- Info del punto actual --}}
                <div class="player-info" id="player-info">
                    <div class="pi-fecha">— Seleccioná un punto —</div>
                    <div class="pi-vel"></div>
                    <div class="pi-dir"></div>
                    <div class="pi-estado"></div>
                </div>

                {{-- Velocidad de animación --}}
                <div class="speed-slider-wrap">
                    <i class="fas fa-rabbit text-white" style="font-size:.8rem"></i>
                    <input type="range" id="speed-slider" min="1" max="10" value="5" title="Velocidad de reproducción">
                    <i class="fas fa-horse-head text-white" style="font-size:.8rem"></i>
                    <span id="speed-label" class="text-white" style="font-size:.75rem">5x</span>
                </div>

                {{-- Barra de progreso --}}
                <div class="progress-wrap">
                    <span id="prog-current">0</span>
                    <div class="progress-bar-custom" id="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progress-bar-fill"></div>
                    </div>
                    <span id="prog-total">0</span>
                </div>
            </div>

            {{-- Mapa --}}
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

{{-- Formulario de exportación Excel --}}
<form id="form-excel" method="POST" action="{{ route('cecoco.historico-movil.exportar-excel') }}" style="display:none">
    @csrf
    <input type="hidden" id="export-data" name="data">
</form>

<script>
(function () {
    'use strict';

    /* ════════════════════════════════════════
       ESTADO GLOBAL
    ════════════════════════════════════════ */
    let resultadoData = null;

    // Mapa
    let recorridoMap = null;
    let mapInitialized = false;
    let routePolyline = null;       // Traza completa (gris)
    let trailPolyline = null;       // Traza recorrida (azul)
    let carMarker = null;           // Marcador animado
    let staticMarkers = [];         // Marcadores fijos (paradas, inicio, fin)

    // Playback
    let playbackIndex = 0;
    let isPlaying = false;
    let playTimer = null;
    let registros = [];

    /* ════════════════════════════════════════
       DRAG-AND-DROP ARCHIVO
    ════════════════════════════════════════ */
    const zone       = document.getElementById('upload-zone');
    const fileInput  = document.getElementById('archivo');
    const labelEl    = document.getElementById('upload-label');
    const filenameEl = document.getElementById('upload-filename');

    zone.addEventListener('click', () => fileInput.click());
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('dragover');
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; mostrarNombre(e.dataTransfer.files[0].name); }
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) mostrarNombre(fileInput.files[0].name); });
    function mostrarNombre(n) { labelEl.style.display='none'; filenameEl.textContent='📄 '+n; filenameEl.style.display='block'; }

    /* ════════════════════════════════════════
       SUBMIT FORMULARIO
    ════════════════════════════════════════ */
    document.getElementById('form-upload').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!fileInput.files.length) { swal('Atención','Debe seleccionar un archivo .xls o .xlsx','warning'); return; }

        const formData = new FormData(this);
        document.getElementById('processing-overlay').classList.add('show');

        fetch('{{ route("cecoco.historico-movil.procesar") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
        .then(data => {
            document.getElementById('processing-overlay').classList.remove('show');
            resultadoData = data;
            registros = data.registros;
            renderizarResultado(data);
            if (data.historial_id) agregarItemHistorial(data);
        })
        .catch(err => {
            document.getElementById('processing-overlay').classList.remove('show');
            const msg = err.message || (err.errors ? Object.values(err.errors).flat().join('<br>') : 'Error al procesar el archivo.');
            swal('Error', msg, 'error');
        });
    });

    /* ════════════════════════════════════════
       RENDER TABLA
    ════════════════════════════════════════ */
    function renderizarResultado(data) {
        const { metadata, registros: regs, velocidad_maxima, umbral_naranja, umbral_rojo, errores } = data;

        document.getElementById('meta-recurso').textContent = metadata.recurso || '—';
        document.getElementById('meta-desde').textContent   = metadata.fecha_inicio || '—';
        document.getElementById('meta-hasta').textContent   = metadata.fecha_fin || '—';
        document.getElementById('meta-pos').textContent     = metadata.posiciones || regs.length;

        // Actualizar leyenda con umbrales reales
        const un = umbral_naranja ?? 30, ur = umbral_rojo ?? 45;
        document.getElementById('ley-naranja').textContent = un;
        document.getElementById('ley-rango').textContent   = un + '–' + ur;
        document.getElementById('ley-rojo').textContent    = ur;

        const thExceso = document.getElementById('th-exceso');
        if (velocidad_maxima > 0) { thExceso.style.display=''; thExceso.textContent='Exceso (>'+velocidad_maxima+' km/h)'; }

        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        regs.forEach((reg, idx) => {
            const tr = document.createElement('tr');
            tr.dataset.index = idx;
            if (reg.color_estado === 'detenido') tr.style.background = '#e8f4ff';

            td(tr, reg.id, 'text-center font-weight-bold');
            td(tr, reg.fecha, '');
            td(tr, reg.velocidad + ' km/h', reg.exceso_velocidad ? 'text-center text-danger font-weight-bold' : 'text-center');
            td(tr, reg.direccion, '');

            // Mapa
            const tdM = document.createElement('td'); tdM.className = 'text-center';
            if (reg.enlace) {
                const a = document.createElement('a'); a.href=reg.enlace; a.target='_blank';
                a.className='btn btn-sm btn-dark'; a.innerHTML='<i class="fas fa-map-marker-alt"></i>';
                tdM.appendChild(a);
            }
            tr.appendChild(tdM);

            // Estado
            const tdE = document.createElement('td'); tdE.className='text-center';
            const pill = document.createElement('span');
            pill.className = reg.color_estado==='detenido' ? 'pill-detenido' : 'pill-movimiento';
            pill.textContent = reg.estado; tdE.appendChild(pill); tr.appendChild(tdE);

            // Tiempo detenido
            const tdT = document.createElement('td'); tdT.className='text-center';
            if (reg.tiempo_detenido) {
                tdT.textContent = reg.tiempo_detenido;
                const cm = { yellow:'td-yellow', orange:'td-orange', red:'td-red' };
                tdT.classList.add(cm[reg.color_tiempo]||'td-yellow');
                tdT.style.fontWeight = '600';
            }
            tr.appendChild(tdT);

            // Exceso
            if (velocidad_maxima > 0) {
                const tdX = document.createElement('td'); tdX.className='text-center';
                if (reg.exceso_velocidad) { const b=document.createElement('span'); b.className='badge-exceso'; b.textContent='EXCESO'; tdX.appendChild(b); }
                tr.appendChild(tdX);
            }

            // Click fila → ir al punto en mapa (si el modal está abierto)
            tr.addEventListener('click', () => {
                if ($('#recorridoModal').hasClass('show')) irAPunto(idx);
            });

            tbody.appendChild(tr);
        });

        document.getElementById('resultado-section').style.display = '';
        document.getElementById('resultado-section').scrollIntoView({ behavior: 'smooth' });

        if (errores && errores.length > 0) {
            const lista = errores.slice(0,5).join('<br>') + (errores.length>5 ? `<br>...y ${errores.length-5} más.` : '');
            swal({ title:'Advertencias', content:{ element:'div', attributes:{ innerHTML:lista } }, icon:'warning' });
        }
    }

    function td(tr, txt, cls) {
        const c = document.createElement('td'); c.className=cls; c.textContent=txt??''; tr.appendChild(c); return c;
    }

    /* ════════════════════════════════════════
       MAPA – INICIALIZACIÓN
    ════════════════════════════════════════ */
    function initMapaRecorrido() {
        if (!mapInitialized) {
            recorridoMap = L.map('recorrido-map', { zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(recorridoMap);
            mapInitialized = true;
        }

        // Limpiar capas previas
        limpiarMapa();

        setTimeout(() => {
            cargarRecorrido();
            recorridoMap.invalidateSize();
        }, 350);
    }

    function limpiarMapa() {
        if (routePolyline)  { recorridoMap.removeLayer(routePolyline); routePolyline = null; }
        if (trailPolyline)  { recorridoMap.removeLayer(trailPolyline); trailPolyline = null; }
        if (carMarker)      { recorridoMap.removeLayer(carMarker);     carMarker = null; }
        staticMarkers.forEach(m => recorridoMap.removeLayer(m));
        staticMarkers = [];
        resetPlayback();
    }

    function cargarRecorrido() {
        if (!registros.length) return;

        const puntosValidos = registros.filter(r => r.lat !== null && r.lng !== null);
        if (!puntosValidos.length) return;

        const coords = puntosValidos.map(r => [r.lat, r.lng]);

        // Traza completa (fondo gris semi-transparente)
        routePolyline = L.polyline(coords, { color: '#adb5bd', weight: 3, opacity: .5, dashArray: '4 4' }).addTo(recorridoMap);

        // Traza recorrida (se irá dibujando en la animación)
        trailPolyline = L.polyline([], { color: '#6777ef', weight: 4, opacity: .85 }).addTo(recorridoMap);

        // Marcadores: inicio y fin
        const iconInicio = L.icon({ iconUrl: '/img/flag_init.svg',   iconSize:[32,32], iconAnchor:[8,32], popupAnchor:[0,-34] });
        const iconFin    = L.icon({ iconUrl: '/img/flag_finish.svg', iconSize:[32,32], iconAnchor:[8,32], popupAnchor:[0,-34] });

        const mInicio = L.marker(coords[0], { icon: iconInicio })
            .bindPopup(`<b>Inicio</b><br>${registros[0]?.fecha ?? ''}<br>${registros[0]?.direccion ?? ''}`)
            .addTo(recorridoMap);
        const mFin = L.marker(coords[coords.length-1], { icon: iconFin })
            .bindPopup(`<b>Fin</b><br>${puntosValidos[puntosValidos.length-1]?.fecha ?? ''}<br>${puntosValidos[puntosValidos.length-1]?.direccion ?? ''}`)
            .addTo(recorridoMap);
        staticMarkers.push(mInicio, mFin);

        // Marcadores de paradas prolongadas (>45 min)
        registros.forEach(r => {
            if (r.lat === null) return;
            if (r.segundos_detenido && r.segundos_detenido >= 2700) {
                const m = L.circleMarker([r.lat, r.lng], {
                    radius: 8, color: '#ff0000', fillColor: '#ff0000',
                    fillOpacity: .7, weight: 2
                }).bindPopup(`<b>Parada larga</b><br>${r.fecha}<br>${r.tiempo_detenido}`)
                  .addTo(recorridoMap);
                staticMarkers.push(m);
            }
        });

        // Marcador animado (carro)
        const carIcon = L.divIcon({
            className: 'car-marker',
            html: '<div class="car-dot"></div>',
            iconSize: [18,18], iconAnchor: [9,9]
        });
        carMarker = L.marker(coords[0], { icon: carIcon, zIndexOffset: 1000 })
            .bindPopup(popupContent(registros[0]))
            .addTo(recorridoMap);

        // Ajustar vista
        recorridoMap.fitBounds(routePolyline.getBounds(), { padding: [30,30] });

        // Iniciar estado
        playbackIndex = 0;
        actualizarProgress();
        actualizarPlayerInfo(registros[0]);
    }

    /* ════════════════════════════════════════
       PLAYBACK
    ════════════════════════════════════════ */
    function resetPlayback() {
        isPlaying = false;
        if (playTimer) { clearInterval(playTimer); playTimer = null; }
        playbackIndex = 0;
        actualizarBtnPlay();
    }

    function irAPunto(idx) {
        if (!carMarker) return;
        const reg = registros[idx];
        if (reg.lat === null) return;

        // Resaltar fila en tabla
        document.querySelectorAll('#tabla-body tr').forEach(r => r.classList.remove('fila-activa'));
        const filaTarget = document.querySelector(`#tabla-body tr[data-index="${idx}"]`);
        if (filaTarget) filaTarget.classList.add('fila-activa');

        // Actualizar trail
        const trail = registros.slice(0, idx+1)
            .filter(r => r.lat !== null)
            .map(r => [r.lat, r.lng]);
        trailPolyline.setLatLngs(trail);

        // Mover carro
        carMarker.setLatLng([reg.lat, reg.lng]);
        carMarker.setPopupContent(popupContent(reg));

        // Colorear marcador según estado
        const dot = carMarker.getElement()?.querySelector('.car-dot');
        if (dot) {
            if (reg.exceso_velocidad)     dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #ff0000 55%, #990000 100%)';
            else if (reg.color_estado==='detenido') dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #0070c0 55%, #004a80 100%)';
            else                          dot.style.background = 'radial-gradient(circle at 40% 40%, #fff 10%, #6777ef 55%, #35199a 100%)';
        }

        playbackIndex = idx;
        actualizarProgress();
        actualizarPlayerInfo(reg);
        recorridoMap.panTo([reg.lat, reg.lng], { animate: true, duration: .3 });
    }

    function avanzar() {
        if (playbackIndex >= registros.length - 1) {
            pausar(); return;
        }
        playbackIndex++;
        // Saltar puntos sin coordenadas
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
        playTimer = setInterval(() => { avanzar(); if (playbackIndex >= registros.length-1) pausar(); }, intervaloMs());
        // Ajustar velocidad si el slider cambia mientras reproduce
        document.getElementById('speed-slider').addEventListener('input', () => {
            if (isPlaying) { clearInterval(playTimer); playTimer = setInterval(() => { avanzar(); if (playbackIndex >= registros.length-1) pausar(); }, intervaloMs()); }
        });
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
        pi.querySelector('.pi-dir').textContent    = reg.direccion;
        pi.querySelector('.pi-estado').textContent = reg.estado + (reg.tiempo_detenido ? ' · ' + reg.tiempo_detenido : '');
        pi.querySelector('.pi-vel').style.color    = reg.exceso_velocidad ? '#ff7675' : '#7ecff7';
    }

    function popupContent(reg) {
        if (!reg) return '';
        return `<div style="font-size:.82rem;min-width:180px">
            <b>${reg.fecha}</b><br>
            <span style="color:#0070c0"><b>${reg.velocidad} km/h</b></span>
            ${reg.exceso_velocidad ? '<span style="color:red"> ⚠ EXCESO</span>' : ''}<br>
            ${reg.direccion}<br>
            <span style="color:${reg.color_estado==='detenido'?'#0070c0':'#00a832'}">${reg.estado}</span>
            ${reg.tiempo_detenido ? '<br><b>Detenido: '+reg.tiempo_detenido+'</b>' : ''}
        </div>`;
    }

    /* ════════════════════════════════════════
       EVENTOS CONTROLES
    ════════════════════════════════════════ */
    document.getElementById('btn-play').addEventListener('click', () => isPlaying ? pausar() : reproducir());
    document.getElementById('btn-step').addEventListener('click', () => { pausar(); avanzar(); });
    document.getElementById('btn-prev').addEventListener('click', () => { pausar(); retroceder(); });
    document.getElementById('btn-stop').addEventListener('click', detener);

    document.getElementById('speed-slider').addEventListener('input', function() {
        document.getElementById('speed-label').textContent = this.value + 'x';
    });

    // Click en barra de progreso → saltar a posición
    document.getElementById('progress-bar-wrap').addEventListener('click', function(e) {
        const pct = e.offsetX / this.offsetWidth;
        const idx = Math.round(pct * (registros.length - 1));
        pausar();
        irAPunto(Math.max(0, Math.min(idx, registros.length - 1)));
    });

    /* ════════════════════════════════════════
       MODAL RECORRIDO
    ════════════════════════════════════════ */
    document.getElementById('btn-ver-recorrido').addEventListener('click', function() {
        if (!resultadoData) { swal('Atención','Primero procesá un archivo','warning'); return; }
        const meta = resultadoData.metadata;
        document.getElementById('recorridoModalLabel').innerHTML =
            `<i class="fas fa-route mr-2" style="color:#6777ef"></i>Recorrido — ${meta.recurso} (${meta.fecha_inicio} → ${meta.fecha_fin})`;
        document.getElementById('prog-total').textContent = registros.length;
        $('#recorridoModal').modal('show');
    });

    $('#recorridoModal').on('shown.bs.modal', function() {
        initMapaRecorrido();
    });

    $('#recorridoModal').on('hidden.bs.modal', function() {
        pausar();
    });

    /* ════════════════════════════════════════
       EXPORTAR EXCEL
    ════════════════════════════════════════ */
    document.getElementById('btn-export-excel').addEventListener('click', function() {
        if (!resultadoData) return;
        document.getElementById('export-data').value = JSON.stringify(resultadoData);
        document.getElementById('form-excel').submit();
    });

    /* ════════════════════════════════════════
       EXPORTAR PDF
    ════════════════════════════════════════ */
    document.getElementById('btn-export-pdf').addEventListener('click', function() {
        if (!resultadoData) return;
        const { metadata, registros: regs, velocidad_maxima } = resultadoData;
        const tieneExceso = velocidad_maxima > 0;
        const colorCls = { yellow:'td-y', orange:'td-o', red:'td-r' };

        let html = `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
        <title>Histórico Móvil – ${metadata.recurso}</title>
        <style>
            body { font-family:Arial,sans-serif; font-size:11px; color:#000; margin:20px; }
            h2 { text-align:center; margin-bottom:4px; }
            .meta { text-align:center; color:#555; margin-bottom:10px; font-size:10px; }
            table { width:100%; border-collapse:collapse; }
            th { background:#4a4a9a; color:#fff; padding:5px 7px; text-align:center; font-size:10px; }
            td { padding:4px 7px; border:1px solid #ccc; font-size:10px; vertical-align:middle; }
            .pill-det { background:#0070c0; color:#fff; padding:2px 8px; border-radius:10px; }
            .pill-mov { background:#00c41c; color:#fff; padding:2px 8px; border-radius:10px; }
            .td-y { background:#ffff00; } .td-o { background:#ffa500; } .td-r { background:#ff0000; color:#fff; }
            .exceso { background:#ff0000; color:#fff; padding:2px 8px; border-radius:10px; font-weight:700; }
            .ley { margin-bottom:8px; } .lb { display:inline-block; padding:2px 10px; border-radius:10px; font-size:10px; font-weight:600; }
            @media print { .no-print { display:none } }
        </style></head><body>
        <div class="no-print" style="position:fixed;top:10px;right:10px;display:flex;gap:8px">
            <button onclick="window.print()" style="background:#1d6f42;color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer">🖨 Imprimir / PDF</button>
            <button onclick="window.close()" style="background:#c0392b;color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer">✕ Cerrar</button>
        </div>
        <h2>Histórico de Posiciones GPS</h2>
        <div class="meta">Recurso: <b>${metadata.recurso}</b> &nbsp;|&nbsp; Desde: <b>${metadata.fecha_inicio}</b> &nbsp;|&nbsp; Hasta: <b>${metadata.fecha_fin}</b> &nbsp;|&nbsp; Posiciones: <b>${metadata.posiciones||regs.length}</b>${tieneExceso?' &nbsp;|&nbsp; Vel. máx.: <b>'+velocidad_maxima+' km/h</b>':''}</div>
        <div class="ley">
            <span class="lb" style="background:#ffff00;color:#222">■ &lt;30 min</span>
            <span class="lb" style="background:#ffa500;color:#222;margin-left:6px">■ 30–45 min</span>
            <span class="lb" style="background:#ff0000;color:#fff;margin-left:6px">■ &gt;45 min</span>
        </div>
        <table><thead><tr>
            <th>#</th><th>Fecha/Hora</th><th>Vel.</th><th>Dirección</th><th>Estado</th><th>Tiempo Detenido</th>${tieneExceso?'<th>Exceso</th>':''}
        </tr></thead><tbody>`;

        regs.forEach(r => {
            html += `<tr>
                <td style="text-align:center">${r.id??''}</td>
                <td>${r.fecha}</td>
                <td style="text-align:center${r.exceso_velocidad?';color:red;font-weight:700':''}">${r.velocidad} km/h</td>
                <td>${r.direccion}</td>
                <td style="text-align:center">${r.color_estado==='detenido'?'<span class="pill-det">Detenido</span>':'<span class="pill-mov">En movimiento</span>'}</td>
                <td class="${r.color_tiempo?colorCls[r.color_tiempo]:''}" style="text-align:center;font-weight:${r.tiempo_detenido?700:400}">${r.tiempo_detenido??''}</td>
                ${tieneExceso?`<td style="text-align:center">${r.exceso_velocidad?'<span class="exceso">EXCESO</span>':''}</td>`:''}
            </tr>`;
        });

        html += '</tbody></table></body></html>';
        const win = window.open('','_blank','width=1100,height=800');
        win.document.write(html); win.document.close();
    });

    /* ════════════════════════════════════════
       HISTORIAL — paginación + búsqueda + fechas
    ════════════════════════════════════════ */
    let histPagina   = 1;
    let histIdActivo = null;

    function getFiltros() {
        return {
            q:     document.getElementById('hist-busqueda').value.trim(),
            desde: document.getElementById('hist-desde').value,
            hasta: document.getElementById('hist-hasta').value,
        };
    }

    function hayFiltrosActivos() {
        const f = getFiltros();
        return f.q || f.desde || f.hasta;
    }

    function actualizarBtnLimpiar() {
        document.getElementById('btn-limpiar-filtros').style.display = hayFiltrosActivos() ? 'flex' : 'none';
    }

    function cargarHistorial(pagina) {
        pagina = pagina ?? histPagina;
        const { q, desde, hasta } = getFiltros();
        const params = new URLSearchParams({ page: pagina, q, desde, hasta });

        fetch(`{{ route('cecoco.historico-movil.buscar') }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            histPagina = data.pagina_actual;
            renderHistorial(data);
        })
        .catch(() => {
            document.getElementById('hist-body').innerHTML =
                `<tr><td colspan="9" class="text-center text-danger py-3">Error al cargar el historial.</td></tr>`;
        });
    }

    /* ── Toggle panel de fechas ── */
    document.getElementById('btn-toggle-filtros').addEventListener('click', function() {
        const panel = document.getElementById('hist-filtros-fecha');
        const icon  = document.getElementById('icon-toggle-filtros');
        const visible = panel.style.display !== 'none';
        panel.style.display = visible ? 'none' : '';
        icon.className = visible ? 'fas fa-chevron-down ml-1' : 'fas fa-chevron-up ml-1';
        this.style.background = visible ? 'rgba(255,255,255,.15)' : 'rgba(103,119,239,.5)';
        if (!visible) document.getElementById('hist-desde').focus();
    });

    /* ── Atajos de rango ── */
    document.querySelectorAll('.btn-atajo').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-atajo').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');

            const hoy   = new Date();
            const fmt   = d => d.toISOString().slice(0, 10);
            let desde = '', hasta = fmt(hoy);

            switch (this.dataset.rango) {
                case 'hoy': {
                    desde = fmt(hoy);
                    break;
                }
                case 'semana': {
                    const lunes = new Date(hoy);
                    lunes.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
                    desde = fmt(lunes);
                    break;
                }
                case 'mes': {
                    desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
                    break;
                }
                case 'mes_ant': {
                    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
                    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
                    desde = fmt(primerDia);
                    hasta = fmt(ultimoDia);
                    break;
                }
            }

            document.getElementById('hist-desde').value = desde;
            document.getElementById('hist-hasta').value = hasta;
            actualizarBtnLimpiar();
            histPagina = 1;
            cargarHistorial(1);
        });
    });

    /* ── Cambio manual de fechas → quitar atajo activo ── */
    ['hist-desde', 'hist-hasta'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelectorAll('.btn-atajo').forEach(b => b.classList.remove('activo'));
            actualizarBtnLimpiar();
            histPagina = 1;
            cargarHistorial(1);
        });
    });

    /* ── Limpiar todos los filtros ── */
    document.getElementById('btn-limpiar-filtros').addEventListener('click', function() {
        document.getElementById('hist-busqueda').value = '';
        document.getElementById('hist-desde').value   = '';
        document.getElementById('hist-hasta').value   = '';
        document.querySelectorAll('.btn-atajo').forEach(b => b.classList.remove('activo'));
        this.style.display = 'none';
        histPagina = 1;
        cargarHistorial(1);
    });

    function renderHistorial(data) {
        const tbody = document.getElementById('hist-body');

        if (!data.items || data.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:.4"></i>
                No hay registros. Procesá un archivo para que quede guardado.
            </td></tr>`;
            document.getElementById('hist-count').textContent    = '0 registros';
            document.getElementById('hist-info-pag').textContent = '';
            document.getElementById('hist-pag-num').textContent  = '';
            document.getElementById('hist-prev').disabled = true;
            document.getElementById('hist-next').disabled = true;
            return;
        }

        tbody.innerHTML = data.items.map(item => {
            const activo = item.id === histIdActivo ? 'style="background:rgba(103,119,239,.1)"' : '';
            return `<tr class="hist-fila" data-id="${item.id}" ${activo} style="cursor:pointer">
                <td class="text-center pl-2">
                    <i class="fas fa-file-excel" style="color:#1d6f42;font-size:.9rem"></i>
                </td>
                <td>
                    <div style="font-weight:600;font-size:.85rem">${item.recurso || '—'}</div>
                    <div style="font-size:.72rem;color:#6c757d;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                         title="${item.nombre_archivo}">${item.nombre_archivo}</div>
                </td>
                <td class="text-center" style="font-size:.8rem;white-space:nowrap">
                    ${item.fecha_inicio}<br><span style="color:#6c757d">→ ${item.fecha_fin}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-secondary">${item.posiciones}</span>
                </td>
                <td class="text-center" style="font-size:.82rem">${item.velocidad_maxima} km/h</td>
                <td class="text-center" style="font-size:.75rem">
                    <span style="background:#ffa500;color:#000;padding:1px 5px;border-radius:8px">${item.umbral_naranja}m</span>
                    <span style="background:#ff0000;color:#fff;padding:1px 5px;border-radius:8px;margin-left:2px">${item.umbral_rojo}m</span>
                </td>
                <td class="text-center" style="font-size:.78rem">${item.procesado_por}</td>
                <td class="text-center" style="font-size:.78rem;white-space:nowrap">${item.procesado_el}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary btn-cargar-hist px-2 py-1" data-id="${item.id}" title="Cargar resultado">
                        <i class="fas fa-upload"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');

        // Paginación
        const desde = (data.pagina_actual - 1) * 12 + 1;
        const hasta = Math.min(data.pagina_actual * 12, data.total);
        document.getElementById('hist-count').textContent    = `${data.total} registros`;
        document.getElementById('hist-info-pag').textContent = `Mostrando ${desde}–${hasta} de ${data.total}`;
        document.getElementById('hist-pag-num').textContent  = `Pág. ${data.pagina_actual} / ${data.ultima_pagina}`;
        document.getElementById('hist-prev').disabled = data.pagina_actual <= 1;
        document.getElementById('hist-next').disabled = data.pagina_actual >= data.ultima_pagina;
    }

    // Botones paginación
    document.getElementById('hist-prev').addEventListener('click', () => cargarHistorial(histPagina - 1));
    document.getElementById('hist-next').addEventListener('click', () => cargarHistorial(histPagina + 1));

    // Búsqueda de texto con debounce
    let histTimer = null;
    document.getElementById('hist-busqueda').addEventListener('input', function() {
        clearTimeout(histTimer);
        actualizarBtnLimpiar();
        histTimer = setTimeout(() => { histPagina = 1; cargarHistorial(1); }, 350);
    });

    // Click en fila o botón cargar
    document.getElementById('hist-body').addEventListener('click', function(e) {
        const btn  = e.target.closest('.btn-cargar-hist');
        const fila = e.target.closest('.hist-fila');
        const id   = (btn || fila)?.dataset.id;
        if (!id) return;

        histIdActivo = parseInt(id);
        document.getElementById('processing-overlay').classList.add('show');

        fetch(`/cecoco/historico-movil/${id}/cargar`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            document.getElementById('processing-overlay').classList.remove('show');
            resultadoData = data;
            registros     = data.registros;
            renderizarResultado(data);
            // Resaltar fila activa
            document.querySelectorAll('.hist-fila').forEach(r => r.style.background = '');
            const filaActiva = document.querySelector(`.hist-fila[data-id="${id}"]`);
            if (filaActiva) filaActiva.style.background = 'rgba(103,119,239,.12)';
            document.getElementById('resultado-section').scrollIntoView({ behavior: 'smooth' });
        })
        .catch(() => {
            document.getElementById('processing-overlay').classList.remove('show');
            swal('Error', 'No se pudo cargar el historial.', 'error');
        });
    });

    // Refrescar historial tras procesar un archivo nuevo
    function agregarItemHistorial(data) {
        histPagina = 1;
        cargarHistorial(1);
    }

    // Carga inicial
    cargarHistorial(1);

    /* ════════════════════════════════════════
       MODO LOTE
    ════════════════════════════════════════ */
    const LOTE_MAX = 10;
    let archivosLote = []; // array de File

    /* ── Toggle Individual / Por lotes ── */
    document.getElementById('btn-modo-individual').addEventListener('click', () => setModo('individual'));
    document.getElementById('btn-modo-lote').addEventListener('click',       () => setModo('lote'));

    function setModo(modo) {
        const esLote = modo === 'lote';
        document.getElementById('seccion-individual').style.display = esLote ? 'none' : '';
        document.getElementById('seccion-lote').style.display       = esLote ? ''     : 'none';
        document.getElementById('btn-modo-individual').classList.toggle('activo', !esLote);
        document.getElementById('btn-modo-lote').classList.toggle('activo',       esLote);
    }

    /* ── Drop zone lote ── */
    const zoneLote   = document.getElementById('upload-zone-lote');
    const inputLote  = document.getElementById('archivos-lote');

    zoneLote.addEventListener('click', () => inputLote.click());
    zoneLote.addEventListener('dragover',  e => { e.preventDefault(); zoneLote.classList.add('dragover'); });
    zoneLote.addEventListener('dragleave', () => zoneLote.classList.remove('dragover'));
    zoneLote.addEventListener('drop', e => {
        e.preventDefault(); zoneLote.classList.remove('dragover');
        agregarArchivosLote(Array.from(e.dataTransfer.files));
    });
    inputLote.addEventListener('change', () => {
        agregarArchivosLote(Array.from(inputLote.files));
        inputLote.value = '';
    });

    function agregarArchivosLote(nuevos) {
        const validos = nuevos.filter(f => /\.(xls|xlsx)$/i.test(f.name));
        const rechazados = nuevos.length - validos.length;

        validos.forEach(f => {
            if (archivosLote.length >= LOTE_MAX) return;
            // evitar duplicados por nombre
            if (!archivosLote.find(x => x.name === f.name)) archivosLote.push(f);
        });

        if (rechazados > 0) swal('Atención', `${rechazados} archivo(s) ignorado(s): solo se aceptan .xls y .xlsx.`, 'warning');
        if (archivosLote.length >= LOTE_MAX) swal('Límite alcanzado', `Máximo ${LOTE_MAX} archivos por lote.`, 'info');

        renderColaLote();
    }

    function renderColaLote() {
        const n = archivosLote.length;
        document.getElementById('lote-count').textContent    = n;
        document.getElementById('lote-btn-count').textContent = n;
        document.getElementById('btn-procesar-lote').disabled = n === 0;
        document.getElementById('lote-lista-wrap').style.display = n ? '' : 'none';

        const tbody = document.getElementById('lote-cola-body');
        tbody.innerHTML = archivosLote.map((f, i) => `
            <tr id="lote-fila-${i}">
                <td class="text-center text-muted" style="font-size:.75rem">${i + 1}</td>
                <td style="font-size:.8rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${f.name}">
                    <i class="fas fa-file-excel mr-1" style="color:#1d6f42"></i>${f.name}
                </td>
                <td class="text-center" style="font-size:.75rem;color:#6c757d">${(f.size/1024).toFixed(0)} KB</td>
                <td class="text-center lote-estado-espera" id="lote-estado-${i}">
                    <i class="fas fa-clock mr-1"></i>En espera
                </td>
                <td class="text-center">
                    <button onclick="quitarArchivoLote(${i})" class="btn btn-sm" style="padding:1px 6px;color:#dc3545;border:none;background:none" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>`).join('');
    }

    window.quitarArchivoLote = function(idx) {
        archivosLote.splice(idx, 1);
        renderColaLote();
        document.getElementById('lote-resultados').style.display = 'none';
    };

    document.getElementById('btn-lote-limpiar-lista').addEventListener('click', () => {
        archivosLote = [];
        renderColaLote();
        document.getElementById('lote-resultados').style.display = 'none';
    });

    /* ── Procesar lote (secuencial con fetch) ── */
    document.getElementById('btn-procesar-lote').addEventListener('click', async function() {
        if (!archivosLote.length) return;

        const vel     = document.getElementById('lote-vel').value     || 45;
        const naranja = document.getElementById('lote-naranja').value || 30;
        const rojo    = document.getElementById('lote-rojo').value    || 45;
        const csrf    = '{{ csrf_token() }}';
        const url     = '{{ route("cecoco.historico-movil.procesar") }}';

        this.disabled = true;
        document.getElementById('lote-resultados').style.display = 'none';

        const resultados = [];
        let okCount = 0, errCount = 0;

        for (let i = 0; i < archivosLote.length; i++) {
            // Marcar como "procesando"
            const celdaEstado = document.getElementById(`lote-estado-${i}`);
            celdaEstado.className = 'text-center lote-estado-proceso';
            celdaEstado.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...';

            const fd = new FormData();
            fd.append('archivo',          archivosLote[i]);
            fd.append('velocidad_maxima', vel);
            fd.append('umbral_naranja',   naranja);
            fd.append('umbral_rojo',      rojo);
            fd.append('_token',           csrf);

            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                const data = await resp.json();

                if (resp.ok) {
                    okCount++;
                    celdaEstado.className = 'text-center lote-estado-ok';
                    celdaEstado.innerHTML = '<i class="fas fa-check-circle mr-1"></i>OK';
                    resultados.push({ ok: true, nombre: archivosLote[i].name, data });
                } else {
                    throw new Error(data.message || 'Error del servidor');
                }
            } catch (err) {
                errCount++;
                celdaEstado.className = 'text-center lote-estado-error';
                celdaEstado.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Error';
                resultados.push({ ok: false, nombre: archivosLote[i].name, error: err.message });
            }
        }

        this.disabled = false;

        // Mostrar tabla de resultados
        renderResultadosLote(resultados, okCount, errCount);

        // Refrescar historial
        histPagina = 1;
        cargarHistorial(1);
    });

    function renderResultadosLote(resultados, okCount, errCount) {
        document.getElementById('lote-res-ok').textContent  = `${okCount} OK`;
        const elErr = document.getElementById('lote-res-err');
        elErr.textContent  = `${errCount} con error`;
        elErr.style.display = errCount ? '' : 'none';

        const tbody = document.getElementById('lote-res-body');
        tbody.innerHTML = resultados.map((r, i) => {
            if (r.ok) {
                const meta = r.data.metadata || {};
                return `<tr>
                    <td class="text-center text-muted">${i + 1}</td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${r.nombre}">
                        <i class="fas fa-file-excel mr-1" style="color:#1d6f42"></i>${r.nombre}
                    </td>
                    <td class="text-center">${meta.recurso || '—'}</td>
                    <td class="text-center"><span class="badge badge-secondary">${r.data.registros?.length ?? 0}</span></td>
                    <td class="text-center"><span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Procesado</span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary px-2 py-1" onclick="cargarDesdeResultadoLote(${r.data.historial_id})" title="Ver resultado">
                            <i class="fas fa-eye mr-1"></i>Ver
                        </button>
                    </td>
                </tr>`;
            } else {
                return `<tr class="table-danger">
                    <td class="text-center text-muted">${i + 1}</td>
                    <td colspan="3" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${r.nombre}">
                        <i class="fas fa-file-excel mr-1" style="color:#dc3545"></i>${r.nombre}
                    </td>
                    <td class="text-center" colspan="2"><span class="text-danger"><i class="fas fa-times-circle mr-1"></i>${r.error || 'Error al procesar'}</span></td>
                </tr>`;
            }
        }).join('');

        document.getElementById('lote-resultados').style.display = '';
        document.getElementById('lote-resultados').scrollIntoView({ behavior: 'smooth' });
    }

    window.cargarDesdeResultadoLote = function(id) {
        document.getElementById('processing-overlay').classList.add('show');
        fetch(`/cecoco/historico-movil/${id}/cargar`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            document.getElementById('processing-overlay').classList.remove('show');
            resultadoData = data;
            registros     = data.registros;
            renderizarResultado(data);
            setModo('individual');
            document.getElementById('resultado-section').scrollIntoView({ behavior: 'smooth' });
        })
        .catch(() => {
            document.getElementById('processing-overlay').classList.remove('show');
            swal('Error', 'No se pudo cargar el resultado.', 'error');
        });
    };

})();
</script>
@endsection
