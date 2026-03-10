@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Expediente N° {{ $eventoCecoco->nro_expediente }}</h4>
        @php
            $tipoLower = strtolower($eventoCecoco->tipo_servicio ?? '');
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
        <span class="badge badge-{{ $badgeClass }}">{{ $eventoCecoco->tipo_servicio }}</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-lg-6 mb-4 mb-lg-0">
                <h5 class="mb-3">Datos del evento</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Nº Expediente:</th>
                                <td>{{ $eventoCecoco->nro_expediente }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Fecha/Hora:</th>
                                <td>{{ $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @if($eventoCecoco->fecha_cierre)
                            <tr>
                                <th class="table-secondary">Fecha Cierre:</th>
                                <td>{{ $eventoCecoco->fecha_cierre->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="table-secondary">Tipo Servicio:</th>
                                <td>{{ $eventoCecoco->tipo_servicio }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Período:</th>
                                <td>{{ $eventoCecoco->periodo }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Año:</th>
                                <td>{{ $eventoCecoco->anio }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Mes:</th>
                                <td>
                                    @php
                                        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    @endphp
                                    {{ $eventoCecoco->mes ? $mesesNombres[$eventoCecoco->mes] : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <h5 class="mb-3">Operación y contacto</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%" class="table-secondary">Operador:</th>
                                <td>{{ $eventoCecoco->operador ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Box:</th>
                                <td>{{ $eventoCecoco->box ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Teléfono:</th>
                                <td>
                                    @if($eventoCecoco->telefono)
                                        <a href="tel:{{ $eventoCecoco->telefono }}">{{ $eventoCecoco->telefono }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary">Dirección:</th>
                                <td class="text-break">{{ $eventoCecoco->direccion ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3">Descripción completa</h5>
        <div class="p-3 border rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 14px; background-color: var(--bs-secondary-bg); color: var(--bs-body-color);">
            @if($eventoCecoco->descripcion)
                {{ $eventoCecoco->descripcion }}
            @else
                <em class="text-muted">Sin descripción registrada.</em>
            @endif
        </div>
    </div>
    <div class="card-footer text-muted">
        <small>
            <i class="bi bi-file-earmark"></i> 
            Archivo origen: <strong>{{ $eventoCecoco->importacion ? $eventoCecoco->importacion->nombre_archivo_corto : 'N/A' }}</strong>
            @if($eventoCecoco->importacion)
                | Importado el {{ $eventoCecoco->importacion->created_at->format('d/m/Y H:i') }}
            @endif
        </small>
    </div>
</div>
@endsection
