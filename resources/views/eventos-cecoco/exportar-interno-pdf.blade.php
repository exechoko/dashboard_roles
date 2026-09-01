@extends('layouts.print')

@section('content')
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; }
    .rpt-header { border-bottom: 3px solid #0d3b66; padding-bottom: 12px; margin-bottom: 20px; }
    .rpt-header h1 { font-size: 19px; margin: 0 0 6px; color: #0d3b66; }
    .rpt-header .rpt-sub { font-size: 12px; color: #555; }
    .rpt-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        color: #fff;
        background: #0d3b66;
        vertical-align: middle;
    }
    .rpt-section { margin-bottom: 22px; page-break-inside: avoid; }
    .rpt-section h2 {
        font-size: 14px;
        color: #0d3b66;
        border-bottom: 1px solid #ccc;
        padding-bottom: 4px;
        margin-bottom: 8px;
    }
    .rpt-grid { display: flex; gap: 20px; }
    .rpt-grid > div { flex: 1; }
    table.rpt-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 0; }
    table.rpt-table th, table.rpt-table td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
    table.rpt-table th { background: #f0f2f5; width: 34%; color: #000; }
    table.rpt-table thead tr { background: #0d3b66; }
    .rpt-text-block {
        border: 1px solid #ddd;
        background: #f8f9fa;
        padding: 10px;
        white-space: pre-wrap;
        font-size: 12px;
        border-radius: 4px;
    }
    .rpt-footer { margin-top: 30px; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
</style>

@php
    $nroExpediente = str_replace('Expediente: ', '', $detalle['nro_expediente'] ?? $eventoCecoco->nro_expediente);
    $tipoServicio = $detalle['tipo_servicio'] ?: ($eventoCecoco->tipo_servicio ?? '-');
    $cierre = $detalle['cierre'] ?? [];
@endphp

<div class="rpt-header">
    <h1>Reporte de Evento CeCoCo &mdash; Expediente N&deg; {{ $nroExpediente }}</h1>
    <div class="rpt-sub">
        Generado el {{ now()->format('d/m/Y H:i') }}{{ auth()->user() ? ' por ' . auth()->user()->name : '' }}
        &nbsp;&middot;&nbsp; <span class="rpt-badge">{{ $tipoServicio }}</span>
    </div>
</div>

<div class="rpt-section">
    <h2>Datos generales</h2>
    <div class="rpt-grid">
        <div>
            <table class="rpt-table">
                <tbody>
                    <tr>
                        <th>Fecha creaci&oacute;n</th>
                        <td>{{ $detalle['fecha_hora_inicial'] ?: ($eventoCecoco->fecha_hora ? $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') : '-') }}</td>
                    </tr>
                    <tr>
                        <th>Operador</th>
                        <td>{{ $detalle['operador_inicial'] ?: ($eventoCecoco->operador ?? '-') }}</td>
                    </tr>
                    <tr>
                        <th>Tel&eacute;fono</th>
                        <td>{{ $detalle['telefono'] ?: ($eventoCecoco->telefono ?? '-') }}</td>
                    </tr>
                    <tr>
                        <th>Direcci&oacute;n</th>
                        <td>{{ $detalle['direccion'] ?: ($eventoCecoco->direccion ?? '-') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <table class="rpt-table">
                <tbody>
                    @if(!empty($detalle['historial']['puesto']) || $eventoCecoco->box)
                        <tr>
                            <th>Puesto</th>
                            <td>{{ $detalle['historial']['puesto'] ?? $eventoCecoco->box }}</td>
                        </tr>
                    @endif
                    @if(!empty($detalle['historial']['barrio']))
                        <tr><th>Barrio</th><td>{{ $detalle['historial']['barrio'] }}</td></tr>
                    @endif
                    @if(!empty($detalle['historial']['jurisdiccion']))
                        <tr><th>Jurisdicci&oacute;n</th><td>{{ $detalle['historial']['jurisdiccion'] }}</td></tr>
                    @endif
                    @if(!empty($detalle['historial']['estado']))
                        <tr><th>Estado</th><td>{{ $detalle['historial']['estado'] }}</td></tr>
                    @endif
                    @if(!empty($detalle['historial']['municipio']))
                        <tr><th>Municipio</th><td>{{ $detalle['historial']['municipio'] }}</td></tr>
                    @endif
                    @if(!empty($detalle['historial']['sector']))
                        <tr><th>Sector</th><td>{{ $detalle['historial']['sector'] }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(!empty($detalle['descripcion_inicial']))
    <div class="rpt-section">
        <h2>Descripci&oacute;n</h2>
        <div class="rpt-text-block">{{ $detalle['descripcion_inicial'] }}</div>
    </div>
@endif

@if(!empty(array_filter($cierre)))
    <div class="rpt-section">
        <h2>Datos del cierre</h2>
        <table class="rpt-table" style="margin-bottom: 8px;">
            <tbody>
                @if(!empty($cierre['fecha']))
                    <tr><th>Fecha de cierre</th><td>{{ $cierre['fecha'] }}</td></tr>
                @endif
                @if(!empty($cierre['tipo']))
                    <tr><th>Tipo de cierre</th><td>{{ $cierre['tipo'] }}</td></tr>
                @endif
            </tbody>
        </table>
        <div class="rpt-text-block">{{ $cierre['observaciones'] ?: 'Sin observaciones de cierre.' }}</div>
    </div>
@endif

@if(!empty($detalle['tramites']))
    <div class="rpt-section">
        <h2>Recursos que intervinieron ({{ $detalle['total_tramites'] ?? count($detalle['tramites']) }})</h2>
        <table class="rpt-table">
            <thead>
                <tr>
                    <th style="color:#fff;">Unidad</th>
                    <th style="color:#fff;">Asignaci&oacute;n</th>
                    <th style="color:#fff;">Salida</th>
                    <th style="color:#fff;">Llegada</th>
                    <th style="color:#fff;">Fin atenci&oacute;n</th>
                    <th style="color:#fff;">Desasignaci&oacute;n</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalle['tramites'] as $t)
                    <tr>
                        <td>{{ ($t['unidad'] ?? $t['tr_amites'] ?? '') ?: '-' }}</td>
                        <td>{{ ($t['h_asig'] ?? '') ?: '-' }}</td>
                        <td>{{ ($t['h_sal'] ?? '') ?: '-' }}</td>
                        <td>{{ ($t['h_llegada'] ?? '') ?: '-' }}</td>
                        <td>{{ ($t['h_f_atenci_on'] ?? $t['h_f_atencion'] ?? '') ?: '-' }}</td>
                        <td>{{ ($t['h_desasig'] ?? '') ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($detalle['timeline']))
    <div class="rpt-section">
        <h2>Cronolog&iacute;a de acciones ({{ $detalle['total_eventos'] ?? count($detalle['timeline']) }})</h2>
        <table class="rpt-table">
            <thead>
                <tr>
                    <th style="width:130px; color:#fff;">Fecha - Hora</th>
                    <th style="width:150px; color:#fff;">Operador</th>
                    <th style="color:#fff;">Acci&oacute;n</th>
                    <th style="width:160px; color:#fff;">Caracter&iacute;sticas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalle['timeline'] as $ev)
                    <tr>
                        <td>{{ $ev['fecha_hora'] ?? '-' }}</td>
                        <td>{{ $ev['operador'] ?? '-' }}</td>
                        <td>{{ \App\Helpers\CecocoAccionTraductor::traducir($ev['descripcion'] ?? '') }}</td>
                        <td>{{ $ev['estado'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="rpt-footer">
    Reporte generado autom&aacute;ticamente desde el sistema de gesti&oacute;n &mdash; datos obtenidos de CECOCO.
</div>
@endsection
