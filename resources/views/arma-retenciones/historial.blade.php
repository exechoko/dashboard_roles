@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Historial de Devoluciones</h3>
            <div>
                <a href="{{ route('armas.retenciones.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a retenciones
                </a>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Armas devueltas a funcionarios</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('armas.retenciones.historial') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar funcionario o arma..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="tipo" class="form-control">
                                    <option value="">Todos los tipos</option>
                                    <option value="RETENCIÓN" {{ request('tipo') == 'RETENCIÓN' ? 'selected' : '' }}>Retención</option>
                                    <option value="REGULACIÓN" {{ request('tipo') == 'REGULACIÓN' ? 'selected' : '' }}>Regulación</option>
                                    <option value="RESGUARDO" {{ request('tipo') == 'RESGUARDO' ? 'selected' : '' }}>Resguardo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('armas.retenciones.historial') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Arma</th>
                                    <th>Tipo</th>
                                    <th>Motivo</th>
                                    <th>Fecha Posesión</th>
                                    <th>Fecha Devolución</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($devoluciones as $devuelta)
                                    <tr>
                                        <td>
                                            <strong>{{ $devuelta->personal->apellido }}, {{ $devuelta->personal->nombre }}</strong>
                                            <br><small class="text-muted">{{ $devuelta->personal->jerarquia }} - LP: {{ $devuelta->personal->lp }}</small>
                                        </td>
                                        <td>
                                            {{ $devuelta->arma_numero ?? $devuelta->arma?->numero ?? '-' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $devuelta->tipo == 'RETENCIÓN' ? 'warning' : ($devuelta->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                                {{ $devuelta->tipo_label }}
                                            </span>
                                        </td>
                                        <td>{{ $devuelta->motivo->nombre }}</td>
                                        <td>{{ $devuelta->fecha_posesion->format('d/m/Y') }}</td>
                                        <td>{{ optional($devuelta->fecha_devolucion)->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-success">{{ $devuelta->estado_label }}</span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('armas.retenciones.show', $devuelta) }}" class="btn btn-sm btn-outline-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No hay armas devueltas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $devoluciones->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
