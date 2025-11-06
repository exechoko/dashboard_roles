@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Administración</h3>
        </div>

        <div class="section-body">

            <div class="card">
                <div class="card-body">

                    <!-- ACCIONES SUPERIORES -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a class="btn btn-success" href="{{ route('flota.create') }}">Nuevo</a>
                        <label class="alert alert-dark mb-0">
                            Registros: {{ $flota->total() }}
                        </label>
                    </div>

                    <!-- BUSCADOR -->
                    <form action="{{ route('flota.index') }}" method="get" onsubmit="return showLoad()">
                        <div class="input-group mt-4">
                            <input type="text" name="texto" class="form-control"
                                placeholder="Ingrese el nombre del flota que desea buscar" value="{{ $texto }}">

                            <button type="submit" class="btn btn-info">Buscar</button>
                        </div>
                    </form>

                    <!-- ✅✅ MOBILE → CARDS -->
                    <div class="mobile-cards mt-4">

                        @forelse ($flota as $f)

                            @include('flota.modal.detalle')
                            @include('flota.modal.borrar')

                            <div class="flota-card">
                                <div class="flota-card-header">
                                    <div class="flota-card-tei">
                                        <a href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                            TEI: {{ $f->equipo->tei }}
                                        </a>
                                    </div>

                                    <img width="50" class="img-thumbnail" src="{{ asset($f->equipo->tipo_terminal->imagen) }}">
                                </div>

                                <div class="flota-card-body">

                                    <div class="flota-card-item">
                                        <span class="flota-card-label">Tipo/Modelo:</span>
                                        <span>{{ $f->equipo->tipo_terminal->tipo_uso->uso }}/{{ $f->equipo->tipo_terminal->modelo }}</span>
                                    </div>

                                    <div class="flota-card-item">
                                        <span class="flota-card-label">Recurso:</span>
                                        <span>{{ $f->recurso->nombre ?? '-' }}</span>
                                    </div>

                                    <div class="flota-card-item">
                                        <span class="flota-card-label">Dependencia:</span>
                                        <span>{{ $f->destino->nombre }}<br><small>{{ $f->destino->dependeDe() }}</small></span>
                                    </div>

                                    <div class="flota-card-item">
                                        <span class="flota-card-label">Fecha:</span>
                                        <span>{{ $f->fecha_ultimo_mov ?? '-' }}</span>
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
                                        <a class="btn btn-danger btn-sm" data-toggle="modal" data-target="#ModalDelete{{ $f->id }}">
                                            <i class="far fa-trash-alt"></i>
                                        </a>
                                    @endcan
                                </div>
                            </div>

                        @empty
                            <div class="alert alert-info">No se encontraron resultados</div>
                        @endforelse

                    </div>

                    <!-- ✅✅ DESKTOP → TABLA -->
                    <div class="table-responsive desktop-table mt-4">

                        <table class="table table-hover">
                            <thead style="background:linear-gradient(45deg,#6777ef, #35199a)">
                                <tr>
                                    <th style="display:none;">ID</th>
                                    <th class="text-white">TEI</th>
                                    <th class="text-white">Tipo/Modelo</th>
                                    <th class="text-white">Fecha</th>
                                    <th class="text-white">Último mov.</th>
                                    <th class="text-white">Recurso</th>
                                    <th class="text-white">Dependencia</th>
                                    <th class="text-white">Obs.</th>
                                    <th class="text-white" style="width:200px;"></th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($flota as $f)

                                    @include('flota.modal.detalle')
                                    @include('flota.modal.borrar')

                                    <tr>
                                        <td style="display:none;">{{ $f->id }}</td>

                                        <td>
                                            <a class="btn btn-dark" href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                                {{ $f->equipo->tei }}
                                            </a>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column align-items-center">
                                                <img width="60" class="img-thumbnail"
                                                    src="{{ asset($f->equipo->tipo_terminal->imagen) }}">
                                                <small>
                                                    {{ $f->equipo->tipo_terminal->tipo_uso->uso }}/{{ $f->equipo->tipo_terminal->modelo }}
                                                </small>
                                            </div>
                                        </td>

                                        <td>{{ $f->fecha_ultimo_mov ?? '-' }}</td>
                                        <td>{{ $f->ultimo_movimiento ?? '-' }}</td>
                                        <td>{{ $f->recurso->nombre ?? '-' }}</td>
                                        <td>{{ $f->destino->nombre }}<br><small>{{ $f->destino->dependeDe() }}</small></td>

                                        <td class="obs-cell">
                                            <span class="obs-text" data-tooltip="{{ $f->observaciones_ultimo_mov }}">
                                                {{ Str::limit($f->observaciones_ultimo_mov, 40, '...') }}
                                            </span>
                                        </td>


                                        <td>
                                            <a class="btn btn-warning" data-toggle="modal"
                                                data-target="#ModalDetalle{{ $f->id }}">
                                                <i class="far fa-eye"></i>
                                            </a>

                                            @can('editar-flota')
                                                <a class="btn btn-success" href="{{ route('flota.edit', $f->id) }}">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            @endcan

                                            @can('borrar-flota')
                                                <a class="btn btn-danger" data-toggle="modal"
                                                    data-target="#ModalDelete{{ $f->id }}">
                                                    <i class="far fa-trash-alt"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>

                                @empty
                                    <tr>
                                        <td colspan="9">No se encontraron resultados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>

                    <!-- PAGINACIÓN -->
                    <div class="pagination justify-content-end">
                        {{ $flota->links() }}
                    </div>

                </div>
            </div>

        </div>

    </section>
@endsection

@push('scripts')
    <style>
        .obs-cell {
            position: relative;
        }

        .obs-text {
            cursor: pointer;
        }

        /* ✅ Tooltip SOLO si se toca el texto */
        .obs-text:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translate(-50%, -8px);
            background: rgba(30, 30, 30, 0.95);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            max-width: 350px;
            white-space: normal;
            z-index: 2000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            font-size: 13px;
        }

        /* flechita */
        .obs-text:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translate(-50%, 4px);
            border-width: 6px;
            border-style: solid;
            border-color: rgba(30, 30, 30, 0.95) transparent transparent transparent;
            z-index: 2001;
        }

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
    </style>
@endpush
