@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Terminales</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="">
                                @can('crear-equipo')
                                    <a class="btn btn-success" href="{{ route('equipos.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $equipos->total() }}</label>
                            </div>

                            <form action="{{ route('equipos.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el TEI o ISSI que desea buscar" value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">TEI</th>
                                        <th style="color:#fff;">Tipo/Marca/Modelo</th>
                                        <th style="color:#fff;">ISSI</th>
                                        <th style="color: #fff">Estado</th>
                                        <th style="color: #fff">Obs.</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($equipos) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                        @foreach ($equipos as $equipo)
                                            @include('equipos.modal.detalle')
                                            @include('equipos.modal.borrar')
                                            {{-- @include('equipos.modal.editar') --}}
                                            <tr>
                                                <td style="display: none;">{{ $equipo->id }}</td>
                                                <td><a class="btn btn-dark"
                                                    href="{{ route('verHistoricoDesdeEquipo', $equipo->id) }}" target="_blank">{{ $equipo->tei }}</a>
                                                </td>
                                                <td><img alt="" width="70px" id="myImg"
                                                    src="{{ asset($equipo->tipo_terminal->imagen) }}"
                                                    class="img-fluid img-thumbnail">
                                                        {{ $equipo->tipo_terminal->tipo_uso->uso . ' / ' . $equipo->tipo_terminal->marca . ' / ' . $equipo->tipo_terminal->modelo }}
                                                </td>
                                                <td>{{ $equipo->issi }}</td>
                                                <td>{{ $equipo->estado->nombre }}</td>
                                                <td>{{ $equipo->observaciones }}</td>
                                                <td>
                                                    <form action="{{ route('equipos.destroy', $equipo->id) }}"
                                                        method="POST">

                                                        {{--<a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $equipo->id }}">Editar</a>--}}

                                                        <a class="btn btn-warning" href="#" data-toggle="modal"
                                                            data-target="#ModalDetalle{{ $equipo->id }}"><i class="fas fa-eye"></i></a>

                                                        @can('editar-equipo')
                                                            <a class="btn btn-info"
                                                                href="{{ route('equipos.edit', $equipo->id) }}"><i class="fas fa-edit"></i></a>
                                                        @endcan

                                                        @can('borrar-equipo')
                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $equipo->id }}"><i
                                                                class="far fa-trash-alt"></i></a>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {!! $equipos->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
