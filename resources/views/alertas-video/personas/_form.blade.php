@php $persona = $personaAlerta ?? null; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="dni">D.N.I.</label>
                    <input type="text" name="dni" id="dni" class="form-control @error('dni') is-invalid @enderror"
                           value="{{ old('dni', $persona->dni ?? '') }}" maxlength="20">
                    @error('dni')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label for="apellido_nombre">Apellido y Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="apellido_nombre" id="apellido_nombre" class="form-control @error('apellido_nombre') is-invalid @enderror"
                           value="{{ old('apellido_nombre', $persona->apellido_nombre ?? '') }}" required>
                    @error('apellido_nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion" class="form-control @error('direccion') is-invalid @enderror"
                           value="{{ old('direccion', $persona->direccion ?? '') }}">
                    @error('direccion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fecha_hecho">Fecha del hecho</label>
                    <input type="date" name="fecha_hecho" id="fecha_hecho" class="form-control @error('fecha_hecho') is-invalid @enderror"
                           value="{{ old('fecha_hecho', $persona && $persona->fecha_hecho ? $persona->fecha_hecho->format('Y-m-d') : '') }}"
                           max="{{ now()->format('Y-m-d') }}">
                    @error('fecha_hecho')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Foto del rostro</label>
            <div class="d-flex align-items-start">
                @if ($persona && $persona->foto_url)
                    <img src="{{ $persona->foto_url }}" alt="Foto actual" class="alerta-foto-preview mr-3">
                @endif
                <div class="flex-grow-1">
                    <input type="file" name="foto" id="foto" class="form-control-file @error('foto') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('foto')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">JPG, PNG o WEBP. Máximo 4 MB.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="solicitado_por">Solicitado por</label>
            <input type="text" name="solicitado_por" id="solicitado_por" class="form-control @error('solicitado_por') is-invalid @enderror"
                   value="{{ old('solicitado_por', $persona->solicitado_por ?? '') }}" placeholder="Nombre o cargo de quien solicitó la carga">
            @error('solicitado_por')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="funcionario_carga">Funcionario que carga</label>
            <input type="text" name="funcionario_carga" id="funcionario_carga" class="form-control @error('funcionario_carga') is-invalid @enderror"
                   value="{{ old('funcionario_carga', $persona->funcionario_carga ?? '') }}">
            @error('funcionario_carga')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="d-block">&nbsp;</label>
        <div class="form-check form-check-inline mt-2">
            <input type="hidden" name="identificado" value="0">
            <input type="checkbox" name="identificado" id="identificado" class="form-check-input" value="1"
                   {{ old('identificado', $persona->identificado ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="identificado">Identificado</label>
        </div>
        <div class="form-check form-check-inline mt-2">
            <input type="hidden" name="finalizado" value="0">
            <input type="checkbox" name="finalizado" id="finalizado" class="form-check-input" value="1"
                   {{ old('finalizado', $persona->finalizado ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="finalizado">Finalizado</label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="notificar_a">Notificar / Avisar a</label>
            <input type="text" name="notificar_a" id="notificar_a" class="form-control @error('notificar_a') is-invalid @enderror"
                   value="{{ old('notificar_a', $persona->notificar_a ?? '') }}"
                   placeholder="Persona, área o contacto a avisar si el sistema detecta a esta persona">
            @error('notificar_a')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="motivo">Hecho relacionado</label>
            <textarea name="motivo" id="motivo" class="form-control @error('motivo') is-invalid @enderror" rows="3"
                      placeholder="Ej: Solicitud de localización y restitución al hogar...">{{ old('motivo', $persona->motivo ?? '') }}</textarea>
            @error('motivo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3">{{ old('observaciones', $persona->observaciones ?? '') }}</textarea>
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
