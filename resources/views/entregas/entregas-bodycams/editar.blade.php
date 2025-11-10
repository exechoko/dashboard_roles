@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-edit"></i> Editar Entrega de Bodycams - Acta N° {{ $entrega->id }}</h1>
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

            @if ($bodycamsDisponibles->isEmpty())
                <div class="alert alert-warning" role="alert">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>No hay bodycams disponibles</h4>
                        <p>Actualmente no hay bodycams disponibles para agregar a esta entrega.</p>
                        <a href="{{ route('entrega-bodycams.show', $entrega->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Detalle
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('entrega-bodycams.update', $entrega->id) }}" method="POST" id="editarEntregaForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Información de la Entrega --}}
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-file-alt"></i> Información de la Entrega</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror"
                                                    id="fecha_entrega" name="fecha_entrega"
                                                    value="{{ old('fecha_entrega', $entrega->fecha_entrega->format('Y-m-d')) }}" required>
                                                @error('fecha_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hora_entrega">Hora de Entrega <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control @error('hora_entrega') is-invalid @enderror"
                                                    id="hora_entrega" name="hora_entrega"
                                                    value="{{ old('hora_entrega', $entrega->hora_entrega) }}" required>
                                                @error('hora_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="dependencia">Dependencia <span class="text-danger">*</span></label>
                                                <select class="form-control select2 @error('dependencia') is-invalid @enderror" id="dependencia" name="dependencia" required>
                                                    <option value="">Seleccione una dependencia</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->nombre }}" {{ old('dependencia', $entrega->dependencia) == $destino->nombre ? 'selected' : '' }}>
                                                            {{ $destino->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('dependencia')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="personal_receptor">Personal Receptor <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('personal_receptor') is-invalid @enderror"
                                                    id="personal_receptor" name="personal_receptor"
                                                    value="{{ old('personal_receptor', $entrega->personal_receptor) }}"
                                                    maxlength="255" required placeholder="Nombre completo del receptor">
                                                @error('personal_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="legajo_receptor">Legajo Receptor</label>
                                                <input type="text" class="form-control @error('legajo_receptor') is-invalid @enderror"
                                                    id="legajo_receptor" name="legajo_receptor"
                                                    value="{{ old('legajo_receptor', $entrega->legajo_receptor) }}"
                                                    maxlength="50" placeholder="Número de legajo (opcional)">
                                                @error('legajo_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="personal_entrega">Personal que entregó <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('personal_entrega') is-invalid @enderror"
                                                    id="personal_entrega" name="personal_entrega"
                                                    value="{{ old('personal_entrega', $entrega->personal_entrega) }}"
                                                    maxlength="255" required placeholder="Nombre del personal que entregó">
                                                @error('personal_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="legajo_entrega">Legajo que entregó</label>
                                                <input type="text" class="form-control @error('legajo_entrega') is-invalid @enderror"
                                                    id="legajo_entrega" name="legajo_entrega"
                                                    value="{{ old('legajo_entrega', $entrega->legajo_entrega) }}"
                                                    maxlength="50" placeholder="Número de legajo (opcional)">
                                                @error('legajo_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="motivo_operativo">Motivo Operativo <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('motivo_operativo') is-invalid @enderror" id="motivo_operativo"
                                            name="motivo_operativo" rows="3" required placeholder="Describa el motivo de la entrega de bodycams">{{ old('motivo_operativo', $entrega->motivo_operativo) }}</textarea>
                                        @error('motivo_operativo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones"
                                            rows="3" placeholder="Observaciones adicionales (opcional)">{{ old('observaciones', $entrega->observaciones) }}</textarea>
                                        @error('observaciones')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selección de Bodycams --}}
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-video"></i> Bodycams Disponibles</h4>
                                    <div class="card-header-action">
                                        <span class="badge badge-success" id="totalDisponibles">{{ $bodycamsDisponibles->count() }} disponibles</span>
                                        <span class="badge badge-info" id="contadorSeleccionados">0 seleccionados</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label><i class="fas fa-search"></i> Buscar bodycams:</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control" id="buscarBodycam"
                                                placeholder="Código, Serie, ID o cualquier texto...">
                                        </div>
                                    </div>

                                    <div class="contador-seleccionados">
                                        <span id="resumenSeleccion">Selecciona bodycams para continuar</span>
                                    </div>

                                    <div style="max-height: 400px; overflow-y: auto;" id="listaBodycams">
                                        @foreach ($bodycamsDisponibles as $bodycam)
                                            @php
                                                $isEntregada = $entrega->bodycams->contains('id', $bodycam->id);
                                                $bodycamsSeleccionadas = old('bodycams_seleccionadas', $entrega->bodycams->pluck('id')->toArray());
                                            @endphp
                                            <div class="bodycam-item"
                                                data-id="{{ $bodycam->id }}"
                                                data-codigo="{{ $bodycam->codigo ?? '' }}"
                                                data-serie="{{ $bodycam->numero_serie ?? '' }}"
                                                data-marca="{{ $bodycam->marca ?? '' }}"
                                                data-modelo="{{ $bodycam->modelo ?? '' }}">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="bodycam_{{ $bodycam->id }}" name="bodycams_seleccionadas[]"
                                                        value="{{ $bodycam->id }}"
                                                        {{ in_array($bodycam->id, $bodycamsSeleccionadas) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="bodycam_{{ $bodycam->id }}">
                                                        <div class="bodycam-info">
                                                            <div><strong>Código:</strong> {{ $bodycam->codigo ?? 'N/A' }}</div>
                                                            <div><strong>Serie:</strong> {{ $bodycam->numero_serie ?? 'N/A' }}</div>
                                                            <div><strong>Marca:</strong> {{ $bodycam->marca ?? 'N/A' }}</div>
                                                            <div><strong>Modelo:</strong> {{ $bodycam->modelo ?? 'N/A' }}</div>
                                                            @if ($bodycam->numero_tarjeta_sd)
                                                                <div><strong>Tarjeta SD:</strong> {{ $bodycam->numero_tarjeta_sd }}</div>
                                                            @endif
                                                            <div class="mt-1">
                                                                @if($isEntregada)
                                                                    <span class="badge badge-warning badge-sm">En esta entrega</span>
                                                                @else
                                                                    <span class="badge badge-success badge-sm">Disponible</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div id="noBodycamsFound" class="no-bodycams-found" style="display: none;">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No se encontraron bodycams</h5>
                                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                                    </div>

                                    @error('bodycams_seleccionadas')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                    @error('bodycams_seleccionadas.*')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Imágenes y archivos adjuntos --}}
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-paperclip"></i> Archivos Adjuntos</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Archivo adjunto -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="archivo">Archivo adjunto</label>
                                                <input type="file" name="archivo" class="form-control"
                                                    accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                                <small class="text-muted">PDF, DOC, DOCX, XLSX, ZIP, RAR (máx. 2MB)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección de imágenes -->
                                    <div class="row">
                                        <div class="col-12">
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
                            </div>
                        </div>
                    </div>

                    {{-- Bodycams Seleccionadas --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4><i class="fas fa-list-check"></i> Bodycams Seleccionadas</h4>
                        </div>
                        <div class="card-body" id="bodycamsSeleccionadasContainer">
                            <p class="text-muted">Aún no hay bodycams seleccionadas.</p>
                            <ul class="list-group" id="bodycamsSeleccionadasList"></ul>
                        </div>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('entrega-bodycams.show', $entrega->id) }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted">Los cambios en bodycams modificarán automáticamente sus estados</small>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-warning" id="btnActualizar" disabled>
                                                <i class="fas fa-save"></i> Actualizar Entrega
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Variables para tracking
            let bodycamsDisponibles = [];
            let bodycamsFiltradas = [];
            let imageCount = 0;
            const maxImages = 3;

            // Inicializar datos de bodycams desde el DOM
            function inicializarBodycams() {
                bodycamsDisponibles = [];
                $('.bodycam-item').each(function() {
                    const $item = $(this);
                    const bodycamData = {
                        id: $item.data('id'),
                        codigo: $item.data('codigo'),
                        serie: $item.data('serie'),
                        marca: $item.data('marca'),
                        modelo: $item.data('modelo'),
                        element: $item
                    };
                    bodycamsDisponibles.push(bodycamData);
                    $item.show();
                });
                bodycamsFiltradas = [...bodycamsDisponibles];
            }

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
                                    accept="image/*;capture=camera"
                                    onchange="previewImage(this, ${imageCount})">
                                <small class="text-muted">JPG, PNG, GIF (máx. 2MB)</small>
                            </div>
                            <div class="col-md-4">
                                <div id="preview${imageCount}"></div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="remove-image-btn" onclick="removeImageField(${imageCount})">
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

                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen es muy grande. Máximo 2MB permitido.');
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html(`
                            <img src="${e.target.result}" class="image-preview" alt="Preview">
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

            // Función para actualizar contador
            function actualizarContador() {
                const seleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked').length;
                const total = bodycamsDisponibles.length;

                $('#contadorSeleccionados').text(`${seleccionadas} seleccionados`);
                $('#totalDisponibles').text(`${total} disponibles`);

                if (seleccionadas === 0) {
                    $('#resumenSeleccion').text('Selecciona bodycams para continuar');
                } else {
                    $('#resumenSeleccion').html(`
                        <i class="fas fa-check-circle text-success"></i>
                        ${seleccionadas} bodycam${seleccionadas !== 1 ? 's' : ''} seleccionada${seleccionadas !== 1 ? 's' : ''}
                    `);
                }

                validarFormulario();
            }

            // Función para filtrar bodycams
            function filtrarBodycams(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let bodycamsEncontradas = 0;

                bodycamsFiltradas = [];

                $('.bodycam-item').each(function() {
                    const $item = $(this);
                    const codigo = ($item.data('codigo') || '').toString().toLowerCase();
                    const serie = ($item.data('serie') || '').toString().toLowerCase();
                    const marca = ($item.data('marca') || '').toString().toLowerCase();
                    const modelo = ($item.data('modelo') || '').toString().toLowerCase();

                    const coincide = term === '' ||
                        codigo.includes(term) ||
                        serie.includes(term) ||
                        marca.includes(term) ||
                        modelo.includes(term);

                    if (coincide) {
                        $item.show();
                        bodycamsEncontradas++;
                        bodycamsFiltradas.push({
                            id: $item.data('id'),
                            element: $item
                        });
                    } else {
                        $item.hide();
                    }
                });

                if (bodycamsEncontradas === 0 && term !== '') {
                    $('#noBodycamsFound').show();
                } else {
                    $('#noBodycamsFound').hide();
                }
            }

            // Función para validar formulario
            function validarFormulario() {
                const dependencia = $('#dependencia').val().trim();
                const personalReceptor = $('#personal_receptor').val().trim();
                const personalEntrega = $('#personal_entrega').val().trim();
                const motivoOperativo = $('#motivo_operativo').val().trim();
                const bodycamsSeleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked').length;

                const isValid = dependencia && personalReceptor && personalEntrega && motivoOperativo && bodycamsSeleccionadas > 0;
                $('#btnActualizar').prop('disabled', !isValid);
            }

            // Función para renderizar bodycams seleccionadas
            function renderBodycamsSeleccionadas() {
                const lista = $('#bodycamsSeleccionadasList');
                lista.empty();

                const seleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked');

                if (seleccionadas.length === 0) {
                    $('#bodycamsSeleccionadasContainer p').show();
                    return;
                }

                $('#bodycamsSeleccionadasContainer p').hide();

                seleccionadas.each(function() {
                    const $checkbox = $(this);
                    const bodycamItem = $checkbox.closest('.bodycam-item');

                    const codigo = bodycamItem.data('codigo') || 'Código N/A';
                    const serie = bodycamItem.data('serie') || 'Serie N/A';
                    const marca = bodycamItem.data('marca') || 'N/A';
                    const modelo = bodycamItem.data('modelo') || 'N/A';

                    const li = $(`
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span><strong>${codigo}</strong> - ${serie}</span>
                                <div><small><strong>Marca:</strong> ${marca} | <strong>Modelo:</strong> ${modelo}</small></div>
                            </div>
                            <button class="btn btn-sm btn-danger quitar-bodycam" data-id="${$checkbox.val()}">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                    `);
                    lista.append(li);
                });
            }

            // Event listeners
            $(document).on('change', 'input[name="bodycams_seleccionadas[]"]', function() {
                const $bodycamItem = $(this).closest('.bodycam-item');
                if ($(this).is(':checked')) {
                    $bodycamItem.addClass('selected');
                } else {
                    $bodycamItem.removeClass('selected');
                }
                actualizarContador();
                renderBodycamsSeleccionadas();
            });

            // Click en bodycam-item para seleccionar
            $(document).on('click', '.bodycam-item', function(e) {
                if (e.target.type !== 'checkbox' && !$(e.target).is('label')) {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Quitar bodycam de la lista de seleccionadas
            $(document).on('click', '.quitar-bodycam', function() {
                const id = $(this).data('id');
                const checkbox = $(`#bodycam_${id}`);
                checkbox.prop('checked', false).trigger('change');
            });

            // Funcionalidad de búsqueda
            $('#buscarBodycam').on('input', function() {
                const searchTerm = $(this).val();
                filtrarBodycams(searchTerm);
            });

            // Validación en tiempo real
            $('#dependencia, #personal_receptor, #personal_entrega, #motivo_operativo').on('input', function() {
                validarFormulario();
            });

            // Validación del formulario antes de enviar
            $('#editarEntregaForm').on('submit', function(e) {
                const bodycamsSeleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked').length;

                if (bodycamsSeleccionadas === 0) {
                    e.preventDefault();
                    alert('Debe seleccionar al menos una bodycam para la entrega.');
                    return false;
                }

                if (!confirm(`¿Está seguro de actualizar esta entrega con ${bodycamsSeleccionadas} bodycam(s)? Se modificarán los estados de las bodycams según corresponde.`)) {
                    e.preventDefault();
                    return false;
                }

                $('#btnActualizar').html('<i class="fas fa-spinner fa-spin"></i> Actualizando...').prop('disabled', true);
            });

            // Inicializar
            inicializarBodycams();
            actualizarContador();
            renderBodycamsSeleccionadas();

            // Marcar bodycams como seleccionadas si están checked
            $('input[name="bodycams_seleccionadas[]"]:checked').each(function() {
                $(this).closest('.bodycam-item').addClass('selected');
            });

            // Scroll suave a la sección de bodycams si hay errores
            @if ($errors->has('bodycams_seleccionadas') || $errors->has('bodycams_seleccionadas.*'))
                $('html, body').animate({
                    scrollTop: $('#listaBodycams').offset().top - 100
                }, 500);
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .bodycam-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .bodycam-item:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .bodycam-item.selected {
            background-color: #e3f2fd;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }

        .bodycam-info {
            font-size: 13px;
            line-height: 1.4;
        }

        .bodycam-info strong {
            color: #495057;
            font-weight: 600;
        }

        .badge-sm {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .custom-control-label {
            cursor: pointer;
            width: 100%;
            padding-left: 5px;
        }

        .custom-control-input:checked~.custom-control-label::before {
            background-color: #007bff;
            border-color: #007bff;
        }

        #listaBodycams {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
        }

        .card-header h4 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border: none;
            color: #212529;
        }

        .btn-warning:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 12px 15px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .contador-seleccionados {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
            color: #495057;
        }

        .no-bodycams-found {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }

        .btn-group-sm .btn {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Scrollbar personalizada */
        #listaBodycams::-webkit-scrollbar {
            width: 6px;
        }

        #listaBodycams::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #listaBodycams::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        #listaBodycams::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .section-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .section-header h1 {
            color: #495057;
            margin-bottom: 10px;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: #495057;
        }

        /* Indicador visual para bodycams que ya están en la entrega */
        .bodycam-item .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        /* Animación suave para cambios de estado */
        .bodycam-item {
            transition: all 0.3s ease;
        }

        .bodycam-item.selected {
            transform: scale(1.02);
        }

        /* Estilos para lista de bodycams seleccionadas */
        .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .list-group-item .btn-danger {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-upload-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .remove-image-btn {
            background: #dc3545;
            border: none;
            color: white;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-image-btn:hover {
            background: #c82333;
        }

        /* Select2 personalización */
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            border-radius: 8px;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            padding-left: 15px;
            padding-right: 20px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .card-body {
                padding: 15px;
            }

            .section-header h1 {
                font-size: 1.5rem;
            }

            #listaBodycams {
                max-height: 300px;
            }
        }
    </style>
@endpush
