@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ !empty($filtros) ? route('cecoco.index', $filtros) : route('cecoco.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    @can('ver-expediente-cecoco')
    <a href="{{ route('cecoco.expediente', $eventoCecoco) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-success">
        <i class="bi bi-file-earmark-text"></i> Ver Detalle Completo
    </a>
    @endcan
    @can('ver-grabacion-evento')
    <button type="button" class="btn btn-dark" id="btnGrabaciones" onclick="abrirGrabaciones()">
        <i class="bi bi-mic-fill"></i> Grabaciones de llamada
    </button>
    @endcan
    @can('escuchar-modulaciones-cecoco')
    <button type="button" class="btn btn-primary" id="btnModulaciones" onclick="abrirModulaciones()">
        <i class="bi bi-broadcast-pin"></i> Modulaciones
    </button>
    @endcan
</div>

@include('eventos-cecoco.partials.resumen_ia', ['eventoCecoco' => $eventoCecoco])

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Expediente N° {{ $eventoCecoco->nro_expediente }}</h4>
        @php
            $tipoLower = strtolower($eventoCecoco->tipo_servicio ?? '');
            $badgeClass = 'primary';
            
            // NIVEL 1: CRÍTICO (Rojo - Danger)
            if(str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego') ||
               str_contains($tipoLower, 'herido con arma') || str_contains($tipoLower, 'persona armada') ||
               str_contains($tipoLower, 'persona fallecida') || str_contains($tipoLower, 'abuso de arma') ||
               str_contains($tipoLower, 'violencia de genero con detenidos') || str_contains($tipoLower, 'tentativa de suicidio') ||
               str_contains($tipoLower, 'persona ajena en los fondos') || str_contains($tipoLower, 'solicitud de ambulancia') ||
               str_contains($tipoLower, 'accidente de transito con fallecido') || str_contains($tipoLower, 'accidente de transito con lesionados')) {
                $badgeClass = 'danger';
            }
            // NIVEL 2: URGENTE (Naranja - Warning)
            elseif(str_contains($tipoLower, 'accidente') || str_contains($tipoLower, 'amenazas') ||
                    str_contains($tipoLower, 'alarma activada') || str_contains($tipoLower, 'persona extraviada') ||
                    str_contains($tipoLower, 'persona tirada en la via publica') || str_contains($tipoLower, 'lesiones') ||
                    str_contains($tipoLower, 'violacion de domicilio') || str_contains($tipoLower, 'violencia de genero') ||
                    str_contains($tipoLower, 'tentativa de arrebato') || str_contains($tipoLower, 'tentativa de hurto') ||
                    str_contains($tipoLower, 'tentativa de robo') || str_contains($tipoLower, 'tentativa de estafa') ||
                    str_contains($tipoLower, 'hurto') || str_contains($tipoLower, 'robo') ||
                    str_contains($tipoLower, 'arrebato') || str_contains($tipoLower, 'estafa') ||
                    str_contains($tipoLower, 'usurpacion') || str_contains($tipoLower, 'sustraccion') ||
                    str_contains($tipoLower, 'detencion') || str_contains($tipoLower, 'secuestro de elementos') ||
                    str_contains($tipoLower, 'derrame quimicos') || str_contains($tipoLower, 'ebrios')) {
                $badgeClass = 'warning';
            }
            // NIVEL 3: IMPORTANTE (Azul - Info)
            elseif(str_contains($tipoLower, 'aviso') || str_contains($tipoLower, 'animales sueltos') ||
                    str_contains($tipoLower, 'daños') || str_contains($tipoLower, 'ruidos molestos') ||
                    str_contains($tipoLower, 'elementos abandonados') || str_contains($tipoLower, 'cuidacoches') ||
                    str_contains($tipoLower, 'problemas entre vecinos') || str_contains($tipoLower, 'problemas familiares') ||
                    str_contains($tipoLower, 'maltrato animal') || str_contains($tipoLower, 'pedido de captura') ||
                    str_contains($tipoLower, 'pedido de localizacion') || str_contains($tipoLower, 'persona en actitud sospechosa') ||
                    str_contains($tipoLower, 'allanamiento') || str_contains($tipoLower, 'corte de calle') ||
                    str_contains($tipoLower, 'desorden en la via publica') || str_contains($tipoLower, 'delitos contra la honestidad') ||
                    str_contains($tipoLower, 'portacion de arma blanca') || str_contains($tipoLower, 'tiroteo') ||
                    str_contains($tipoLower, 'inclemencias climaticas')) {
                $badgeClass = 'info';
            }
            // NIVEL 4: MODERADO (Gris - Secondary)
            elseif(str_contains($tipoLower, 'colaboracion') || str_contains($tipoLower, 'informa datos') ||
                    str_contains($tipoLower, 'llamada falsa') || str_contains($tipoLower, 'broma') ||
                    str_contains($tipoLower, 'no responde') || str_contains($tipoLower, 'reiteracion de llamada') ||
                    str_contains($tipoLower, 'equivocado') || str_contains($tipoLower, 'insulto') ||
                    str_contains($tipoLower, 'correcta identificacion') || str_contains($tipoLower, 'recepcion sospechosa') ||
                    str_contains($tipoLower, 'servicio bancario')) {
                $badgeClass = 'secondary';
            }
            // NIVEL 5: LEVE (Verde - Success)
            elseif(str_contains($tipoLower, 'consulta') || str_contains($tipoLower, 'psicologico')) {
                $badgeClass = 'success';
            }
        @endphp
        <span class="badge badge-{{ $badgeClass }}">{{ $eventoCecoco->tipo_servicio }}</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-lg-6 mb-4 mb-lg-0">
                <h5 class="mb-3">Datos del evento</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Nº Expediente:</th>
                                <td>{{ $eventoCecoco->nro_expediente }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Fecha/Hora:</th>
                                <td>{{ $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @if($eventoCecoco->fecha_cierre)
                            <tr>
                                <th class="table-secondary">Fecha Cierre:</th>
                                <td>{{ $eventoCecoco->fecha_cierre->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="table-secondary">Tipo Servicio:</th>
                                <td>{{ $eventoCecoco->tipo_servicio }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Período:</th>
                                <td>{{ $eventoCecoco->periodo }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Año:</th>
                                <td>{{ $eventoCecoco->anio }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Mes:</th>
                                <td>
                                    @php
                                        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    @endphp
                                    {{ $eventoCecoco->mes ? $mesesNombres[$eventoCecoco->mes] : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <h5 class="mb-3">Operación y contacto</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Operador:</th>
                                <td>{{ $eventoCecoco->operador ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Box:</th>
                                <td>{{ $eventoCecoco->box ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Teléfono:</th>
                                <td>
                                    @if($eventoCecoco->telefono)
                                        <a href="tel:{{ $eventoCecoco->telefono }}">{{ $eventoCecoco->telefono }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Dirección:</th>
                                <td class="text-break">{{ $eventoCecoco->direccion ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @php
            $detalleCache = optional($eventoCecoco->detalle)->detalle_json ?? [];
            $recursosQuick = $detalleCache['tramites'] ?? [];
            $cierreQuick = $detalleCache['cierre'] ?? [];
        @endphp

        @if(!empty($recursosQuick))
            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-truck"></i> Recursos que intervinieron</h5>
                <span class="badge badge-secondary">{{ count($recursosQuick) }}</span>
            </div>
            <div class="mb-3">
                @foreach($recursosQuick as $r)
                    <span class="badge badge-primary mr-1 mb-1" style="font-size:.9rem;">
                        <i class="bi bi-geo-alt-fill"></i> {{ ($r['unidad'] ?? $r['tr_amites'] ?? '') ?: '-' }}
                    </span>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Unidad</th>
                            <th>H. Asignación</th>
                            <th>H. Llegada</th>
                            <th>H. Fin Atención</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recursosQuick as $r)
                            <tr>
                                <td><strong>{{ ($r['unidad'] ?? $r['tr_amites'] ?? '') ?: '-' }}</strong></td>
                                <td><small>{{ ($r['h_asig'] ?? '') ?: '-' }}</small></td>
                                <td><small>{{ ($r['h_llegada'] ?? '') ?: '-' }}</small></td>
                                <td><small>{{ ($r['h_f_atenci_on'] ?? $r['h_f_atencion'] ?? '') ?: '-' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($eventoCecoco->detalle)
            <hr class="my-4">
            <h5 class="mb-2"><i class="bi bi-truck"></i> Recursos que intervinieron</h5>
            <p class="text-muted mb-0"><em>No se registraron recursos asignados para este evento.</em></p>
        @endif

        @if(!empty(array_filter($cierreQuick)))
            <hr class="my-4">
            <h5 class="mb-2"><i class="bi bi-door-closed"></i> Observaciones de cierre</h5>
            <div class="p-3 border rounded mb-2" style="white-space: pre-wrap; font-size: 13px; background-color: var(--bs-secondary-bg);">
                {{ !empty($cierreQuick['observaciones']) ? $cierreQuick['observaciones'] : 'Sin observaciones de cierre.' }}
            </div>
        @endif

        <hr class="my-4">

        <h5 class="mb-3">Descripción completa</h5>
        <div class="p-3 border rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 14px; background-color: var(--bs-secondary-bg); color: var(--bs-body-color);">
            @if($eventoCecoco->descripcion)
                {{ $eventoCecoco->descripcion }}
            @else
                <em class="text-muted">Sin descripción registrada.</em>
            @endif
        </div>
    </div>
    <div class="card-footer text-muted">
        <small>
            <i class="bi bi-file-earmark"></i>
            Archivo origen: <strong>{{ $eventoCecoco->importacion ? $eventoCecoco->importacion->nombre_archivo_corto : 'N/A' }}</strong>
            @if($eventoCecoco->importacion)
                | Importado el {{ $eventoCecoco->importacion->created_at->format('d/m/Y H:i') }}
            @endif
        </small>
    </div>
</div>
@endsection

{{-- ===== MODAL GRABACIONES BRI (fuera del section content) ===== --}}
@can('ver-grabacion-evento')

@section('page_css')
<style>
.grabacion-spin { animation: grabacion-spin-anim 1s linear infinite; display: inline-block; }
@keyframes grabacion-spin-anim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.grabacion-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
}
.grabacion-card .grabacion-meta {
    font-size: .82rem;
    color: #6c757d;
    margin-bottom: .5rem;
}
.grabacion-card audio {
    width: 100%;
    margin-top: .5rem;
    border-radius: 6px;
}
.grabacion-card .grabacion-actions {
    margin-top: .5rem;
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    align-items: center;
}
.grabacion-nombre {
    font-weight: 600;
    word-break: break-all;
    font-size: .9rem;
}
</style>
@endsection

@push('scripts')
{{-- Modal --}}
<div class="modal fade" id="modalGrabaciones" tabindex="-1" role="dialog" aria-labelledby="modalGrabacionesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalGrabacionesLabel">
                    <i class="bi bi-mic-fill"></i> Grabaciones BRI &mdash; Exp. {{ $eventoCecoco->nro_expediente }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" id="grabaciones-ventana" style="display:none; font-size:.85rem;">
                    <i class="bi bi-clock"></i> Ventana de búsqueda: <span id="ventana-desde"></span> → <span id="ventana-hasta"></span>
                </div>
                <div id="grabaciones-loading" class="text-center py-4">
                    <i class="bi bi-arrow-repeat grabacion-spin"></i> Buscando grabaciones...
                </div>
                <div id="grabaciones-empty" style="display:none;" class="text-center py-4 text-muted">
                    <i class="bi bi-mic-mute" style="font-size:2rem;"></i>
                    <p class="mt-2">No se encontraron grabaciones para este evento.</p>
                    <small>Teléfono buscado: <strong>{{ $eventoCecoco->telefono ?? 'sin teléfono' }}</strong></small>
                </div>
                <div id="grabaciones-error" style="display:none;" class="alert alert-danger py-2"></div>
                <div id="grabaciones-lista" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Las grabaciones se reproducen directamente en el navegador.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirGrabaciones() {
    document.getElementById('grabaciones-loading').style.display = 'block';
    document.getElementById('grabaciones-empty').style.display   = 'none';
    document.getElementById('grabaciones-error').style.display   = 'none';
    document.getElementById('grabaciones-lista').style.display   = 'none';
    document.getElementById('grabaciones-ventana').style.display = 'none';
    document.getElementById('grabaciones-lista').innerHTML       = '';

    $('#modalGrabaciones').modal('show');

    fetch('{{ route("api.cecoco.grabaciones", $eventoCecoco) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('grabaciones-loading').style.display = 'none';

        if (!data.success) {
            document.getElementById('grabaciones-error').style.display  = 'block';
            document.getElementById('grabaciones-error').textContent    = data.message || 'Error al obtener grabaciones.';
            return;
        }

        if (data.ventana) {
            document.getElementById('ventana-desde').textContent        = data.ventana.desde;
            document.getElementById('ventana-hasta').textContent        = data.ventana.hasta;
            document.getElementById('grabaciones-ventana').style.display = 'block';
        }

        if (!data.grabaciones || data.grabaciones.length === 0) {
            document.getElementById('grabaciones-empty').style.display = 'block';
            return;
        }

        var lista = document.getElementById('grabaciones-lista');
        lista.style.display = 'block';
        var streamBase = '{{ route("api.cecoco.grabacion.stream") }}';

        data.grabaciones.forEach(function(g) {
            var nombre      = g.nombreFichero || g.nombre || 'grabacion';
            var streamUrl   = g.url
                ? (g.fuente === 'local' ? g.url : streamBase + '?url=' + encodeURIComponent(g.url))
                : null;
            var downloadUrl = streamUrl ? streamUrl + '&download=1' : null;
            var duracion    = g.duracion || '—';

            var card = document.createElement('div');
            card.className = 'grabacion-card';

            var audioHtml = streamUrl
                ? '<audio controls preload="none" style="width:100%;margin-top:.5rem;">' +
                      '<source src="' + streamUrl + '" type="audio/wav">' +
                      '<source src="' + streamUrl + '" type="audio/mpeg">' +
                      '<source src="' + streamUrl + '" type="audio/ogg">' +
                      'Tu navegador no soporta reproducción de audio.' +
                  '</audio>'
                : '<div class="text-muted" style="font-size:.8rem;margin-top:.4rem;"><i class="bi bi-exclamation-circle"></i> Sin enlace de audio disponible</div>';

            var downloadHtml = downloadUrl
                ? '<a href="' + downloadUrl + '" class="btn btn-sm btn-outline-primary" download="' + escHtml(nombre) + '">' +
                      '<i class="bi bi-download"></i> Descargar' +
                  '</a>'
                : '';

            card.innerHTML =
                '<div class="grabacion-nombre"><i class="bi bi-file-earmark-music"></i> ' + escHtml(nombre) + '</div>' +
                '<div class="grabacion-meta">' +
                    '<span><i class="bi bi-calendar3"></i> ' + escHtml(g.fechaInicio || '—') + '</span>' +
                    ' &nbsp;|&nbsp; <span><i class="bi bi-stopwatch"></i> ' + escHtml(duracion) + '</span>' +
                    ' &nbsp;|&nbsp; <span><i class="bi bi-person"></i> ' + escHtml(g.operador || '—') + '</span>' +
                    (g.numero ? ' &nbsp;|&nbsp; <span><i class="bi bi-telephone"></i> ' + escHtml(g.numero) + '</span>' : '') +
                '</div>' +
                audioHtml +
                '<div class="grabacion-actions">' + downloadHtml + '</div>';
            lista.appendChild(card);
        });
    })
    .catch(function(err) {
        document.getElementById('grabaciones-loading').style.display = 'none';
        document.getElementById('grabaciones-error').style.display   = 'block';
        document.getElementById('grabaciones-error').textContent     = 'Error de red al obtener grabaciones.';
        console.error(err);
    });
}

$('#modalGrabaciones').on('hide.bs.modal', function() {
    document.querySelectorAll('#grabaciones-lista audio').forEach(function(audio) {
        audio.pause();
        audio.currentTime = 0;
    });
});

function calcularDuracion(inicio, fin) {
    try {
        var diff = Math.round((new Date(fin) - new Date(inicio)) / 1000);
        if (diff < 0) return '—';
        var m = Math.floor(diff / 60).toString().padStart(2, '0');
        var s = (diff % 60).toString().padStart(2, '0');
        return m + ':' + s;
    } catch(e) { return '—'; }
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush

@endcan

{{-- ===== MODAL MODULACIONES TETRA (grabador) ===== --}}
@can('escuchar-modulaciones-cecoco')

@push('scripts')
<div class="modal fade" id="modalModulaciones" tabindex="-1" role="dialog" aria-labelledby="modalModulacionesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalModulacionesLabel">
                    <i class="bi bi-broadcast-pin"></i> Modulaciones &mdash; Exp. {{ $eventoCecoco->nro_expediente }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-2" id="modulaciones-ventana" style="display:none; font-size:.85rem;">
                    <i class="bi bi-clock"></i> Ventana de búsqueda: <span id="mod-ventana-desde"></span> → <span id="mod-ventana-hasta"></span>
                    <span class="badge badge-light ml-1" id="mod-fuente" style="display:none;" title="De dónde se obtuvo el listado"></span>
                    <span class="badge badge-warning ml-1" id="mod-sin-audio" style="display:none;"
                          title="Modulaciones que ningún operador de CECOCO escuchó (no hay copia en el backup) y cuyo WAV no se puede obtener porque este servidor no tiene el Replay Server del grabador"></span>
                </div>
                <div id="modulaciones-filtro-wrap" class="mb-2" style="display:none;">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                        </div>
                        <input type="text" id="modulaciones-filtro" class="form-control" autocomplete="off"
                               placeholder="Filtrar por recurso, SSI, canal u hora (ej: M2231216, Cria 904, 06:16)...">
                        <div class="input-group-append">
                            <span class="input-group-text text-muted" id="modulaciones-contador">0</span>
                        </div>
                    </div>
                </div>
                <div id="modulaciones-loading" class="text-center py-4">
                    <i class="bi bi-arrow-repeat grabacion-spin"></i> Buscando modulaciones...
                </div>
                <div id="modulaciones-empty" style="display:none;" class="text-center py-4 text-muted">
                    <i class="bi bi-broadcast" style="font-size:2rem;"></i>
                    <p class="mt-2">No se encontraron modulaciones en la ventana del evento.</p>
                </div>
                <div id="modulaciones-sin-filtro" style="display:none;" class="text-center py-3 text-muted small">
                    <i class="bi bi-funnel"></i> Ninguna modulación coincide con el filtro.
                </div>
                <div id="modulaciones-error" style="display:none;" class="alert alert-danger py-2"></div>
                <div id="modulaciones-lista" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Las modulaciones se reproducen directamente en el navegador.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModulaciones() {
    document.getElementById('modulaciones-loading').style.display     = 'block';
    document.getElementById('modulaciones-empty').style.display       = 'none';
    document.getElementById('modulaciones-error').style.display       = 'none';
    document.getElementById('modulaciones-lista').style.display       = 'none';
    document.getElementById('modulaciones-ventana').style.display     = 'none';
    document.getElementById('modulaciones-filtro-wrap').style.display = 'none';
    document.getElementById('modulaciones-sin-filtro').style.display  = 'none';
    document.getElementById('modulaciones-lista').innerHTML           = '';
    document.getElementById('modulaciones-filtro').value             = '';

    $('#modalModulaciones').modal('show');

    fetch('{{ route("api.cecoco.modulaciones", $eventoCecoco) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('modulaciones-loading').style.display = 'none';

        if (!data.success) {
            document.getElementById('modulaciones-error').style.display = 'block';
            document.getElementById('modulaciones-error').textContent   = data.message || 'Error al obtener modulaciones.';
            return;
        }

        if (data.ventana) {
            document.getElementById('mod-ventana-desde').textContent       = data.ventana.desde;
            document.getElementById('mod-ventana-hasta').textContent       = data.ventana.hasta;
            document.getElementById('modulaciones-ventana').style.display  = 'block';
        }

        if (data.fuente) {
            var fuenteEl = document.getElementById('mod-fuente');
            fuenteEl.textContent   = (data.fuente === 'grabador') ? 'Fuente: grabador' : 'Fuente: backup local';
            fuenteEl.style.display = '';
        }

        var sinAudio = (data.modulaciones || []).filter(function(m) { return m.audioDisponible === false; }).length;
        var sinAudioEl = document.getElementById('mod-sin-audio');
        if (sinAudio > 0) {
            sinAudioEl.textContent   = sinAudio + ' sin audio';
            sinAudioEl.style.display = '';
        } else {
            sinAudioEl.style.display = 'none';
        }

        if (!data.modulaciones || data.modulaciones.length === 0) {
            document.getElementById('modulaciones-empty').style.display = 'block';
            return;
        }

        var lista = document.getElementById('modulaciones-lista');
        lista.style.display = 'block';

        data.modulaciones.forEach(function(m) {
            var streamUrl   = m.url;
            var downloadUrl = streamUrl + '&download=1';
            var hora        = (m.fechaInicio || '').split(' ')[1] || (m.fechaInicio || '—');

            // Quién moduló: SSI llamante del grabador; si el recurso ya lo contiene
            // (ej. "Cria 904 (M2230904)") se usa esa etiqueta, que es más clara.
            var llamante = m.ssiLlamante || '';
            var quien    = m.recurso || llamante || m.canal || m.grupo || '—';
            if (llamante && m.recurso && m.recurso.indexOf(llamante) === -1) {
                quien = m.recurso + ' (' + llamante + ')';
            }

            // A quién moduló: SSI llamado, o el grupo si fue una llamada de grupo.
            var destino = m.ssiLlamado || m.grupo || '';

            // Tipo de llamada (Símplex / Dúplex) según el grabador.
            var tipoBadge = '';
            if (m.tipo) {
                var esDuplex = m.tipo.toLowerCase().indexOf('d') === 0;
                tipoBadge = ' <span class="badge ' + (esDuplex ? 'badge-warning' : 'badge-info') +
                            ' mod-tipo" title="Tipo de llamada">' + escHtml(m.tipo) + '</span>';
            }

            var nOperadores = (m.operadores && m.operadores.length) ? m.operadores.length : (m.copias || 1);
            var opsBadge = (nOperadores > 1)
                ? ' <span class="badge badge-secondary" title="Registrada por: ' + escHtml((m.operadores || []).join(', ')) + '">' +
                      '<i class="bi bi-people-fill"></i> ' + nOperadores + '</span>'
                : '';

            // Texto sobre el que se filtra (recurso + canal + hora + ssi + tipo).
            var filtro = [m.recurso, m.canal, m.grupo, m.tipo, m.fechaInicio, m.ssiLlamante, m.ssiLlamado]
                .filter(Boolean).join(' ').toLowerCase();

            var card = document.createElement('div');
            card.className = 'modulacion-card';
            card.setAttribute('data-filtro', filtro);

            // El canal completo va en la línea de detalle sólo si difiere del título.
            var sub = (m.canal && m.canal !== quien) ? escHtml(m.canal) : '';

            // Player + descarga; si el backend avisa que el audio no está disponible
            // (Replay Server inaccesible), se muestra el aviso directamente.
            var audioHtml = (m.audioDisponible === false)
                ? '<span class="mod-audio mod-audio-error badge badge-warning" title="El servidor no tiene acceso al Replay Server del grabador">' +
                      '<i class="bi bi-volume-mute"></i> Audio no disponible</span>'
                : '<audio class="mod-audio" controls preload="none">' +
                      '<source src="' + streamUrl + '">' +
                  '</audio>' +
                  '<a href="' + downloadUrl + '" class="btn btn-sm btn-outline-secondary mod-dl" download title="Descargar audio">' +
                      '<i class="bi bi-download"></i>' +
                  '</a>';

            card.innerHTML =
                '<div class="mod-info">' +
                    '<div class="mod-titulo">' +
                        '<i class="bi bi-mic-fill text-primary"></i> ' +
                        '<span class="mod-recurso" title="Quién moduló">' + escHtml(quien) + '</span>' +
                        (destino ? ' <i class="bi bi-arrow-right mod-flecha"></i> <span class="mod-destino" title="A quién/qué grupo moduló">' + escHtml(destino) + '</span>' : '') +
                        tipoBadge + opsBadge +
                    '</div>' +
                    '<div class="mod-meta">' +
                        '<span title="Hora de inicio"><i class="bi bi-clock"></i> ' + escHtml(hora) + '</span>' +
                        '<span title="Duración"><i class="bi bi-stopwatch"></i> ' + escHtml(m.duracion || '—') + '</span>' +
                        (llamante && quien.indexOf(llamante) === -1 ? '<span title="SSI que moduló"><i class="bi bi-mic"></i> SSI ' + escHtml(llamante) + '</span>' : '') +
                        (sub ? '<span title="Canal"><i class="bi bi-broadcast"></i> ' + sub + '</span>' : '') +
                    '</div>' +
                '</div>' +
                audioHtml;
            lista.appendChild(card);

            // Si el navegador no logra cargar el audio (ej. el proxy devuelve un
            // error), se reemplaza el player por un aviso claro.
            var sourceEl = card.querySelector('audio source');
            if (sourceEl) {
                sourceEl.addEventListener('error', function() {
                    var audioEl = card.querySelector('audio');
                    if (audioEl) {
                        var aviso = document.createElement('span');
                        aviso.className = 'mod-audio mod-audio-error badge badge-warning';
                        aviso.innerHTML = '<i class="bi bi-volume-mute"></i> Audio no disponible';
                        audioEl.replaceWith(aviso);
                    }
                });
            }
        });

        document.getElementById('modulaciones-filtro-wrap').style.display = 'block';
        filtrarModulaciones();
    })
    .catch(function(err) {
        document.getElementById('modulaciones-loading').style.display = 'none';
        document.getElementById('modulaciones-error').style.display   = 'block';
        document.getElementById('modulaciones-error').textContent     = 'Error de red al obtener modulaciones.';
        console.error(err);
    });
}

function filtrarModulaciones() {
    var q       = (document.getElementById('modulaciones-filtro').value || '').trim().toLowerCase();
    var cards   = document.querySelectorAll('#modulaciones-lista .modulacion-card');
    var visibles = 0;

    cards.forEach(function(card) {
        var coincide = (q === '') || (card.getAttribute('data-filtro') || '').indexOf(q) !== -1;
        card.style.display = coincide ? '' : 'none';
        if (coincide) { visibles++; }
    });

    document.getElementById('modulaciones-contador').textContent = visibles + ' / ' + cards.length;
    document.getElementById('modulaciones-sin-filtro').style.display = (visibles === 0 && cards.length > 0) ? 'block' : 'none';
}

document.getElementById('modulaciones-filtro').addEventListener('input', filtrarModulaciones);

$('#modalModulaciones').on('hide.bs.modal', function() {
    document.querySelectorAll('#modulaciones-lista audio').forEach(function(audio) {
        audio.pause();
        audio.currentTime = 0;
    });
});
</script>

<style>
.modulacion-card {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .6rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 6px;
    margin-bottom: .4rem;
    background: var(--bs-secondary-bg, #f8f9fa);
}
.modulacion-card .mod-info { flex: 1 1 40%; min-width: 0; }
.modulacion-card .mod-titulo {
    font-weight: 600;
    font-size: .85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.modulacion-card .mod-meta {
    font-size: .72rem;
    color: #6c757d;
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
}
.modulacion-card .mod-destino { font-weight: 400; }
.modulacion-card .mod-flecha { color: #6c757d; }
.modulacion-card .mod-tipo { font-size: .65rem; vertical-align: middle; }
.modulacion-card .mod-audio { flex: 1 1 55%; height: 32px; max-width: 320px; }
.modulacion-card .mod-audio-error {
    height: auto;
    padding: .35rem .5rem;
    text-align: center;
    white-space: normal;
}
.modulacion-card .mod-dl { flex: 0 0 auto; padding: .15rem .45rem; }
@media (max-width: 575px) {
    .modulacion-card { flex-wrap: wrap; }
    .modulacion-card .mod-audio { flex: 1 1 100%; max-width: none; }
}

/* Modo oscuro */
[data-theme="dark"] .modulacion-card {
    background: var(--bg-secondary, #0b1b31) !important;
    border-color: var(--border-color, rgba(255, 255, 255, .15)) !important;
    color: var(--text-primary, #eaf6ff);
}
[data-theme="dark"] .modulacion-card .mod-dl {
    color: var(--text-primary, #eaf6ff) !important;
    border-color: rgba(255, 255, 255, .45) !important;
}
[data-theme="dark"] .modulacion-card .mod-dl:hover {
    background: rgba(255, 255, 255, .15) !important;
}
[data-theme="dark"] .modulacion-card .mod-titulo { color: var(--text-primary, #eaf6ff); }
[data-theme="dark"] .modulacion-card .mod-meta,
[data-theme="dark"] .modulacion-card .mod-flecha { color: var(--text-secondary, #9fb6c9); }
[data-theme="dark"] .modulacion-card .mod-audio { color-scheme: dark; }
[data-theme="dark"] #modulaciones-empty,
[data-theme="dark"] #modulaciones-sin-filtro,
[data-theme="dark"] #modulaciones-loading { color: var(--text-secondary, #9fb6c9) !important; }
</style>
@endpush

@endcan
