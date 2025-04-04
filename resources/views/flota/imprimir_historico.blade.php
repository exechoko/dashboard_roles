@extends('layouts.print')

@section('content')
    <div class="container">
        <div class="print-header mb-4">
            <img src="{{ asset($desdeEquipo ? $flota->tipo_terminal->imagen : $flota->equipo->tipo_terminal->imagen) }}"
                 style="float: left; width: 100px; margin-right: 20px;">
            <div>
                <h2>Histórico del Equipo</h2>
                <ul style="list-style: none; padding-left: 0;">
                    <li><strong>TEI:</strong> {{ $desdeEquipo ? $flota->tei : $flota->equipo->tei }}</li>
                    <li><strong>ISSI:</strong> {{ $desdeEquipo ? ($flota->issi ?? 'Sin asignar') : ($flota->equipo->issi ?? 'Sin asignar') }}</li>
                    <li><strong>Marca:</strong> {{ $desdeEquipo ? $flota->tipo_terminal->marca : $flota->equipo->tipo_terminal->marca }}</li>
                    <li><strong>Modelo:</strong> {{ $desdeEquipo ? $flota->tipo_terminal->modelo : $flota->equipo->tipo_terminal->modelo }}</li>
                    <li><strong>Estado:</strong> {{ $desdeEquipo ? $flota->estado->nombre : $flota->equipo->estado->nombre }}</li>
                </ul>
            </div>
            <div style="clear: both;"></div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr style="background-color: #d6d6d6; color: rgb(0, 0, 0);">
                    <th>Movimiento</th>
                    <th>Fecha de asignación</th>
                    <th>Movil/Recurso</th>
                    <th>Actualmente en</th>
                    <th>Recurso anterior</th>
                    <th>Ticket PER</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hist as $h)
                    <tr>
                        @if (is_null($h->tipoMovimiento))
                            <td>-</td>
                        @else
                            <td>{{ $h->tipoMovimiento->nombre }}</td>
                        @endif
                        <td>{{ Carbon\Carbon::parse($h->fecha_asignacion)->format('d/m/Y H:i') }}</td>
                        @if (is_null($h->recurso_asignado))
                            <td>-</td>
                        @else
                            <td>{{ $h->recurso_asignado . ($h->vehiculo_asignado ? ' - Dom.: ' . $h->vehiculo_asignado : '') }}</td>
                        @endif
                        <td>
                            @if ($h->destino)
                                {{ $h->destino->nombre }}
                            @else
                                -
                            @endif
                        </td>
                        @if (is_null($h->recurso_desasignado))
                            <td>-</td>
                        @else
                            <td>{{ $h->recurso_desasignado . ($h->vehiculo_desasignado ? ' - Dom.: ' . $h->vehiculo_desasignado : '') }}</td>
                        @endif
                        <td>{{ $h->ticket_per }}</td>
                        <td>{{ $h->observaciones }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="print-footer mt-4">
            <p>Impreso el: {{ now()->format('d/m/Y H:i') }} - Usuario: {{ Auth::user()->name }}</p>
        </div>
    </div>
@endsection
