<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="personal_id">Funcionario <span class="text-danger">*</span></label>
            <select name="personal_id" id="personal_id" class="form-control select2 @error('personal_id') is-invalid @enderror" required>
                <option value="">Seleccione un funcionario</option>
                @foreach ($personales as $personal)
                    <option value="{{ $personal->id }}" {{ old('personal_id', $retencion->personal_id ?? '') == $personal->id ? 'selected' : '' }}>
                        {{ $personal->nombre_completo }}
                    </option>
                @endforeach
            </select>
            @error('personal_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="numeracion_arma">Numeración del Arma <span class="text-danger">*</span></label>
            <input type="text" name="numeracion_arma" id="numeracion_arma" class="form-control @error('numeracion_arma') is-invalid @enderror"
                   value="{{ old('numeracion_arma', $retencion->numeracion_arma ?? '') }}" required>
            @error('numeracion_arma')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nro_chaleco">Número de Chaleco</label>
            <input type="text" name="nro_chaleco" id="nro_chaleco" class="form-control @error('nro_chaleco') is-invalid @enderror"
                   value="{{ old('nro_chaleco', $retencion->nro_chaleco ?? '') }}">
            @error('nro_chaleco')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="motivo_id">Motivo <span class="text-danger">*</span></label>
            <select name="motivo_id" id="motivo_id" class="form-control select2 @error('motivo_id') is-invalid @enderror" required>
                <option value="">Seleccione un motivo</option>
                @foreach ($motivos as $motivo)
                    <option value="{{ $motivo->id }}"
                            data-tipo="{{ $motivo->tipo_asignado }}"
                            data-dias="{{ $motivo->dias }}"
                            {{ old('motivo_id', $retencion->motivo_id ?? '') == $motivo->id ? 'selected' : '' }}>
                        {{ $motivo->nombre }} ({{ $motivo->tipo_asignado }} - {{ $motivo->dias }} días)
                    </option>
                @endforeach
            </select>
            @error('motivo_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="fecha_posesion">Fecha de Posesión <span class="text-danger">*</span></label>
            <input type="date" name="fecha_posesion" id="fecha_posesion" class="form-control @error('fecha_posesion') is-invalid @enderror"
                   value="{{ old('fecha_posesion', isset($retencion->fecha_posesion) ? $retencion->fecha_posesion->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('fecha_posesion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Tipo Asignado</label>
            <input type="text" id="tipo_asignado_display" class="form-control" readonly value="{{ old('tipo', $retencion->tipo ?? '') }}">
            <small class="form-text text-muted">Se asigna automáticamente según el motivo</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3">{{ old('observaciones', $retencion->observaciones ?? '') }}</textarea>
            @error('observaciones')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const motivoSelect = document.getElementById('motivo_id');
    const tipoDisplay = document.getElementById('tipo_asignado_display');

    function actualizarTipo() {
        const selectedOption = motivoSelect.options[motivoSelect.selectedIndex];
        const tipo = selectedOption.dataset.tipo || '';
        tipoDisplay.value = tipo;
    }

    motivoSelect.addEventListener('change', actualizarTipo);
    actualizarTipo();
});
</script>
@endpush
