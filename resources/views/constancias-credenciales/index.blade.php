@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="section-title">Actas de Credenciales</h1>
            <p class="section-subtitle">Constancias de recepción de credenciales de acceso al Sistema CAR911</p>
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
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Total Actas</h4></div>
                            <div class="card-body">{{ $totalConstancias }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-success bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Firmadas</h4></div>
                            <div class="card-body">{{ $totalFirmadas }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-warning bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Pendientes</h4></div>
                            <div class="card-body">{{ $totalPendientes }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-info bg-info">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Emails Enviados</h4></div>
                            <div class="card-body">{{ $totalEmailsEnviados }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-mobile-optimized">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row w-100">
                        <h4 class="card-title mb-2 mb-md-0">Listado de Actas de Credenciales</h4>
                        @can('crear-constancias-credenciales')
                            <a href="{{ route('constancias-credenciales.create') }}" class="btn btn-primary btn-lg-mobile">
                                <i class="fas fa-plus"></i> Nueva Acta de Credenciales
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('constancias-credenciales.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, DNI o email..." value="{{ request('buscar') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="firmada" {{ request('estado') === 'firmada' ? 'selected' : '' }}>Firmadas</option>
                                    <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_desde" class="form-control" placeholder="Fecha desde" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_hasta" class="form-control" placeholder="Fecha hasta" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('constancias-credenciales.index') }}" class="btn btn-secondary btn-block mt-1">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped mobile-table">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Fecha</th>
                                    <th>Nombre y Apellido</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Firma</th>
                                    <th>Email Enviado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($constancias as $constancia)
                                    <tr>
                                        <td>{{ $constancia->id }}</td>
                                        <td>{{ $constancia->fecha_entrega->format('d/m/Y') }}</td>
                                        <td>{{ $constancia->nombre_apellido }}</td>
                                        <td>{{ $constancia->dni }}</td>
                                        <td>{{ $constancia->email }}</td>
                                        <td>{!! $constancia->estado_badge !!}</td>
                                        <td>{!! $constancia->email_estado !!}</td>
                                        <td>
                                            <div class="action-buttons">
                                                @can('ver-constancias-credenciales')
                                                    <a href="{{ route('constancias-credenciales.show', $constancia) }}" class="btn btn-warning btn-sm" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('editar-constancias-credenciales')
                                                    <a href="{{ route('constancias-credenciales.edit', $constancia) }}" class="btn btn-info btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @if($constancia->ruta_archivo)
                                                    <a href="{{ route('constancias-credenciales.descargar', $constancia) }}" class="btn btn-primary btn-sm" title="Descargar documento">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                @can('borrar-constancias-credenciales')
                                                    <form action="{{ route('constancias-credenciales.destroy', $constancia) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta constancia?')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">No se encontraron actas de credenciales</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $constancias->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
