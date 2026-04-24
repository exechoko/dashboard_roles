@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-chart-bar"></i> Dashboard Patrimonial</h1>
    </div>
    <div class="section-body">
        {{-- Totales generales --}}
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary"><i class="fas fa-broadcast-tower"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Total Flota</h4></div>
                    <div class="card-body">{{ $totales['total_flota'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Patrimoniados</h4></div>
                    <div class="card-body">{{ $totales['patrimoniados'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Pendientes Firma</h4></div>
                    <div class="card-body">{{ $totales['pendientes_firma'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Sin Patrimoniar</h4></div>
                    <div class="card-body">{{ $totales['sin_patrimoniar'] }}</div></div>
                </div>
            </div>
        </div>

        {{-- Barra de progreso general --}}
        @php $pctPatrimoniado = $totales['total_flota'] > 0 ? round(($totales['patrimoniados'] / $totales['total_flota']) * 100) : 0; @endphp
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6>Progreso de patrimoniado general: <strong>{{ $pctPatrimoniado }}%</strong></h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: {{ $pctPatrimoniado }}%">{{ $totales['patrimoniados'] }} patrimoniados</div>
                            @if($totales['pendientes_firma'] > 0)
                            <div class="progress-bar bg-warning" style="width: {{ $totales['total_flota'] > 0 ? round(($totales['pendientes_firma'] / $totales['total_flota']) * 100) : 0 }}%">{{ $totales['pendientes_firma'] }} pendientes</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Departamentales --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-sitemap"></i> Por Departamental</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Dependencia</th>
                                        <th class="text-center">Patrimoniados</th>
                                        <th class="text-center">Pendientes Firma</th>
                                        <th class="text-center">Sin Patrimoniar</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($departamentales as $dep)
                                    @php $totalDep = ($dep->stats['patrimoniados'] ?? 0) + ($dep->stats['sin_patrimoniar'] ?? 0); @endphp
                                    <tr class="font-weight-bold" style="background-color: #f0f4ff; cursor:pointer;" data-toggle="collapse" data-target=".hijos-{{ $dep->id }}">
                                        <td><i class="fas fa-caret-down mr-2"></i>{{ $dep->nombre }}</td>
                                        <td class="text-center"><span class="badge badge-success">{{ $dep->stats['patrimoniados'] ?? 0 }}</span></td>
                                        <td class="text-center"><span class="badge badge-warning">{{ $dep->stats['pendientes_firma'] ?? 0 }}</span></td>
                                        <td class="text-center"><span class="badge badge-danger">{{ $dep->stats['sin_patrimoniar'] ?? 0 }}</span></td>
                                        <td class="text-center"><strong>{{ $totalDep }}</strong></td>
                                    </tr>
                                    @foreach($dep->hijos as $hijo)
                                    @php $totalHijo = ($hijo->stats['patrimoniados'] ?? 0) + ($hijo->stats['sin_patrimoniar'] ?? 0); @endphp
                                    @if($totalHijo > 0)
                                    <tr class="collapse hijos-{{ $dep->id }}">
                                        <td class="pl-5"><i class="fas fa-level-up-alt fa-rotate-90 mr-1 text-muted"></i>{{ $hijo->nombre }}</td>
                                        <td class="text-center">{{ $hijo->stats['patrimoniados'] ?? 0 }}</td>
                                        <td class="text-center">{{ $hijo->stats['pendientes_firma'] ?? 0 }}</td>
                                        <td class="text-center">{{ $hijo->stats['sin_patrimoniar'] ?? 0 }}</td>
                                        <td class="text-center">{{ $totalHijo }}</td>
                                    </tr>
                                    @endif
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Direcciones --}}
        @if($direcciones->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-building"></i> Por Dirección</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr class="bg-light">
                                    <th>Dirección</th>
                                    <th class="text-center">Patrimoniados</th>
                                    <th class="text-center">Pendientes</th>
                                    <th class="text-center">Sin Patrimoniar</th>
                                    <th class="text-center">Total</th>
                                </tr></thead>
                                <tbody>
                                @foreach($direcciones as $dir)
                                    @php $totalDir = ($dir->stats['patrimoniados'] ?? 0) + ($dir->stats['sin_patrimoniar'] ?? 0); @endphp
                                    @if($totalDir > 0)
                                    <tr>
                                        <td>{{ $dir->nombre }}</td>
                                        <td class="text-center"><span class="badge badge-success">{{ $dir->stats['patrimoniados'] ?? 0 }}</span></td>
                                        <td class="text-center"><span class="badge badge-warning">{{ $dir->stats['pendientes_firma'] ?? 0 }}</span></td>
                                        <td class="text-center"><span class="badge badge-danger">{{ $dir->stats['sin_patrimoniar'] ?? 0 }}</span></td>
                                        <td class="text-center"><strong>{{ $totalDir }}</strong></td>
                                    </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <a href="{{ route('patrimonio.cargos.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Cargos</a>
    </div>
</section>
@endsection
@push('styles')<style>.card{border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08)}</style>@endpush
