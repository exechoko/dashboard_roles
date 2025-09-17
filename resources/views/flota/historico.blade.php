@extends('layouts.app')

@section('css')
<style>
    #cabecera {
        background: #FFFBB9;
        border: 2px solid #0a3fee;
        padding: 10px;
    }

    .logo {
        width: 200px;
        height: 200px;
        border: 2px solid #ee930a;
        margin: none;
    }

    /* Estilo para el contenedor del botón */
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .print-btn-container {
        margin-left: auto;
        /* Empuja el botón a la derecha */
    }
</style>
@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <div class="header-container">
                <h3 class="page__heading">Historico</h3>
                @if ($desdeEquipo == false)
                    <div class="print-btn-container">
                        <a href="{{ route('flota.historico.imprimir', $flota->id) }}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-lg-12">
                                @if ($desdeEquipo == true)
                                    <img class="mr-5" src="{{ asset($flota->tipo_terminal->imagen) }}"
                                        style="float: left; width: 150px;">
                                    <ul>
                                        <li>
                                            <h3>TEI: <b>{{ $flota->tei }}</b>
                                                @if (!is_null($flota->issi))
                                                            - ISSI: <b>{{ $flota->issi }}</b>
                                                    </li>
                                                @else
                                                    - ISSI: <b>Sin asignar</b></h3>
                                                    </li>
                                                @endif
                                        <li>
                                            <h4>Marca: <b>{{ $flota->tipo_terminal->marca }}</b> - Modelo:
                                                <b>{{ $flota->tipo_terminal->modelo }}</b>
                                            </h4>
                                        </li>
                                        <li>
                                            <h4>Estado: <b>{{ $flota->estado->nombre }}</b></h4>
                                        </li>
                                    </ul>
                                @else
                                    <img class="mr-5" src="{{ asset($flota->equipo->tipo_terminal->imagen) }}"
                                        style="float: left; width: 150px;">
                                    <ul>
                                        <li>
                                            <h3>TEI: <b>{{ $flota->equipo->tei }}</b>
                                                @if (!is_null($flota->equipo->issi))
                                                            - ISSI: <b>{{ $flota->equipo->issi }}</b>
                                                    </li>
                                                @else
                                                    - ISSI: <b>Sin asignar</b></h3>
                                                    </li>
                                                @endif
                                        <li>
                                            <h4>Marca: <b>{{ $flota->equipo->tipo_terminal->marca }}</b> - Modelo:
                                                <b>{{ $flota->equipo->tipo_terminal->modelo }}</b>
                                            </h4>
                                        </li>
                                        <li>
                                            <h4>Estado: <b>{{ $flota->equipo->estado->nombre }}</b></h4>
                                        </li>
                                    </ul>
                                @endif
                            </div>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-hover mt-2 display">
                                    <thead style="background: linear-gradient(45deg,#888888, #5f5e63)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Movimiento</th>
                                        <th style="color:#fff;">Fecha de asignación</th>
                                        <th style="color:#fff;">Movil/Recurso</th>
                                        <th style="color:#fff;">Actualmente en</th>
                                        <th style="color:#fff;">Recurso anterior</th>
                                        <th style="color:#fff;">Ticket PER</th>
                                        <th style="color:#fff;">Observaciones</th>
                                        <th style="color:#fff;">Anexo</th>
                                        @if ($desdeEquipo == false)
                                            @can('editar-historico')
                                                <th style="color:#fff;"></th>
                                            @endcan
                                        @endif
                                    </thead>
                                    <tbody>
                                        @foreach ($hist as $h)
                                            @include('flota.modal.editar_historico', ['h' => $h])
                                        @endforeach
                                        @foreach ($hist as $h)
                                            <tr>
                                                <td style="display: none;">{{ $h->id }}</td>
                                                @if (is_null($h->tipoMovimiento))
                                                    <td>-</td>
                                                @else
                                                    <td>
                                                        <span class="badge" style="background-color: {{ $h->tipoMovimiento->color ?? '#28a745' }}; color: white;">
                                                            {{ $h->tipoMovimiento->nombre }}
                                                        </span>
                                                    </td>
                                                @endif
                                                <td>{{ Carbon\Carbon::parse($h->fecha_asignacion)->format('d/m/Y H:i') }}
                                                </td>
                                                @if (is_null($h->recurso_asignado))
                                                    <td>-</td>
                                                @else
                                                    <td>{{ $h->recurso_asignado . ($h->vehiculo_asignado ? ' - Dom.: ' . $h->vehiculo_asignado : '') }}
                                                    </td>
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
                                                    <td>{{ $h->recurso_desasignado . ($h->vehiculo_desasignado ? ' - Dom.: ' . $h->vehiculo_desasignado : '') }}
                                                    </td>
                                                @endif
                                                <td>{{ $h->ticket_per }}</td>
                                                <td>{{ $h->observaciones }}</td>
                                                <td>
                                                    @if (!empty($h->rutas_imagenes))
                                                        <div style="display: flex; flex-wrap: wrap; align-items: center;">
                                                            @foreach ($h->rutas_imagenes as $ruta)
                                                                @if (strpos($ruta, '.jpg') !== false || strpos($ruta, '.png') !== false || strpos($ruta, '.jpeg') !== false)
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <img src="{{ asset($ruta) }}" alt="Miniatura"
                                                                            style="width: 25px; height: auto; margin-right: 5px;">
                                                                    </a>
                                                                @elseif (strpos($ruta, '.pdf') !== false)
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <i class="fas fa-file-pdf"
                                                                            style="font-size: 24px; color: #e74c3c; margin-right: 5px;"
                                                                            title="PDF"></i>
                                                                    </a>
                                                                @elseif (strpos($ruta, '.doc') !== false || strpos($ruta, '.docx') !== false)
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <i class="fas fa-file-word"
                                                                            style="font-size: 24px; color: #007aff; margin-right: 5px;"
                                                                            title="Word Document"></i>
                                                                    </a>
                                                                @elseif (strpos($ruta, '.xlsx') !== false)
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <i class="fas fa-file-excel"
                                                                            style="font-size: 24px; color: #28a745; margin-right: 5px;"
                                                                            title="Excel Spreadsheet"></i>
                                                                    </a>
                                                                @elseif (strpos($ruta, '.zip') !== false || strpos($ruta, '.rar') !== false)
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <i class="fas fa-file-archive"
                                                                            style="font-size: 24px; color: #6f42c1; margin-right: 5px;"
                                                                            title="Compressed File"></i>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                @if ($desdeEquipo == false)
                                                    @can('editar-historico')
                                                        <td>
                                                            <form action="#" method="POST">
                                                                <a class="btn btn-info" href="#" data-toggle="modal"
                                                                    data-target="#ModalEditar{{ $h->id }}">Editar</a>
                                                            </form>
                                                        </td>
                                                    @endcan
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="pagination justify-content-end">
                                {{-- !! $flota->links() !! --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
