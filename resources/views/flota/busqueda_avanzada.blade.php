@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Búsqueda avanzada</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" style="background-color: #e0f5c4;">
                        <div class="card-header card-header-bg">
                            <h4 class="mb-0">Filtros de Búsqueda</h4>
                        </div>
                        <div class="card-body">
                            @if($hayBusqueda)
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                    <label class="alert alert-dark mb-2 mb-md-0">
                                        <i class="fas fa-list"></i> Registros: {{ $totalRegistros }}
                                    </label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('flota.busquedaAvanzada.export', request()->query()) }}" class="btn btn-outline-success">
                                            <i class="fas fa-file-excel"></i> Exportar Excel
                                        </a>
                                        <a href="{{ route('flota.busquedaAvanzada') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-sync-alt"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    Configure los filtros y presione "Buscar" para ver resultados.
                                </div>
                            @endif

                            <form action="{{ route('flota.busquedaAvanzada') }}" method="get" id="search-form">
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            </div>
                                            <input type="text" name="texto" class="form-control"
                                                placeholder="Buscar por texto" value="{{ $texto }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="tipo_terminal_id">Tipo de terminal</label>
                                            <select name="tipo_terminal_id[]" class="form-control select2" multiple="multiple">
                                                @foreach ($tiposTerminal as $tipo)
                                                    <option value="{{ $tipo->id }}"
                                                        {{ in_array($tipo->id, (array) $tipo_terminal_id) ? 'selected' : '' }}>
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
                                                @foreach ($equipos as $equipo)
                                                    <option value="{{ $equipo->id }}"
                                                        {{ in_array($equipo->id, (array) $equipo_id) ? 'selected' : '' }}>
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
                                                @foreach ($recursos as $recurso)
                                                    <option value="{{ $recurso->id }}"
                                                        {{ in_array($recurso->id, (array) $recurso_id) ? 'selected' : '' }}>
                                                        {{ $recurso->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="destino_actual_id">Dependencia Actual</label>
                                            <select name="destino_actual_id[]" class="form-control select2" multiple="multiple">
                                                @foreach ($destinos as $destino)
                                                    <option value="{{ $destino->id }}"
                                                        {{ in_array($destino->id, (array) $destino_actual_id) ? 'selected' : '' }}>
                                                        {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="destino_id">Dependencia Patrimonial</label>
                                            <select name="destino_id[]" class="form-control select2" multiple="multiple">
                                                @foreach ($destinos as $destino)
                                                    <option value="{{ $destino->id }}"
                                                        {{ in_array($destino->id, (array) $destino_id) ? 'selected' : '' }}>
                                                        {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="estado_id">Estado</label>
                                            <select name="estado_id[]" class="form-control select2" multiple="multiple">
                                                @foreach ($estados as $estado)
                                                    <option value="{{ $estado->id }}"
                                                        {{ in_array($estado->id, (array) $estado_id) ? 'selected' : '' }}>
                                                        {{ $estado->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="fecha_rango">Rango de fechas</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">
                                                        <i class="fas fa-calendar"></i>
                                                    </div>
                                                </div>
                                                <input name="fecha_rango" type="text" class="form-control daterange-cus"
                                                    placeholder="Seleccione rango" value="{{ $fecha_rango }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="filter-label" for="ticket_per">Ticket PER</label>
                                            <input type="text" name="ticket_per" class="form-control"
                                                placeholder="Ticket PER" value="{{ $ticket_per }}">
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

                    @if(!$hayBusqueda)
                        <!-- Mensaje cuando no hay búsqueda -->
                    @else
                        <!-- Resultados -->
                        <div class="card mt-4">
                            <div class="card-body">

                                {{-- INCLUSIÓN DE MODALES --}}
                                @forelse ($flota as $f)
                                    @include('flota.modal.detalle')
                                    @include('flota.modal.borrar')
                                @empty
                                @endforelse

                                <!-- ✅✅ MOBILE → CARDS -->
                                <div class="mobile-cards">
                                    @forelse ($flota as $f)
                                        <div class="flota-card">
                                            <div class="flota-card-header">
                                                <div class="flota-card-tei">
                                                    <a href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                                        TEI: {{ $f->equipo->tei }}
                                                    </a>
                                                </div>
                                                <img width="50" class="img-thumbnail"
                                                    src="{{ asset($f->equipo->tipo_terminal->imagen) }}">
                                            </div>

                                            <div class="flota-card-body">
                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Tipo/Modelo:</span>
                                                    <span class="flota-card-value">
                                                        {{ $f->equipo->tipo_terminal->tipo_uso->uso }}/{{ $f->equipo->tipo_terminal->modelo }}
                                                    </span>
                                                </div>

                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Fecha:</span>
                                                    <span class="flota-card-value">{{ $f->fecha_ultimo_mov ?? '-' }}</span>
                                                </div>

                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Último mov.:</span>
                                                    <span class="flota-card-value">{{ $f->ultimo_movimiento ?? '-' }}</span>
                                                </div>

                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Recurso:</span>
                                                    <span class="flota-card-value">{{ $f->recurso->nombre ?? '-' }}</span>
                                                </div>

                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Dependencia:</span>
                                                    <span class="flota-card-value">
                                                        @php
                                                            $destino = $f->destino instanceof \Illuminate\Database\Eloquent\Collection
                                                                ? $f->destino->first()
                                                                : $f->destino;
                                                        @endphp
                                                        {{ $destino->nombre ?? '-' }}<br>
                                                        <small>{{ $destino->dependeDe() ?? '-' }}</small>
                                                    </span>
                                                </div>

                                                <div class="flota-card-item">
                                                    <span class="flota-card-label">Observaciones:</span>
                                                    <span class="flota-card-value">
                                                        {{ Str::limit($f->observaciones_ultimo_mov, 50, '...') }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flota-card-actions">
                                                <a class="btn btn-warning btn-sm" data-toggle="modal"
                                                    data-target="#ModalDetalle{{ $f->id }}">
                                                    <i class="far fa-eye"></i> Ver
                                                </a>

                                                @can('editar-flota')
                                                    <a class="btn btn-success btn-sm" href="{{ route('flota.edit', $f->id) }}">
                                                        <i class="fas fa-plus"></i> Editar
                                                    </a>
                                                @endcan

                                                @can('borrar-flota')
                                                    <a class="btn btn-danger btn-sm" data-toggle="modal"
                                                        data-target="#ModalDelete{{ $f->id }}">
                                                        <i class="far fa-trash-alt"></i>
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>

                                    @empty
                                        <div class="alert alert-info text-center">
                                            <i class="fas fa-exclamation-circle fa-2x mb-3" style="color: #6777ef;"></i>
                                            <h5>No se encontraron resultados</h5>
                                            <p class="text-muted">Intenta ajustar tus filtros de búsqueda</p>
                                        </div>
                                    @endforelse
                                </div>

                                <!-- ✅✅ DESKTOP → TABLA -->
                                <div class="desktop-table table-responsive">
                                    <table id="dataTable" class="table table-hover mt-2 display">
                                        <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                            <th style="display: none;">ID</th>
                                            <th style="color:#fff;">TEI</th>
                                            <th style="color:#fff;">Tipo/Modelo</th>
                                            <th style="color:#fff;">Fecha</th>
                                            <th style="color:#fff;">Último mov.</th>
                                            <th style="color:#fff;">Recurso asignado</th>
                                            <th style="color:#fff;">Dependencia</th>
                                            <th style="color:#fff;">Obs.</th>
                                            <th style="color: #fff; width: 130px;">Acciones</th>
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
                                                            <a class="btn btn-dark" href="{{ route('verHistorico', $f->id) }}"
                                                            target="_blank">{{ $f->equipo->tei }}</a>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-column align-items-center">
                                                                <img alt="" width="60px" src="{{ asset($f->equipo->tipo_terminal->imagen) }}" class="img-fluid img-thumbnail">
                                                                <span style="font-size: 12px;">
                                                                    {{ $f->equipo->tipo_terminal->tipo_uso->uso . '/' . $f->equipo->tipo_terminal->modelo }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>{{ $f->fecha_ultimo_mov ?? '-' }}</td>
                                                        <td>{{ $f->ultimo_movimiento ?? '-' }}</td>
                                                        <td>{{ $f->recurso->nombre ?? '-' }}</td>
                                                        <td>
                                                            @php
                                                                $destino = $f->destino instanceof \Illuminate\Database\Eloquent\Collection
                                                                    ? $f->destino->first()
                                                                    : $f->destino;
                                                            @endphp
                                                            {{ $destino->nombre ?? '-' }}<br>
                                                            {{ $destino->dependeDe() ?? '-' }}
                                                        </td>
                                                        <td class="obs-cell">
                                                            <span class="obs-text" data-tooltip="{{ $f->observaciones_ultimo_mov }}">
                                                                {{ Str::limit($f->observaciones_ultimo_mov, 40, '...') }}
                                                            </span>
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

                                <!-- Paginación -->
                                @if($flota instanceof \Illuminate\Pagination\LengthAwarePaginator && $flota->hasPages())
                                    <div class="pagination justify-content-end mt-4">
                                        {{ $flota->appends(request()->query())->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar componentes UI
            inicializarUI();

            function inicializarUI() {
                // Inicializar Select2
                $('.select2').select2({
                    theme: 'bootstrap',
                    language: 'es',
                    placeholder: 'Seleccione una o varias opciones',
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });

                // Manejo del foco en Select2
                $('.select2').on('select2:open', function (e) {
                    setTimeout(() => {
                        const $dropdown = $('.select2-container--open');
                        const searchField = $dropdown.find('.select2-search__field');
                        if (searchField.length > 0) {
                            searchField[0].focus();
                        }
                    }, 50);
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
                });

                // Eventos del date picker
                $('.daterange-cus').on('apply.daterangepicker', function (ev, picker) {
                    $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
                });

                $('.daterange-cus').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                });
            }

            // Mostrar loading en el botón al enviar formulario
            $('#search-form').on('submit', function() {
                const $submitBtn = $(this).find('button[type="submit"]');
                const originalText = $submitBtn.html();

                $submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Buscando...')
                    .prop('disabled', true);

                // Restaurar el botón si hay error
                setTimeout(() => {
                    $submitBtn.html(originalText).prop('disabled', false);
                }, 30000);
            });

            // Responsive adjustments
            if ($(window).width() < 768) {
                $('.card-body').addClass('p-2');
                $('.form-group').addClass('mb-3');
            }
        });
    </script>
@endsection

@push('scripts')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css">
    <style>
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

        .no-search-message {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border: 2px dashed #6777ef;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }

        .search-icon {
            font-size: 3rem;
            color: #6777ef;
            margin-bottom: 1rem;
        }

        /* ============================================
           RESPONSIVE: Mobile Cards vs Desktop Table
           ============================================ */

        /* ✅ MOBILE */
        .mobile-cards {
            display: block !important;
        }

        .desktop-table {
            display: none !important;
        }

        /* ✅ DESKTOP */
        @media (min-width: 768px) {
            .mobile-cards {
                display: none !important;
            }

            .desktop-table {
                display: block !important;
            }
        }

        /* ============================================
           MEJORAS RESPONSIVE PARA FORMULARIO
           ============================================ */

        @media (max-width: 767px) {
            .section-header h3 {
                font-size: 18px;
            }

            .card-body {
                padding: 15px;
            }

            .alert {
                font-size: 14px;
                padding: 8px 12px;
            }

            .btn-lg {
                padding: 10px 20px;
                font-size: 16px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .filter-label {
                font-size: 14px;
            }

            .form-control, .select2-container {
                font-size: 14px;
            }

            /* Ajustar espaciado en móvil */
            .row.mt-3, .row.mt-2, .row.mt-4 {
                margin-top: 10px !important;
            }

            /* Botón de búsqueda full width en móvil */
            .text-right {
                text-align: center !important;
            }

            .btn-success.btn-lg {
                width: 100%;
            }

            /* Ajustar el input group */
            .input-group-text {
                font-size: 14px;
            }
        }
    </style>
@endpush
