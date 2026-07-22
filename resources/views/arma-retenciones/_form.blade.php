<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="personal_id">Funcionario <span class="text-danger">*</span></label>
            <select name="personal_id" id="personal_id" class="form-control select2 @error('personal_id') is-invalid @enderror" required>
                <option value="">Seleccione un funcionario</option>
                @foreach ($personales as $personal)
                    <option value="{{ $personal->id }}" {{ old('personal_id', $retencion->personal_id ?? '') == $personal->id ? 'selected' : '' }}
                            data-arma="{{ $personal->armaAsignacionActual?->arma?->numero ?? ((isset($retencion) && $retencion->personal_id === $personal->id) ? $retencion->arma_numero : $personal->numeracion_arma) }}"
                            data-tipo="{{ $personal->armaAsignacionActual?->arma?->tipo?->nombre ?? ((isset($retencion) && $retencion->personal_id === $personal->id) ? $retencion->arma_tipo : $personal->tipoArma?->nombre) }}"
                            data-chaleco="{{ $personal->chalecoAsignacionActual?->chaleco?->numero_serie ?? ((isset($retencion) && $retencion->personal_id === $personal->id) ? $retencion->chaleco_numero : $personal->nro_chaleco) ?? '' }}"
                            data-chaleco-detalle="{{ $personal->chalecoAsignacionActual ? collect([$personal->chalecoAsignacionActual?->chaleco?->marca, $personal->chalecoAsignacionActual?->chaleco?->modelo, $personal->chalecoAsignacionActual?->chaleco?->talle ? 'Talle '.$personal->chalecoAsignacionActual->chaleco->talle : null])->filter()->implode(' - ') : ((isset($retencion) && $retencion->personal_id === $personal->id) ? $retencion->chaleco_detalle : '') }}"
                            data-dni="{{ $personal->dni ?? '' }}">
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
            <label>Arma Asignada</label>
            <div id="arma_asignada_display" class="form-control bg-light" style="cursor: default;">
                <span class="text-muted">Seleccione un funcionario</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
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
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Tipo Asignado</label>
            <input type="text" id="tipo_asignado_display" class="form-control" readonly value="{{ old('tipo', $retencion->tipo ?? '') }}">
            <small class="form-text text-muted">Se asigna automáticamente según el motivo</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3">{{ old('observaciones', $retencion->observaciones ?? '') }}</textarea>
            @error('observaciones')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<hr>
<h5 class="text-primary"><i class="fas fa-file-word"></i> Datos para el Acta de Retención</h5>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="ciudad">Ciudad</label>
            <select name="ciudad" id="ciudad" class="form-control select2 @error('ciudad') is-invalid @enderror">
                <option value="">Seleccione una ciudad</option>
                @foreach (\App\Models\ArmaRetencion::CIUDADES as $ciudad)
                    <option value="{{ $ciudad }}" {{ old('ciudad', $retencion->ciudad ?? '') == $ciudad ? 'selected' : '' }}>
                        {{ $ciudad }}
                    </option>
                @endforeach
            </select>
            @error('ciudad')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="hora_posesion">Hora</label>
            <input type="time" name="hora_posesion" id="hora_posesion" class="form-control @error('hora_posesion') is-invalid @enderror"
                   value="{{ old('hora_posesion', isset($retencion) && $retencion->hora_posesion ? \Carbon\Carbon::parse($retencion->hora_posesion)->format('H:i') : '') }}">
            @error('hora_posesion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="marca_modelo">Marca / Modelo del Arma</label>
            <input type="text" name="marca_modelo" id="marca_modelo" class="form-control @error('marca_modelo') is-invalid @enderror"
                   value="{{ old('marca_modelo', $retencion->marca_modelo ?? '') }}">
            @error('marca_modelo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="dni_display">DNI del Funcionario</label>
            <input type="text" id="dni_display" class="form-control bg-light" readonly value="">
            <small class="form-text text-muted">Se completa según el funcionario seleccionado.</small>
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-group">
            <label for="estado_conservacion">Estado de Conservación</label>
            <select name="estado_conservacion" id="estado_conservacion" class="form-control @error('estado_conservacion') is-invalid @enderror">
                <option value="">Seleccione un estado</option>
                @foreach (\App\Models\ArmaRetencion::ESTADOS_CONSERVACION as $estado)
                    <option value="{{ $estado }}" {{ old('estado_conservacion', $retencion->estado_conservacion ?? '') == $estado ? 'selected' : '' }}>
                        {{ ucfirst($estado) }}
                    </option>
                @endforeach
            </select>
            @error('estado_conservacion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>&nbsp;</label>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="con_cargador" name="con_cargador" value="1"
                       {{ old('con_cargador', $retencion->con_cargador ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="con_cargador">Con cargador</label>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>&nbsp;</label>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="con_cartucheria" name="con_cartucheria" value="1"
                       {{ old('con_cartucheria', $retencion->con_cartucheria ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="con_cartucheria">Con cartuchería</label>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="comentario">Nota / Comentario (opcional)</label>
            <textarea name="comentario" id="comentario" class="form-control" rows="2" maxlength="500"
                      placeholder="Agregue una nota o comentario sobre esta retención...">{{ old('comentario') }}</textarea>
            <small class="form-text text-muted">Este comentario quedará registrado en el historial de la retención.</small>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    // Forzar el foco en el campo de búsqueda cuando se abre el Select2
    $(document).on('select2:open', () => {
        setTimeout(() => {
            let select2Field = document.querySelector('.select2-container--open .select2-search__field');
            if (select2Field) {
                select2Field.focus();
            }
        }, 0);
    });

    function actualizarArma() {
        var selectedOption = $('#personal_id').find('option:selected');
        var arma = selectedOption.data('arma') || 'Sin asignar';
        var tipo = selectedOption.data('tipo') || '';
        var chaleco = selectedOption.data('chaleco') || '';
        var chalecoDetalle = selectedOption.data('chaleco-detalle') || '';

        if (selectedOption.val()) {
            var html = '<strong>N°:</strong> ' + arma;
            if (tipo) html += ' | <strong>Tipo:</strong> ' + tipo;
            if (chaleco) html += ' | <strong>Chaleco:</strong> ' + chaleco + (chalecoDetalle ? ' (' + chalecoDetalle + ')' : '');
            $('#arma_asignada_display').html(html);
        } else {
            $('#arma_asignada_display').html('<span class="text-muted">Seleccione un funcionario</span>');
        }
    }

    function actualizarDni() {
        var selectedOption = $('#personal_id').find('option:selected');
        var dni = selectedOption.data('dni') || '';

        if (selectedOption.val()) {
            $('#dni_display').val(dni ? dni : 'No registrado');
        } else {
            $('#dni_display').val('');
        }
    }

    function actualizarMarcaModelo(forzar) {
        var selectedOption = $('#personal_id').find('option:selected');
        var tipo = selectedOption.data('tipo') || '';
        var campo = $('#marca_modelo');

        if (tipo && (forzar || campo.val().trim() === '')) {
            campo.val(tipo);
        }
    }

    function actualizarTipo() {
        var selectedOption = $('#motivo_id').find('option:selected');
        var tipo = selectedOption.data('tipo') || '';
        $('#tipo_asignado_display').val(tipo);
    }

    $('#personal_id').on('change', function () {
        actualizarArma();
        actualizarDni();
        actualizarMarcaModelo(true);
    });
    $('#motivo_id').on('change', actualizarTipo);

    actualizarArma();
    actualizarDni();
    actualizarMarcaModelo(false);
    actualizarTipo();
});
</script>
@endpush
