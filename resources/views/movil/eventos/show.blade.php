@extends('layouts.movil')

@section('title', 'Expte. ' . $eventoCecoco->nro_expediente)
@section('back', $volver)

@section('content')
    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            Expte. {{ $eventoCecoco->nro_expediente }}
            @if ($eventoCecoco->tipo_servicio)
                <span class="m-chip m-chip--{{ \App\Helpers\TipoServicioCecocoClasificador::badgeClass($eventoCecoco->tipo_servicio) }}">{{ $eventoCecoco->tipo_servicio }}</span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Fecha</dt><dd>{{ optional($eventoCecoco->fecha_hora)->format('d/m/Y H:i') }}</dd></div>
            <div class="m-detail__row"><dt>Operador</dt><dd>{{ $eventoCecoco->operador ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Dirección</dt><dd>{{ $eventoCecoco->direccion ?? '—' }}</dd></div>
            @if ($eventoCecoco->telefono)
                <div class="m-detail__row"><dt>Teléfono</dt><dd>{{ $eventoCecoco->telefono }}</dd></div>
            @endif
            @if ($eventoCecoco->descripcion)
                <div class="m-detail__row"><dt>Descripción</dt><dd>{{ $eventoCecoco->descripcion }}</dd></div>
            @endif
        </dl>
    </div>

    @can('ver-expediente-cecoco')
        <div class="m-card__meta" style="margin-bottom:1rem;">
            <a href="{{ route('cecoco.exportar.pdf-resumen', $eventoCecoco) }}" target="_blank" class="m-btn m-btn--outline">
                <i class="fas fa-print"></i> Imprimir Parte de Novedad
            </a>
            @can('exportar-whatsapp-cecoco')
                <button type="button" class="m-btn m-btn--outline" title="Compartir el PDF de la Parte de Novedad"
                    onclick="compartirPdfEvento('{{ route('cecoco.exportar.pdf-resumen', $eventoCecoco) }}', 'ParteDeNovedad_{{ $eventoCecoco->nro_expediente }}.pdf', this)">
                    <i class="fab fa-whatsapp"></i>
                </button>
            @endcan
            <a href="{{ route('cecoco.exportar.pdf-interno', $eventoCecoco) }}" target="_blank" class="m-btn m-btn--outline">
                <i class="fas fa-file-invoice"></i> PDF Interno Completo
            </a>
            @can('exportar-whatsapp-cecoco')
                <button type="button" class="m-btn m-btn--outline" title="Compartir el PDF Interno Completo"
                    onclick="compartirPdfEvento('{{ route('cecoco.exportar.pdf-interno', $eventoCecoco) }}', 'ReporteInterno_{{ $eventoCecoco->nro_expediente }}.pdf', this)">
                    <i class="fab fa-whatsapp"></i>
                </button>
            @endcan
        </div>
    @endcan

    @include('eventos-cecoco.partials.resumen_ia', ['eventoCecoco' => $eventoCecoco])

    @if ($errorExpediente)
        <div class="m-alert m-alert--danger">{{ $errorExpediente }}</div>
    @elseif ($detalle)
        @php $historial = $detalle['historial'] ?? []; @endphp
        <div class="m-section-title">Expediente completo</div>
        <div class="m-detail">
            <dl style="margin:0;">
                @if (!empty($historial['barrio']))
                    <div class="m-detail__row"><dt>Barrio</dt><dd>{{ $historial['barrio'] }}</dd></div>
                @endif
                @if (!empty($historial['jurisdiccion']))
                    <div class="m-detail__row"><dt>Jurisdicción</dt><dd>{{ $historial['jurisdiccion'] }}</dd></div>
                @endif
                @if (!empty($historial['municipio']) || !empty($historial['localidad']))
                    <div class="m-detail__row"><dt>Localidad</dt><dd>{{ $historial['municipio'] ?? $historial['localidad'] }}</dd></div>
                @endif
                @if (!empty($historial['estado']))
                    <div class="m-detail__row"><dt>Estado</dt><dd>{{ $historial['estado'] }}</dd></div>
                @endif
            </dl>
        </div>

        @if ($tiempoRespuesta)
            <div class="m-section-title">Tiempo de respuesta</div>
            <div class="m-detail">
                <dl style="margin:0;">
                    <div class="m-detail__row"><dt>Minutos hasta atención</dt><dd><strong>{{ $tiempoRespuesta['minutos'] }} min</strong></dd></div>
                    <div class="m-detail__row"><dt>Recurso más rápido</dt><dd>{{ $tiempoRespuesta['recurso'] }}</dd></div>
                    <div class="m-detail__row"><dt>Recursos calculados</dt><dd>{{ $tiempoRespuesta['recursos_totales'] }}</dd></div>
                </dl>
            </div>
        @endif

        @if (!empty($detalle['tramites']))
            <div class="m-section-title">Recursos asignados ({{ $detalle['total_tramites'] ?? count($detalle['tramites']) }})</div>
            <div class="m-list">
                @foreach ($detalle['tramites'] as $tramite)
                    <div class="m-card">
                        <div class="m-card__title" style="font-size:.95rem;">{{ ($tramite['unidad'] ?? $tramite['tr_amites'] ?? '') ?: '-' }}</div>
                        <div class="m-card__meta">
                            <span class="m-chip">Asig. {{ ($tramite['h_asig'] ?? '') ?: '-' }}</span>
                            <span class="m-chip">Llegada {{ ($tramite['h_llegada'] ?? '') ?: '-' }}</span>
                            <span class="m-chip">Fin at. {{ ($tramite['h_f_atenci_on'] ?? $tramite['h_f_atencion'] ?? '') ?: '-' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (!empty($detalle['timeline']))
            <details class="m-detail" open style="padding:0; overflow:hidden;">
                <summary class="m-section-title" style="margin:0; padding:1rem; cursor:pointer; list-style:none;">
                    Cronología ({{ count($detalle['timeline']) }}) — tocar para ocultar/mostrar
                </summary>
                <div class="m-list" style="padding:0 1rem 1rem;">
                    @foreach ($detalle['timeline'] as $paso)
                        <div class="m-card">
                            <div class="m-card__subtitle">{{ $paso['fecha_hora'] ?? '' }} · {{ $paso['operador'] ?? '' }}</div>
                            <div>{{ \App\Helpers\CecocoAccionTraductor::traducir($paso['descripcion'] ?? '') }}</div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        @if (!empty($detalle['cierre']))
            <div class="m-section-title">Cierre</div>
            <div class="m-detail">
                <dl style="margin:0;">
                    <div class="m-detail__row"><dt>Fecha</dt><dd>{{ $detalle['cierre']['fecha'] ?? '—' }}</dd></div>
                    <div class="m-detail__row"><dt>Tipo</dt><dd>{{ $detalle['cierre']['tipo'] ?? '—' }}</dd></div>
                    @if (!empty($detalle['cierre']['observaciones']))
                        <div class="m-detail__row"><dt>Observaciones</dt><dd>{{ $detalle['cierre']['observaciones'] }}</dd></div>
                    @endif
                </dl>
            </div>
        @endif
    @endif
@endsection

@can('exportar-whatsapp-cecoco')
    @section('scripts')
        <script>
            // Comparte el PDF ya generado como archivo adjunto (no un link) usando el
            // share sheet nativo, para poder mandarlo por WhatsApp aunque el
            // destinatario no tenga cuenta en el sistema. Si el navegador no soporta
            // compartir archivos, lo descarga para adjuntarlo a mano.
            async function compartirPdfEvento(url, nombreArchivo, boton) {
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
                    var archivo = new File([blob], nombreArchivo, { type: blob.type || 'application/pdf' });

                    if (navigator.canShare && navigator.canShare({ files: [archivo] })) {
                        await navigator.share({ files: [archivo] });
                        return;
                    }

                    var urlObjeto = URL.createObjectURL(blob);
                    var enlace = document.createElement('a');
                    enlace.href = urlObjeto;
                    enlace.download = nombreArchivo;
                    document.body.appendChild(enlace);
                    enlace.click();
                    document.body.removeChild(enlace);
                    URL.revokeObjectURL(urlObjeto);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        alert('No se pudo compartir el PDF: ' + e.message);
                    }
                } finally {
                    icono.className = claseOriginal;
                    boton.disabled = false;
                }
            }
        </script>
    @endsection
@endcan
