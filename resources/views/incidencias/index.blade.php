@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-chart-area"></i> Análisis de Períodos — Sistema 911</h1>
    </div>

    <div class="section-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Períodos de Facturación</h4>
                        @can('crear-periodo-911')
                        <a href="{{ route('incidencias.periodos.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Período
                        </a>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" style="width:80px">Período</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th class="text-center">Días</th>
                                        <th class="text-center">Incidencias</th>
                                        <th class="text-center">Def. Total</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center" style="width:140px">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($periodos as $periodo)
                                    @php
                                        $analisis     = $periodo->analisis();
                                        $defTotal     = $analisis['total_ponderado'];
                                        $aplicaMulta  = $analisis['aplica_multa'];
                                        $rowClass     = $aplicaMulta ? 'table-danger' : '';
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="text-center">
                                            <span class="badge badge-primary font-weight-bold" style="font-size:0.95em">
                                                {{ $periodo->label }}
                                            </span>
                                        </td>
                                        <td>{{ $periodo->fecha_inicio->format('d/m/Y') }}</td>
                                        <td>{{ $periodo->fecha_fin->format('d/m/Y') }}</td>
                                        <td class="text-center">{{ $periodo->dias }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ $periodo->incidencias->count() }}</span>
                                        </td>
                                        <td class="text-center font-weight-bold">
                                            <span class="{{ $aplicaMulta ? 'text-danger' : ($defTotal > 1.0 ? 'text-warning' : 'text-success') }}">
                                                {{ number_format($defTotal, 5) }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($aplicaMulta)
                                                <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> MULTA</span>
                                            @else
                                                <span class="badge badge-success"><i class="fas fa-check"></i> OK</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-periodo-911')
                                            <a href="{{ route('incidencias.periodos.edit', $periodo->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay períodos registrados aún.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($periodos->hasPages())
                    <div class="card-footer">
                        {{ $periodos->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
