@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Entrega de Equipos</h1>
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

            @if ($equiposDisponibles->isEmpty())
                <div class="alert alert-warning" role="alert">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>No hay equipos disponibles</h4>
                        <p>Actualmente no hay equipos disponibles para entrega. Todos los equipos están entregados o en
                            mantenimiento.</p>
                        <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('entrega-equipos.store') }}" method="POST" id="crearEntregaForm">
                    @csrf

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
                                                <label for="fecha_entrega">Fecha de Entrega <span
                                                        class="text-danger">*</span></label>
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
                                                <label for="hora_entrega">Hora de Entrega <span
                                                        class="text-danger">*</span></label>
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
                                                <select class="form-control select2 @error('dependencia') is-invalid @enderror" id="dependencia" name="dependencia"
                                                    required>
                                                    <option value="">Seleccione una dependencia</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->nombre }}" {{ old('dependencia', $entrega->dependencia ?? '') == $destino->nombre ? 'selected' : '' }}>
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
                                                <label for="personal_receptor">Personal Receptor <span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control @error('personal_receptor') is-invalid @enderror"
                                                    id="personal_receptor" name="personal_receptor"
                                                    value="{{ old('personal_receptor', isset($entregaOriginal) ? $entregaOriginal->personal_receptor : '') }}"
                                                    maxlength="255" required placeholder="Nombre completo del receptor">
                                                @error('personal_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="legajo_receptor">Legajo Receptor</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_receptor') is-invalid @enderror"
                                                    id="legajo_receptor" name="legajo_receptor"
                                                    value="{{ old('legajo_receptor', isset($entregaOriginal) ? $entregaOriginal->legajo_receptor : '') }}"
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
                                                <label for="personal_entrega">Personal que entrega <span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control @error('personal_entrega') is-invalid @enderror"
                                                    id="personal_entrega" name="personal_entrega"
                                                    value="{{ old('personal_entrega', isset($entregaOriginal) ? $entregaOriginal->personal_entrega : '') }}"
                                                    maxlength="255" required placeholder="Nombre del personal que entrega">
                                                @error('personal_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="legajo_entrega">Legajo del personal que entrega</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_entrega') is-invalid @enderror"
                                                    id="legajo_entrega" name="legajo_entrega"
                                                    value="{{ old('legajo_entrega', isset($entregaOriginal) ? $entregaOriginal->legajo_entrega : '') }}"
                                                    maxlength="50" placeholder="Número de legajo (opcional)">
                                                @error('legajo_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="motivo_operativo">Motivo Operativo <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control @error('motivo_operativo') is-invalid @enderror" id="motivo_operativo"
                                            name="motivo_operativo" rows="3" required placeholder="Describa el motivo de la entrega de equipos">{{ old('motivo_operativo', isset($entregaOriginal) ? $entregaOriginal->motivo_operativo : '') }}</textarea>
                                        @error('motivo_operativo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones"
                                            rows="3" placeholder="Observaciones adicionales (opcional)">{{ old('observaciones', isset($entregaOriginal) ? $entregaOriginal->observaciones : '') }}</textarea>
                                        @error('observaciones')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selección de Equipos --}}
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Equipos HT Disponibles</h4>
                                    <div class="card-header-action">
                                        <span class="badge badge-success"
                                            id="totalDisponibles">{{ $equiposDisponibles->count() }} disponibles</span>
                                        <span class="badge badge-info" id="contadorSeleccionados">0 seleccionados</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label><i class="fas fa-search"></i> Buscar equipos:</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control" id="buscarEquipo"
                                                placeholder="TEI, ISSI, ID o cualquier texto...">
                                        </div>
                                    </div>

                                    <!--div class="btn-group btn-group-sm d-flex mb-3">
                                        <button type="button" class="btn btn-outline-primary flex-fill"
                                            id="seleccionarTodos">
                                            <i class="fas fa-check-square"></i> Todos
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary flex-fill"
                                            id="deseleccionarTodos">
                                            <i class="fas fa-square"></i> Ninguno
                                        </button>
                                        <button type="button" class="btn btn-outline-info flex-fill"
                                            id="seleccionarVisibles">
                                            <i class="fas fa-eye"></i> Visibles
                                        </button>
                                    </div-->

                                    <div class="contador-seleccionados">
                                        <span id="resumenSeleccion">Selecciona equipos para continuar</span>
                                    </div>

                                    <div style="max-height: 400px; overflow-y: auto;" id="listaEquipos">
                                        @foreach ($equiposDisponibles as $flota)
                                            <div class="equipo-item" data-id="{{ $flota->id }}"
                                                data-tei="{{ $flota->equipo->tei ?? '' }}"
                                                data-issi="{{ $flota->equipo->issi ?? '' }}"
                                                data-id_equipo="{{ $flota->equipo->id_equipo ?? '' }}"
                                                data-numero_bateria="{{ $flota->equipo->numero_bateria ?? '' }}">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="equipo_{{ $flota->id }}" name="equipos_seleccionados[]"
                                                        value="{{ $flota->id }}"
                                                        {{ in_array($flota->id, old('equipos_seleccionados', [])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="equipo_{{ $flota->id }}">
                                                        <div class="equipo-info">
                                                            <div><strong>ID:</strong> {{ $flota->equipo->nombre_issi ?? 'N/A' }}
                                                            </div>
                                                            <div><strong>TEI:</strong> {{ $flota->equipo->tei ?? 'N/A' }}
                                                            </div>
                                                            <div><strong>ISSI:</strong> {{ $flota->equipo->issi ?? 'N/A' }}
                                                            </div>
                                                            @if ($flota->equipo->numero_bateria)
                                                                <div><strong>Batería:</strong>
                                                                    {{ $flota->equipo->numero_bateria }}</div>
                                                            @endif
                                                            <div><strong>MARCA:</strong> {{ $flota->equipo->tipo_terminal->marca ?? 'N/A' }} <strong>Modelo:</strong> {{ $flota->equipo->tipo_terminal->modelo ?? '' }}
                                                            </div>
                                                            <div class="mt-1">
                                                                <span
                                                                    class="badge badge-success badge-sm">Disponible</span>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div id="noEquiposFound" class="no-equipos-found" style="display: none;">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No se encontraron equipos</h5>
                                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                                    </div>

                                    @error('equipos_seleccionados')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                    @error('equipos_seleccionados.*')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4><i class="fas fa-list-check"></i> Equipos Seleccionados</h4>
                        </div>
                        <div class="card-body" id="equiposSeleccionadosContainer">
                            <p class="text-muted">Aún no hay equipos seleccionados.</p>
                            <ul class="list-group" id="equiposSeleccionadosList"></ul>
                        </div>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted">Los equipos seleccionados cambiarán a estado
                                                "entregado"</small>
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
            $('.select2').select2({
                width: '100%'
            });
            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });
            $(document).on('click', '.quitar-equipo', function() {
                const id = $(this).data('id');
                const checkbox = $(`#equipo_${id}`);
                checkbox.prop('checked', false).trigger('change');
            });

            function renderEquiposSeleccionados() {
                console.log('Renderizando equipos seleccionados...');
                const lista = $('#equiposSeleccionadosList');
                lista.empty();

                const seleccionados = $('input[name="equipos_seleccionados[]"]:checked');

                if (seleccionados.length === 0) {
                    $('#equiposSeleccionadosContainer p').show();
                    return;
                }

                $('#equiposSeleccionadosContainer p').hide();

                seleccionados.each(function() {
                    const $checkbox = $(this);
                    const equipoItem = $checkbox.closest('.equipo-item');

                    const tei = equipoItem.data('tei') || 'TEI N/A';
                    const issi = equipoItem.data('issi') || 'ISSI N/A';
                    const id = equipoItem.data('id') || 'ID N/A';

                    const li = $(`
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>${tei}</strong> - ${issi}</span>
                <button class="btn btn-sm btn-danger quitar-equipo" data-id="${$checkbox.val()}">
                    <i class="fas fa-times"></i>
                </button>
            </li>
        `);
                    lista.append(li);
                });
            }


            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Variables para tracking
            let equiposDisponibles = [];
            let equiposFiltrados = [];

            // Inicializar datos de equipos desde el DOM
            function inicializarEquipos() {
                equiposDisponibles = [];
                $('.equipo-item').each(function() {
                    const $item = $(this);
                    const equipoData = {
                        id: $item.data('id'),
                        id_equipo: $item.data('id_equipo'),
                        tei: $item.data('tei'),
                        issi: $item.data('issi'),
                        numero_bateria: $item.data('numero_bateria'),
                        element: $item
                    };
                    equiposDisponibles.push(equipoData);
                    // Mostrar todos los equipos inicialmente
                    $item.show();
                });
                equiposFiltrados = [...equiposDisponibles];
            }

            // Inicializar equipos
            inicializarEquipos();

            // Función para actualizar contador
            function actualizarContador() {
                const seleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;
                const total = equiposDisponibles.length;

                $('#contadorSeleccionados').text(`${seleccionados} seleccionados`);
                $('#totalDisponibles').text(`${total} disponibles`);

                // Actualizar resumen
                if (seleccionados === 0) {
                    $('#resumenSeleccion').text('Selecciona equipos para continuar');
                } else {
                    $('#resumenSeleccion').html(`
                    <i class="fas fa-check-circle text-success"></i>
                    ${seleccionados} equipo${seleccionados !== 1 ? 's' : ''} seleccionado${seleccionados !== 1 ? 's' : ''}
                `);
                }

                // Validar formulario
                validarFormulario();
            }

            // Función para filtrar equipos
            function filtrarEquipos(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let equiposEncontrados = 0;

                // Limpiar la lista de equipos filtrados
                equiposFiltrados = [];

                $('.equipo-item').each(function() {
                    const $item = $(this);
                    const id = $item.data('id').toString().toLowerCase();
                    const idEquipo = ($item.data('id_equipo') || '').toString().toLowerCase();
                    const tei = ($item.data('tei') || '').toString().toLowerCase();
                    const issi = ($item.data('issi') || '').toString().toLowerCase();
                    const bateria = ($item.data('numero_bateria') || '').toString().toLowerCase();

                    const coincide = term === '' ||
                        id.includes(term) ||
                        idEquipo.includes(term) ||
                        tei.includes(term) ||
                        issi.includes(term) ||
                        bateria.includes(term);

                    if (coincide) {
                        $item.show();
                        equiposEncontrados++;
                        // Agregar a la lista de equipos filtrados
                        equiposFiltrados.push({
                            id: $item.data('id'),
                            element: $item
                        });
                    } else {
                        $item.hide();
                    }
                });

                // Mostrar/ocultar mensaje de "no encontrados"
                if (equiposEncontrados === 0 && term !== '') {
                    $('#noEquiposFound').show();
                } else {
                    $('#noEquiposFound').hide();
                }
            }

            // Función para validar formulario
            function validarFormulario() {
                const dependencia = $('#dependencia').val().trim();
                const personalReceptor = $('#personal_receptor').val().trim();
                const motivoOperativo = $('#motivo_operativo').val().trim();
                const equiposSeleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;

                const isValid = dependencia && personalReceptor && motivoOperativo && equiposSeleccionados > 0;
                $('#btnCrear').prop('disabled', !isValid);
            }

            // Event listeners

            // Actualizar contador cuando cambie la selección
            $(document).on('change', 'input[name="equipos_seleccionados[]"]', function() {
                const $equipoItem = $(this).closest('.equipo-item');
                console.log('Checkbox cambiado:', $equipoItem.data('id'));
                if ($(this).is(':checked')) {
                    $equipoItem.addClass('selected');
                } else {
                    $equipoItem.removeClass('selected');
                }
                actualizarContador();
                renderEquiposSeleccionados(); // 👈 Agregado aquí
            });

            // Click en equipo-item para seleccionar
            $(document).on('click', '.equipo-item', function(e) {
                if (e.target.type !== 'checkbox' && !$(e.target).is('label')) {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Funcionalidad de búsqueda
            $('#buscarEquipo').on('input', function() {
                const searchTerm = $(this).val();
                filtrarEquipos(searchTerm);
            });

            // Seleccionar todos los equipos
            $('#seleccionarTodos').on('click', function() {
                $('.equipo-item input[type="checkbox"]').prop('checked', true).trigger('change');
            });

            // Deseleccionar todos los equipos
            $('#deseleccionarTodos').on('click', function() {
                $('.equipo-item input[type="checkbox"]').prop('checked', false).trigger('change');
            });

            // Seleccionar equipos visibles
            $('#seleccionarVisibles').on('click', function() {
                // Usar equiposFiltrados en lugar de todos los visibles
                equiposFiltrados.forEach(equipo => {
                    equipo.element.find('input[type="checkbox"]').prop('checked', true).trigger(
                        'change');
                });
            });

            // Validación en tiempo real
            $('#dependencia, #personal_receptor, #motivo_operativo').on('input', function() {
                validarFormulario();
            });

            // Validación del formulario antes de enviar
            $('#crearEntregaForm').on('submit', function(e) {
                const equiposSeleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;

                if (equiposSeleccionados === 0) {
                    e.preventDefault();
                    alert('Debe seleccionar al menos un equipo para la entrega.');
                    return false;
                }

                // Confirmar creación
                if (!confirm(
                        `¿Está seguro de crear esta entrega con ${equiposSeleccionados} equipo(s)? Los equipos seleccionados cambiarán a estado "entregado".`
                    )) {
                    e.preventDefault();
                    return false;
                }

                // Mostrar loading en el botón
                $('#btnCrear').html('<i class="fas fa-spinner fa-spin"></i> Creando...').prop('disabled',
                    true);
            });

            // Auto-complete para dependencias (opcional)
            const dependenciasComunes = [
                'Comisaría 1ra',
                'Comisaría 2da',
                'Comisaría 3ra',
                'División Investigaciones',
                'Comando Radioeléctrico',
                'Infantería',
                'Motorizada'
            ];

            // Implementar autocomplete básico
            $('#dependencia').on('input', function() {
                // Podrías implementar un dropdown con las sugerencias
            });

            // Inicializar contador y validación
            actualizarContador();

            // Si hay valores old, marcar equipos como seleccionados
            @if (old('equipos_seleccionados'))
                @foreach (old('equipos_seleccionados') as $equipoId)
                    $('#equipo_{{ $equipoId }}').prop('checked', true).trigger('change');
                @endforeach
            @endif

            // Scroll suave a la sección de equipos si hay errores
            @if ($errors->has('equipos_seleccionados') || $errors->has('equipos_seleccionados.*'))
                $('html, body').animate({
                    scrollTop: $('#listaEquipos').offset().top - 100
                }, 500);
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .equipo-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .equipo-item:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .equipo-item.selected {
            background-color: #e3f2fd;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }

        .equipo-info {
            font-size: 13px;
            line-height: 1.4;
        }

        .equipo-info strong {
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

        #listaEquipos {
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

        .no-equipos-found {
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
        #listaEquipos::-webkit-scrollbar {
            width: 6px;
        }

        #listaEquipos::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #listaEquipos::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        #listaEquipos::-webkit-scrollbar-thumb:hover {
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
    </style>
@endpush
