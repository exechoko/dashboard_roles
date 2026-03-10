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
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Expediente, dirección, teléfono..." value="{{ request('buscar') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Año</label>
                    <select name="anio" class="form-select">
                        <option value="">Todos</option>
                        @foreach($anios as $anio)
                            <option value="{{ $anio }}" {{ request('anio') == $anio ? 'selected' : '' }}>{{ $anio }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Mes</label>
                    <select name="mes" class="form-select">
                        <option value="">Todos</option>
                        @php
                            $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        @endphp
                        @if(request('anio') && count($meses) > 0)
                            @foreach($meses as $mes)
                                <option value="{{ $mes }}" {{ request('mes') == $mes ? 'selected' : '' }}>{{ $mesesNombres[$mes] }}</option>
                            @endforeach
                        @else
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ request('mes') == $i ? 'selected' : '' }}>{{ $mesesNombres[$i] }}</option>
                            @endfor
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Operador</label>
                    <select name="operador" class="form-select">
                        <option value="">Todos</option>
                        @foreach($operadores as $operador)
                            @if($operador)
                                <option value="{{ $operador }}" {{ request('operador') == $operador ? 'selected' : '' }}>{{ $operador }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo Servicio</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tipos as $tipo)
                            @if($tipo)
                                <option value="{{ $tipo }}" {{ request('tipo') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                    <a href="{{ route('cecoco.exportar.csv') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-outline-success">
                        <i class="bi bi-download"></i> Exportar CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($eventos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nº Exp.</th>
                            <th>Fecha/Hora</th>
                            <th>Operador</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Tipo Servicio</th>
                            <th>Período</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventos as $evento)
                            <tr>
                                <td><strong>{{ $evento->nro_expediente }}</strong></td>
                                <td>
                                    {{ $evento->fecha_hora->format('d/m/Y') }}<br>
                                    <small class="text-muted">{{ $evento->fecha_hora->format('H:i') }}</small>
                                </td>
                                <td>{{ $evento->operador }}</td>
                                <td>{{ Str::limit($evento->direccion, 40) }}</td>
                                <td>
                                    @if($evento->telefono)
                                        <a href="tel:{{ $evento->telefono }}">{{ $evento->telefono }}</a>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $tipoLower = strtolower($evento->tipo_servicio ?? '');
                                        $badgeClass = 'info text-dark';
                                        if(str_contains($tipoLower, 'llamada falsa')) {
                                            $badgeClass = 'secondary';
                                        } elseif(str_contains($tipoLower, 'accidente')) {
                                            $badgeClass = 'danger';
                                        } elseif(str_contains($tipoLower, 'personas')) {
                                            $badgeClass = 'warning';
                                        } elseif(str_contains($tipoLower, 'broma')) {
                                            $badgeClass = 'light text-dark';
                                        }
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}">{{ $evento->tipo_servicio }}</span>
                                </td>
                                <td>{{ $evento->periodo }}</td>
                                <td>
                                    <a href="{{ route('cecoco.show', $evento) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
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
