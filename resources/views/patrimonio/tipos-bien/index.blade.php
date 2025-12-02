@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-tags"></i> Tipos de Bien</h1>
            <p class="section-subtitle">Configuración de categorías patrimoniales</p>
        </div>

        <div class="section-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h4 class="card-title mb-2 mb-md-0">Listado de Tipos</h4>
                                <a href="{{ route('patrimonio.tipos-bien.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Nuevo Tipo
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Tabla Propia</th>
                                            <th>Referencia</th>
                                            <th>Cant. Bienes</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tipos as $tipo)
                                            <tr>
                                                <td>{{ $tipo->id }}</td>
                                                <td><strong>{{ $tipo->nombre }}</strong></td>
                                                <td>{{ Str::limit($tipo->descripcion, 50) ?? '-' }}</td>
                                                <td>
                                                    @if($tipo->tiene_tabla_propia)
                                                        <span class="badge badge-success"><i class="fas fa-check"></i> Sí</span>
                                                    @else
                                                        <span class="badge badge-secondary"><i class="fas fa-times"></i> No</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($tipo->tabla_referencia)
                                                        <code>{{ $tipo->tabla_referencia }}</code>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ $tipo->bienes_count }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('patrimonio.tipos-bien.edit', $tipo->id) }}"
                                                            class="btn btn-warning" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($tipo->bienes_count == 0)
                                                            <form action="{{ route('patrimonio.tipos-bien.destroy', $tipo->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger"
                                                                        onclick="return confirm('¿Está seguro de eliminar este tipo de bien?')"
                                                                        title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button class="btn btn-danger" disabled title="No se puede eliminar (tiene bienes asociados)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p>No hay tipos de bien configurados</p>
                                                    <a href="{{ route('patrimonio.tipos-bien.create') }}" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Crear Primer Tipo
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="d-flex justify-content-center mt-4">
                                {{ $tipos->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tarjetas informativas --}}
            <div class="row">
                <div class="col-md-4">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total Tipos</h4>
                            </div>
                            <div class="card-body">
                                {{ $tipos->total() }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Con Tabla Propia</h4>
                            </div>
                            <div class="card-body">
                                {{ $tipos->where('tiene_tabla_propia', true)->count() }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total Bienes</h4>
                            </div>
                            <div class="card-body">
                                {{ $tipos->sum('bienes_count') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    code {
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        color: #e83e8c;
    }
</style>
@endpush
