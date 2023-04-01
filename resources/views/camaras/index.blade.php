@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Camaras</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <a class="btn btn-success" href="{{ route('camaras.create') }}">Nuevo</a>

                            <form action="{{ route('camaras.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el sitio"
                                        value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">IP</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Sitio</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($camaras) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($camaras as $camara)
                                                @include('camaras.modal.detalle')
                                                @include('camaras.modal.borrar')
                                                {{-- @include('equipos.modal.editar') --}}
                                                <tr>
                                                    <td style="display: none;">{{ $camara->id }}</td>
                                                    <td>{{ $camara->ip }}</td>
                                                    <td>{{ $camara->nombre }}</td>
                                                    <td>{{ $camara->sitio }}</td>
                                                    <td>
                                                        <form action="{{ route('camaras.destroy', $camara->id) }}"
                                                            method="POST">

                                                            {{-- <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $equipo->id }}">Editar</a> --}}

                                                            <a class="btn btn-warning" href="#" data-toggle="modal"
                                                                data-target="#ModalDetalle{{ $camara->id }}">Detalles</a>


                                                            <a class="btn btn-info"
                                                                href="{{ route('camaras.edit', $camara->id) }}">Editar</a>



                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $camara->id }}">Borrar</a>

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
                                {!! $camaras->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
