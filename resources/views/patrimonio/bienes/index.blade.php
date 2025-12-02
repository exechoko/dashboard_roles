@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-boxes"></i> Patrimonio - Bienes</h1>
            <p class="section-subtitle">Gestión de bienes patrimoniales</p>
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h4 class="card-title mb-2 mb-md-0">Listado de Bienes</h4>
                                <a href="{{ route('patrimonio.bienes.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Nuevo Bien
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Filtros de búsqueda --}}
                            <div class="search-container mb-4">
                                <button class="btn btn-outline-info btn-block d-md-none mb-2" type="button" data-toggle="collapse" data-target="#searchForm">
                                    <i class="fas fa-search"></i> Mostrar/Ocultar Búsqueda
                                </button>

                                <div class="collapse d-md-block" id="searchForm">
                                    <form method="GET" action="{{ route('patrimonio.bienes.index') }}">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <input type="text" name="busqueda" class="form-control"
                                                    placeholder="SIAF, Serie, Descripción, Ubicación..." value="{{ request('busqueda') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="tipo_bien_id" class="form-control">
                                                    <option value="">Todos los tipos</option>
                                                    @foreach($tiposBien as $tipo)
                                                        <option value="{{ $tipo->id }}" {{ request('tipo_bien_id') == $tipo->id ? 'selected' : '' }}>
                                                            {{ $tipo->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="destino_id" class="form-control">
                                                    <option value="">Todos los destinos</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->id }}" {{ request('destino_id') == $destino->id ? 'selected' : '' }}>
                                                            {{ $destino->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="text" name="ubicacion" class="form-control"
                                                    placeholder="Ubicación..." value="{{ request('ubicacion') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="estado" class="form-control">
                                                    <option value="">Todos los estados</option>
                                                    @foreach(\App\Models\PatrimonioBien::ESTADOS as $key => $label)
                                                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1">
                                                <div class="d-grid gap-2 d-md-block">
                                                    <button type="submit" class="btn btn-info btn-block">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12 text-right">
                                                <a href="{{ route('patrimonio.bienes.index') }}" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-times"></i> Limpiar Filtros
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Tabla de bienes --}}
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>SIAF</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>N° Serie</th>
                                            <th>Destino</th>
                                            <th>Ubicación</th>
                                            <th>Estado</th>
                                            <th>Fecha Alta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bienes as $bien)
                                            <tr>
                                                <td>{{ $bien->id }}</td>
                                                <td>
                                                    @if($bien->siaf)
                                                        <span class="badge badge-info">{{ $bien->siaf }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $bien->tipoBien->nombre }}</td>
                                                <td>{{ Str::limit($bien->descripcion, 40) }}</td>
                                                <td>{{ $bien->numero_serie ?? '-' }}</td>
                                                <td>{{ $bien->destino->nombre ?? '-' }}</td>
                                                <td>
                                                    @if($bien->ubicacion)
                                                        <span class="badge badge-secondary">{{ Str::limit($bien->ubicacion, 20) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @switch($bien->estado)
                                                        @case('activo')
                                                            <span class="badge badge-success">{{ $bien->estado_formateado }}</span>
                                                            @break
                                                        @case('baja')
                                                            <span class="badge badge-danger">{{ $bien->estado_formateado }}</span>
                                                            @break
                                                        @case('transferido')
                                                            <span class="badge badge-info">{{ $bien->estado_formateado }}</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-warning">{{ $bien->estado_formateado }}</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $bien->fecha_alta->format('d/m/Y') }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('patrimonio.bienes.show', $bien->id) }}"
                                                            class="btn btn-info" title="Ver">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if($bien->estado === 'activo')
                                                            <a href="{{ route('patrimonio.bienes.edit', $bien->id) }}"
                                                                class="btn btn-warning" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="{{ route('patrimonio.bienes.traslado', $bien->id) }}"
                                                                class="btn btn-success" title="Trasladar">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </a>
                                                            <a href="{{ route('patrimonio.bienes.baja', $bien->id) }}"
                                                                class="btn btn-danger" title="Dar de Baja">
                                                                <i class="fas fa-times-circle"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p>No se encontraron bienes</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="d-flex justify-content-center mt-4">
                                {{ $bienes->appends(request()->query())->links() }}
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
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Auto expand search form on page load if there are search parameters
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const hasSearchParams = Array.from(urlParams.keys()).some(key =>
            key !== 'page' && urlParams.get(key) !== ''
        );

        if (hasSearchParams && window.innerWidth < 768) {
            $('#searchForm').collapse('show');
        }
    });
</script>
@endpush

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .search-container .collapse:not(.show) {
            display: none;
        }
    }
</style>
@endpush
