{{-- resources/views/entregas/entregas-equipos/crear.blade.php --}}

@extends('layouts.app')

@section('title', 'Nueva Entrega de Equipos')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Nueva Entrega de Equipos</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="{{ route('entrega-equipos.index') }}">Entregas de Equipos</a></div>
                <div class="breadcrumb-item active">Crear Nueva</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if($equiposDisponibles->isEmpty())
                <div class="alert alert-warning" role="alert">
                    <h4>No hay equipos disponibles</h4>
                    <p>Actualmente no hay equipos disponibles para entrega. Todos los equipos están entregados o en mantenimiento.</p>
                    <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            @else
                <form action="{{ route('entrega-equipos.store') }}" method="POST" id="crearEntregaForm">
                    @csrf

                    <div class="row">
                        {{-- Información de la Entrega --}}
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Información de la Entrega</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror"
                                                       id="fecha_entrega" name="fecha_entrega"
                                                       value="{{ old('fecha_entrega', date('Y-m-d')) }}"
                                                       required>
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
                                                       value="{{ old('hora_entrega', date('H:i')) }}"
                                                       required>
                                                @error('hora_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dependencia">Dependencia <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('dependencia') is-invalid @enderror"
                                                       id="dependencia" name="dependencia"
                                                       value="{{ old('dependencia') }}"
                                                       maxlength="255" required
                                                       placeholder="Ej: Comisaría 1ra">
                                                @error('dependencia')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="personal_receptor">Personal Receptor <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('personal_receptor') is-invalid @enderror"
                                                       id="personal_receptor" name="personal_receptor"
                                                       value="{{ old('personal_receptor') }}"
                                                       maxlength="255" required
                                                       placeholder="Nombre completo del receptor">
                                                @error('personal_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="legajo_receptor">Legajo Receptor</label>
                                                <input type="text" class="form-control @error('legajo_receptor') is-invalid @enderror"
                                                       id="legajo_receptor" name="legajo_receptor"
                                                       value="{{ old('legajo_receptor') }}"
                                                       maxlength="50"
                                                       placeholder="Número de legajo (opcional)">
                                                @error('legajo_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="motivo_operativo">Motivo Operativo <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('motivo_operativo') is-invalid @enderror"
                                                  id="motivo_operativo" name="motivo_operativo"
                                                  rows="3" required
                                                  placeholder="Describa el motivo de la entrega de equipos">{{ old('motivo_operativo') }}</textarea>
                                        @error('motivo_operativo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                                  id="observaciones" name="observaciones"
                                                  rows="3"
                                                  placeholder="Observaciones adicionales (opcional)">{{ old('observaciones') }}</textarea>
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
                                    <h4>Equipos Disponibles</h4>
                                    <div class="card-header-action">
                                        <span class="badge badge-success">{{ $equiposDisponibles->count() }} disponibles</span>
                                        <span class="badge badge-info" id="contadorSeleccionados">0 seleccionados</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Buscar equipos:</label>
                                        <input type="text" class="form-control" id="buscarEquipo"
                                               placeholder="Buscar por TEI, ISSI o ID...">
                                    </div>

                                    <div class="text-center mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="seleccionarTodos">
                                            Seleccionar Todos
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="deseleccionarTodos">
                                            Deseleccionar Todos
                                        </button>
                                    </div>

                                    <div style="max-height: 400px; overflow-y: auto;" id="listaEquipos">
                                        @foreach($equiposDisponibles as $equipo)
                                            <div class="custom-control custom-checkbox mb-2 equipo-item"
                                                 data-tei="{{ $equipo->equipo->tei ?? '' }}"
                                                 data-issi="{{ $equipo->equipo->issi ?? '' }}"
                                                 data-id="{{ $equipo->equipo->id }}"
                                                data-id_equipo="{{ $equipo->equipo->id_equipo }}">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="equipo_{{ $equipo->equipo->id }}"
                                                       name="equipos_seleccionados[]"
                                                       value="{{ $equipo->equipo->id }}"
                                                       {{ in_array($equipo->id, old('equipos_seleccionados', [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="equipo_{{ $equipo->id }}">
                                                    <small>
                                                        <strong>ID:</strong> {{ $equipo->equipo->id_equipo ?? 'N/A' }}<br>
                                                        <strong>TEI:</strong> {{ $equipo->equipo->tei ?? 'N/A' }}<br>
                                                        <strong>ISSI:</strong> {{ $equipo->equipo->issi ?? 'N/A' }}<br>
                                                        @if($equipo->equipo->numero_bateria)
                                                            <strong>Batería:</strong> {{ $equipo->equipo->numero_bateria }}<br>
                                                        @endif
                                                        <span class="badge badge-sm badge-success">Disponible</span>
                                                    </small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>

                                    @error('equipos_seleccionados')
                                        <div class="text-danger mt-2">
                                            <small>{{ $message }}</small>
                                        </div>
                                    @enderror
                                    @error('equipos_seleccionados.*')
                                        <div class="text-danger mt-2">
                                            <small>{{ $message }}</small>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
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
</div>
@endsection

@push('scripts')
    <script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Contador de equipos seleccionados
        function actualizarContador() {
            const seleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;
            $('#contadorSeleccionados').text(seleccionados + ' seleccionados');

            // Habilitar/deshabilitar botón de crear
            if (seleccionados > 0) {
                $('#btnCrear').prop('disabled', false);
            } else {
                $('#btnCrear').prop('disabled', true);
            }
        }

        // Inicializar contador
        actualizarContador();

        // Actualizar contador cuando cambie la selección
        $('input[name="equipos_seleccionados[]"]').change(function() {
            actualizarContador();
        });

        // Funcionalidad de búsqueda
        $('#buscarEquipo').on('input', function () {
            const searchTerm = $(this).val().toLowerCase();

            $('.equipo-item').each(function () {
                const tei = ($(this).data('tei') || '').toString().toLowerCase();
                const issi = ($(this).data('issi') || '').toString().toLowerCase();
                const id = ($(this).data('id') || '').toString().toLowerCase();

                const labelText = $(this).find('label').text().toLowerCase();

                if (
                    labelText.includes(searchTerm) ||
                    tei.includes(searchTerm) ||
                    issi.includes(searchTerm) ||
                    id.includes(searchTerm)
                ) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Seleccionar todos los equipos visibles
        $('#seleccionarTodos').on('click', function() {
            $('.equipo-item:visible input[type="checkbox"]').prop('checked', true);
            actualizarContador();
        });

        // Deseleccionar todos los equipos
        $('#deseleccionarTodos').on('click', function() {
            $('.equipo-item input[type="checkbox"]').prop('checked', false);
            actualizarContador();
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
            if (!confirm('¿Está seguro de crear esta entrega? Los equipos seleccionados cambiarán a estado "entregado".')) {
                e.preventDefault();
                return false;
            }

            // Mostrar loading en el botón
            $('#btnCrear').html('<i class="fas fa-spinner fa-spin"></i> Creando...').prop('disabled', true);
        });

        // Validación en tiempo real
        $('#dependencia, #personal_receptor, #motivo_operativo').on('input', function() {
            validarCamposRequeridos();
        });

        function validarCamposRequeridos() {
            const dependencia = $('#dependencia').val().trim();
            const personalReceptor = $('#personal_receptor').val().trim();
            const motivoOperativo = $('#motivo_operativo').val().trim();
            const equiposSeleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;

            if (dependencia && personalReceptor && motivoOperativo && equiposSeleccionados > 0) {
                $('#btnCrear').prop('disabled', false);
            } else {
                $('#btnCrear').prop('disabled', true);
            }
        }

        // Auto-complete para dependencias (opcional - podrías implementar con datos del servidor)
        const dependenciasComunes = [
            'Comisaría 1ra',
            'Comisaría 2da',
            'Comisaría 3ra',
            'División Investigaciones',
            'Comando Radioeléctrico',
            'Infantería',
            'Motorizada'
        ];

        $('#dependencia').on('focus', function() {
            // Implementar autocomplete si es necesario
        });
    });
    </script>
@endpush

@push('styles')
<style>
    .equipo-item {
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 10px;
        background-color: #f8f9fa;
        transition: background-color 0.2s;
    }

    .equipo-item:hover {
        background-color: #e9ecef;
    }

    .equipo-item input[type="checkbox"]:checked + label {
        color: #007bff;
        font-weight: 500;
    }

    .badge-sm {
        font-size: 10px;
        padding: 2px 6px;
    }

    .custom-control-label {
        cursor: pointer;
        width: 100%;
    }

    #listaEquipos {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        background-color: white;
    }

    .form-group label {
        font-weight: 600;
        color: #495057;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .card-header-action .badge {
        margin-left: 5px;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endpush
