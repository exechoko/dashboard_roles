@extends('layouts.app')

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css">
    <style>
        /* Estilos mejorados para la interfaz */
        .tooltip-text {
            position: absolute;
            white-space: normal;
            background-color: #333;
            color: #fff;
            padding: 5px;
            border-radius: 4px;
            max-width: 400px;
            display: none;
            z-index: 10;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            left: 50%;
            transform: translateX(-50%);
        }

        td:hover .tooltip-text {
            display: block;
        }

        /* Estilos para los selectores multiselect */
        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice {
            background-color: #6777ef;
            border: 1px solid #6777ef;
            color: white;
            margin-top: 5px;
        }

        .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        }

        .select2-container--bootstrap .select2-selection--multiple .select2-selection__clear {
            color: #6c757d;
            font-size: 1.2em;
            margin-right: 5px;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #444;
        }

        .card-header-bg {
            background: linear-gradient(45deg, #6777ef, #35199a);
            color: white;
        }

        .btn-purple {
            background: linear-gradient(45deg, #6777ef, #35199a);
            color: white;
            border: none;
        }

        .btn-purple:hover {
            background: linear-gradient(45deg, #5a6bd8, #2d1580);
            color: white;
        }

        .table-header-bg {
            background: linear-gradient(45deg,#6777ef, #35199a);
            color: white;
        }

        .section-header h3 {
            font-weight: 700;
            color: #35199a;
        }

        .action-buttons .btn {
            padding: 0.3rem 0.6rem;
            margin: 0 2px;
        }
    </style>
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Búsqueda avanzada</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header card-header-bg">
                            <h4 class="mb-0">Filtros de Búsqueda</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="alert alert-dark mb-0">
                                    <i class="fas fa-list"></i> Registros encontrados: {{ $flota->total() }}
                                </label>
                            </div>

                            <form action="{{ route('flota.busquedaAvanzada') }}" method="get" id="search-form">
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            </div>
                                            <input type="text" name="texto" class="form-control"
                                                placeholder="Buscar por texto en todos los campos" value="{{ $texto }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="tipo_terminal_id">Tipo de terminal</label>
                                            <select name="tipo_terminal_id[]" class="form-control select2" multiple="multiple">
                                                <option value="">Seleccionar Tipo de Terminal</option>
                                                @foreach ($tiposTerminal as $tipo)
                                                    <option value="{{ $tipo->id }}"
                                                        {{ in_array($tipo->id, (array) request('tipo_terminal_id', [])) ? 'selected' : '' }}>
                                                        {{ $tipo->marca . ' ' . $tipo->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="equipo_id">Equipo</label>
                                            <select name="equipo_id[]" class="form-control select2" multiple="multiple">
                                                <option value="">Seleccionar Equipo</option>
                                                @foreach ($equipos as $equipo)
                                                    <option value="{{ $equipo->id }}"
                                                        {{ in_array($equipo->id, (array) request('equipo_id', [])) ? 'selected' : '' }}>
                                                        {{ $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo . ' - ' . $equipo->tipo_terminal->tipo_uso->uso . ' - ' . $equipo->tei . ' ' . $equipo->issi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="recurso_id">Recurso</label>
                                            <select name="recurso_id[]" class="form-control select2" multiple="multiple">
                                                <option value="">Seleccionar Recurso</option>
                                                @foreach ($recursos as $recurso)
                                                    <option value="{{ $recurso->id }}"
                                                        {{ in_array($recurso->id, (array) request('recurso_id', [])) ? 'selected' : '' }}>
                                                        {{ $recurso->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="filter-label" for="destino_actual_id">Dependencia Actual</label>
                                            <select name="destino_actual_id[]" class="form-control select2" multiple="multiple">
                                                <option value="">Seleccionar Dependencia Actual</option>
                                                @foreach ($destinos as $destino)
                                                    <option value="{{ $destino->id }}"
                                                        {{ in_array($destino->id, (array) request('destino_actual_id', [])) ? 'selected' : '' }}>
                                                        {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="filter-label" for="destino_id">Dependencia Patrimonial</label>
                                            <select name="destino_id[]" class="form-control select2" multiple="multiple">
                                                <option value="">Seleccionar Dependencia Patrimonial</option>
                                                @foreach ($destinos as $destino)
                                                    <option value="{{ $destino->id }}"
                                                        {{ in_array($destino->id, (array) request('destino_id', [])) ? 'selected' : '' }}>
                                                        {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="fecha_rango">Rango de fechas de asignación</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">
                                                        <i class="fas fa-calendar"></i>
                                                    </div>
                                                </div>
                                                <input name="fecha_rango" type="text" class="form-control daterange-cus"
                                                    placeholder="Fecha de asignación" value="{{ request('fecha_rango') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="ticket_per">Ticket PER</label>
                                            <input type="text" name="ticket_per" class="form-control"
                                                placeholder="Ticket PER" value="{{ $ticketPer }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="observaciones">Observaciones</label>
                                            <input type="text" name="observaciones" class="form-control"
                                                placeholder="Observaciones" value="{{ $observaciones }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-success btn-lg px-5">
                                            <i class="fas fa-search mr-2"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-hover mt-2 display">
                                    <thead class="table-header-bg">
                                        <th style="display: none;">ID</th>
                                        <th>TEI</th>
                                        <th>Tipo/Modelo</th>
                                        <th>Fecha</th>
                                        <th>Último mov.</th>
                                        <th>Recurso asignado</th>
                                        <th>Dependencia</th>
                                        <th>Obs.</th>
                                        <th style="width: 130px;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($flota) <= 0)
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="fas fa-exclamation-circle fa-2x mb-3" style="color: #6777ef;"></i>
                                                    <h5>No se encontraron resultados</h5>
                                                    <p class="text-muted">Intenta ajustar tus filtros de búsqueda</p>
                                                </td>
                                            </tr>
                                        @else
                                            @foreach ($flota as $f)
                                                <tr>
                                                    <td style="display: none;">{{ $f->id }}</td>
                                                    <td>
                                                        <a class="btn btn-dark btn-sm" href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                                            {{ $f->equipo->tei }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column align-items-center">
                                                            <img alt="" width="60px" src="{{ asset($f->equipo->tipo_terminal->imagen) }}" class="img-fluid img-thumbnail">
                                                            <span style="font-size: 12px;">
                                                                {{ $f->equipo->tipo_terminal->tipo_uso->uso . '/' . $f->equipo->tipo_terminal->modelo }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>{{ $f->fecha_ultimo_mov ? $f->fecha_ultimo_mov : '-' }}</td>
                                                    <td>{{ $f->ultimo_movimiento ? $f->ultimo_movimiento : '-' }}</td>
                                                    @if (is_null($f->recurso_id))
                                                        <td>-</td>
                                                    @else
                                                        @if (is_null($f->recurso))
                                                            <td>-</td>
                                                        @else
                                                            <td>{{ $f->recurso->nombre }}</td>
                                                        @endif
                                                    @endif
                                                    <td>
                                                        @php
                                                            $destino = $f->destino instanceof \Illuminate\Database\Eloquent\Collection
                                                                ? $f->destino->first()
                                                                : $f->destino;
                                                        @endphp
                                                        {{ $destino->nombre ?? '-' }}<br>
                                                        {{ $destino->dependeDe() ?? '-' }}
                                                    </td>
                                                    <td style="position: relative;" title="{{ $f->observaciones }}">
                                                        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                                                            {{ $f->observaciones_ultimo_mov ?: '-' }}
                                                        </div>
                                                        <div class="tooltip-text">{{ $f->observaciones_ultimo_mov }}</div>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <div class="d-flex justify-content-center">
                                                            <a class="btn btn-warning btn-sm mx-1" href="#" data-toggle="modal" data-target="#ModalDetalle{{ $f->id }}">
                                                                <i class="far fa-eye"></i>
                                                            </a>
                                                            @can('editar-flota')
                                                                <a class="btn btn-success btn-sm mx-1" href="{{ route('flota.edit', $f->id) }}">
                                                                    <i class="fas fa-plus"></i>
                                                                </a>
                                                            @endcan
                                                            @can('borrar-flota')
                                                                <a class="btn btn-danger btn-sm mx-1" href="#" data-toggle="modal" data-target="#ModalDelete{{ $f->id }}">
                                                                    <i class="far fa-trash-alt"></i>
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end mt-4">
                                {{ $flota->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Incluir modales -->
    @if (count($flota) > 0)
        @foreach ($flota as $f)
            @include('flota.modal.detalle')
            @include('flota.modal.borrar')
        @endforeach
    @endif
@endsection

@push('scripts')
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.min.js"></script>

    <!-- Date Range Picker -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 con múltiple selección y opción para limpiar
            $('.select2').select2({
                theme: 'bootstrap',
                language: 'es',
                placeholder: 'Seleccione una o varias opciones',
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });

            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });

            // Inicializar date range picker
            $('.daterange-cus').daterangepicker({
                locale: {
                    format: 'DD-MM-YYYY',
                    applyLabel: 'Aplicar',
                    cancelLabel: 'Cancelar',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    firstDay: 1
                },
                drops: 'down',
                opens: 'right',
                autoUpdateInput: false,
            }, function(start, end) {
                $(this).val(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
            });

            // Manejar el evento "apply" para actualizar el campo cuando se selecciona un rango
            $('.daterange-cus').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
            });

            // Manejar el evento "cancel" para limpiar el campo si el usuario cancela la selección
            $('.daterange-cus').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Mejorar la experiencia de usuario en pantallas pequeñas
            if ($(window).width() < 768) {
                $('.card-body').addClass('p-2');
                $('.form-group').addClass('mb-3');
            }
        });
    </script>
@endpush
