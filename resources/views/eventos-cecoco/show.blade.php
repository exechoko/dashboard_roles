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
            if(str_contains($tipoLower, 'llamada falsa')) {
                $badgeClass = 'secondary';
            } elseif(str_contains($tipoLower, 'incendio') || str_contains($tipoLower, 'fuego')) {
                $badgeClass = 'danger';
            } elseif(str_contains($tipoLower, 'accidente')) {
                $badgeClass = 'warning';
            } elseif(str_contains($tipoLower, 'personas') || str_contains($tipoLower, 'rescate')) {
                $badgeClass = 'info';
            } elseif(str_contains($tipoLower, 'broma')) {
                $badgeClass = 'dark';
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
