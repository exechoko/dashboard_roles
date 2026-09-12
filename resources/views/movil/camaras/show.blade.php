@extends('layouts.movil')

@section('title', $camara->nombre)
@section('back', $volver)

@section('content')
    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            {{ $camara->nombre }}
            @if ($camara->tipoCamara)
                <span class="m-chip">{{ $camara->tipoCamara->tipo }}</span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Sitio</dt><dd>{{ $camara->sitio?->nombre ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Dependencia</dt><dd>{{ $camara->sitio?->destino?->nombre ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Localidad</dt><dd>{{ $camara->sitio?->localidad ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Estado del sitio</dt><dd>{{ $camara->sitio?->activo ? 'Activo' : 'Inactivo' }}</dd></div>
            <div class="m-detail__row"><dt>Etapa</dt><dd>{{ $camara->etapa ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Marca / modelo</dt><dd>{{ $camara->tipoCamara?->marca }} {{ $camara->tipoCamara?->modelo }}</dd></div>
            <div class="m-detail__row"><dt>N.º de serie</dt><dd>{{ $camara->nro_serie ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>IP</dt><dd>{{ $camara->ip ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Orientación</dt><dd>{{ $camara->orientacion ?? '—' }} ({{ $camara->angulo ?? 60 }}°)</dd></div>
            <div class="m-detail__row"><dt>Fecha de instalación</dt><dd>{{ optional($camara->fecha_instalacion)->format('d/m/Y') ?? '—' }}</dd></div>
            @if ($camara->observaciones)
                <div class="m-detail__row"><dt>Observaciones</dt><dd>{{ $camara->observaciones }}</dd></div>
            @endif
        </dl>
    </div>

    @if ($camara->sitio?->latitud && $camara->sitio?->longitud)
        <a class="m-btn m-btn--outline" style="width:100%; margin-bottom:.6rem;"
           href="https://www.google.com/maps/search/?api=1&query={{ $camara->sitio->latitud }},{{ $camara->sitio->longitud }}"
           target="_blank" rel="noopener">
            <i class="fas fa-map-marker-alt"></i> Ver ubicación en Google Maps
        </a>
    @endif

    @can('reiniciar-camara')
        @if ($camara->ip)
            <button type="button" id="btnReiniciarCamara" class="m-btn m-btn--outline" style="width:100%;">
                <i class="fas fa-sync-alt"></i> Reiniciar cámara
            </button>
            <div id="reiniciarCamaraMensaje" class="m-empty" style="display:none; padding:.6rem 0;"></div>
        @endif
    @endcan
@endsection

@can('reiniciar-camara')
    @if ($camara->ip)
        @section('scripts')
            <script>
                (function () {
                    var boton = document.getElementById('btnReiniciarCamara');
                    var mensaje = document.getElementById('reiniciarCamaraMensaje');

                    boton.addEventListener('click', function () {
                        if (!confirm('¿Seguro que desea reiniciar la cámara "{{ addslashes($camara->nombre) }}"?')) {
                            return;
                        }

                        boton.disabled = true;
                        var icono = boton.querySelector('i');
                        icono.className = 'fas fa-hourglass-half fa-spin';
                        mensaje.style.display = 'none';

                        fetch('{{ route('movil.camaras.reiniciar', $camara->id) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                mensaje.style.display = '';
                                mensaje.innerHTML = d.ok
                                    ? '<i class="fas fa-check-circle" style="color:var(--m-success, #22c55e);"></i> Orden de reinicio enviada. La cámara vuelve a estar disponible en unos minutos.'
                                    : '<i class="fas fa-exclamation-triangle" style="color:var(--m-danger);"></i> ' + (d.error || 'No se pudo reiniciar la cámara.');
                            })
                            .catch(function () {
                                mensaje.style.display = '';
                                mensaje.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:var(--m-danger);"></i> No se pudo reiniciar la cámara.';
                            })
                            .finally(function () {
                                boton.disabled = false;
                                icono.className = 'fas fa-sync-alt';
                            });
                    });
                })();
            </script>
        @endsection
    @endif
@endcan
