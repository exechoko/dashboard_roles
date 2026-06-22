<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-7">
                <div class="form-group">
                    <label for="titulo">Título / descripción <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" id="titulo" maxlength="200" required
                           class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $imagen->titulo ?? '') }}"
                           placeholder="Ej: Centro de monitoreo urbano">
                    @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <input type="text" name="categoria" id="categoria" maxlength="100"
                           class="form-control @error('categoria') is-invalid @enderror"
                           value="{{ old('categoria', $imagen->categoria ?? '') }}"
                           placeholder="Ej: Videovigilancia">
                    @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Etiqueta chica sobre la imagen (opcional).</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" min="0" step="1"
                           class="form-control @error('orden') is-invalid @enderror"
                           value="{{ old('orden', $imagen->orden ?? 0) }}">
                    @error('orden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="form-group mb-0">
            <label for="imagen">Imagen (jpg, png, webp · máx. 8 MB)
                @empty($imagen) <span class="text-danger">*</span> @endempty
            </label>
            @if (isset($imagen) && $imagen->imagen)
                <div class="mb-2">
                    <img src="{{ route('web-galeria.imagen', ['ruta' => $imagen->imagen]) }}" alt=""
                         style="height:140px;border-radius:8px;object-fit:cover;">
                    <small class="d-block text-muted">Subí una nueva imagen solo si querés reemplazar la actual.</small>
                </div>
            @endif
            <input type="file" name="imagen" id="imagen" accept="image/*"
                   class="form-control-file @error('imagen') is-invalid @enderror">
            @error('imagen') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
