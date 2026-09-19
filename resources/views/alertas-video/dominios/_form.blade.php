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
    @if (!isset($dominioAlerta))
        <div class="col-md-12">
            <div id="coincidencias-panel" class="alert alert-warning" style="display: none;">
                <strong><i class="fas fa-exclamation-triangle"></i> Posibles coincidencias encontradas.</strong>
                Revise si alguno de estos dominios ya está cargado. Si es así, elíjalo para editarlo y completar sus
                datos en lugar de cargar un registro duplicado.
                <div id="coincidencias-lista" class="list-group mt-2"></div>
            </div>
        </div>
    @endif
    <div class="col-md-3">
        <div class="form-group">
            <label for="marca">Marca <span class="text-danger">*</span></label>
            <input type="text" name="marca" id="marca" class="form-control @error('marca') is-invalid @enderror"
                   value="{{ old('marca', $dominioAlerta->marca ?? '') }}" required>
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
        @php $colorActual = old('color', $dominioAlerta->color ?? ''); @endphp
        <div class="form-group">
            <label for="color">Color</label>
            <select name="color" id="color" class="form-control select2 @error('color') is-invalid @enderror"
                    data-placeholder="Seleccione o escriba un color">
                <option value=""></option>
                @if ($colorActual !== '' && !in_array($colorActual, \App\Models\DominioAlerta::COLORES))
                    <option value="{{ $colorActual }}" selected>{{ $colorActual }}</option>
                @endif
                @foreach (\App\Models\DominioAlerta::COLORES as $color)
                    <option value="{{ $color }}" {{ $colorActual === $color ? 'selected' : '' }}>{{ $color }}</option>
                @endforeach
            </select>
            @error('color')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="solicitado_por">Solicitado por <span class="text-danger">*</span></label>
            <input type="text" name="solicitado_por" id="solicitado_por" class="form-control @error('solicitado_por') is-invalid @enderror"
                   value="{{ old('solicitado_por', $dominioAlerta->solicitado_por ?? '') }}" placeholder="Nombre o cargo de quien solicitó la carga" required>
            @error('solicitado_por')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="funcionario_carga">Funcionario que carga <span class="text-danger">*</span></label>
            <input type="text" name="funcionario_carga" id="funcionario_carga" class="form-control @error('funcionario_carga') is-invalid @enderror"
                   value="{{ old('funcionario_carga', $dominioAlerta->funcionario_carga ?? '') }}" required>
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
            <label for="notificar_a">Notificar / Avisar a <span class="text-danger">*</span></label>
            <input type="text" name="notificar_a" id="notificar_a" class="form-control @error('notificar_a') is-invalid @enderror"
                   value="{{ old('notificar_a', $dominioAlerta->notificar_a ?? '') }}"
                   placeholder="Persona, área o contacto a avisar si el sistema detecta el dominio" required>
            @error('notificar_a')
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
        @php $camaraTextoActual = old('camara_texto', $dominioAlerta->camara_texto ?? ''); @endphp
        <div class="form-group">
            <label for="camara_texto_select">Cámara / Detecciones LPR</label>
            <select id="camara_texto_select" multiple class="form-control select2 @error('camara_texto') is-invalid @enderror"
                    data-placeholder="Seleccione una o más cámaras, o escriba el detalle">
                @if ($camaraTextoActual !== '')
                    <option value="{{ $camaraTextoActual }}" selected>{{ $camaraTextoActual }}</option>
                @endif
                @foreach ($camaras ?? [] as $nombreCamara)
                    @if ($nombreCamara !== $camaraTextoActual)
                        <option value="{{ $nombreCamara }}">{{ $nombreCamara }}</option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" name="camara_texto" id="camara_texto" value="{{ $camaraTextoActual }}">
            <small class="form-text text-muted">Elija una o más cámaras de la lista, o escriba texto libre (fecha, ubicación, detalle) y presione Enter.</small>
            @error('camara_texto')
                <div class="invalid-feedback d-block">{{ $message }}</div>
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

@push('scripts')
<script>
$(document).ready(function () {
    $('#color').select2({
        width: '100%',
        placeholder: 'Seleccione o escriba un color',
        allowClear: true,
        tags: true
    });

    var $camaraSelect = $('#camara_texto_select');
    var $camaraHidden = $('#camara_texto');

    $camaraSelect.select2({
        width: '100%',
        placeholder: 'Seleccione una o más cámaras, o escriba el detalle',
        tags: true
    });

    $camaraSelect.on('change', function () {
        $camaraHidden.val(($(this).val() || []).join(', '));
    });

    $(document).on('select2:open', () => {
        setTimeout(() => {
            let select2Field = document.querySelector('.select2-container--open .select2-search__field');
            if (select2Field) {
                select2Field.focus();
            }
        }, 0);
    });
});
</script>
@endpush

@if (!isset($dominioAlerta))
    @push('scripts')
    <script>
    $(document).ready(function () {
        var $panel = $('#coincidencias-panel');
        var $lista = $('#coincidencias-lista');
        var timeoutId = null;

        function buscarCoincidencias() {
            var dominio = $('#dominio').val().trim();

            if (dominio.length < 3) {
                $panel.hide();
                return;
            }

            $.get('{{ route('alertas-video.dominios.buscar-coincidencias') }}', { dominio: dominio })
                .done(function (respuesta) {
                    var coincidencias = respuesta.coincidencias || [];

                    if (coincidencias.length === 0) {
                        $panel.hide();
                        return;
                    }

                    $lista.empty();
                    coincidencias.forEach(function (item) {
                        var estadoBadge = item.activo
                            ? '<span class="badge badge-alerta-activo">' + item.estado_label + '</span>'
                            : '<span class="badge badge-alerta-inactivo">' + item.estado_label + '</span>';
                        var parcialBadge = item.parcial ? ' <span class="badge badge-alerta-no">Parcial</span>' : '';

                        var $item = $(
                            '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                    '<strong>' + $('<div>').text(item.dominio).html() + '</strong>' + parcialBadge + ' ' + estadoBadge + '<br>' +
                                    '<small class="text-muted">' +
                                        $('<div>').text((item.marca || '') + ' ' + (item.modelo || '')).html() +
                                        ' &mdash; ' + $('<div>').text(item.motivo || 'Sin motivo cargado').html() +
                                    '</small>' +
                                '</div>' +
                                '<div>' +
                                    '<a href="' + item.url_show + '" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver detalle">' +
                                        '<i class="fas fa-eye"></i>' +
                                    '</a> ' +
                                    '<a href="' + item.url_edit + '" class="btn btn-sm btn-primary">' +
                                        '<i class="fas fa-edit"></i> Es este, editar' +
                                    '</a>' +
                                '</div>' +
                            '</div>'
                        );
                        $lista.append($item);
                    });

                    $panel.show();
                })
                .fail(function () {
                    $panel.hide();
                });
        }

        $('#dominio').on('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(buscarCoincidencias, 500);
        });
    });
    </script>
    @endpush
@endif
