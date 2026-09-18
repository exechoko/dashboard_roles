<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="dominio">Dominio (patente) <span class="text-danger">*</span></label>
            <input type="text" name="dominio" id="dominio" class="form-control text-uppercase @error('dominio') is-invalid @enderror"
                   value="{{ old('dominio', $dominioAlerta->dominio ?? '') }}" maxlength="15" required>
            @error('dominio')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-check mt-2">
                <input type="hidden" name="parcial" value="0">
                <input type="checkbox" name="parcial" id="parcial" class="form-check-input" value="1"
                       {{ old('parcial', $dominioAlerta->parcial ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="parcial">Dominio parcial (patente incompleta)</label>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="marca">Marca</label>
            <input type="text" name="marca" id="marca" class="form-control @error('marca') is-invalid @enderror"
                   value="{{ old('marca', $dominioAlerta->marca ?? '') }}">
            @error('marca')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="modelo">Modelo</label>
            <input type="text" name="modelo" id="modelo" class="form-control @error('modelo') is-invalid @enderror"
                   value="{{ old('modelo', $dominioAlerta->modelo ?? '') }}">
            @error('modelo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="color">Color</label>
            <input type="text" name="color" id="color" class="form-control @error('color') is-invalid @enderror"
                   value="{{ old('color', $dominioAlerta->color ?? '') }}">
            @error('color')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="solicitado_por">Solicitado por</label>
            <input type="text" name="solicitado_por" id="solicitado_por" class="form-control @error('solicitado_por') is-invalid @enderror"
                   value="{{ old('solicitado_por', $dominioAlerta->solicitado_por ?? '') }}" placeholder="Nombre o cargo de quien solicitó la carga">
            @error('solicitado_por')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="funcionario_carga">Funcionario que carga</label>
            <input type="text" name="funcionario_carga" id="funcionario_carga" class="form-control @error('funcionario_carga') is-invalid @enderror"
                   value="{{ old('funcionario_carga', $dominioAlerta->funcionario_carga ?? '') }}">
            @error('funcionario_carga')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="fecha_hecho">Fecha del hecho</label>
            <input type="date" name="fecha_hecho" id="fecha_hecho" class="form-control @error('fecha_hecho') is-invalid @enderror"
                   value="{{ old('fecha_hecho', isset($dominioAlerta) && $dominioAlerta->fecha_hecho ? $dominioAlerta->fecha_hecho->format('Y-m-d') : '') }}"
                   max="{{ now()->format('Y-m-d') }}">
            @error('fecha_hecho')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="motivo">Hecho relacionado</label>
            <textarea name="motivo" id="motivo" class="form-control @error('motivo') is-invalid @enderror" rows="3"
                      placeholder="Ej: Robo calle Mitre 21/08/2022...">{{ old('motivo', $dominioAlerta->motivo ?? '') }}</textarea>
            @error('motivo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="camara_texto">Cámara / Detecciones LPR</label>
            <textarea name="camara_texto" id="camara_texto" class="form-control @error('camara_texto') is-invalid @enderror" rows="3"
                      placeholder="Cámara, ubicación y fechas donde fue detectado (si se conoce)">{{ old('camara_texto', $dominioAlerta->camara_texto ?? '') }}</textarea>
            @error('camara_texto')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label for="observaciones">Procedimiento / Observaciones</label>
            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3"
                      placeholder="Fecha, descripción, resolución, baja del sistema, etc.">{{ old('observaciones', $dominioAlerta->observaciones ?? '') }}</textarea>
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
            <small class="form-text text-muted">Este comentario quedará registrado en el historial.</small>
        </div>
    </div>
</div>
