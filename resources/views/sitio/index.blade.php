@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Sitios</h3>
        </div>
        <div class="section-body">

            @can('crear-sitio')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="">
                                    <a class="btn btn-success" href="{{ route('sitios.create') }}">Nuevo</a>
                                    <label class="alert alert-secondary mb-0" style="float: right; color: black;">Registros:
                                        {{ $sitios->total() }}</label>
                                </div>
                                <form method="POST" action="{{ route('sitios.import') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="input-group mt-4">
                                        <input type="file" name="excel_file" accept=".xlsx,.xls">
                                        <button type="submit" class="btn btn-danger">Importar</button>
                                    </div>
                                </form>
                                <div class="text-right">
                                    <form action="{{ route('sitios.export') }}" method="GET" style="display: inline;">
                                        <button type="submit" class="btn btn-primary">Exportar Listado Cámaras</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('sitios.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el sitio"
                                        value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Observaciones</th>
                                        @can('ver-sitio')
                                            <th style="color:#fff;">Acciones</th>
                                        @endcan

                                    </thead>
                                    <tbody>
                                        @if (count($sitios) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($sitios as $sitio)
                                                @include('sitios.modal.detalle')
                                                @include('sitios.modal.borrar')
                                                {{-- @include('equipos.modal.editar') --}}
                                                <tr>
                                                    <td style="display: none;">{{ $sitio->id }}</td>
                                                    <td>{{ $sitio->nombre }}</td>
                                                    <td>{{ $sitio->observaciones }}</td>
                                                    <td>
                                                        <form action="{{ route('sitios.destroy', $sitio->id) }}"
                                                            method="POST">

                                                            {{-- <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $equipo->id }}">Editar</a> --}}

                                                            @can('ver-sitio')
                                                                <a class="btn btn-warning" href="#" data-toggle="modal"
                                                                    data-target="#ModalDetalle{{ $sitio->id }}">Detalles</a>
                                                            @endcan

                                                            @can('editar-sitio')
                                                                <a class="btn btn-info"
                                                                    href="{{ route('sitios.edit', $sitio->id) }}">Editar</a>
                                                            @endcan

                                                            @can('borrar-sitio')
                                                                <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                    data-target="#ModalDelete{{ $sitio->id }}">Borrar</a>
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
                                {!! $sitios->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
