@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Entrega de Bodycams</h1>
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
                        <p>Actualmente no hay cámaras corporales disponibles para entrega. Todas las bodycams están entregadas o en mantenimiento.</p>
                        <a href="{{ route('entrega-bodycams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('entrega-bodycams.store') }}" method="POST" id="crearEntregaForm" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        {{-- Información de la Entrega --}}
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-file-alt"></i> Información de la Entrega</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                                                <input type="date"
                                                    class="form-control @error('fecha_entrega') is-invalid @enderror"
                                                    id="fecha_entrega" name="fecha_entrega"
                                                    value="{{ old('fecha_entrega', date('Y-m-d')) }}" required>
                                                @error('fecha_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hora_entrega">Hora de Entrega <span class="text-danger">*</span></label>
                                                <input type="time"
                                                    class="form-control @error('hora_entrega') is-invalid @enderror"
                                                    id="hora_entrega" name="hora_entrega"
                                                    value="{{ old('hora_entrega', date('H:i')) }}" required>
                                                @error('hora_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="dependencia">Dependencia <span class="text-danger">*</span></label>
                                                <select class="form-control select2 @error('dependencia') is-invalid @enderror" id="dependencia" name="dependencia" required>
                                                    <option value="">Seleccione una dependencia</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->nombre }}" {{ old('dependencia') == $destino->nombre ? 'selected' : '' }}>
                                                            {{ $destino->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('dependencia')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="personal_receptor">Personal receptor</label>
                                                <input type="text"
                                                    class="form-control @error('personal_receptor') is-invalid @enderror"
                                                    id="personal_receptor" name="personal_receptor"
                                                    value="{{ old('personal_receptor') }}"
                                                    maxlength="255" placeholder="Nombre completo del receptor">
                                                @error('personal_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="legajo_receptor">L.P. receptor</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_receptor') is-invalid @enderror"
                                                    id="legajo_receptor" name="legajo_receptor"
                                                    value="{{ old('legajo_receptor') }}"
                                                    maxlength="50" placeholder="Número de legajo">
                                                @error('legajo_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="personal_entrega">Personal que entrega</label>
                                                <input type="text"
                                                    class="form-control @error('personal_entrega') is-invalid @enderror"
                                                    id="personal_entrega" name="personal_entrega"
                                                    value="{{ old('personal_entrega') }}"
                                                    maxlength="255" placeholder="Nombre del personal que entrega">
                                                @error('personal_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="legajo_entrega">L.P. del personal que entrega</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_entrega') is-invalid @enderror"
                                                    id="legajo_entrega" name="legajo_entrega"
                                                    value="{{ old('legajo_entrega') }}"
                                                    maxlength="50" placeholder="Número de legajo">
                                                @error('legajo_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="motivo_operativo">Motivo Operativo <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('motivo_operativo') is-invalid @enderror"
                                            id="motivo_operativo" name="motivo_operativo" rows="3" required
                                            placeholder="Describa el motivo de la entrega de bodycams">{{ old('motivo_operativo') }}</textarea>
                                        @error('motivo_operativo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                            id="observaciones" name="observaciones" rows="3"
                                            placeholder="Observaciones adicionales (opcional)">{{ old('observaciones') }}</textarea>
                                        @error('observaciones')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="container col-xs-12 col-sm-12 col-md-12">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-12 col-md-6">
                                                <div class="form-group">
                                                    <label for="archivo">Archivo adjunto</label>
                                                    <input type="file" name="archivo" class="form-control"
                                                        accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                                    @error('archivo')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-xs-12 col-sm-12 col-md-12">
                                                <button type="button" id="addImage" class="btn btn-success">
                                                    <i class="fas fa-plus"></i> Agregar imagen
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row" id="imageContainer"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selección de Bodycams --}}
                        <div class="col-lg-12">
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
                                                placeholder="Código, N° Serie o cualquier texto...">
                                        </div>
                                    </div>

                                    <div class="contador-seleccionados">
                                        <span id="resumenSeleccion">Selecciona bodycams para continuar</span>
                                    </div>

                                    <div style="max-height: 400px; overflow-y: auto;" id="listaBodycams">
                                        @foreach ($bodycamsDisponibles as $bodycam)
                                            <div class="bodycam-item" data-id="{{ $bodycam->id }}"
                                                data-codigo="{{ $bodycam->codigo ?? '' }}"
                                                data-numero_serie="{{ $bodycam->numero_serie ?? '' }}"
                                                data-numero_tarjeta_sd="{{ $bodycam->numero_tarjeta_sd ?? '' }}">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="bodycam_{{ $bodycam->id }}" name="bodycams_seleccionadas[]"
                                                        value="{{ $bodycam->id }}"
                                                        {{ in_array($bodycam->id, old('bodycams_seleccionadas', [])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="bodycam_{{ $bodycam->id }}">
                                                        <div class="bodycam-info">
                                                            <div><strong>Código:</strong> {{ $bodycam->codigo ?? 'N/A' }}</div>
                                                            <div><strong>N° Serie:</strong> {{ $bodycam->numero_serie ?? 'N/A' }}</div>
                                                            <div><strong>Tarjeta SD:</strong> {{ $bodycam->numero_tarjeta_sd ?? 'N/A' }}</div>
                                                            <div><strong>Marca:</strong> {{ $bodycam->marca ?? 'N/A' }}
                                                                <strong>Modelo:</strong> {{ $bodycam->modelo ?? 'N/A' }}
                                                            </div>
                                                            <div class="mt-1">
                                                                <span class="badge badge-success badge-sm">Disponible</span>
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
                                            <a href="{{ route('entrega-bodycams.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted">Las bodycams seleccionadas cambiarán a estado "entregado"</small>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-primary" id="btnCrear" disabled>
                                                <i class="fas fa-save"></i> Crear Entrega
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
            let imageCount = 0;
            let bodycamsDisponibles = [];
            let bodycamsFiltradas = [];

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            // Force focus on search field when Select2 opens
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });

            // Add image functionality
            document.getElementById('addImage').addEventListener('click', function() {
                imageCount++;
                const newImageDiv = document.createElement('div');
                newImageDiv.classList.add('col-xs-12', 'col-sm-12', 'col-md-4');

                newImageDiv.innerHTML = `
                    <div class="form-group">
                        <label for="imagen${imageCount}">Imagen ${imageCount}</label>
                        <input type="file" name="imagen${imageCount}" class="form-control" accept="image/*">
                        <button type="button" class="btn btn-sm btn-danger mt-1 remove-image">
                            <i class="fas fa-times"></i> Quitar
                        </button>
                    </div>
                `;
                document.getElementById('imageContainer').appendChild(newImageDiv);
            });

            // Remove image functionality
            $(document).on('click', '.remove-image', function() {
                $(this).closest('.col-xs-12').remove();
            });

            // Initialize available bodycams data from DOM
            function inicializarBodycams() {
                bodycamsDisponibles = [];
                $('.bodycam-item').each(function() {
                    const $item = $(this);
                    const bodycamData = {
                        id: $item.data('id'),
                        codigo: $item.data('codigo'),
                        numero_serie: $item.data('numero_serie'),
                        numero_tarjeta_sd: $item.data('numero_tarjeta_sd'),
                        element: $item
                    };
                    bodycamsDisponibles.push(bodycamData);
                    $item.show();
                });
                bodycamsFiltradas = [...bodycamsDisponibles];
            }

            // Update counter
            function actualizarContador() {
                const seleccionados = $('input[name="bodycams_seleccionadas[]"]:checked').length;
                const total = bodycamsDisponibles.length;

                $('#contadorSeleccionados').text(`${seleccionados} seleccionados`);
                $('#totalDisponibles').text(`${total} disponibles`);

                // Update summary
                if (seleccionados === 0) {
                    $('#resumenSeleccion').text('Selecciona bodycams para continuar');
                } else {
                    $('#resumenSeleccion').html(`
                        <i class="fas fa-check-circle text-success"></i>
                        ${seleccionados} bodycam${seleccionados !== 1 ? 's' : ''} seleccionada${seleccionados !== 1 ? 's' : ''}
                    `);
                }

                validarFormulario();
            }

            // Filter bodycams
            function filtrarBodycams(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let bodycamsEncontradas = 0;

                bodycamsFiltradas = [];

                $('.bodycam-item').each(function() {
                    const $item = $(this);
                    const codigo = ($item.data('codigo') || '').toString().toLowerCase();
                    const numeroSerie = ($item.data('numero_serie') || '').toString().toLowerCase();
                    const tarjetaSD = ($item.data('numero_tarjeta_sd') || '').toString().toLowerCase();

                    const coincide = term === '' ||
                        codigo.includes(term) ||
                        numeroSerie.includes(term) ||
                        tarjetaSD.includes(term);

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

                // Show/hide "not found" message
                if (bodycamsEncontradas === 0 && term !== '') {
                    $('#noBodycamsFound').show();
                } else {
                    $('#noBodycamsFound').hide();
                }
            }

            // Validate form
            function validarFormulario() {
                const dependencia = $('#dependencia').val().trim();
                const motivoOperativo = $('#motivo_operativo').val().trim();
                const bodycamsSeleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked').length;

                const isValid = dependencia && motivoOperativo && bodycamsSeleccionadas > 0;
                $('#btnCrear').prop('disabled', !isValid);
            }

            // Render selected bodycams
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
                    const numeroSerie = bodycamItem.data('numero_serie') || 'Serie N/A';
                    const tarjetaSD = bodycamItem.data('numero_tarjeta_sd') || 'SD N/A';

                    const li = $(`
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span><strong>${codigo}</strong> - ${numeroSerie}</span>
                                <div><small><strong>Tarjeta SD:</strong> ${tarjetaSD}</small></div>
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

            // Initialize bodycams
            inicializarBodycams();

            // Update counter when selection changes
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

            // Click on bodycam item to select
            $(document).on('click', '.bodycam-item', function(e) {
                if (e.target.type !== 'checkbox' && !$(e.target).is('label')) {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Search functionality
            $('#buscarBodycam').on('input', function() {
                const searchTerm = $(this).val();
                filtrarBodycams(searchTerm);
            });

            // Remove bodycam from selected list
            $(document).on('click', '.quitar-bodycam', function() {
                const id = $(this).data('id');
                const checkbox = $(`#bodycam_${id}`);
                checkbox.prop('checked', false).trigger('change');
            });

            // Real-time validation
            $('#dependencia, #personal_receptor, #motivo_operativo').on('input', function() {
                validarFormulario();
            });

            // Form submission validation
            $('#crearEntregaForm').on('submit', function(e) {
                const bodycamsSeleccionadas = $('input[name="bodycams_seleccionadas[]"]:checked').length;

                if (bodycamsSeleccionadas === 0) {
                    e.preventDefault();
                    alert('Debe seleccionar al menos una bodycam para la entrega.');
                    return false;
                }

                // Confirm creation
                if (!confirm(
                    `¿Está seguro de crear esta entrega con ${bodycamsSeleccionadas} bodycam(s)? Las bodycams seleccionadas cambiarán a estado "entregado".`
                )) {
                    e.preventDefault();
                    return false;
                }

                // Show loading button
                $('#btnCrear').html('<i class="fas fa-spinner fa-spin"></i> Creando...').prop('disabled', true);
            });

            // Initialize counter and validation
            actualizarContador();

            // If there are old values, mark bodycams as selected
            @if (old('bodycams_seleccionadas'))
                @foreach (old('bodycams_seleccionadas') as $bodycamId)
                    $('#bodycam_{{ $bodycamId }}').prop('checked', true).trigger('change');
                @endforeach
            @endif

            // Smooth scroll to bodycams section if there are errors
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

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fadeIn {
            animation: fadeIn 0.3s ease-in;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 15px;
            }

            .bodycam-item {
                padding: 8px;
            }

            .bodycam-info {
                font-size: 12px;
            }
        }
    </style>
@endpush
