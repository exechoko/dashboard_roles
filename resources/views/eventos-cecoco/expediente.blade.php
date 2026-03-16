@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ !empty($filtros) ? route('cecoco.index', $filtros) : route('cecoco.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    <a href="{{ route('cecoco.show', $eventoCecoco) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-outline-primary">
        <i class="bi bi-file-text"></i> Ver resumen
    </a>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
            <i class="bi bi-file-earmark-text"></i> Expediente Completo N° {{ $detalle['nro_expediente'] ?? $eventoCecoco->nro_expediente }}
        </h4>
        @php
            $tipoLower = strtolower($detalle['tipo_servicio'] ?? $eventoCecoco->tipo_servicio ?? '');
            $badgeClass = 'primary';
            
            if(str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego') ||
               str_contains($tipoLower, 'herido con arma') || str_contains($tipoLower, 'persona armada') ||
               str_contains($tipoLower, 'persona fallecida') || str_contains($tipoLower, 'abuso de arma') ||
               str_contains($tipoLower, 'violencia de genero con detenidos') || str_contains($tipoLower, 'tentativa de suicidio') ||
               str_contains($tipoLower, 'persona ajena en los fondos') || str_contains($tipoLower, 'solicitud de ambulancia') ||
               str_contains($tipoLower, 'accidente de transito con fallecido') || str_contains($tipoLower, 'accidente de transito con lesionados')) {
                $badgeClass = 'danger';
            }
            elseif(str_contains($tipoLower, 'accidente') || str_contains($tipoLower, 'amenazas') ||
                    str_contains($tipoLower, 'alarma activada') || str_contains($tipoLower, 'persona extraviada') ||
                    str_contains($tipoLower, 'persona tirada en la via publica') || str_contains($tipoLower, 'lesiones') ||
                    str_contains($tipoLower, 'violacion de domicilio') || str_contains($tipoLower, 'violencia de genero') ||
                    str_contains($tipoLower, 'tentativa de arrebato') || str_contains($tipoLower, 'tentativa de hurto') ||
                    str_contains($tipoLower, 'tentativa de robo') || str_contains($tipoLower, 'tentativa de estafa') ||
                    str_contains($tipoLower, 'hurto') || str_contains($tipoLower, 'robo') ||
                    str_contains($tipoLower, 'arrebato') || str_contains($tipoLower, 'estafa') ||
                    str_contains($tipoLower, 'usurpacion') || str_contains($tipoLower, 'sustraccion') ||
                    str_contains($tipoLower, 'detencion') || str_contains($tipoLower, 'secuestro de elementos') ||
                    str_contains($tipoLower, 'derrame quimicos') || str_contains($tipoLower, 'ebrios')) {
                $badgeClass = 'warning';
            }
            elseif(str_contains($tipoLower, 'aviso') || str_contains($tipoLower, 'animales sueltos') ||
                    str_contains($tipoLower, 'daños') || str_contains($tipoLower, 'ruidos molestos') ||
                    str_contains($tipoLower, 'elementos abandonados') || str_contains($tipoLower, 'cuidacoches') ||
                    str_contains($tipoLower, 'problemas entre vecinos') || str_contains($tipoLower, 'problemas familiares') ||
                    str_contains($tipoLower, 'maltrato animal') || str_contains($tipoLower, 'pedido de captura') ||
                    str_contains($tipoLower, 'pedido de localizacion') || str_contains($tipoLower, 'persona en actitud sospechosa') ||
                    str_contains($tipoLower, 'allanamiento') || str_contains($tipoLower, 'corte de calle') ||
                    str_contains($tipoLower, 'desorden en la via publica') || str_contains($tipoLower, 'delitos contra la honestidad') ||
                    str_contains($tipoLower, 'portacion de arma blanca') || str_contains($tipoLower, 'tiroteo') ||
                    str_contains($tipoLower, 'inclemencias climaticas')) {
                $badgeClass = 'info';
            }
            elseif(str_contains($tipoLower, 'colaboracion') || str_contains($tipoLower, 'informa datos') ||
                    str_contains($tipoLower, 'llamada falsa') || str_contains($tipoLower, 'broma') ||
                    str_contains($tipoLower, 'no responde') || str_contains($tipoLower, 'reiteracion de llamada') ||
                    str_contains($tipoLower, 'equivocado') || str_contains($tipoLower, 'insulto') ||
                    str_contains($tipoLower, 'correcta identificacion') || str_contains($tipoLower, 'recepcion sospechosa') ||
                    str_contains($tipoLower, 'servicio bancario')) {
                $badgeClass = 'secondary';
            }
            elseif(str_contains($tipoLower, 'consulta') || str_contains($tipoLower, 'psicologico')) {
                $badgeClass = 'success';
            }
        @endphp
        <span class="badge badge-{{ $badgeClass }}">{{ $detalle['tipo_servicio'] ?? $eventoCecoco->tipo_servicio }}</span>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="mb-3"><i class="bi bi-info-circle"></i> Información General</h5>
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr>
                            <th width="40%" class="table-secondary">Nº Expediente:</th>
                            <td><strong>{{ $detalle['nro_expediente'] ?? $eventoCecoco->nro_expediente }}</strong></td>
                        </tr>
                        <tr>
                            <th class="table-secondary">Fecha Inicio:</th>
                            <td>{{ $detalle['fecha_inicio'] ?? ($eventoCecoco->fecha_hora ? $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') : '-') }}</td>
                        </tr>
                        @if(!empty($detalle['fecha_cierre']))
                        <tr>
                            <th class="table-secondary">Fecha Cierre:</th>
                            <td>{{ $detalle['fecha_cierre'] }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th class="table-secondary">Tipo Servicio:</th>
                            <td>{{ $detalle['tipo_servicio'] ?? $eventoCecoco->tipo_servicio }}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary">Operador Inicial:</th>
                            <td>{{ $detalle['operador_inicial'] ?? $eventoCecoco->operador ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Ubicación y Contacto</h5>
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr>
                            <th width="40%" class="table-secondary">Dirección:</th>
                            <td class="text-break">{{ $detalle['direccion'] ?? $eventoCecoco->direccion ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary">Teléfono:</th>
                            <td>
                                @if(!empty($detalle['telefono']) || $eventoCecoco->telefono)
                                    <a href="tel:{{ $detalle['telefono'] ?? $eventoCecoco->telefono }}">
                                        {{ $detalle['telefono'] ?? $eventoCecoco->telefono }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="table-secondary">Total Eventos:</th>
                            <td><strong class="text-primary">{{ count($detalle['timeline'] ?? []) }}</strong> registros</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($detalle['descripcion_inicial']))
        <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-chat-left-text"></i> Descripción Inicial</h5>
            <div class="p-3 border rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 14px; background-color: var(--bs-secondary-bg);">{{ $detalle['descripcion_inicial'] }}</div>
        </div>
        @endif

        <hr class="my-4">

        <h5 class="mb-4"><i class="bi bi-clock-history"></i> Línea de Tiempo del Expediente</h5>

        @if(!empty($detalle['timeline']) && count($detalle['timeline']) > 0)
            <div class="timeline-container">
                @foreach($detalle['timeline'] as $index => $evento)
                    <div class="timeline-item mb-4">
                        <div class="row">
                            <div class="col-md-2 text-md-end">
                                <div class="timeline-badge">
                                    <span class="badge bg-primary">{{ $index + 1 }}</span>
                                </div>
                                <div class="timeline-time mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        <strong>{{ $evento['fecha_hora'] ?? '-' }}</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="row g-2 mb-2">
                                            @if(!empty($evento['operador']))
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <strong>Operador:</strong> {{ $evento['operador'] }}
                                                </small>
                                            </div>
                                            @endif
                                            @if(!empty($evento['recurso']))
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="bi bi-truck"></i> <strong>Recurso:</strong> {{ $evento['recurso'] }}
                                                </small>
                                            </div>
                                            @endif
                                            @if(!empty($evento['estado']))
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="bi bi-flag"></i> <strong>Estado:</strong> 
                                                    <span class="badge bg-secondary">{{ $evento['estado'] }}</span>
                                                </small>
                                            </div>
                                            @endif
                                        </div>
                                        @if(!empty($evento['descripcion']))
                                        <div class="mt-2 p-2 rounded" style="background-color: var(--bs-light); border-left: 3px solid var(--bs-primary);">
                                            <small style="white-space: pre-wrap;">{{ $evento['descripcion'] }}</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No se encontraron eventos en la línea de tiempo del expediente.
            </div>
        @endif
    </div>
    <div class="card-footer text-muted">
        <small>
            <i class="bi bi-database"></i> 
            Datos obtenidos desde sistema CECOCO en tiempo real
        </small>
    </div>
</div>

<style>
.timeline-container {
    position: relative;
}

.timeline-item {
    position: relative;
}

.timeline-badge {
    display: inline-block;
}

@media (min-width: 768px) {
    .timeline-container::before {
        content: '';
        position: absolute;
        left: calc(16.666% + 10px);
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #dee2e6 0%, #dee2e6 100%);
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: calc(16.666% + 1px);
        top: 30px;
        width: 20px;
        height: 2px;
        background: #dee2e6;
    }
}

.timeline-time {
    font-size: 0.85rem;
}

.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}
</style>
@endsection
