@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Alertas de Video &mdash; Personas</h3>
            <div>
                @can('crear-alerta-persona')
                    <a href="{{ route('alertas-video.personas.importar') }}" class="btn btn-info">
                        <i class="fas fa-upload"></i> Importar
                    </a>
                    <a href="{{ route('alertas-video.personas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Cargar Persona
                    </a>
                @endcan
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @php $activoFiltro = request('activo', '1'); @endphp
            <div class="row">
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', ['busqueda' => request('busqueda'), 'activo' => 'todos']) }}"
                       class="alerta-stat-card bg-slate {{ $activoFiltro === 'todos' ? 'active' : '' }}">
                        <div class="small">Total</div>
                        <div class="h3 mb-0">{{ $contadores['total'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', ['busqueda' => request('busqueda'), 'activo' => '1']) }}"
                       class="alerta-stat-card bg-green {{ $activoFiltro === '1' ? 'active' : '' }}">
                        <div class="small">Activos</div>
                        <div class="h3 mb-0">{{ $contadores['activos'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', ['busqueda' => request('busqueda'), 'activo' => '0']) }}"
                       class="alerta-stat-card bg-red {{ $activoFiltro === '0' ? 'active' : '' }}">
                        <div class="small">Inactivos</div>
                        <div class="h3 mb-0">{{ $contadores['inactivos'] }}</div>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('alertas-video.personas.index') }}" class="mb-3">
                        <input type="hidden" name="activo" value="{{ $activoFiltro }}">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar por D.N.I., apellido y nombre o motivo..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('alertas-video.personas.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>D.N.I.</th>
                                    <th>Apellido y Nombre</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($personas as $persona)
                                    <tr>
                                        <td>
                                            @if ($persona->foto_url)
                                                <img src="{{ $persona->foto_url }}" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td>{{ $persona->dni ?? '-' }}</td>
                                        <td><strong>{{ $persona->apellido_nombre }}</strong></td>
                                        <td class="text-truncate" style="max-width: 320px;">{{ $persona->motivo ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-alerta-{{ $persona->activo ? 'activo' : 'inactivo' }}">
                                                {{ $persona->estado_label }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('alertas-video.personas.show', $persona) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-alerta-persona')
                                                <a href="{{ route('alertas-video.personas.edit', $persona) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No hay personas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $personas->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('alertas-video._styles')
@endpush
