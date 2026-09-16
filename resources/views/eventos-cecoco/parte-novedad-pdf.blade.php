<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Parte de novedad general &mdash; Expediente {{ $eventoCecoco->nro_expediente }}</title>
    <style>
        @page { margin: 12mm 15mm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; color: #000; margin: 0; }

        .pn-logo { width: 165px; height: 60px; margin-bottom: 2px; }

        .pn-titulo { font-size: 18px; color: #e8951b; margin: 4px 0 10px; }

        table.pn-grid { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        table.pn-grid td { border: 1px solid #d7dde1; padding: 4px 10px; vertical-align: top; }

        .pn-expediente-bar { background: #2f7fc1; color: #fff; font-weight: bold; font-size: 13px; padding: 6px 10px; }

        .pn-label { background: #e9edf0; font-weight: bold; width: 22%; color: #333; text-align: right; }
        .pn-valor { width: 28%; text-align: left; }
        .pn-full-label { background: #e9edf0; font-weight: bold; width: 15%; color: #333; text-align: right; }
        .pn-full-valor { width: 85%; white-space: pre-wrap; text-align: left; }

        table.pn-tabla { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.pn-tabla th { background: #2f7fc1; color: #fff; padding: 5px 8px; font-size: 11px; text-align: left; }
        table.pn-tabla td { border: 1px solid #d7dde1; padding: 4px 8px; font-size: 11px; }
        table.pn-tabla tr:nth-child(odd) td { background: #eef1f3; }

        .pn-footer-line { border-top: 2px solid #e8951b; margin-top: 14px; }
        table.pn-footer { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.pn-footer td { border: none; padding: 0; font-size: 10px; color: #555; vertical-align: middle; }
        .pn-footer-fecha { width: 33%; }
        .pn-footer-titulo { color: #2f7fc1; }
        .pn-footer-pagina { width: 33%; text-align: right; }
    </style>
</head>
<body>

@php
    $historial = $detalle['historial'] ?? [];
    $cierre = $detalle['cierre'] ?? [];

    $limpiar = function ($valor) {
        if ($valor === null) {
            return '';
        }
        $valor = preg_replace('/\x{00A0}|\xC2\xA0/u', ' ', (string) $valor);
        return trim((string) $valor);
    };

    $fechaCreacion = $limpiar($detalle['fecha_hora_inicial'] ?? null) ?: ($eventoCecoco->fecha_hora ? $eventoCecoco->fecha_hora->format('d/m/Y H:i:s') : '');
    $operador = $limpiar($detalle['operador_inicial'] ?? null) ?: $limpiar($eventoCecoco->operador ?? null);
    $tipoServicio = $limpiar($detalle['tipo_servicio'] ?? null) ?: $limpiar($eventoCecoco->tipo_servicio ?? null);
    $direccion = $limpiar($detalle['direccion'] ?? null) ?: $limpiar($eventoCecoco->direccion ?? null);
    $numeroLlamante = $limpiar($detalle['telefono'] ?? null) ?: $limpiar($eventoCecoco->telefono ?? null);
    $descripcion = $limpiar($detalle['descripcion_inicial'] ?? null);

    $sector = $limpiar($historial['sector'] ?? null);
    $estado = $limpiar($historial['estado'] ?? null);
    $puesto = $limpiar($historial['puesto'] ?? null) ?: $limpiar($eventoCecoco->box ?? null);
    $servidor = $limpiar($historial['servidor'] ?? null);
    $llamante = $limpiar($historial['llamante_nombre'] ?? null);

    $fechaCierre = $limpiar($cierre['fecha'] ?? null);
    $tipoCierre = $limpiar($cierre['tipo'] ?? null);
    $observacionesCierre = $limpiar($cierre['observaciones'] ?? null);
@endphp

{{-- Logo CeCoCo reconstruido en SVG (no se dispone del archivo de imagen original) --}}
<img class="pn-logo" src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="440" height="160" viewBox="0 0 440 160">
    <text x="10" y="95" font-family="Arial, Helvetica, sans-serif" font-weight="bold" font-size="80" letter-spacing="-2" fill="#5c6b73">CeCoCo</text>
    <text x="6" y="91" font-family="Arial, Helvetica, sans-serif" font-weight="bold" font-size="80" letter-spacing="-2" fill="#ffffff" stroke="#8a949a" stroke-width="1.5" paint-order="stroke fill">CeCoCo</text>
    <path d="M0,128 C50,108 90,144 150,120 C210,96 260,132 320,112 C360,98 400,110 440,102 L440,150 L0,150 Z" fill="#2f7fc1"/>
    <path d="M0,124 C50,106 90,138 150,116 C210,94 260,126 320,108 C360,96 400,106 440,99" fill="none" stroke="#7fb3dd" stroke-width="3"/>
</svg>') }}" alt="CeCoCo">
<div class="pn-titulo">Parte de novedad general</div>

<table class="pn-grid">
    <tr>
        <td class="pn-expediente-bar" colspan="4">Expediente:{{ $eventoCecoco->nro_expediente }}</td>
    </tr>
    <tr>
        <td class="pn-label">Fecha de creaci&oacute;n</td>
        <td class="pn-valor">{{ $fechaCreacion }}</td>
        <td class="pn-label">Fecha de cierre</td>
        <td class="pn-valor">{{ $fechaCierre }}</td>
    </tr>
    <tr>
        <td class="pn-label">Sector</td>
        <td class="pn-valor">{{ $sector }}</td>
        <td class="pn-label">Tipo de cierre</td>
        <td class="pn-valor">{{ $tipoCierre }}</td>
    </tr>
    <tr>
        <td class="pn-label">Operador</td>
        <td class="pn-valor">{{ $operador }}</td>
        <td class="pn-label">Tipo de servicio</td>
        <td class="pn-valor">{{ $tipoServicio }}</td>
    </tr>
    <tr>
        <td class="pn-label">Estado</td>
        <td class="pn-valor">{{ $estado }}</td>
        <td class="pn-label">Prioridad</td>
        <td class="pn-valor"></td>
    </tr>
    <tr>
        <td class="pn-label">N&uacute;mero del llamante</td>
        <td class="pn-valor">{{ $numeroLlamante }}</td>
        <td class="pn-label">Puesto:</td>
        <td class="pn-valor">{{ $puesto }}</td>
    </tr>
    <tr>
        <td class="pn-label">Llamante</td>
        <td class="pn-valor">{{ $llamante }}</td>
        <td class="pn-label">Servidor</td>
        <td class="pn-valor">{{ $servidor }}</td>
    </tr>
    <tr>
        <td class="pn-full-label">Direcci&oacute;n</td>
        <td class="pn-full-valor" colspan="3">{{ $direccion }}</td>
    </tr>
    <tr>
        <td class="pn-full-label">Descripci&oacute;n</td>
        <td class="pn-full-valor" colspan="3">{{ $descripcion }}</td>
    </tr>
    <tr>
        <td class="pn-full-label">Observaciones de cierre</td>
        <td class="pn-full-valor" colspan="3">{{ $observacionesCierre }}</td>
    </tr>
</table>

<table class="pn-tabla">
    <thead>
        <tr>
            <th>Unidad</th>
            <th>H. Asig.</th>
            <th>H. Sal.</th>
            <th>H. Llegada</th>
            <th>H. F. Atenci&oacute;n</th>
            <th>H. Desasig.</th>
            <th>H. Inv&aacute;lido</th>
        </tr>
    </thead>
    <tbody>
        @foreach($detalle['tramites'] ?? [] as $t)
            <tr>
                <td>{{ $t['unidad'] ?? '' }}</td>
                <td>{{ $t['h_asig'] ?? '' }}</td>
                <td>{{ $t['h_sal'] ?? '' }}</td>
                <td>{{ $t['h_llegada'] ?? '' }}</td>
                <td>{{ $t['h_f_atenci_on'] ?? $t['h_f_atencion'] ?? '' }}</td>
                <td>{{ $t['h_desasig'] ?? '' }}</td>
                <td>{{ $t['h_inv_alido'] ?? $t['h_invalido'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="pn-footer-line"></div>
<table class="pn-footer">
    <tr>
        <td class="pn-footer-fecha">{{ now()->format('d/m/Y H:i:s') }}</td>
        <td class="pn-footer-titulo">Parte de novedad general</td>
        <td class="pn-footer-pagina">1 / 1</td>
    </tr>
</table>

</body>
</html>
