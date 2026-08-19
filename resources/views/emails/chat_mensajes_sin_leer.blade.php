<div style="font-family: Arial, sans-serif; font-size: 14px; color: #222;">
    <h2 style="margin: 0 0 10px 0;">Tenés mensajes sin leer</h2>

    <p style="margin: 0 0 12px 0;">
        Hay {{ $pendientes->count() }} conversación(es) del chat interno con mensajes sin leer hace rato.
    </p>

    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th align="left">Conversación</th>
                <th align="left">Sin leer</th>
                <th align="left">Desde</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendientes as $item)
                <tr>
                    <td>
                        <strong>
                            <a href="{{ route('chat.index', ['conversacion' => $item['conversacion_id']]) }}">
                                {{ $item['nombre'] }}
                            </a>
                        </strong>
                    </td>
                    <td>{{ $item['no_leidos'] }}</td>
                    <td>{{ $item['desde']->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin: 12px 0 0 0;">
        Ver en el sistema:
        <a href="{{ route('chat.index') }}">Abrir el chat</a>
    </p>
</div>
