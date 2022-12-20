@extends('layouts.app')

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
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">ISSI</th>
                                        <th style="color:#fff;">TEI</th>
                                        <th style="color:#fff;">Movil</th>
                                        <th style="color:#fff;">Actualmente en</th>
                                        <th style="color:#fff;">Fecha</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($hist as $h)
                                            <tr>
                                                <td style="display: none;">{{ $h->id }}</td>
                                                <td>{{ $h->equipo->issi }}</td>
                                                <td>{{ $h->equipo->tei }}</td>
                                                @if(is_null($h->recurso))
                                                    <td>-</td>
                                                @else
                                                    <td>{{ $h->recurso->nombre }}</td>
                                                @endif

                                                <td>{{ $h->destino->nombre }}</td>
                                                <td>{{ $h->created_at }}</td>
                                                <td>
                                                    <form action="{{ route('flota.destroy', $h->id) }}"
                                                        method="POST">

                                                        @can('editar-flota')
                                                            <a class="btn btn-info"
                                                                href="{{ route('flota.edit', $h->id) }}">Editar</a>
                                                        @endcan

                                                        @can('borrar-flota')
                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $h->id }}">Borrar</a>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {{--!! $flota->links() !!--}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
