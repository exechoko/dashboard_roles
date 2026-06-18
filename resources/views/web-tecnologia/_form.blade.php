<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-7">
                <div class="form-group">
                    <label for="titulo">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" id="titulo" maxlength="200" required
                           class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $card->titulo ?? '') }}">
                    @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="color">Color <span class="text-danger">*</span></label>
                    <select name="color" id="color" required class="form-control @error('color') is-invalid @enderror">
                        @foreach ($colores as $id => $nombre)
                            <option value="{{ $id }}" @selected(old('color', $card->color ?? 'blue') === $id)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" min="0" step="1"
                           class="form-control @error('orden') is-invalid @enderror"
                           value="{{ old('orden', $card->orden ?? 0) }}">
                    @error('orden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="texto">Texto <span class="text-danger">*</span></label>
            <textarea name="texto" id="texto" rows="5" required
                      class="form-control @error('texto') is-invalid @enderror"
                      placeholder="Separá los párrafos con una línea en blanco.">{{ old('texto', $card->texto ?? '') }}</textarea>
            @error('texto') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group mb-0">
            <label for="imagen">Imagen de cabecera (jpg, png, webp · máx. 5 MB)</label>
            @if (isset($card) && $card->imagen)
                <div class="mb-2">
                    <img src="{{ route('web-tecnologia.imagen', $card->imagen) }}" alt=""
                         style="height:120px;border-radius:8px;object-fit:cover;">
                    <div class="custom-control custom-checkbox mt-1">
                        <input type="checkbox" name="quitar_imagen" value="1" id="quitar_imagen" class="custom-control-input">
                        <label class="custom-control-label text-danger" for="quitar_imagen">Quitar la imagen actual</label>
                    </div>
                </div>
            @endif
            <input type="file" name="imagen" id="imagen" accept="image/*" class="form-control-file">
            <small class="text-muted">Si no cargás imagen, la card muestra solo el color elegido.</small>
        </div>
    </div>
</div>
