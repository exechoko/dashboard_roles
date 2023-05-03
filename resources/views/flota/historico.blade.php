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
    </style>

@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Historico</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-lg-12">
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
                            </div>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-bordered mt-2">
                                    <thead style="background: linear-gradient(45deg,#888888, #5f5e63)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Movimiento</th>
                                        <th style="color:#fff;">Fecha de asignación</th>
                                        <th style="color:#fff;">Movil/Recurso</th>
                                        <th style="color:#fff;">Actualmente en</th>
                                        <th style="color:#fff;">Recurso anterior</th>
                                        <th style="color:#fff;">Vehiculo anterior</th>
                                        <th style="color:#fff;">Ticket PER</th>
                                        <th style="color:#fff;">Observaciones</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($hist as $h)
                                            @include('flota.modal.editar_historico')
                                            <tr>
                                                <td style="display: none;">{{ $h->id }}</td>
                                                @if (is_null($h->tipoMovimiento))
                                                    <td>-</td>
                                                @else
                                                    <td>{{ $h->tipoMovimiento->nombre }}</td>
                                                @endif
                                                <td>{{ $h->fecha_asignacion }}</td>
                                                @if (is_null($h->recurso))
                                                    <td>-</td>
                                                @else
                                                    <td>{{ $h->recurso->nombre }}</td>
                                                @endif
                                                <td>{{ $h->destino->nombre }}</td>
                                                <td>
                                                    @if (!is_null($h->recurso_desasignado))
                                                        {{ $h->recurso_desasignado }}
                                                    @else
                                                    -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (!is_null($h->vehiculo_desasignado))
                                                        {{ $h->vehiculo_desasignado }}
                                                    @else
                                                    -
                                                    @endif
                                                </td>
                                                <td>{{ $h->ticket_per }}</td>
                                                <td>{{ $h->observaciones }}</td>

                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- @can('editar-historico') --}}
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-info" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $h->id }}">Editar</a>
                                                        {{-- @endcan --}}
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
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
