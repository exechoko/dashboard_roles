@extends('layouts.app')

@section('css')
<style>
    .main-sidebar,
    .navbar,
    .content-header,
    footer.main-footer {
        display: none !important;
    }

    body,
    .wrapper,
    .content-wrapper,
    .content,
    section.content {
        padding:  0 !important;
        margin:   0 !important;
        height:   100vh !important;
        overflow: hidden !important;
        background: #1a1a2e;
    }

    #gis-toolbar {
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        background: rgba(255, 255, 255, 0.96);
        border-radius: 8px;
        padding: 6px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 3px 14px rgba(0,0,0,0.35);
        backdrop-filter: blur(6px);
        white-space: nowrap;
    }

    #gis-toolbar .toolbar-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #2c3e50;
    }

    #gis-toolbar .btn {
        padding: 3px 8px;
        font-size: 0.78rem;
        line-height: 1.4;
    }

    #gis-loading {
        position: fixed;
        inset: 0;
        background: #1a1a2e;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 99998;
        color: #fff;
        gap: 16px;
    }

    #gis-loading.hidden {
        display: none;
    }

    #gis-loading .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 0.3em;
    }

    #gis-error {
        display: none;
        position: fixed;
        inset: 0;
        background: #1a1a2e;
        color: #e74c3c;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        z-index: 99997;
        text-align: center;
        padding: 20px;
    }

    #gis-frame {
        position: fixed;
        inset: 0;
        width:  100%;
        height: 100%;
        border: none;
        display: none;
    }
</style>
@endsection

@section('content')

<div id="gis-toolbar">
    <i class="fas fa-satellite-dish text-success"></i>
    <span class="toolbar-title">Mapa GIS &mdash; CeCoCo</span>
    <div class="d-flex gap-1">
        <a href="{{ route('cecoco.index') }}" class="btn btn-sm btn-outline-secondary" title="Volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <button id="btn-reload" class="btn btn-sm btn-outline-primary" title="Recargar">
            <i class="fas fa-sync-alt"></i>
        </button>
        <button id="btn-fullscreen" class="btn btn-sm btn-outline-dark" title="Pantalla completa">
            <i class="fas fa-expand" id="icon-fs"></i>
        </button>
        @if($gisUrl)
        <a href="{{ $gisUrl }}" target="_blank" class="btn btn-sm btn-outline-success" title="Abrir en nueva pestaña">
            <i class="fas fa-external-link-alt"></i>
        </a>
        @endif
    </div>
</div>

{{-- Spinner de carga --}}
<div id="gis-loading">
    <div class="spinner-border text-info" role="status">
        <span class="sr-only">Cargando...</span>
    </div>
    <span style="font-size:0.95rem;">Conectando con el Visor GIS CeCoCo&hellip;</span>
</div>

{{-- Error --}}
<div id="gis-error">
    <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
    <p id="gis-error-msg" style="font-size:1.1rem; max-width:500px;">
        @if($error)
            {{ $error }}
        @else
            No se pudo cargar el visor GIS.
        @endif
    </p>
    <button onclick="location.reload()" class="btn btn-outline-danger mt-1">
        <i class="fas fa-redo"></i> Reintentar
    </button>
    <a href="{{ route('cecoco.index') }}" class="btn btn-outline-light mt-1">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

{{-- Iframe: carga directo al servidor GIS con JSESSIONID en la URL --}}
@if($gisUrl)
<iframe
    id="gis-frame"
    src="{{ $gisUrl }}"
    allow="geolocation; fullscreen"
    title="Visor GIS CeCoCo"
></iframe>
@endif

@endsection

@section('js')
<script>
(function () {
    const frame   = document.getElementById('gis-frame');
    const loading = document.getElementById('gis-loading');
    const errorEl = document.getElementById('gis-error');
    const errorMsg = document.getElementById('gis-error-msg');

    @if($error)
        // Error de autenticación desde el servidor
        loading.classList.add('hidden');
        errorEl.style.display = 'flex';
    @elseif($gisUrl)
        // Iframe disponible: mostrar cuando cargue
        let timer = setTimeout(() => {
            if (!loading.classList.contains('hidden')) {
                loading.classList.add('hidden');
                errorEl.style.display = 'flex';
                errorMsg.textContent = 'Tiempo de espera agotado. El servidor GIS no responde.';
            }
        }, 30000);

        frame.addEventListener('load', function () {
            clearTimeout(timer);
            loading.classList.add('hidden');
            frame.style.display = 'block';
        });

        frame.addEventListener('error', function () {
            clearTimeout(timer);
            loading.classList.add('hidden');
            errorEl.style.display = 'flex';
            errorMsg.textContent = 'Error al cargar el visor GIS.';
        });
    @else
        loading.classList.add('hidden');
        errorEl.style.display = 'flex';
    @endif

    // Recargar
    document.getElementById('btn-reload').addEventListener('click', function () {
        location.reload();
    });

    // Pantalla completa
    const iconFS = document.getElementById('icon-fs');
    document.getElementById('btn-fullscreen').addEventListener('click', function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen()
                .then(() => { iconFS.className = 'fas fa-compress'; })
                .catch(() => {});
        } else {
            document.exitFullscreen()
                .then(() => { iconFS.className = 'fas fa-expand'; })
                .catch(() => {});
        }
    });
    document.addEventListener('fullscreenchange', function () {
        if (!document.fullscreenElement) iconFS.className = 'fas fa-expand';
    });
})();
</script>
@endsection
