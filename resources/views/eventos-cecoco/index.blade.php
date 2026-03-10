@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Registro de Eventos CECOCO</h2>
    <a href="{{ route('cecoco.importar') }}" class="btn btn-primary">
        <i class="bi bi-cloud-upload"></i> Importar
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total en BD</h6>
                <h3 class="mb-0">{{ number_format($totalEnBd) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Resultados de búsqueda</h6>
                <h3 class="mb-0">{{ number_format($eventos->total()) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total importaciones</h6>
                <h3 class="mb-0">{{ number_format($totalImportaciones) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('cecoco.index') }}">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de búsqueda</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('cecoco.index') }}">
                        
                        {{-- Fila 1: Búsqueda general --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="buscar" class="form-control" 
                                    placeholder="Expediente, dirección, teléfono..." 
                                    value="{{ request('buscar') }}">
                            </div>
                        </div>

                        {{-- Fila 2: Tipo y Operador --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo Servicio</label>
                                <select name="tipo" class="form-select">
                                    <option value="">Todos</option>
                                    @foreach($tipos as $tipo)
                                        @if($tipo)
                                            <option value="{{ $tipo }}" {{ request('tipo') == $tipo ? 'selected' : '' }}>
                                                {{ $tipo }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Operador</label>
                                <select name="operador" class="form-select">
                                    <option value="">Todos</option>
                                    @foreach($operadores as $operador)
                                        @if($operador)
                                            <option value="{{ $operador }}" {{ request('operador') == $operador ? 'selected' : '' }}>
                                                {{ Str::limit($operador, 25) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Fila 3: Rango de fechas y horas --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Fecha Desde</label>
                                <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Hora Desde</label>
                                <input type="time" name="hora_desde" class="form-control" value="{{ request('hora_desde') }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Fecha Hasta</label>
                                <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Hora Hasta</label>
                                <input type="time" name="hora_hasta" class="form-control" value="{{ request('hora_hasta') }}">
                            </div>
                        </div>

                        {{-- Fila 4: Botones de acción --}}
                        <div class="row g-2">
                            <div class="col-12 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                                <a href="{{ route('cecoco.exportar.csv') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" 
                                class="btn btn-outline-success ms-auto">
                                    <i class="bi bi-download"></i> Exportar CSV
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($eventos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th class="text-nowrap">Nº Exp.</th>
                            <th class="text-nowrap">Fecha/Hora</th>
                            <th class="d-none d-md-table-cell">Operador</th>
                            <th class="d-none d-lg-table-cell">Dirección</th>
                            <th class="d-none d-md-table-cell">Teléfono</th>
                            <th>Tipo Servicio</th>
                            <th class="d-none d-lg-table-cell">Período</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventos as $evento)
                            <tr>
                                <td><strong class="text-nowrap">{{ $evento->nro_expediente }}</strong></td>
                                <td class="text-nowrap">
                                    <div>{{ $evento->fecha_hora->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $evento->fecha_hora->format('H:i') }}</small>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <small>{{ Str::limit($evento->operador, 25) }}</small>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small>{{ Str::limit($evento->direccion, 40) }}</small>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if($evento->telefono)
                                        <a href="tel:{{ $evento->telefono }}" class="text-nowrap">
                                            <small>{{ $evento->telefono }}</small>
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $tipoLower = strtolower($evento->tipo_servicio ?? '');
                                        $badgeClass = 'primary';
                                        if(str_contains($tipoLower, 'llamada falsa')) {
                                            $badgeClass = 'secondary';
                                        } elseif(str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego')) {
                                            $badgeClass = 'danger';
                                        } elseif(str_contains($tipoLower, 'accidente')) {
                                            $badgeClass = 'warning';
                                        } elseif(str_contains($tipoLower, 'personas') || str_contains($tipoLower, 'rescate')) {
                                            $badgeClass = 'info';
                                        } elseif(str_contains($tipoLower, 'broma')) {
                                            $badgeClass = 'dark';
                                        }
                                    @endphp
                                    <span class="badge badge-{{ $badgeClass }} text-wrap" style="max-width: 150px; font-size: 0.85rem;">
                                        {{ Str::limit($evento->tipo_servicio, 30) }}
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small>{{ $evento->periodo }}</small>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('cecoco.show', $evento) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i><span class="d-none d-sm-inline"> Ver</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando {{ $eventos->firstItem() }}–{{ $eventos->lastItem() }} de {{ number_format($eventos->total()) }} registros
                </div>
                <div>
                    {{ $eventos->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No se encontraron eventos con los filtros aplicados</p>
            </div>
        @endif
    </div>
</div>
@endsection
