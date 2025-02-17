@extends('layouts.app')

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Aquí puedes agregar tus estilos CSS */
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
    </style>
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Administración</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a class="btn btn-success" href="{{ route('flota.create') }}">Nuevo</a>
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $flota->total() }}</label>
                            </div>
                            <form action="{{ route('flota.index') }}" method="get">
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <input type="text" name="texto" class="form-control"
                                            placeholder="Buscar por texto" value="{{ $texto }}">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <select name="tipo_terminal_id" class="form-control select2">
                                            <option value="">Seleccionar Tipo de Terminal</option>
                                            @foreach ($tiposTerminal as $tipo)
                                                <option value="{{ $tipo->id }}"
                                                    {{ request('tipo_terminal_id') == $tipo->id ? 'selected' : '' }}>
                                                    {{ $tipo->marca . ' ' . $tipo->modelo }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="equipo_id" class="form-control select2">
                                            <option value="">Seleccionar Equipo</option>
                                            @foreach ($equipos as $equipo)
                                                <option value="{{ $equipo->id }}"
                                                    {{ request('equipo_id') == $equipo->id ? 'selected' : '' }}>
                                                    {{ $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo . ' - ' . $equipo->tipo_terminal->tipo_uso->uso . ' - ' . $equipo->tei . ' ' . $equipo->issi }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="recurso_id" class="form-control select2">
                                            <option value="">Seleccionar Recurso</option>
                                            @foreach ($recursos as $recurso)
                                                <option value="{{ $recurso->id }}"
                                                    {{ request('recurso_id') == $recurso->id ? 'selected' : '' }}>
                                                    {{ $recurso->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <select name="destino_actual_id" class="form-control select2">
                                            <option value="">Seleccionar Dependencia Actual</option>
                                            @foreach ($destinos as $destino)
                                                <option value="{{ $destino->id }}"
                                                    {{ request('destino_actual_id') == $destino->id ? 'selected' : '' }}>
                                                    {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="destino_id" class="form-control select2">
                                            <option value="">Seleccionar Dependencia Patrimonial</option>
                                            @foreach ($destinos as $destino)
                                                <option value="{{ $destino->id }}"
                                                    {{ request('destino_id') == $destino->id ? 'selected' : '' }}>
                                                    {{ $destino->nombre . ' - ' . $destino->dependeDe() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">
                                                    <i class="fas fa-calendar"></i>
                                                </div>
                                            </div>
                                            <input name="fecha_rango" type="text" class="form-control daterange-cus"
                                                placeholder="Fecha de asignación">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="ticket_per" class="form-control"
                                            placeholder="Ticket PER" value="{{ $ticketPer }}">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="observaciones" class="form-control"
                                            placeholder="Observaciones" value="{{ $observaciones }}">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
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
                                        <!--th style="color:#fff;">Actualmente en</th-->
                                        <th style="color:#fff; width: 150px;"></th>
                                    </thead>
                                    <tbody>
                                        @if (count($flota) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($flota as $f)
                                                {{-- <tr>
                                            <td>
                                                @if ($f->equipo == null)
                                                $f->id
                                                @endif
                                            </td>
                                        </tr> --}}

                                                @include('flota.modal.detalle')
                                                @include('flota.modal.borrar')
                                                {{-- @include('flota.modal.editar') --}}
                                                <tr>
                                                    <td style="display: none;">{{ $f->id }}</td>
                                                    <td><a class="btn btn-dark" href="{{ route('verHistorico', $f->id) }}"
                                                            target="_blank">{{ $f->equipo->tei }}</a>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column align-items-center">
                                                            <img alt="" width="60px" id="myImg"
                                                                src="{{ asset($f->equipo->tipo_terminal->imagen) }}"
                                                                class="img-fluid img-thumbnail">
                                                            <span
                                                                style="font-size: 12px;">{{ $f->equipo->tipo_terminal->tipo_uso->uso . '/' . $f->equipo->tipo_terminal->modelo }}</span>
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

                                                    <td>{{ $f->destino->nombre }}<br>{{ $f->destino->dependeDe() }}</td>
                                                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; position: relative;"
                                                        title="{{ $f->observaciones }}">
                                                        <span
                                                            class="tooltip-text">{{ $f->observaciones_ultimo_mov }}</span>
                                                    </td>
                                                    {{-- @if (is_null($f->ultimoLugar()))
                                                    <td>Sin movimientos</td>
                                                @else
                                                    <td>{{ $f->ultimoLugar() }}</td>
                                                @endif --}}
                                                    <td>
                                                        <form action="{{ route('flota.destroy', $f->id) }}"
                                                            method="POST">

                                                            {{-- <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $flota->id }}">Editar</a> --}}

                                                            {{-- <a class="btn btn-success" href="{{ route('generateDocxConTabla', $f->id) }}">Acta de entrega</a> --}}

                                                            {{-- <a class="btn btn-warning" href="#" data-toggle="modal"
                                                            data-target="#ModalDetalle{{ $f->id }}">Detalles</a> --}}

                                                            @can('editar-flota')
                                                                <a class="btn btn-success"
                                                                    href="{{ route('flota.edit', $f->id) }}"><i
                                                                        class="fas fa-plus"></i></a>
                                                            @endcan

                                                            @can('borrar-flota')
                                                                <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                    data-target="#ModalDelete{{ $f->id }}"><i
                                                                        class="far fa-trash-alt"></i></a>
                                                            @endcan
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {{ $flota->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.daterange-cus').daterangepicker({
                locale: {
                    format: 'DD-MM-YYYY'
                },
                drops: 'down',
                opens: 'right',
                autoUpdateInput: false, // Evita que se establezca un valor inicial
            }, function(start, end, label) {
                // Actualiza el campo con el rango de fechas seleccionado
                $(this).val(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
            });

            // Manejar el evento "apply" para actualizar el campo cuando se selecciona un rango
            $('.daterange-cus').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format(
                    'DD-MM-YYYY'));
            });

            // Manejar el evento "cancel" para limpiar el campo si el usuario cancela la selección
            $('.daterange-cus').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

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
        });
    </script>
@endsection
