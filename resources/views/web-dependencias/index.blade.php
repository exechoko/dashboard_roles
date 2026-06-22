@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Administrar Web — Dependencias</h3>
            <div>
                <button type="button" class="btn btn-outline-primary js-web-preview"
                        data-pagina="dependencias.html" data-title="Dependencias">
                    <i class="fas fa-eye"></i> Vista previa
                </button>
                <a href="{{ route('web-dependencias.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva dependencia
                </a>
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

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Dirección</th>
                                    <th>Teléfonos</th>
                                    <th style="width:120px" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dependencias as $dep)
                                    <tr>
                                        <td>{{ $dep->orden }}</td>
                                        <td><strong>{{ $dep->nombre }}</strong></td>
                                        <td><span class="badge badge-info">{{ $categorias[$dep->categoria] ?? $dep->categoria }}</span></td>
                                        <td>{{ $dep->direccion }}</td>
                                        <td>{{ implode(', ', $dep->telefonos ?? []) }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('web-dependencias.edit', $dep) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('web-dependencias.destroy', $dep) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar esta dependencia? Se quitará también de la web.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No hay dependencias cargadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $dependencias->links() }}
                </div>
            </div>
        </div>
    </section>

    @include('web-admin._preview')
@endsection
