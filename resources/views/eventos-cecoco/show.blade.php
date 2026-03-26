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
</div>

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
@can('ver-llamadas-cecoco')

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
