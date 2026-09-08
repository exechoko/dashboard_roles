@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Flota 911 — Control de Vehículos</h3>
    </div>
    <div class="section-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- Contadores globales --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-primary"><i class="fas fa-truck-pickup fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalRecursos }}</div>
                            <small class="text-muted">Recursos en la división</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-danger"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalNovedadesPendientes }}</div>
                            <small class="text-muted">Novedades pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-warning"><i class="fas fa-exchange-alt fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalPrestados }}</div>
                            <small class="text-muted">Préstamos activos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div class="row mb-4">
            @can('generar-parte-diario')
            <div class="col-auto">
                <a href="{{ route('flota-911.informes.parte-diario') }}" class="btn btn-primary">
                    <i class="fas fa-file-alt mr-1"></i> Parte Diario
                </a>
            </div>
            @endcan
            @can('generar-estado-flota')
            <div class="col-auto">
                <a href="{{ route('flota-911.informes.estado-flota') }}" class="btn btn-secondary">
                    <i class="fas fa-clipboard-list mr-1"></i> Estado Flota
                </a>
            </div>
            @endcan
            @can('gestionar-flota-911')
            <div class="col-auto">
                <a href="{{ route('flota-911.prestamos.index') }}" class="btn btn-warning">
                    <i class="fas fa-exchange-alt mr-1"></i> Préstamos
                </a>
            </div>
            @endcan
        </div>

        {{-- Recursos por sección --}}
        @foreach($secciones as $seccion)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <h5 class="header-title">{{ $seccion->nombre }}</h5>
                        <small class="text-muted">{{ $seccion->recursos->count() }} recurso(s)</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Móvil</th>
                                <th>Dominio</th>
                                <th>Tipo / Marca / Modelo</th>
                                <th>Estado</th>
                                <th>Novedades</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seccion->recursos as $recurso)
                            @php
                                $vehiculo = $recurso->vehiculoActual();
                                $estado = $recurso->estadoSeccion;
                                $pendientes = $recurso->novedadesPendientes->count();
                                $prestamo = $recurso->prestamoActivo;
                            @endphp
                            <tr>
                                <td><strong>{{ $recurso->nombre }}</strong></td>
                                <td>
                                    @if($vehiculo)
                                        <span class="tei-badge">
                                            <i class="fas fa-id-card mr-1"></i>{{ $vehiculo->dominio ?? '—' }}
                                        </span>
                                    @else
                                        <span class="badge badge-light text-muted">Sin ficha</span>
                                    @endif
                                </td>
                                <td>
                                    @if($vehiculo)
                                        <small>{{ implode(' / ', array_filter([$vehiculo->tipo_vehiculo, $vehiculo->marca, $vehiculo->modelo])) }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    @if($prestamo)
                                        <span class="badge badge-info">
                                            <i class="fas fa-exchange-alt mr-1"></i>Prestado a {{ $prestamo->destinoDestino->nombre }}
                                        </span>
                                    @elseif($estado)
                                        <span class="badge badge-{{ $estado->badgeClass }}">{{ $estado->label }}</span>
                                    @else
                                        <span class="badge badge-success">En servicio</span>
                                    @endif
                                </td>
                                <td>
                                    @if($pendientes > 0)
                                        <span class="badge badge-danger">{{ $pendientes }} pendiente(s)</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center action-td">
                                    <a href="{{ route('flota-911.novedades.index', $recurso->id) }}"
                                       class="action-btn btn-view" title="Ver historial">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    @can('gestionar-flota-911')
                                    <button class="action-btn btn-edit" title="Cambiar estado"
                                        data-toggle="modal" data-target="#modalEstado{{ $recurso->id }}">
                                        <i class="fas fa-sliders-h"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modales de estado por recurso --}}
        @can('gestionar-flota-911')
        @foreach($seccion->recursos as $recurso)
        @php $estadoActual = $recurso->estadoSeccion; @endphp
        <div class="modal fade" id="modalEstado{{ $recurso->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Estado de {{ $recurso->nombre }}
                            @php $v = $recurso->vehiculoActual(); @endphp
                            @if($v) <small class="text-muted">({{ $v->dominio }})</small> @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('flota-911.estado-seccion.update', $recurso->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Estado general</label>
                                <select name="estado" class="form-control" required>
                                    @foreach(\App\Models\RecursoEstadoSeccion::$estados as $key => $label)
                                        <option value="{{ $key }}" {{ ($estadoActual?->estado ?? 'en_servicio') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2" maxlength="500">{{ $estadoActual?->observaciones }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
        @endcan
        @endforeach

        {{-- Préstamos activos --}}
        @if($prestamosActivos->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon text-warning"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <h5 class="header-title">Préstamos activos</h5>
                        <small class="text-muted">Recursos prestados a otras dependencias</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Dominio</th>
                                <th>Destino</th>
                                <th>Desde</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prestamosActivos as $prestamo)
                            <tr>
                                <td>{{ $prestamo->recurso->nombre }}</td>
                                <td><span class="tei-badge">{{ $prestamo->vehiculoSnapshot?->dominio ?? '—' }}</span></td>
                                <td>{{ $prestamo->destinoDestino->nombre }}</td>
                                <td>{{ $prestamo->fecha_salida->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</section>
@endsection
