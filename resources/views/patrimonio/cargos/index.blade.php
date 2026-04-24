@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-file-signature"></i> Cargos Patrimoniales</h1>
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

            {{-- Contadores --}}
            <div class="row mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary"><i class="fas fa-file-alt"></i></div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Total</h4></div>
                            <div class="card-body">{{ $contadores['total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Pendientes</h4></div>
                            <div class="card-body">{{ $contadores['pendientes'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Firmados</h4></div>
                            <div class="card-body">{{ $contadores['firmados'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-danger"><i class="fas fa-times-circle"></i></div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>Rechazados</h4></div>
                            <div class="card-body">{{ $contadores['rechazados'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h4 class="card-title mb-2 mb-md-0">Listado de Cargos</h4>
                                <a href="{{ route('patrimonio.dashboard') }}" class="btn btn-info">
                                    <i class="fas fa-chart-bar"></i> Dashboard Patrimonial
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Filtros --}}
                            <div class="search-container mb-4">
                                <button class="btn btn-outline-info btn-block d-md-none mb-2" type="button"
                                    data-toggle="collapse" data-target="#searchForm">
                                    <i class="fas fa-search"></i> Mostrar/Ocultar Búsqueda
                                </button>
                                <div class="collapse d-md-block" id="searchForm">
                                    <form method="GET" action="{{ route('patrimonio.cargos.index') }}">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <input type="text" name="busqueda" class="form-control"
                                                    placeholder="TEI, ISSI, Firmante, Legajo..."
                                                    value="{{ request('busqueda') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <select name="destino_id" class="form-control select2">
                                                    <option value="">Todas las dependencias</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->id }}"
                                                            {{ request('destino_id') == $destino->id ? 'selected' : '' }}>
                                                            {{ $destino->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="estado" class="form-control">
                                                    <option value="">Todos los estados</option>
                                                    @foreach(\App\Models\PatrimonioCargo::ESTADOS as $key => $label)
                                                        <option value="{{ $key }}"
                                                            {{ request('estado') == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <div class="input-group">
                                                    <input type="date" name="fecha_desde" class="form-control"
                                                        placeholder="Desde" value="{{ request('fecha_desde') }}">
                                                    <input type="date" name="fecha_hasta" class="form-control"
                                                        placeholder="Hasta" value="{{ request('fecha_hasta') }}">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1">
                                                <button type="submit" class="btn btn-info btn-block">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12 text-right">
                                                <a href="{{ route('patrimonio.cargos.index') }}"
                                                    class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-times"></i> Limpiar Filtros
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Tabla de cargos --}}
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Equipo</th>
                                            <th>Dependencia</th>
                                            <th>Dependencia Padre</th>
                                            <th>Estado</th>
                                            <th>Firmante</th>
                                            <th>Fecha Firma</th>
                                            <th>Creado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($cargos as $cargo)
                                            <tr>
                                                <td>{{ $cargo->id }}</td>
                                                <td>
                                                    @if($cargo->equipo)
                                                        <strong>TEI:</strong> {{ $cargo->equipo->tei }}<br>
                                                        <small class="text-muted">ISSI: {{ $cargo->equipo->issi ?? '-' }}</small>
                                                        @if($cargo->equipo->tipo_terminal)
                                                            <br><small>{{ $cargo->equipo->tipo_terminal->marca }} {{ $cargo->equipo->tipo_terminal->modelo }}</small>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>{{ $cargo->destino->nombre ?? '-' }}</td>
                                                <td>
                                                    @if($cargo->destino && $cargo->destino->padre)
                                                        {{ $cargo->destino->padre->nombre }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $cargo->badge_class }}">
                                                        <i class="{{ $cargo->badge_icon }}"></i>
                                                        {{ $cargo->estado_formateado }}
                                                    </span>
                                                </td>
                                                <td>{{ $cargo->firmante_nombre ?? '-' }}</td>
                                                <td>
                                                    {{ $cargo->fecha_firma ? $cargo->fecha_firma->format('d/m/Y H:i') : '-' }}
                                                </td>
                                                <td>{{ $cargo->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('patrimonio.cargos.show', $cargo->id) }}"
                                                            class="btn btn-info" title="Ver detalle">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p>No se encontraron cargos patrimoniales</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                {{ $cargos->appends(request()->query())->links() }}
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
    $('.select2').select2({ width: '100%' });
    $(document).on('select2:open', () => {
        let field = document.querySelector('.select2-search__field');
        if (field) field.focus();
    });
    setTimeout(function() { $('.alert').fadeOut('slow'); }, 5000);
</script>
@endpush

@push('styles')
<style>
    .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); }
    .btn-group-sm .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
</style>
@endpush
