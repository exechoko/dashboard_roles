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
                                    <tr>
                                        <td><strong>{{ $personal->apellido }}</strong></td>
                                        <td>{{ $personal->nombre }}</td>
                                        <td>{{ $personal->lp }}</td>
                                        <td>{{ $personal->jerarquia }}</td>
                                        <td class="text-right">
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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay funcionarios registrados.</td>
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
