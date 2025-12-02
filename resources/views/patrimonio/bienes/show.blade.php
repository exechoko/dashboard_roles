@extends('layouts.app')

@section('content')
    <section class="section">
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
                {{-- Información del Bien --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-box"></i> Detalle del Bien #{{ $bien->id }}</h4>
                            <div class="card-header-action">
                                @switch($bien->estado)
                                    @case('activo')
                                        <span class="badge badge-success badge-lg">{{ $bien->estado_formateado }}</span>
                                        @break
                                    @case('baja')
                                        <span class="badge badge-danger badge-lg">{{ $bien->estado_formateado }}</span>
                                        @break
                                    @case('transferido')
                                        <span class="badge badge-info badge-lg">{{ $bien->estado_formateado }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-warning badge-lg">{{ $bien->estado_formateado }}</span>
                                @endswitch
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Tipo de Bien:</strong></label>
                                        <p class="form-control-static">{{ $bien->tipoBien->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Código SIAF:</strong></label>
                                        <p class="form-control-static">
                                            @if($bien->siaf)
                                                <span class="badge badge-info">{{ $bien->siaf }}</span>
                                            @else
                                                <span class="text-muted">No asignado</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Número de Serie:</strong></label>
                                        <p class="form-control-static">{{ $bien->numero_serie ?? 'No registrado' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Fecha de Alta:</strong></label>
                                        <p class="form-control-static">{{ $bien->fecha_alta->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Descripción:</strong></label>
                                <p class="form-control-static">{{ $bien->descripcion }}</p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Destino Actual:</strong></label>
                                        <p class="form-control-static">
                                            @if($bien->destino)
                                                <span class="badge badge-primary badge-lg">{{ $bien->destino->nombre }}</span>
                                            @else
                                                <span class="text-muted">Sin asignar</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Ubicación Específica:</strong></label>
                                        <p class="form-control-static">
                                            @if($bien->ubicacion)
                                                <span class="badge badge-secondary badge-lg">{{ $bien->ubicacion }}</span>
                                            @else
                                                <span class="text-muted">No especificada</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($bien->observaciones)
                                <div class="form-group">
                                    <label><strong>Observaciones:</strong></label>
                                    <p class="form-control-static">{{ $bien->observaciones }}</p>
                                </div>
                            @endif

                            @if($bien->tabla_origen && $bien->id_origen)
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-link"></i> Vinculación con Sistema</h6>
                                    <p class="mb-0">Este bien está vinculado con: <strong>{{ ucwords(str_replace('_', ' ', $bien->tabla_origen)) }}</strong> (ID: {{ $bien->id_origen }})</p>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Fecha de Registro:</strong></label>
                                        <p class="form-control-static">{{ $bien->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Última Actualización:</strong></label>
                                        <p class="form-control-static">{{ $bien->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Historial de Movimientos --}}
                    @if($bien->movimientos->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-history"></i> Historial de Movimientos ({{ $bien->movimientos->count() }})</h4>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    @foreach($bien->movimientos->sortByDesc('fecha') as $movimiento)
                                        <div class="timeline-item">
                                            <div class="timeline-marker
                                                @if($movimiento->tipo_movimiento == 'alta') bg-success
                                                @elseif(str_starts_with($movimiento->tipo_movimiento, 'baja')) bg-danger
                                                @else bg-info
                                                @endif"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <h6 class="timeline-title">
                                                        {{ $movimiento->tipo_formateado }}
                                                    </h6>
                                                    <small class="text-muted">
                                                        {{ $movimiento->fecha->format('d/m/Y H:i') }}
                                                    </small>
                                                </div>
                                                <div class="timeline-body">
                                                    @if($movimiento->tipo_movimiento == 'traslado')
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p class="mb-1"><strong>Desde:</strong></p>
                                                                <p class="mb-0"><i class="fas fa-building"></i> {{ $movimiento->destinoDesde->nombre ?? 'Sin especificar' }}</p>
                                                                @if($movimiento->ubicacion_desde)
                                                                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i> {{ $movimiento->ubicacion_desde }}</p>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p class="mb-1"><strong>Hasta:</strong></p>
                                                                <p class="mb-0"><i class="fas fa-building"></i> {{ $movimiento->destinoHasta->nombre ?? 'Sin especificar' }}</p>
                                                                @if($movimiento->ubicacion_hasta)
                                                                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i> {{ $movimiento->ubicacion_hasta }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif($movimiento->tipo_movimiento == 'alta')
                                                        <p><strong>Destino inicial:</strong> {{ $movimiento->destinoHasta->nombre ?? 'Sin asignar' }}</p>
                                                        @if($movimiento->ubicacion_hasta)
                                                            <p><strong>Ubicación inicial:</strong> {{ $movimiento->ubicacion_hasta }}</p>
                                                        @endif
                                                    @endif

                                                    @if($movimiento->observaciones)
                                                        <p class="mt-2"><strong>Observaciones:</strong> {{ $movimiento->observaciones }}</p>
                                                    @endif

                                                    @if($movimiento->usuario_creador)
                                                        <small class="text-muted">
                                                            <i class="fas fa-user"></i> {{ $movimiento->usuario_creador }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Acciones --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-cog"></i> Acciones</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($bien->estado === 'activo')
                                    <a href="{{ route('patrimonio.bienes.edit', $bien->id) }}"
                                        class="btn btn-warning btn-block mb-2">
                                        <i class="fas fa-edit"></i> Editar Bien
                                    </a>

                                    <a href="{{ route('patrimonio.bienes.traslado', $bien->id) }}"
                                        class="btn btn-success btn-block mb-2">
                                        <i class="fas fa-exchange-alt"></i> Trasladar
                                    </a>

                                    <hr>

                                    <a href="{{ route('patrimonio.bienes.baja', $bien->id) }}"
                                        class="btn btn-danger btn-block mb-2">
                                        <i class="fas fa-times-circle"></i> Dar de Baja
                                    </a>
                                @endif

                                <a href="{{ route('patrimonio.bienes.index') }}"
                                    class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>

                                @if($bien->estado !== 'baja')
                                    <hr>
                                    <form action="{{ route('patrimonio.bienes.destroy', $bien->id) }}"
                                            method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-block"
                                                onclick="return confirm('¿Está seguro de eliminar este bien? Esta acción eliminará también todo su historial.')">
                                            <i class="fas fa-trash"></i> Eliminar Bien
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Resumen --}}
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-bar"></i> Resumen</h4>
                        </div>
                        <div class="card-body">
                            <div class="summary-item">
                                <h6>Total de Movimientos</h6>
                                <h3 class="text-primary">{{ $bien->movimientos->count() }}</h3>
                            </div>

                            <div class="summary-item">
                                <h6>Altas</h6>
                                <h4 class="text-success">{{ $bien->movimientos->where('tipo_movimiento', 'alta')->count() }}</h4>
                            </div>

                            <div class="summary-item">
                                <h6>Traslados</h6>
                                <h4 class="text-info">{{ $bien->movimientos->where('tipo_movimiento', 'traslado')->count() }}</h4>
                            </div>

                            @php
                                $bajas = $bien->movimientos->filter(function($m) {
                                    return str_starts_with($m->tipo_movimiento, 'baja');
                                })->count();
                            @endphp

                            @if($bajas > 0)
                                <div class="summary-item">
                                    <h6>Bajas</h6>
                                    <h4 class="text-danger">{{ $bajas }}</h4>
                                </div>
                            @endif
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
    .form-control-static {
        padding: 7px 0;
        margin: 0;
    }

    .badge-lg {
        font-size: 14px;
        padding: 8px 12px;
    }

    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 45px;
        padding-bottom: 30px;
        border-left: 2px solid #dee2e6;
        margin-left: 10px;
    }

    .timeline-item:last-child {
        border-left: none;
    }

    .timeline-marker {
        position: absolute;
        left: -11px;
        top: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 3px solid #fff;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .timeline-title {
        margin: 0;
        font-weight: 600;
    }

    .summary-item {
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .summary-item h6 {
        margin-bottom: 5px;
        color: #6c757d;
    }
</style>
@endpush
