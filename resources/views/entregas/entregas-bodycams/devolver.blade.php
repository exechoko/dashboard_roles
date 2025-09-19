@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-undo"></i> Devolución de Bodycams</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('entrega-bodycams.index') }}">Entregas</a></div>
                <div class="breadcrumb-item"><a href="{{ route('entrega-bodycams.show', $entrega->id) }}">Acta {{ $entrega->id }}</a></div>
                <div class="breadcrumb-item active">Devolución</div>
            </div>
        </div>

        <div class="section-body">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('entrega-bodycams.procesar-devolucion', $entrega->id) }}" method="POST" id="devolucionForm" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    {{-- Información de la Entrega Original --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Información de la Entrega</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label><strong>N° Acta:</strong></label>
                                    <p>{{ $entrega->id }}</p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Fecha de Entrega:</strong></label>
                                    <p>{{ $entrega->fecha_entrega->format('d/m/Y') }}</p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Dependencia:</strong></label>
                                    <p>{{ $entrega->dependencia }}</p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Personal Receptor:</strong></label>
                                    <p>{{ $entrega->personal_receptor }}</p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Motivo Operativo:</strong></label>
                                    <p>{{ $entrega->motivo_operativo }}</p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Total Bodycams:</strong></label>
                                    <p><span class="badge badge-info">{{ $entrega->bodycams->count() }}</span></p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Bodycams Pendientes:</strong></label>
                                    <p><span class="badge badge-warning">{{ $bodycamsPendientes->count() }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Datos de la Devolución --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-file-alt"></i> Datos de la Devolución</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_devolucion">Fecha de Devolución <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('fecha_devolucion') is-invalid @enderror"
                                                id="fecha_devolucion" name="fecha_devolucion"
                                                value="{{ old('fecha_devolucion', date('Y-m-d')) }}" required>
                                            @error('fecha_devolucion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hora_devolucion">Hora de Devolución <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_devolucion') is-invalid @enderror"
                                                id="hora_devolucion" name="hora_devolucion"
                                                value="{{ old('hora_devolucion', date('H:i')) }}" required>
                                            @error('hora_devolucion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="personal_devuelve">Personal que Devuelve</label>
                                            <input type="text" class="form-control @error('personal_devuelve') is-invalid @enderror"
                                                id="personal_devuelve" name="personal_devuelve"
                                                value="{{ old('personal_devuelve', $entrega->personal_receptor) }}"
                                                maxlength="255" placeholder="Nombre del personal que devuelve">
                                            @error('personal_devuelve')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="legajo_devuelve">Legajo</label>
                                            <input type="text" class="form-control @error('legajo_devuelve') is-invalid @enderror"
                                                id="legajo_devuelve" name="legajo_devuelve"
                                                value="{{ old('legajo_devuelve', $entrega->legajo_receptor) }}"
                                                maxlength="50" placeholder="Número de legajo">
                                            @error('legajo_devuelve')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones de la Devolución</label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                        id="observaciones" name="observaciones" rows="3"
                                        placeholder="Observaciones adicionales sobre la devolución">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Imágenes y archivos adjuntos --}}
                <div class="container col-xs-12 col-sm-12 col-md-12">
                    <div class="row">
                        <!-- Archivo adjunto -->
                        <div class="col-xs-12 col-sm-12 col-md-6">
                            <div class="form-group">
                                <label for="archivo">Archivo adjunto</label>
                                <input type="file" name="archivo" class="form-control"
                                    accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                            </div>
                        </div>
                    </div>

                    <!-- Sección de imágenes -->
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <h6><i class="fas fa-camera"></i> Imágenes (máximo 3)</h6>
                            <div id="imageContainer">
                                <!-- Los campos de imagen se generarán aquí -->
                            </div>
                            <button type="button" id="addImageBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Agregar imagen
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Selección de Bodycams a Devolver --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h4>Bodycams Pendientes de Devolución</h4>
                        <div class="card-header-action">
                            <span class="badge badge-warning" id="contadorSeleccionados">0 seleccionados</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="btn-group btn-group-sm mb-3">
                            <button type="button" class="btn btn-outline-primary" id="seleccionarTodos">
                                <i class="fas fa-check-square"></i> Seleccionar Todos
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="deseleccionarTodos">
                                <i class="fas fa-square"></i> Deseleccionar Todos
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
                                                <label class="custom-control-label" for="selectAllCheckbox"></label>
                                            </div>
                                        </th>
                                        <th>Código</th>
                                        <th>N° Serie</th>
                                        <th>N° Batería</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bodycamsPendientes as $bodycam)
                                        <tr>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input bodycam-checkbox"
                                                        id="bodycam_{{ $bodycam->id }}" name="bodycams_devolver[]"
                                                        value="{{ $bodycam->id }}"
                                                        {{ in_array($bodycam->id, old('bodycams_devolver', [])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="bodycam_{{ $bodycam->id }}"></label>
                                                </div>
                                            </td>
                                            <td>{{ $bodycam->codigo ?? 'N/A' }}</td>
                                            <td>{{ $bodycam->numero_serie ?? 'N/A' }}</td>
                                            <td>{{ $bodycam->numero_bateria ?? 'N/A' }}</td>
                                            <td>{{ $bodycam->marca ?? 'N/A' }}</td>
                                            <td>{{ $bodycam->modelo ?? 'N/A' }}</td>
                                            <td>
                                                @switch($bodycam->estado)
                                                    @case('disponible')
                                                        <span class="badge badge-success">Disponible</span>
                                                        @break
                                                    @case('entregada')
                                                        <span class="badge badge-warning">Entregada</span>
                                                        @break
                                                    @case('mantenimiento')
                                                        <span class="badge badge-info">Mantenimiento</span>
                                                        @break
                                                    @case('perdida')
                                                        <span class="badge badge-danger">Perdida</span>
                                                        @break
                                                    @default
                                                        <span class="badge badge-secondary">{{ $bodycam->estado }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('bodycams_devolver')
                            <div class="text-danger mt-2">
                                <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                            </div>
                        @enderror
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('entrega-bodycams.show', $entrega->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">Las bodycams seleccionadas cambiarán a estado "disponible"</small>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success" id="btnDevolver" disabled>
                                    <i class="fas fa-undo"></i> Registrar Devolución
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    //Carga de imagenes y archivo adjuntos
    let imageCount = 0;
    const maxImages = 3;

    // Función para agregar campo de imagen
    function addImageField() {
        if (imageCount >= maxImages) {
            alert('Máximo 3 imágenes permitidas');
            return;
        }

        imageCount++;
        const imageHtml = `
            <div class="image-upload-item" id="imageItem${imageCount}">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label for="imagen${imageCount}">Imagen ${imageCount}</label>
                        <input type="file" name="imagen${imageCount}" class="form-control image-input"
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            onchange="previewImage(this, ${imageCount})">
                        <small class="text-muted">JPG, PNG, GIF (máx. 2MB)</small>
                    </div>
                    <div class="col-md-4">
                        <div id="preview${imageCount}"></div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-image-btn" onclick="removeImageField(${imageCount})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#imageContainer').append(imageHtml);
        updateAddButton();
    }

    // Función para eliminar campo de imagen
    window.removeImageField = function(id) {
        $(`#imageItem${id}`).remove();
        imageCount--;
        updateAddButton();
    }

    // Función para previsualizar imagen
    window.previewImage = function(input, id) {
        const preview = $(`#preview${id}`);
        preview.empty();

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validar tamaño (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen es muy grande. Máximo 2MB permitido.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.html(`
                    <img src="${e.target.result}" class="image-preview" alt="Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                    <br><small class="text-success">✓ Imagen cargada</small>
                `);
            }
            reader.readAsDataURL(file);
        }
    }

    // Actualizar estado del botón agregar
    function updateAddButton() {
        const btn = $('#addImageBtn');
        if (imageCount >= maxImages) {
            btn.prop('disabled', true).html('<i class="fas fa-check"></i> Máximo alcanzado');
        } else {
            btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Agregar imagen');
        }
    }

    // Event listener para agregar imagen
    $('#addImageBtn').on('click', addImageField);

    // Agregar una imagen por defecto si no hay ninguna
    if ($('.image-upload-item').length === 0) {
        addImageField();
    }

    function actualizarContador() {
        const seleccionados = $('.bodycam-checkbox:checked').length;
        $('#contadorSeleccionados').text(`${seleccionados} seleccionados`);

        // Habilitar/deshabilitar botón
        $('#btnDevolver').prop('disabled', seleccionados === 0);

        // Actualizar checkbox maestro
        const total = $('.bodycam-checkbox').length;
        $('#selectAllCheckbox').prop('checked', seleccionados === total && seleccionados > 0);
        $('#selectAllCheckbox').prop('indeterminate', seleccionados > 0 && seleccionados < total);
    }

    // Event listeners para checkboxes
    $('.bodycam-checkbox').on('change', actualizarContador);

    // Checkbox maestro
    $('#selectAllCheckbox').on('change', function() {
        $('.bodycam-checkbox').prop('checked', this.checked);
        actualizarContador();
    });

    // Botones de selección masiva
    $('#seleccionarTodos').on('click', function() {
        $('.bodycam-checkbox').prop('checked', true);
        actualizarContador();
    });

    $('#deseleccionarTodos').on('click', function() {
        $('.bodycam-checkbox').prop('checked', false);
        actualizarContador();
    });

    // Validación del formulario
    $('#devolucionForm').on('submit', function(e) {
        const bodycamsSeleccionadas = $('.bodycam-checkbox:checked').length;

        if (bodycamsSeleccionadas === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una bodycam para devolver.');
            return false;
        }

        // Confirmar devolución
        if (!confirm(`¿Está seguro de registrar la devolución de ${bodycamsSeleccionadas} bodycam(s)?`)) {
            e.preventDefault();
            return false;
        }

        // Mostrar loading en el botón
        $('#btnDevolver').html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
    });

    // Inicializar contador
    actualizarContador();
});
</script>
@endpush
