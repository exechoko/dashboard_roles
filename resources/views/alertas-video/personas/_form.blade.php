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

        @if (!$persona)
            <div id="coincidencias-panel" class="alert alert-warning" style="display: none;">
                <strong><i class="fas fa-exclamation-triangle"></i> Posibles coincidencias encontradas.</strong>
                Revise si alguna de estas personas ya está cargada. Si es así, elíjala para editarla y completar sus datos
                en lugar de cargar un registro duplicado.
                <div id="coincidencias-lista" class="list-group mt-2"></div>
            </div>
        @endif

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
            <label for="solicitado_por">Solicitado por <span class="text-danger">*</span></label>
            <input type="text" name="solicitado_por" id="solicitado_por" class="form-control @error('solicitado_por') is-invalid @enderror"
                   value="{{ old('solicitado_por', $persona->solicitado_por ?? '') }}" placeholder="Nombre o cargo de quien solicitó la carga" required>
            @error('solicitado_por')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="funcionario_carga">Funcionario que carga <span class="text-danger">*</span></label>
            <input type="text" name="funcionario_carga" id="funcionario_carga" class="form-control @error('funcionario_carga') is-invalid @enderror"
                   value="{{ old('funcionario_carga', $persona->funcionario_carga ?? '') }}" required>
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
        <input type="hidden" name="activo" id="activo_hidden" value="{{ old('activo', ($persona->activo ?? true) ? 1 : 0) }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="notificar_a">Notificar / Avisar a <span class="text-danger">*</span></label>
            <input type="text" name="notificar_a" id="notificar_a" class="form-control @error('notificar_a') is-invalid @enderror"
                   value="{{ old('notificar_a', $persona->notificar_a ?? '') }}"
                   placeholder="Persona, área o contacto a avisar si el sistema detecta a esta persona" required>
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

@push('scripts')
<script>
$(document).ready(function () {
    var $identificado = $('#identificado');
    var $activoHidden = $('#activo_hidden');
    var $comentario = $('#comentario');
    var estabaIdentificado = $identificado.is(':checked');

    $identificado.on('change', function () {
        if (!$(this).is(':checked')) {
            estabaIdentificado = false;
            return;
        }

        if (estabaIdentificado) {
            return;
        }

        Swal.fire({
            title: 'Persona identificada',
            text: '¿Desea desactivar la búsqueda en el sistema?',
            icon: 'question',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Sí, mantener activa',
            denyButtonText: 'No, desactivar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                estabaIdentificado = true;
                $activoHidden.val(1);
            } else if (result.isDenied) {
                Swal.fire({
                    title: 'Detalle de la identificación',
                    text: 'Indique cómo se identificó a la persona (móvil que la identificó, si estaba sin novedad, etc.). Esta nota quedará en el historial.',
                    input: 'textarea',
                    inputPlaceholder: 'Ej: Identificada por móvil 12, sin novedad...',
                    inputAttributes: { maxlength: 500 },
                    showCancelButton: true,
                    confirmButtonText: 'Guardar y desactivar',
                    cancelButtonText: 'Volver',
                    inputValidator: function (value) {
                        if (!value || !value.trim()) {
                            return 'Debe indicar una nota.';
                        }
                    },
                }).then(function (notaResult) {
                    if (notaResult.isConfirmed) {
                        estabaIdentificado = true;
                        $activoHidden.val(0);
                        $comentario.val(notaResult.value.trim());
                    } else {
                        $identificado.prop('checked', false);
                    }
                });
            } else {
                $identificado.prop('checked', false);
            }
        });
    });
});
</script>
@endpush

@if (!$persona)
    @push('scripts')
    <script>
    $(document).ready(function () {
        var $panel = $('#coincidencias-panel');
        var $lista = $('#coincidencias-lista');
        var timeoutId = null;

        function buscarCoincidencias() {
            var dni = $('#dni').val().trim();
            var nombre = $('#apellido_nombre').val().trim();

            if (dni.length < 4 && nombre.length < 3) {
                $panel.hide();
                return;
            }

            $.get('{{ route('alertas-video.personas.buscar-coincidencias') }}', { dni: dni, nombre: nombre })
                .done(function (respuesta) {
                    var coincidencias = respuesta.coincidencias || [];

                    if (coincidencias.length === 0) {
                        $panel.hide();
                        return;
                    }

                    $lista.empty();
                    coincidencias.forEach(function (persona) {
                        var estadoBadge = persona.activo
                            ? '<span class="badge badge-alerta-activo">' + persona.estado_label + '</span>'
                            : '<span class="badge badge-alerta-inactivo">' + persona.estado_label + '</span>';

                        var $item = $(
                            '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                    '<strong>' + $('<div>').text(persona.apellido_nombre).html() + '</strong> ' + estadoBadge + '<br>' +
                                    '<small class="text-muted">' +
                                        (persona.dni ? 'D.N.I. ' + $('<div>').text(persona.dni).html() + ' &mdash; ' : '') +
                                        $('<div>').text(persona.motivo || 'Sin motivo cargado').html() +
                                    '</small>' +
                                '</div>' +
                                '<div>' +
                                    '<a href="' + persona.url_show + '" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver detalle">' +
                                        '<i class="fas fa-eye"></i>' +
                                    '</a> ' +
                                    '<a href="' + persona.url_edit + '" class="btn btn-sm btn-primary">' +
                                        '<i class="fas fa-edit"></i> Es esta, editar' +
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

        $('#dni, #apellido_nombre').on('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(buscarCoincidencias, 500);
        });
    });
    </script>
    @endpush
@endif
