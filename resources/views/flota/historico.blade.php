@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Histórico de Equipo</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">

                        <!-- CARD HEADER -->
                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon"><i class="fas fa-history"></i></div>
                                <div>
                                    @if ($desdeEquipo)
                                        <h5 class="header-title">
                                            <span class="tei-badge mr-2">{{ $flota->tei }}</span>
                                            @if (!is_null($flota->issi))
                                                <small class="text-muted">ISSI: {{ $flota->issi }}</small>
                                            @endif
                                        </h5>
                                        <small class="text-muted">
                                            {{ $flota->tipo_terminal->marca }} {{ $flota->tipo_terminal->modelo }}
                                            &mdash; Estado: <strong>{{ $flota->estado->nombre }}</strong>
                                        </small>
                                    @else
                                        <h5 class="header-title">
                                            <span class="tei-badge mr-2">{{ $flota->equipo->tei }}</span>
                                            @if (!is_null($flota->equipo->issi))
                                                <small class="text-muted">ISSI: {{ $flota->equipo->issi }}</small>
                                            @endif
                                        </h5>
                                        <small class="text-muted">
                                            {{ $flota->equipo->tipo_terminal->marca }} {{ $flota->equipo->tipo_terminal->modelo }}
                                            &mdash; Estado: <strong>{{ $flota->equipo->estado->nombre }}</strong>
                                        </small>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($desdeEquipo == false)
                                    <a href="{{ route('flota.historico.imprimir', $flota->id) }}" target="_blank" class="btn btn-nuevo">
                                        <i class="fas fa-print mr-1"></i> Imprimir
                                    </a>
                                @endif
                                @if ($desdeEquipo)
                                    <img src="{{ asset($flota->tipo_terminal->imagen) }}"
                                        style="width:55px; border-radius:8px; border:1px solid var(--border-color);">
                                @else
                                    <img src="{{ asset($flota->equipo->tipo_terminal->imagen) }}"
                                        style="width:55px; border-radius:8px; border:1px solid var(--border-color);">
                                @endif
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Movimiento</th>
                                            <th>Fecha</th>
                                            <th>Móvil / Recurso</th>
                                            <th>Destino actual</th>
                                            <th>Recurso anterior</th>
                                            <th>Ticket PER</th>
                                            <th>Observaciones</th>
                                            <th>Anexo</th>
                                            @if ($desdeEquipo == false)
                                                @can('editar-historico')
                                                    <th class="text-center"></th>
                                                @endcan
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($hist as $h)
                                            <tr>
                                                <td>
                                                    @if (is_null($h->tipoMovimiento))
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        <span class="badge" style="background-color: {{ $h->tipoMovimiento->color ?? '#28a745' }}; color:#fff; border-radius:20px; padding:.25em .75em; font-size:.8rem; font-weight:500;">
                                                            {{ $h->tipoMovimiento->nombre }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-nowrap">
                                                    <small>{{ Carbon\Carbon::parse($h->fecha_asignacion)->format('d/m/Y H:i') }}</small>
                                                </td>
                                                <td>
                                                    @if ($h->recurso_asignado)
                                                        <span class="dep-nombre">{{ $h->recurso_asignado }}</span>
                                                        @if ($h->vehiculo_asignado)
                                                            <span class="dep-padre"><i class="fas fa-id-card mr-1"></i>{{ $h->vehiculo_asignado }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="dep-cell">
                                                    @if ($h->destino)
                                                        <span class="dep-nombre">{{ $h->destino->nombre }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($h->recurso_desasignado)
                                                        <span class="dep-nombre">{{ $h->recurso_desasignado }}</span>
                                                        @if ($h->vehiculo_desasignado)
                                                            <span class="dep-padre"><i class="fas fa-id-card mr-1"></i>{{ $h->vehiculo_desasignado }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td><small class="text-muted">{{ $h->ticket_per ?? '—' }}</small></td>
                                                <td class="obs-cell">
                                                    @if($h->observaciones)
                                                        <span class="obs-text"
                                                              data-toggle="tooltip" data-placement="left" data-container="body"
                                                              title="{{ $h->observaciones }}">
                                                            {{ Str::limit($h->observaciones, 35, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (!empty($h->rutas_imagenes))
                                                        <div class="d-flex flex-wrap align-items-center" style="gap:.3rem;">
                                                            @foreach ($h->rutas_imagenes as $ruta)
                                                                @if (preg_match('/\.(jpg|jpeg|png)$/i', $ruta))
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <img src="{{ asset($ruta) }}" style="width:28px; height:28px; object-fit:cover; border-radius:4px;">
                                                                    </a>
                                                                @elseif (str_ends_with($ruta, '.pdf'))
                                                                    <a href="{{ asset($ruta) }}" target="_blank" title="PDF">
                                                                        <i class="fas fa-file-pdf" style="font-size:22px; color:#e74c3c;"></i>
                                                                    </a>
                                                                @elseif (preg_match('/\.(doc|docx)$/i', $ruta))
                                                                    <a href="{{ asset($ruta) }}" target="_blank" title="Word">
                                                                        <i class="fas fa-file-word" style="font-size:22px; color:#007aff;"></i>
                                                                    </a>
                                                                @elseif (str_ends_with($ruta, '.xlsx'))
                                                                    <a href="{{ asset($ruta) }}" target="_blank" title="Excel">
                                                                        <i class="fas fa-file-excel" style="font-size:22px; color:#28a745;"></i>
                                                                    </a>
                                                                @elseif (preg_match('/\.(zip|rar)$/i', $ruta))
                                                                    <a href="{{ asset($ruta) }}" target="_blank" title="Archivo">
                                                                        <i class="fas fa-file-archive" style="font-size:22px; color:#6f42c1;"></i>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                @if ($desdeEquipo == false)
                                                    @can('editar-historico')
                                                        <td class="text-center action-td">
                                                            <a class="action-btn btn-edit" href="#" data-toggle="modal"
                                                                data-target="#ModalEditar{{ $h->id }}" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </td>
                                                    @endcan
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @foreach ($hist as $h)
        @include('flota.modal.editar_historico', ['h' => $h])
    @endforeach
@endsection

@push('scripts')
<script>
    window.nuevasImagenes = {};
    window.imagenesActualesMap = {};

    function initializeNuevasImagenes(id) {
        if (!window.nuevasImagenes[id]) window.nuevasImagenes[id] = [];
    }
    function previsualizarImagen(file, id) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const uniqueId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const el = document.createElement('div');
            el.style.cssText = 'position:relative;margin-right:10px;margin-bottom:10px;';
            el.innerHTML = `<img src="${e.target.result}" style="width:100px;height:auto;">
                <button type="button" class="btn btn-danger btn-sm" style="position:absolute;top:0;right:0;"
                    onclick="eliminarNuevaImagen('${uniqueId}','${id}')">X</button>`;
            el.setAttribute('data-nueva', uniqueId);
            window.nuevasImagenes[id].push({ uniqueId, file });
            document.getElementById(`imagenes-actuales-${id}`).appendChild(el);
        };
        reader.readAsDataURL(file);
    }
    function eliminarNuevaImagen(uniqueId, id) {
        if (window.nuevasImagenes[id])
            window.nuevasImagenes[id] = window.nuevasImagenes[id].filter(i => i.uniqueId !== uniqueId);
        const el = document.querySelector(`[data-nueva="${uniqueId}"]`);
        if (el) el.remove();
    }
    function eliminarImagen(ruta, id) {
        if (confirm('¿Eliminar esta imagen?')) {
            if (window.imagenesActualesMap[id])
                window.imagenesActualesMap[id] = window.imagenesActualesMap[id].filter(i => i !== ruta);
            const el = document.querySelector(`[data-ruta="${ruta}"]`);
            if (el) el.remove();
        }
    }
    function guardar(id) {
        let form = document.getElementById(`form-historico-${id}`);
        let formData = new FormData(form);
        if (window.nuevasImagenes[id]?.length)
            window.nuevasImagenes[id].forEach(o => formData.append('nuevas_imagenes[]', o.file));
        if (window.imagenesActualesMap[id])
            formData.append('imagenes_actuales', JSON.stringify(window.imagenesActualesMap[id]));
        $.ajax({
            url: form.action, type: 'POST', data: formData,
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: () => { alert('Registro actualizado.'); location.reload(); },
            error: (xhr) => alert('Error: ' + (xhr.responseText || ''))
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });
    });
</script>
@endpush
