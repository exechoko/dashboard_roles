@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Registro de Eventos CECOCO</h2>
    @can('importar-eventos')
    <a href="{{ route('cecoco.importar') }}" class="btn btn-primary">
        <i class="bi bi-cloud-upload"></i> Importar
    </a>
    @endcan
</div>

@if($eventos !== null)
    <div class="alert alert-light border mb-4">
        <strong>Eventos encontrados:</strong> {{ number_format($totalResultados ?? 0) }}
    </div>
@endif

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

                        {{-- Fila 3: Rango Fecha/Hora --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Desde</label>
                                <input type="datetime-local" name="desde_datetime" class="form-control" value="{{ request('desde_datetime') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Hasta</label>
                                <input type="datetime-local" name="hasta_datetime" class="form-control" value="{{ request('hasta_datetime') }}">
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
                                <a href="{{ route('cecoco.exportar.txt') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" 
                                class="btn btn-outline-success ms-auto">
                                    <i class="bi bi-download"></i> Exportar TXT
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
        @if($eventos === null)
            <div class="alert alert-info text-center py-5">
                <i class="bi bi-funnel" style="font-size: 3rem;"></i>
                <h4 class="mt-3">Selecciona filtros para buscar eventos</h4>
                <p class="mb-0">Por razones de rendimiento, debes aplicar al menos un filtro para ver los resultados.</p>
                <p class="text-muted small">Puedes filtrar por año, mes, tipo de servicio, operador, rango de fechas o búsqueda general.</p>
            </div>
        @elseif($eventos->count() > 0)
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
                                        
                                        // NIVEL 1: CRÍTICO (Rojo - Danger)
                                        if(str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego') ||
                                           str_contains($tipoLower, 'herido con arma') || str_contains($tipoLower, 'persona armada') ||
                                           str_contains($tipoLower, 'persona fallecida') || str_contains($tipoLower, 'abuso de arma') ||
                                           str_contains($tipoLower, 'violencia de genero con detenidos') || str_contains($tipoLower, 'tentativa de suicidio') ||
                                           str_contains($tipoLower, 'persona ajena en los fondos') || str_contains($tipoLower, 'solicitud de ambulancia') ||
                                           str_contains($tipoLower, 'accidente de transito con fallecido') || str_contains($tipoLower, 'accidente de transito con lesionados')) {
                                            $badgeClass = 'danger';
                                        }
                                        // NIVEL 2: URGENTE (Naranja - Warning)
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
                                        // NIVEL 3: IMPORTANTE (Azul - Info)
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
                                        // NIVEL 4: MODERADO (Gris - Secondary)
                                        elseif(str_contains($tipoLower, 'colaboracion') || str_contains($tipoLower, 'informa datos') ||
                                                str_contains($tipoLower, 'llamada falsa') || str_contains($tipoLower, 'broma') ||
                                                str_contains($tipoLower, 'no responde') || str_contains($tipoLower, 'reiteracion de llamada') ||
                                                str_contains($tipoLower, 'equivocado') || str_contains($tipoLower, 'insulto') ||
                                                str_contains($tipoLower, 'correcta identificacion') || str_contains($tipoLower, 'recepcion sospechosa') ||
                                                str_contains($tipoLower, 'servicio bancario')) {
                                            $badgeClass = 'secondary';
                                        }
                                        // NIVEL 5: LEVE (Verde - Success)
                                        elseif(str_contains($tipoLower, 'consulta') || str_contains($tipoLower, 'psicologico')) {
                                            $badgeClass = 'success';
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
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cecoco.show', $evento) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver resumen">
                                            <i class="bi bi-eye"></i><span class="d-none d-lg-inline"> Ver</span>
                                        </a>
                                        @can('ver-expediente-cecoco')
                                        <a href="{{ route('cecoco.expediente', $evento) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" 
                                           class="btn btn-sm btn-outline-success" 
                                           title="Ver detalle completo del expediente">
                                            <i class="bi bi-file-earmark-text"></i><span class="d-none d-lg-inline"> Detalle</span>
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando {{ $eventos->firstItem() }}–{{ $eventos->lastItem() }} de {{ number_format($totalResultados ?? 0) }} registros
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
