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
        <span class="badge bg-{{ $badgeClass }}">{{ $eventoCecoco->tipo_servicio }}</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3">Datos del evento</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th width="40%">Nº Expediente:</th>
                            <td>{{ $eventoCecoco->nro_expediente }}</td>
                        </tr>
                        <tr>
                            <th>Fecha/Hora:</th>
                            <td>{{ $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @if($eventoCecoco->fecha_cierre)
                        <tr>
                            <th>Fecha Cierre:</th>
                            <td>{{ $eventoCecoco->fecha_cierre->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Tipo Servicio:</th>
                            <td>{{ $eventoCecoco->tipo_servicio }}</td>
                        </tr>
                        <tr>
                            <th>Período:</th>
                            <td>{{ $eventoCecoco->periodo }}</td>
                        </tr>
                        <tr>
                            <th>Año:</th>
                            <td>{{ $eventoCecoco->anio }}</td>
                        </tr>
                        <tr>
                            <th>Mes:</th>
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
            <div class="col-md-6">
                <h5 class="mb-3">Operación y contacto</h5>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th width="40%">Operador:</th>
                            <td>{{ $eventoCecoco->operador ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Box:</th>
                            <td>{{ $eventoCecoco->box ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Teléfono:</th>
                            <td>
                                @if($eventoCecoco->telefono)
                                    <a href="tel:{{ $eventoCecoco->telefono }}">{{ $eventoCecoco->telefono }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dirección:</th>
                            <td>{{ $eventoCecoco->direccion ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3">Descripción completa</h5>
        <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; white-space: pre-wrap; font-family: monospace; font-size: 14px;">
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
