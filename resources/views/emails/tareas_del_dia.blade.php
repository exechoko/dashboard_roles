<div style="font-family: Arial, sans-serif; font-size: 14px; color: #222;">
    <h2 style="margin: 0 0 10px 0;">Tareas del día {{ $fecha->format('d/m/Y') }}</h2>

    <p style="margin: 0 0 12px 0;">Hay {{ $items->count() }} tarea(s) pendiente/en proceso para hoy.</p>

    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th align="left">Tarea</th>
                <th align="left">Estado</th>
                <th align="left">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->tarea->nombre ?? ('ID tarea: ' . $item->tarea_id) }}</strong>
                        <div style="color:#666; font-size: 12px;">Programada: {{ optional($item->fecha_programada)->format('d/m/Y') }}</div>
                    </td>
                    <td>{{ \App\Models\TareaItem::ESTADOS[$item->estado] ?? $item->estado }}</td>
                    <td>{{ $item->observaciones ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin: 12px 0 0 0;">
        Ver en el sistema:
        <a href="{{ route('tareas.index', ['vista' => 'todas', 'fecha_programada_desde' => $fecha->toDateString(), 'fecha_programada_hasta' => $fecha->toDateString()]) }}">
            Abrir listado de tareas
        </a>
    </p>
</div>
