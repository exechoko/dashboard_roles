@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Tipos de Arma</h3>
            @can('crear-arma-tipo')
                <a href="{{ route('armas.tipos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Tipo
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
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($armaTipos as $armaTipo)
                                    <tr>
                                        <td><strong>{{ $armaTipo->nombre }}</strong></td>
                                        <td>
                                            @if($armaTipo->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @can('editar-arma-tipo')
                                                <a href="{{ route('armas.tipos.edit', $armaTipo) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('borrar-arma-tipo')
                                                <form action="{{ route('armas.tipos.destroy', $armaTipo) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Desactivar este tipo de arma?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Desactivar">
                                                        <i class="fas fa-toggle-off"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No hay tipos de arma registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $armaTipos->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
