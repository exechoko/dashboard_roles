{{--
    Tarjeta de archivo para la Plataforma de Descargas
    ===================================================

    Props:
        archivo          (DescargaArchivo) requerido
        mostrarCheckbox  (bool) default false — checkbox de selección para armar ZIP
        mostrarFavorito  (bool) default true  — botón de favorito (requiere ruta descargas.toggle-favorito)
        favorito         (bool) default false — estado inicial del favorito

    Uso:
        <x-descarga-archivo-card :archivo="$archivo" mostrar-checkbox mostrar-favorito :favorito="$esFavorito" />
--}}
@props([
    'archivo',
    'mostrarCheckbox' => false,
    'mostrarFavorito' => true,
    'favorito' => false,
])

@php
    $esImagen = in_array(strtolower($archivo->extension), ['jpg', 'jpeg', 'png', 'gif']);
@endphp

<div {{ $attributes->merge(['class' => 'descarga-card card h-100']) }} data-archivo-id="{{ $archivo->id }}">
    @if($mostrarCheckbox)
        <div class="descarga-card__check custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input archivo-checkbox" id="archivo_{{ $archivo->id }}"
                   value="{{ $archivo->id }}" data-tamano="{{ $archivo->tamano_bytes }}">
            <label class="custom-control-label" for="archivo_{{ $archivo->id }}"></label>
        </div>
    @endif

    @if($archivo->destacado)
        <span class="descarga-card__destacado" title="Destacado"><i class="fas fa-star"></i></span>
    @endif

    <a href="{{ route('descargas.show', $archivo) }}" class="descarga-card__preview">
        @if($esImagen)
            <img src="{{ route('descargas.preview', $archivo) }}" alt="{{ $archivo->nombre_original }}" loading="lazy"
                 onerror="this.replaceWith(Object.assign(document.createElement('i'), {className: '{{ $archivo->icono_extension }}'}))">
        @else
            <i class="{{ $archivo->icono_extension }}"></i>
        @endif
    </a>

    <div class="card-body descarga-card__body">
        <a href="{{ route('descargas.show', $archivo) }}" class="descarga-card__nombre" title="{{ $archivo->nombre_original }}">
            {{ Str::limit($archivo->nombre_original, 42) }}
        </a>

        <div class="descarga-card__meta">
            <span class="badge descarga-badge-categoria" style="--categoria-color: {{ $archivo->categoria->color }}">
                <i class="{{ $archivo->categoria->icono }}"></i> {{ $archivo->categoria->nombre }}
            </span>
            @if($archivo->expira_at)
                @if($archivo->esta_expirado)
                    <span class="badge badge-danger">Expirado</span>
                @else
                    <span class="badge badge-warning">Expira en {{ $archivo->dias_para_expirar }} d.</span>
                @endif
            @endif
        </div>

        <div class="descarga-card__datos text-muted">
            <span title="Tamaño"><i class="fas fa-weight-hanging"></i> {{ $archivo->tamano_humano }}</span>
            <span title="Descargas"><i class="fas fa-download"></i> {{ $archivo->descargas_count }}</span>
            <span title="Subido por {{ $archivo->user->name ?? 'Sistema' }}"><i class="fas fa-user"></i> {{ Str::limit($archivo->user->name ?? 'Sistema', 16) }}</span>
        </div>

        @if($archivo->descripcion)
            <p class="descarga-card__descripcion text-muted" title="{{ $archivo->descripcion }}">
                {{ Str::limit($archivo->descripcion, 60) }}
            </p>
        @endif
    </div>

    <div class="card-footer descarga-card__acciones">
        <a href="{{ route('descargas.download', $archivo) }}" class="btn btn-sm btn-success" title="Descargar">
            <i class="fas fa-download"></i>
        </a>
        @if($archivo->es_previeweable)
            <a href="{{ route('descargas.preview', $archivo) }}" class="btn btn-sm btn-outline-info" title="Vista previa" target="_blank">
                <i class="fas fa-eye"></i>
            </a>
        @endif
        @if($mostrarFavorito)
            <button type="button" class="btn btn-sm {{ $favorito ? 'btn-warning' : 'btn-outline-warning' }} btn-toggle-favorito ml-auto"
                    data-archivo-id="{{ $archivo->id }}" title="{{ $favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                <i class="fas fa-star"></i>
            </button>
        @endif
    </div>
</div>
