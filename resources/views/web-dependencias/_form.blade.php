@php
    $telefonos = old('telefonos', isset($dependencia) ? implode(', ', $dependencia->telefonos ?? []) : '');
    $tags = old('tags', isset($dependencia) ? implode(', ', $dependencia->tags ?? []) : '');
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre" maxlength="150" required
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $dependencia->nombre ?? '') }}">
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" min="0" step="1"
                           class="form-control @error('orden') is-invalid @enderror"
                           value="{{ old('orden', $dependencia->orden ?? 0) }}">
                    @error('orden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Menor número = aparece primero.</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="categoria">Categoría <span class="text-danger">*</span></label>
            <select name="categoria" id="categoria" required class="form-control @error('categoria') is-invalid @enderror">
                <option value="">— Elegí una categoría —</option>
                @foreach ($categorias as $id => $titulo)
                    <option value="{{ $id }}" @selected(old('categoria', $dependencia->categoria ?? '') === $id)>{{ $titulo }}</option>
                @endforeach
            </select>
            @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" name="direccion" id="direccion" maxlength="200"
                   class="form-control @error('direccion') is-invalid @enderror"
                   value="{{ old('direccion', $dependencia->direccion ?? '') }}"
                   placeholder="Calle y número – Localidad (opcional)">
            @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="telefonos">Teléfonos</label>
            <input type="text" name="telefonos" id="telefonos" maxlength="300"
                   class="form-control @error('telefonos') is-invalid @enderror"
                   value="{{ $telefonos }}"
                   placeholder="Separados por coma. Ej: 4206224, 3434601942">
            @error('telefonos') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group mb-0">
            <label for="tags">Palabras clave (tags)</label>
            <input type="text" name="tags" id="tags" maxlength="300"
                   class="form-control @error('tags') is-invalid @enderror"
                   value="{{ $tags }}"
                   placeholder="Opcional, separadas por coma. Ej: centro, zona sur, guardia">
            <small class="text-muted">Ayudan a que la dependencia aparezca en la búsqueda de la web.</small>
            @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
