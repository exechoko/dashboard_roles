<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hist&oacute;rico M&oacute;vil GIS &mdash; {{ $historial->recurso }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #000; }
        .rpt-header { border-bottom: 3px solid #0d3b66; padding-bottom: 10px; margin-bottom: 16px; }
        .rpt-header h1 { font-size: 17px; margin: 0 0 4px; color: #0d3b66; }
        .rpt-header .rpt-sub { font-size: 11px; color: #555; }
        {{-- border-collapse:collapse disparaba un algoritmo de DomPDF muy
             costoso en memoria con tablas grandes (2000+ filas agotaba 512MB
             y volaba el proceso). separate + border-spacing:0 se ve igual
             pero evita ese cálculo. --}}
        table.rpt-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 10px; }
        table.rpt-table th, table.rpt-table td { border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        table.rpt-table th:first-child, table.rpt-table td:first-child { border-left: 1px solid #ccc; }
        table.rpt-table thead th { border-top: 1px solid #ccc; }
        table.rpt-table th { background: #0d3b66; color: #fff; }
        tr.estado-detenido { background: #dbe9ff; }
        tr.estado-movimiento { background: #e2f6e9; }
        .badge-exceso { color: #b91c1c; font-weight: bold; }
        .rpt-footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>

<div class="rpt-header">
    <h1>Hist&oacute;rico M&oacute;vil GIS &mdash; {{ $historial->recurso }}</h1>
    <div class="rpt-sub">
        Per&iacute;odo: {{ $historial->fecha_inicio }} &mdash; {{ $historial->fecha_fin }}
        &nbsp;&middot;&nbsp; {{ count($registros) }} posiciones
        &nbsp;&middot;&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<table class="rpt-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Velocidad</th>
            <th>Direcci&oacute;n</th>
            <th>Estado</th>
            <th>Detenido</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($registros as $reg)
            <tr class="estado-{{ $reg['color_estado'] ?? '' }}">
                <td>{{ $reg['id'] ?? $loop->iteration }}</td>
                <td>{{ $reg['fecha'] ?? '' }}</td>
                <td>
                    {{ $reg['velocidad'] ?? 0 }} km/h
                    @if(!empty($reg['exceso_velocidad']))
                        <span class="badge-exceso">EXCESO</span>
                    @endif
                </td>
                <td>{{ $reg['direccion'] ?? '' }}</td>
                <td>{{ $reg['estado'] ?? '' }}</td>
                <td>{{ $reg['tiempo_detenido'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="rpt-footer">
    Reporte generado desde la app m&oacute;vil de C.A.R. 911{{ auth()->user() ? ' por ' . auth()->user()->name : '' }}.
</div>

</body>
</html>
