<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="marca">Marca</label>
            <input type="text" name="marca" id="marca" class="form-control @error('marca') is-invalid @enderror"
                   value="{{ old('marca', $armeriaChaleco->marca ?? '') }}">
            @error('marca')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="modelo">Modelo / Protección</label>
            <input type="text" name="modelo" id="modelo" class="form-control @error('modelo') is-invalid @enderror"
                   value="{{ old('modelo', $armeriaChaleco->modelo ?? '') }}">
            @error('modelo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="talle">Talle</label>
            <select name="talle" id="talle" class="form-control @error('talle') is-invalid @enderror">
                <option value="">Sin especificar</option>
                @foreach (\App\Models\ArmeriaChaleco::TALLES as $talle)
                    <option value="{{ $talle }}" {{ old('talle', $armeriaChaleco->talle ?? '') == $talle ? 'selected' : '' }}>{{ $talle }}</option>
                @endforeach
            </select>
            @error('talle')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="movil">Móvil asignado</label>
            <input type="text" name="movil" id="movil" class="form-control @error('movil') is-invalid @enderror"
                   value="{{ old('movil', $armeriaChaleco->movil ?? '') }}" placeholder="Ej: 911-25">
            @error('movil')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="numero_serie">N° de Serie <span class="text-danger">*</span></label>
            <input type="text" name="numero_serie" id="numero_serie" class="form-control @error('numero_serie') is-invalid @enderror"
                   value="{{ old('numero_serie', $armeriaChaleco->numero_serie ?? '') }}" required>
            @error('numero_serie')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@unless (isset($armeriaChaleco))
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select name="estado" id="estado" class="form-control @error('estado') is-invalid @enderror">
                    <option value="EN_SERVICIO">En Servicio</option>
                    <option value="EN_REPARACION">En Reparación</option>
                    <option value="DE_BAJA">De Baja</option>
                </select>
                @error('estado')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="ubicacion">Ubicación inicial</label>
                <select name="ubicacion" id="ubicacion" class="form-control @error('ubicacion') is-invalid @enderror">
                    <option value="DIVISION_911">Armería División 911</option>
                    <option value="JEFATURA_CENTRAL">Armería Jefatura Central</option>
                </select>
                @error('ubicacion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="fecha">Fecha y hora de carga</label>
                <input type="datetime-local" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror"
                       value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                @error('fecha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Si el alta es atrasada, indique la fecha y hora reales en que ingresó a la armería.</small>
            </div>
        </div>
    </div>
@endunless

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3">{{ old('observaciones', $armeriaChaleco->observaciones ?? '') }}</textarea>
            @error('observaciones')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="comentario">Nota / Comentario (opcional)</label>
            <textarea name="comentario" id="comentario" class="form-control" rows="2" maxlength="500"
                      placeholder="Agregue una nota sobre esta carga o modificación...">{{ old('comentario') }}</textarea>
            <small class="form-text text-muted">Este comentario quedará registrado en el historial de movimientos.</small>
        </div>
    </div>
</div>
