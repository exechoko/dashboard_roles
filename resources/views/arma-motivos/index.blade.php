@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Motivos de Retención</h3>
            @can('crear-arma-motivo')
                <a href="{{ route('armas.motivos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Motivo
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
                                    <th>Tipo Asignado</th>
                                    <th>Días</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($motivos as $motivo)
                                    <tr>
                                        <td><strong>{{ $motivo->nombre }}</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $motivo->tipo_asignado == 'RETENCIÓN' ? 'warning' : ($motivo->tipo_asignado == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                                {{ $motivo->tipo_asignado }}
                                            </span>
                                        </td>
                                        <td>{{ $motivo->dias }} días</td>
                                        <td>
                                            @if($motivo->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @can('editar-arma-motivo')
                                                <a href="{{ route('armas.motivos.edit', $motivo) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('borrar-arma-motivo')
                                                <form action="{{ route('armas.motivos.destroy', $motivo) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Está seguro de eliminar este motivo?');">
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
                                        <td colspan="5" class="text-center text-muted py-4">No hay motivos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $motivos->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
