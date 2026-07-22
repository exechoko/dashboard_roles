@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Detalle de Retención</h3>
            <div>
                <a href="{{ route('armas.retenciones.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('crear-arma-retencion')
                    <a href="{{ route('armas.retenciones.documento', $armaRetencion) }}" class="btn btn-secondary">
                        <i class="fas fa-file-word"></i> Generar Acta Word
                    </a>
                @endcan
                @can('editar-arma-retencion')
                    <a href="{{ route('armas.retenciones.edit', $armaRetencion) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                @endcan
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Funcionario</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%">Nombre Completo:</th>
                                    <td>{{ $armaRetencion->personal->nombre_completo }}</td>
                                </tr>
                                <tr>
                                    <th>Jerarquía:</th>
                                    <td>{{ $armaRetencion->personal->jerarquia }}</td>
                                </tr>
                                <tr>
                                    <th>Legajo Policial:</th>
                                    <td>{{ $armaRetencion->personal->lp }}</td>
                                </tr>
                                <tr>
                                    <th>DNI:</th>
                                    <td>{{ $armaRetencion->personal->dni ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Arma</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%">Numeración:</th>
                                    <td>{{ $armaRetencion->arma_numero ?? $armaRetencion->arma?->numero ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td>{{ $armaRetencion->arma_tipo ?? $armaRetencion->arma?->tipo?->nombre ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>N° Chaleco:</th>
                                    <td>
                                        {{ $armaRetencion->chaleco_numero ?? $armaRetencion->chaleco?->numero_serie ?? '-' }}
                                        @if($armaRetencion->chaleco_detalle)
                                            <br><small class="text-muted">{{ $armaRetencion->chaleco_detalle }}</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Marca / Modelo:</th>
                                    <td>{{ $armaRetencion->marca_modelo ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Estado de Conservación:</th>
                                    <td>{{ $armaRetencion->estado_conservacion ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Cargador / Cartuchería:</th>
                                    <td>{{ $armaRetencion->con_cargador ? 'Con' : 'Sin' }} cargador, {{ $armaRetencion->con_cartucheria ? 'con' : 'sin' }} cartuchería</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información de la Retención</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 25%">Tipo:</th>
                                    <td>
                                        <span class="badge badge-{{ $armaRetencion->tipo == 'RETENCIÓN' ? 'warning' : ($armaRetencion->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                            {{ $armaRetencion->tipo_label }}
                                        </span>
                                    </td>
                                    <th style="width: 25%">Motivo:</th>
                                    <td>{{ $armaRetencion->motivo->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de Posesión:</th>
                                    <td>
                                        {{ $armaRetencion->fecha_posesion->format('d/m/Y') }}
                                        @if($armaRetencion->hora_posesion)
                                            {{ \Carbon\Carbon::parse($armaRetencion->hora_posesion)->format('H:i') }}hs
                                        @endif
                                    </td>
                                    <th>Días para Elevación:</th>
                                    <td>
                                        @if($armaRetencion->dias_restantes !== null)
                                            <span class="badge badge-{{ $armaRetencion->dias_restantes <= 5 ? 'danger' : ($armaRetencion->dias_restantes <= 15 ? 'warning' : 'success') }}">
                                                {{ $armaRetencion->dias_restantes }} días
                                            </span>
                                        @else
                                            <span class="text-muted">No aplica</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha de Elevación:</th>
                                    <td>
                                        @if($armaRetencion->fecha_elevacion)
                                            {{ $armaRetencion->fecha_elevacion->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">Pendiente</span>
                                        @endif
                                    </td>
                                    <th>Fecha de Devolución:</th>
                                    <td>
                                        @if($armaRetencion->fecha_devolucion)
                                            {{ $armaRetencion->fecha_devolucion->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado Actual:</th>
                                    <td colspan="3">
                                        <span class="badge badge-{{ $armaRetencion->estado == 'DEVUELTA' ? 'success' : ($armaRetencion->estado == 'EN_JEF_CENTRAL' ? 'info' : 'warning') }}">
                                            {{ $armaRetencion->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ciudad:</th>
                                    <td colspan="3">{{ $armaRetencion->ciudad ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Observaciones:</th>
                                    <td colspan="3">{{ $armaRetencion->observaciones ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @can('editar-arma-retencion')
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Acciones</h4>
                            </div>
                            <div class="card-body">
                                @if(!$armaRetencion->fecha_elevacion && $armaRetencion->estado == 'EN_ARMERIA')
                                    <form action="{{ route('armas.retenciones.elevar', $armaRetencion) }}" method="POST">
                                        @csrf
                                        <div class="form-row align-items-end">
                                            <div class="col-auto">
                                                <label>Fecha de Elevación:</label>
                                                <input type="date" name="fecha_elevacion" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Comentario (opcional):</label>
                                                <input type="text" name="comentario" class="form-control" placeholder="Agregar comentario..." maxlength="500">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-info" onclick="return confirm('¿Confirmar elevación a Jefatura Central?')">
                                                    <i class="fas fa-arrow-up"></i> Elevar a Jef. Central
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif

                                @if(!$armaRetencion->fecha_devolucion && ($armaRetencion->fecha_elevacion || $armaRetencion->estado == 'EN_ARMERIA'))
                                    <form action="{{ route('armas.retenciones.devolver', $armaRetencion) }}" method="POST" class="mt-2">
                                        @csrf
                                        <div class="form-row align-items-end">
                                            <div class="col-auto">
                                                <label>Fecha de Devolución:</label>
                                                <input type="date" name="fecha_devolucion" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Comentario (opcional):</label>
                                                <input type="text" name="comentario" class="form-control" placeholder="Agregar comentario..." maxlength="500">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('¿Confirmar devolución al funcionario?')">
                                                    <i class="fas fa-undo"></i> Devolver al Funcionario
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif

                                @if($armaRetencion->estado == 'DEVUELTA')
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle"></i> El arma ha sido devuelta al funcionario.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Historial Completo</h4>
                        </div>
                        <div class="card-body">
                            @can('crear-arma-retencion')
                                <form action="{{ route('armas.retenciones.comentario', $armaRetencion) }}" method="POST" class="mb-3">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label class="text-muted"><i class="fas fa-comment"></i> Agregar nota o comentario</label>
                                        <textarea name="comentario" class="form-control" rows="2" maxlength="500"
                                                  placeholder="Describa la novedad, observación o detalle relevante..."
                                                  required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> Agregar nota
                                    </button>
                                </form>
                                <hr>
                            @endcan

                            @if($armaRetencion->historial->isNotEmpty())
                                <div class="timeline">
                                    @foreach($armaRetencion->historial as $entry)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-{{ $entry->accion_color }}"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">
                                                    <i class="fas {{ $entry->accion_icon }} text-{{ $entry->accion_color }}"></i>
                                                    <strong>{{ $entry->accion_label }}</strong>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-user"></i> {{ $entry->usuario->name ?? 'Sistema' }}
                                                    &mdash;
                                                    <i class="fas fa-clock"></i> {{ $entry->created_at->format('d/m/Y H:i') }}
                                                </small>
                                                @if($entry->comentario)
                                                    <div class="mt-1 p-2 bg-light rounded">
                                                        <small>{{ $entry->comentario }}</small>
                                                    </div>
                                                @endif
                                                @if($entry->datos_adicionales && isset($entry->datos_adicionales['cambios']))
                                                    <div class="mt-1">
                                                        <small class="text-warning">
                                                            @foreach($entry->datos_adicionales['cambios'] as $cambio)
                                                                <span class="d-block"><i class="fas fa-exchange-alt"></i> {{ $cambio }}</span>
                                                            @endforeach
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p>Sin registros en el historial.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px currentColor;
        }
        .timeline-marker.bg-info { box-shadow: 0 0 0 2px #17a2b8; }
        .timeline-marker.bg-warning { box-shadow: 0 0 0 2px #ffc107; }
        .timeline-marker.bg-primary { box-shadow: 0 0 0 2px #007bff; }
        .timeline-marker.bg-success { box-shadow: 0 0 0 2px #28a745; }
        .timeline-marker.bg-danger { box-shadow: 0 0 0 2px #dc3545; }
        .timeline-marker.bg-secondary { box-shadow: 0 0 0 2px #6c757d; }
    </style>
@endpush
