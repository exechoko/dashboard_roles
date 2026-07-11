@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Personal</h3>
            @can('crear-personal')
                <a href="{{ route('armas.personal.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Funcionario
                </a>
            @endcan
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

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('armas.personal.index') }}" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar por apellido, nombre o LP..."
                                       value="{{ $busqueda }}">
                            </div>
                            <div class="col-md-3">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'activos'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'activos' ? 'btn-primary' : 'btn-outline-primary' }}">
                                        <i class="fas fa-user-check"></i> Activos
                                    </a>
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'eliminados'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'eliminados' ? 'btn-danger' : 'btn-outline-danger' }}">
                                        <i class="fas fa-user-slash"></i> Eliminados
                                    </a>
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'todos'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'todos' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-users"></i> Todos
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('armas.personal.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>LP</th>
                                    <th>Jerarquía</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($personales as $personal)
                                    <tr class="{{ $personal->trashed() ? 'table-danger' : '' }}">
                                        <td>
                                            <strong>{{ $personal->apellido }}</strong>
                                            @if($personal->trashed())
                                                <span class="badge badge-danger">Eliminado</span>
                                            @endif
                                        </td>
                                        <td>{{ $personal->nombre }}</td>
                                        <td>{{ $personal->lp }}</td>
                                        <td>{{ $personal->jerarquia }}</td>
                                        <td class="text-right">
                                            @if($personal->trashed())
                                                @can('restaurar-personal')
                                                    <form action="{{ route('armas.personal.restore', $personal->id) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Restaurar este funcionario?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Restaurar">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('ver-personal')
                                                    <a href="{{ route('armas.personal.show', $personal) }}" class="btn btn-sm btn-info" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('editar-personal')
                                                    <a href="{{ route('armas.personal.edit', $personal) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('borrar-personal')
                                                    <form action="{{ route('armas.personal.destroy', $personal) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Está seguro de eliminar este funcionario?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            @if($ver_eliminados === 'eliminados')
                                                No hay funcionarios eliminados.
                                            @else
                                                No hay funcionarios registrados.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $personales->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
