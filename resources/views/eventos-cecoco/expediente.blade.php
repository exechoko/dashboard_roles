@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <a href="{{ !empty($filtros) ? route('cecoco.index', $filtros) : route('cecoco.index') }}"
            class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
        <a href="{{ route('cecoco.show', $eventoCecoco) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
            class="btn btn-outline-primary">
            <i class="bi bi-file-text"></i> Ver resumen
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-file-earmark-text"></i> Expediente Completo N°
                {{ str_replace('Expediente: ', '', $detalle['nro_expediente'] ?? $eventoCecoco->nro_expediente) }}
            </h4>
            @php
                $tipoLower = strtolower($detalle['tipo_servicio'] ?: ($eventoCecoco->tipo_servicio ?? ''));
                $badgeClass = 'primary';

                if (
                    str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego') ||
                    str_contains($tipoLower, 'herido con arma') || str_contains($tipoLower, 'persona armada') ||
                    str_contains($tipoLower, 'persona fallecida') || str_contains($tipoLower, 'abuso de arma') ||
                    str_contains($tipoLower, 'violencia de genero con detenidos') || str_contains($tipoLower, 'tentativa de suicidio') ||
                    str_contains($tipoLower, 'persona ajena en los fondos') || str_contains($tipoLower, 'solicitud de ambulancia') ||
                    str_contains($tipoLower, 'accidente de transito con fallecido') || str_contains($tipoLower, 'accidente de transito con lesionados')
                ) {
                    $badgeClass = 'danger';
                } elseif (
                    str_contains($tipoLower, 'accidente') || str_contains($tipoLower, 'amenazas') ||
                    str_contains($tipoLower, 'alarma activada') || str_contains($tipoLower, 'persona extraviada') ||
                    str_contains($tipoLower, 'persona tirada en la via publica') || str_contains($tipoLower, 'lesiones') ||
                    str_contains($tipoLower, 'violacion de domicilio') || str_contains($tipoLower, 'violencia de genero') ||
                    str_contains($tipoLower, 'tentativa de arrebato') || str_contains($tipoLower, 'tentativa de hurto') ||
                    str_contains($tipoLower, 'tentativa de robo') || str_contains($tipoLower, 'tentativa de estafa') ||
                    str_contains($tipoLower, 'hurto') || str_contains($tipoLower, 'robo') ||
                    str_contains($tipoLower, 'arrebato') || str_contains($tipoLower, 'estafa') ||
                    str_contains($tipoLower, 'usurpacion') || str_contains($tipoLower, 'sustraccion') ||
                    str_contains($tipoLower, 'detencion') || str_contains($tipoLower, 'secuestro de elementos') ||
                    str_contains($tipoLower, 'derrame quimicos') || str_contains($tipoLower, 'ebrios')
                ) {
                    $badgeClass = 'warning';
                } elseif (
                    str_contains($tipoLower, 'aviso') || str_contains($tipoLower, 'animales sueltos') ||
                    str_contains($tipoLower, 'daños') || str_contains($tipoLower, 'ruidos molestos') ||
                    str_contains($tipoLower, 'elementos abandonados') || str_contains($tipoLower, 'cuidacoches') ||
                    str_contains($tipoLower, 'problemas entre vecinos') || str_contains($tipoLower, 'problemas familiares') ||
                    str_contains($tipoLower, 'maltrato animal') || str_contains($tipoLower, 'pedido de captura') ||
                    str_contains($tipoLower, 'pedido de localizacion') || str_contains($tipoLower, 'persona en actitud sospechosa') ||
                    str_contains($tipoLower, 'allanamiento') || str_contains($tipoLower, 'corte de calle') ||
                    str_contains($tipoLower, 'desorden en la via publica') || str_contains($tipoLower, 'delitos contra la honestidad') ||
                    str_contains($tipoLower, 'portacion de arma blanca') || str_contains($tipoLower, 'tiroteo') ||
                    str_contains($tipoLower, 'inclemencias climaticas')
                ) {
                    $badgeClass = 'info';
                } elseif (
                    str_contains($tipoLower, 'colaboracion') || str_contains($tipoLower, 'informa datos') ||
                    str_contains($tipoLower, 'llamada falsa') || str_contains($tipoLower, 'broma') ||
                    str_contains($tipoLower, 'no responde') || str_contains($tipoLower, 'reiteracion de llamada') ||
                    str_contains($tipoLower, 'equivocado') || str_contains($tipoLower, 'insulto') ||
                    str_contains($tipoLower, 'correcta identificacion') || str_contains($tipoLower, 'recepcion sospechosa') ||
                    str_contains($tipoLower, 'servicio bancario')
                ) {
                    $badgeClass = 'secondary';
                } elseif (str_contains($tipoLower, 'consulta') || str_contains($tipoLower, 'psicologico')) {
                    $badgeClass = 'success';
                }
            @endphp
            <span
                class="badge badge-{{ $badgeClass }}">{{ $detalle['tipo_servicio'] ?: ($eventoCecoco->tipo_servicio ?? '') }}</span>
        </div>

        <div class="card-body">

            {{-- ===== HISTORIAL DE LA INCIDENCIA ===== --}}
            <h5 class="mb-3"><i class="bi bi-file-text"></i> Historial de la Incidencia</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Nº Expediente:</th>
                                <td><strong>{{ str_replace('Expediente: ', '', $detalle['nro_expediente'] ?? $eventoCecoco->nro_expediente) }}</strong>
                                </td>
                            </tr>
                            @if(!empty($detalle['historial']['puesto']) || $eventoCecoco->box)
                                <tr>
                                    <th class="table-secondary">Puesto:</th>
                                    <td>{{ $detalle['historial']['puesto'] ?: $eventoCecoco->box }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th class="table-secondary">Tipo Servicio:</th>
                                <td>{{ $detalle['tipo_servicio'] ?: ($eventoCecoco->tipo_servicio ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Dirección:</th>
                                <td class="text-break">{{ $detalle['direccion'] ?: ($eventoCecoco->direccion ?? '-') }}</td>
                            </tr>
                            @if(!empty($detalle['historial']['barrio']))
                                <tr>
                                    <th class="table-secondary">Barrio:</th>
                                    <td>{{ $detalle['historial']['barrio'] }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Fecha Creación:</th>
                                <td>{{ $detalle['fecha_hora_inicial'] ?: ($eventoCecoco->fecha_hora ? $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') : '-') }}
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Operador:</th>
                                <td>{{ $detalle['operador_inicial'] ?: ($eventoCecoco->operador ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Teléfono:</th>
                                <td>
                                    @php $tel = $detalle['telefono'] ?: ($eventoCecoco->telefono ?? ''); @endphp
                                    @if($tel)
                                        <a href="tel:{{ $tel }}">{{ $tel }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @if(!empty($detalle['historial']['jurisdiccion']))
                                <tr>
                                    <th class="table-secondary">Jurisdicción:</th>
                                    <td>{{ $detalle['historial']['jurisdiccion'] }}</td>
                                </tr>
                            @endif
                            @if(!empty($detalle['historial']['estado']))
                                <tr>
                                    <th class="table-secondary">Estado:</th>
                                    <td><span class="badge bg-info">{{ $detalle['historial']['estado'] }}</span></td>
                                </tr>
                            @endif
                            @if(!empty($detalle['historial']['municipio']))
                                <tr>
                                    <th class="table-secondary">Municipio:</th>
                                    <td>{{ $detalle['historial']['municipio'] }}</td>
                                </tr>
                            @endif
                            @if(!empty($detalle['historial']['sector']))
                                <tr>
                                    <th class="table-secondary">Sector:</th>
                                    <td>{{ $detalle['historial']['sector'] }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($detalle['descripcion_inicial']))
                <div class="mb-3">
                    <h6 class="text-muted"><i class="bi bi-chat-left-text"></i> Descripción</h6>
                    <div class="p-3 border rounded"
                        style="white-space: pre-wrap; font-size: 13px; background-color: var(--bs-secondary-bg);">
                        {{ $detalle['descripcion_inicial'] }}</div>
                </div>
            @endif

            <hr class="my-3">

            {{-- ===== ACCIONES ===== --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Acciones</h5>
                <small class="text-muted">Total: <strong>{{ $detalle['total_eventos'] ?? 0 }}</strong> eventos</small>
            </div>

            @if(!empty($detalle['timeline']) && count($detalle['timeline']) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:155px;">Fecha - Hora</th>
                                <th style="width:185px;">Operador</th>
                                <th>Acción</th>
                                <th style="width:200px;">Características</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalle['timeline'] as $evento)
                                <tr>
                                    <td class="text-nowrap"><small>{{ $evento['fecha_hora'] ?? '-' }}</small></td>
                                    <td><small>{{ $evento['operador'] ?? '-' }}</small></td>
                                    <td><small>{{ $evento['descripcion'] ?? '' }}</small></td>
                                    <td>
                                        @if(!empty($evento['estado']))
                                            <small class="text-muted text-wrap text-start">{{ $evento['estado'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> No se encontraron eventos en el expediente.
                </div>
            @endif

            {{-- ===== TRÁMITES ===== --}}
            @if(!empty($detalle['tramites']) && count($detalle['tramites']) > 0)
                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Trámites / Recursos Asignados</h5>
                    <small class="text-muted">Total: <strong>{{ $detalle['total_tramites'] ?? 0 }}</strong></small>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Unidad</th>
                                <th>H. Asignación</th>
                                <th>H. Salida</th>
                                <th>H. Llegada</th>
                                <th>H. Fin Atención</th>
                                <th>H. Desasignación</th>
                                <th>H. Inválido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalle['tramites'] as $tramite)
                                <tr>
                                    <td><strong>{{ ($tramite['unidad'] ?? $tramite['tr_amites'] ?? '') ?: '-' }}</strong></td>
                                    <td><small>{{ ($tramite['h_asig'] ?? '') ?: '-' }}</small></td>
                                    <td><small>{{ ($tramite['h_sal'] ?? '') ?: '-' }}</small></td>
                                    <td><small>{{ ($tramite['h_llegada'] ?? '') ?: '-' }}</small></td>
                                    <td><small>{{ ($tramite['h_f_atenci_on'] ?? $tramite['h_f_atencion'] ?? '') ?: '-' }}</small>
                                    </td>
                                    <td><small>{{ ($tramite['h_desasig'] ?? '') ?: '-' }}</small></td>
                                    <td><small>{{ ($tramite['h_invalido'] ?? $tramite['h_inv_alido'] ?? '') ?: '-' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
        <div class="card-footer text-muted">
            <small><i class="bi bi-database"></i> Datos obtenidos desde sistema CECOCO en tiempo real</small>
        </div>
    </div>
@endsection