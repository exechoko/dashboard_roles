@extends('layouts.app')

@section('content')
    @php
        $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    @endphp

    <section class="section">
        <div class="section-header">
            <h1 class="section-title">Entregas de Combustible</h1>
            <p class="section-subtitle">Diesel solicitado por soporte para grupos electrógenos y generadores</p>
        </div>

        <div class="section-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total litros {{ $meses[$mes] }} {{ $anio }}</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->litros }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-info bg-info">
                            <i class="fas fa-fill-drip"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Bidones solicitados</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->bidones }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-warning bg-warning">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Entregas del período</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->entregas }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-mobile-optimized">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row w-100">
                        <h4 class="card-title mb-2 mb-md-0">Listado de Entregas</h4>
                        @can('crear-entrega-combustible')
                            <a href="{{ route('entrega-combustible.create') }}" class="btn btn-primary btn-lg-mobile">
                                <i class="fas fa-plus"></i> Nueva Entrega
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('entrega-combustible.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <input type="text" name="ticket" class="form-control" placeholder="Ticket" value="{{ request('ticket') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha" class="form-control" value="{{ request('fecha') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="empresa_soporte" class="form-control" placeholder="Empresa" value="{{ request('empresa_soporte') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="mes" class="form-control">
                                    @foreach($meses as $numeroMes => $nombreMes)
                                        <option value="{{ $numeroMes }}" {{ $mes === $numeroMes ? 'selected' : '' }}>{{ $nombreMes }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2020" max="2100">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('entrega-combustible.index') }}" class="btn btn-secondary btn-block mt-1">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped mobile-table">
                            <thead>
                                <tr>
                                    <th>N° Acta</th>
                                    <th>Fecha</th>
                                    <th>Ticket</th>
                                    <th>Empresa</th>
                                    <th>Receptor</th>
                                    <th>Cantidad</th>
                                    <th>Acta Firmada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entregas as $entrega)
                                    <tr>
                                        <td>{{ $entrega->id }}</td>
                                        <td>{{ $entrega->fecha_entrega->format('d/m/Y') }}<br><small class="text-muted">{{ substr($entrega->hora_entrega, 0, 5) }}</small></td>
                                        <td>{{ $entrega->ticket }}</td>
                                        <td>{{ $entrega->empresa_soporte }}</td>
                                        <td>{{ $entrega->personal_receptor }}</td>
                                        <td><span class="badge badge-info">{{ $entrega->cantidad_litros }} litros</span><br><small>{{ $entrega->cantidad_bidones }} bidones x {{ $entrega->litros_por_bidon }} L</small></td>
                                        <td>
                                            @if($entrega->ruta_acta_firmada)
                                                <a href="{{ asset($entrega->ruta_acta_firmada) }}" target="_blank" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Ver
                                                </a>
                                            @else
                                                <span class="badge badge-warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                @can('ver-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.show', $entrega) }}" class="btn btn-warning btn-sm" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('editar-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.edit', $entrega) }}" class="btn btn-info btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('crear-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.documento', $entrega) }}" class="btn btn-secondary btn-sm" title="Generar Word">
                                                        <i class="fas fa-file-word"></i>
                                                    </a>
                                                @endcan
                                                @if($entrega->ruta_archivo)
                                                    <a href="{{ route('entrega-combustible.descargar', $entrega) }}" class="btn btn-primary btn-sm" title="Descargar acta generada">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                @can('borrar-entrega-combustible')
                                                    <form action="{{ route('entrega-combustible.destroy', $entrega) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta entrega?')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">No se encontraron entregas de combustible</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $entregas->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
