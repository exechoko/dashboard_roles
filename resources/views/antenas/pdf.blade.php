<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Antenas (SBS)</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #000; }
        .rpt-header { border-bottom: 3px solid #0d3b66; padding-bottom: 10px; margin-bottom: 16px; }
        .rpt-header h1 { font-size: 17px; margin: 0 0 4px; color: #0d3b66; }
        .rpt-header .rpt-sub { font-size: 11px; color: #555; }
        table.rpt-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 10px; }
        table.rpt-table th, table.rpt-table td { border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        table.rpt-table th:first-child, table.rpt-table td:first-child { border-left: 1px solid #ccc; }
        table.rpt-table thead th { border-top: 1px solid #ccc; }
        table.rpt-table th { background: #0d3b66; color: #fff; }
        .badge-activa { color: #0e6655; font-weight: bold; }
        .badge-inactiva { color: #922b21; font-weight: bold; }
        .rpt-footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>

<div class="rpt-header">
    <h1>Listado de Antenas (SBS)</h1>
    <div class="rpt-sub">
        {{ $antenas->count() }} registros
        @if($texto) &mdash; buscando "{{ $texto }}" @endif
        &nbsp;&middot;&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<table class="rpt-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Localidad</th>
            <th>Ubicación</th>
            <th>Lat / Long</th>
            <th>Altura</th>
            <th>Estado</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($antenas as $antena)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $antena->nombre }}</td>
                <td>{{ $antena->localidad ?? '-' }}</td>
                <td>{{ $antena->ubicacion ?? '-' }}</td>
                <td>{{ $antena->latitud ?? '-' }} / {{ $antena->longitud ?? '-' }}</td>
                <td>{{ $antena->altura ?? '-' }}</td>
                <td class="{{ $antena->activa ? 'badge-activa' : 'badge-inactiva' }}">
                    {{ $antena->activa ? 'Activa' : 'Inactiva' }}
                </td>
                <td>{!! $antena->observaciones ? nl2br(e($antena->observaciones)) : '-' !!}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="rpt-footer">
    Reporte generado desde C.A.R. 911{{ auth()->user() ? ' por ' . auth()->user()->name : '' }}.
</div>

</body>
</html>
