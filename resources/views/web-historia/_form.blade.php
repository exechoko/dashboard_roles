<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="anio">Año o período <span class="text-danger">*</span></label>
                    <input type="text" name="anio" id="anio" maxlength="40" required
                           class="form-control @error('anio') is-invalid @enderror"
                           value="{{ old('anio', $card->anio ?? '') }}"
                           placeholder="Ej: 2012, 2012 - 2020, Hoy">
                    @error('anio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label for="tag">Etiqueta (tag)</label>
                    <input type="text" name="tag" id="tag" maxlength="60"
                           class="form-control @error('tag') is-invalid @enderror"
                           value="{{ old('tag', $card->tag ?? '') }}"
                           placeholder="Ej: Hito fundacional (opcional)">
                    @error('tag') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" min="0" step="1"
                           class="form-control @error('orden') is-invalid @enderror"
                           value="{{ old('orden', $card->orden ?? 0) }}">
                    @error('orden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Menor = aparece primero.</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="titulo">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" id="titulo" maxlength="200" required
                   class="form-control @error('titulo') is-invalid @enderror"
                   value="{{ old('titulo', $card->titulo ?? '') }}">
            @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="texto">Texto <span class="text-danger">*</span></label>
            <textarea name="texto" id="texto" rows="5" required
                      class="form-control @error('texto') is-invalid @enderror">{{ old('texto', $card->texto ?? '') }}</textarea>
            @error('texto') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group mb-0">
            <label for="imagenes">Imágenes solapadas — hasta 3 (jpg, png, webp · máx. 5 MB c/u)</label>
            @if (isset($card) && !empty($card->imagenes))
                <div class="d-flex flex-wrap mb-2" style="gap:1rem;">
                    @foreach ($card->imagenes as $nombre)
                        <div class="text-center">
                            <img src="{{ route('web-historia.imagen', $nombre) }}" alt=""
                                 style="height:110px;border-radius:8px;object-fit:cover;">
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" name="quitar_imagenes[]" value="{{ $nombre }}"
                                       id="quitar_{{ $loop->index }}" class="custom-control-input">
                                <label class="custom-control-label text-danger" for="quitar_{{ $loop->index }}">Quitar</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" name="imagenes[]" id="imagenes" accept="image/*" multiple class="form-control-file">
            <small class="text-muted">
                Se muestran solapadas en la esquina de la tarjeta; al hacer clic se abre una galería con todas.
                @if (isset($card) && !empty($card->imagenes))
                    Ya tiene {{ count($card->imagenes) }}/3; las que subas se suman a las que conserves.
                @else
                    Si no cargás ninguna, la tarjeta se muestra sin fotos.
                @endif
            </small>
        </div>
    </div>
</div>
