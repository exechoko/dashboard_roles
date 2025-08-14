{{-- resources/views/entregas/entregas-equipos/devolver.blade.php --}}

@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-undo"></i> Devolución de Equipos</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('entrega-equipos.index') }}">Entregas</a></div>
                <div class="breadcrumb-item"><a href="{{ route('entrega-equipos.show', $entrega->id) }}">Acta {{ $entrega->id }}</a></div>
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

            <form action="{{ route('entrega-equipos.procesar-devolucion', $entrega->id) }}" method="POST" id="devolucionForm">
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
                                    <label><strong>Total Equipos:</strong></label>
                                    <p><span class="badge badge-info">{{ $entrega->equipos->count() }}</span></p>
                                </div>
                                <div class="form-group">
                                    <label><strong>Equipos Pendientes:</strong></label>
                                    <p><span class="badge badge-warning">{{ $equiposPendientes->count() }}</span></p>
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

                {{-- Selección de Equipos a Devolver --}}
                <div class="card">
                    <div class="card-header">
                        <h4>Equipos Pendientes de Devolución</h4>
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
                                        <th>ID Equipo</th>
                                        <th>TEI</th>
                                        <th>ISSI</th>
                                        <th>N° Batería</th>
                                        <th>Marca/Modelo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($equiposPendientes as $equipo)
                                        <tr>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input equipo-checkbox"
                                                        id="equipo_{{ $equipo->id }}" name="equipos_devolver[]"
                                                        value="{{ $equipo->id }}"
                                                        {{ in_array($equipo->id, old('equipos_devolver', [])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="equipo_{{ $equipo->id }}"></label>
                                                </div>
                                            </td>
                                            <td>{{ $equipo->equipo->nombre_issi ?? 'N/A' }}</td>
                                            <td>{{ $equipo->equipo->tei ?? 'N/A' }}</td>
                                            <td>{{ $equipo->equipo->issi ?? 'N/A' }}</td>
                                            <td>{{ $equipo->equipo->numero_bateria ?? 'N/A' }}</td>
                                            <td>{{ $equipo->equipo->tipo_terminal->marca ?? 'N/A' }} {{ $equipo->equipo->tipo_terminal->modelo ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('equipos_devolver')
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
                                <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">Los equipos seleccionados cambiarán a estado "disponible"</small>
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
    function actualizarContador() {
        const seleccionados = $('.equipo-checkbox:checked').length;
        $('#contadorSeleccionados').text(`${seleccionados} seleccionados`);

        // Habilitar/deshabilitar botón
        $('#btnDevolver').prop('disabled', seleccionados === 0);

        // Actualizar checkbox maestro
        const total = $('.equipo-checkbox').length;
        $('#selectAllCheckbox').prop('checked', seleccionados === total && seleccionados > 0);
        $('#selectAllCheckbox').prop('indeterminate', seleccionados > 0 && seleccionados < total);
    }

    // Event listeners para checkboxes
    $('.equipo-checkbox').on('change', actualizarContador);

    // Checkbox maestro
    $('#selectAllCheckbox').on('change', function() {
        $('.equipo-checkbox').prop('checked', this.checked);
        actualizarContador();
    });

    // Botones de selección masiva
    $('#seleccionarTodos').on('click', function() {
        $('.equipo-checkbox').prop('checked', true);
        actualizarContador();
    });

    $('#deseleccionarTodos').on('click', function() {
        $('.equipo-checkbox').prop('checked', false);
        actualizarContador();
    });

    // Validación del formulario
    $('#devolucionForm').on('submit', function(e) {
        const equiposSeleccionados = $('.equipo-checkbox:checked').length;

        if (equiposSeleccionados === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un equipo para devolver.');
            return false;
        }

        // Confirmar devolución
        if (!confirm(`¿Está seguro de registrar la devolución de ${equiposSeleccionados} equipo(s)?`)) {
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
