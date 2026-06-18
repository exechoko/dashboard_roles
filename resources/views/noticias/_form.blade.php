@php
    $fecha = old('fecha_publicacion', optional($noticia?->fecha_publicacion)->format('Y-m-d') ?? now()->format('Y-m-d'));
    $publicada = old('publicada', $noticia?->publicada ?? true);
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="titulo">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" id="titulo" maxlength="200" required
                           class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $noticia?->titulo) }}">
                    @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fecha_publicacion">Fecha de publicación <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_publicacion" id="fecha_publicacion" required
                           class="form-control @error('fecha_publicacion') is-invalid @enderror"
                           value="{{ $fecha }}">
                    @error('fecha_publicacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="bajada">Bajada / copete</label>
            <input type="text" name="bajada" id="bajada" maxlength="300"
                   class="form-control @error('bajada') is-invalid @enderror"
                   value="{{ old('bajada', $noticia?->bajada) }}"
                   placeholder="Resumen breve que se muestra en la tarjeta (opcional)">
            @error('bajada') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="cuerpo">Cuerpo de la noticia <span class="text-danger">*</span></label>
            <textarea name="cuerpo" id="cuerpo" rows="10" required
                      class="form-control @error('cuerpo') is-invalid @enderror"
                      placeholder="Texto completo. Separá los párrafos con un salto de línea.">{{ old('cuerpo', $noticia?->cuerpo) }}</textarea>
            @error('cuerpo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="custom-control custom-switch mb-2">
            <input type="hidden" name="publicada" value="0">
            <input type="checkbox" name="publicada" value="1" id="publicada"
                   class="custom-control-input" {{ $publicada ? 'checked' : '' }}>
            <label class="custom-control-label" for="publicada">Publicada (visible en la web)</label>
        </div>
    </div>
</div>

@if ($noticia && $noticia->imagenes->isNotEmpty())
    <div class="card">
        <div class="card-header"><h4>Imágenes actuales</h4></div>
        <div class="card-body">
            <p class="text-muted">Elegí la <strong>miniatura</strong> (la que se ve en la tarjeta) o marcá imágenes para eliminar.</p>
            <div class="row">
                @foreach ($noticia->imagenes as $img)
                    <div class="col-lg-3 col-md-4 col-6 mb-3">
                        <div class="card h-100">
                            <img src="{{ route('noticias.imagen', $img->archivo) }}" alt=""
                                 style="height:140px;object-fit:cover;border-top-left-radius:.5rem;border-top-right-radius:.5rem;">
                            <div class="card-body py-2">
                                <div class="custom-control custom-radio mb-1">
                                    <input type="radio" name="miniatura" value="e{{ $img->id }}"
                                           id="mini_e{{ $img->id }}" class="custom-control-input"
                                           {{ $img->es_miniatura ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="mini_e{{ $img->id }}">Miniatura</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="eliminar[]" value="{{ $img->id }}"
                                           id="del_{{ $img->id }}" class="custom-control-input">
                                    <label class="custom-control-label text-danger" for="del_{{ $img->id }}">Eliminar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><h4>{{ $noticia ? 'Agregar más imágenes' : 'Imágenes' }}</h4></div>
    <div class="card-body">
        <div class="form-group">
            <label for="imagenes">Seleccioná una o varias imágenes (jpg, png, webp · máx. 5 MB c/u)</label>
            <input type="file" name="imagenes[]" id="imagenes" multiple accept="image/*" class="form-control-file">
        </div>
        <div class="row" id="previewImagenes"></div>
        @if (!$noticia)
            <p class="text-muted small mb-0">La imagen marcada como <strong>miniatura</strong> es la que se muestra en la tarjeta del listado.</p>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('imagenes');
        const preview = document.getElementById('previewImagenes');
        const hayMiniaturaExistente = document.querySelector('input[name="miniatura"][value^="e"]:checked') !== null;

        input.addEventListener('change', function () {
            preview.innerHTML = '';
            Array.from(input.files).forEach(function (file, idx) {
                const url = URL.createObjectURL(file);
                const col = document.createElement('div');
                col.className = 'col-lg-3 col-md-4 col-6 mb-3';
                const checked = (!hayMiniaturaExistente && idx === 0) ? 'checked' : '';
                col.innerHTML = `
                    <div class="card h-100">
                        <img src="${url}" alt="" style="height:140px;object-fit:cover;border-top-left-radius:.5rem;border-top-right-radius:.5rem;">
                        <div class="card-body py-2">
                            <div class="custom-control custom-radio">
                                <input type="radio" name="miniatura" value="n${idx}" id="mini_n${idx}" class="custom-control-input" ${checked}>
                                <label class="custom-control-label" for="mini_n${idx}">Miniatura</label>
                            </div>
                            <small class="text-muted text-truncate d-block">${file.name}</small>
                        </div>
                    </div>`;
                preview.appendChild(col);
            });
        });
    });
</script>
