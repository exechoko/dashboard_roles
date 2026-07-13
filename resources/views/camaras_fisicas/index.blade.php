@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Cámaras Físicas</h3>
        </div>
        <div class="section-body">
            @can('crear-camara')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="{{ route('camaras-fisicas.import') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="input-group mt-4">
                                        <input type="file" name="excel_file" accept=".xlsx,.xls">
                                        <button type="submit" class="btn btn-danger">Importar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('camaras_fisicas.index') }}" method="get" onsubmit="return showLoad()">
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
                                        <th style="color:#fff;">Tipo</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Observaciones</th>
                                        @can('ver-camara')
                                            <th style="color:#fff;">Acciones</th>
                                        @endcan

                                    </thead>
                                    <tbody>
                                        @if (count($camaras) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($camaras as $camara)
                                                @include('camaras.modal.detalle')
                                                {{-- @include('equipos.modal.editar') --}}
                                                <tr>
                                                    <td style="display: none;">{{ $camara->id }}</td>
                                                    <td><img alt="" width="70px" id="myImg"
                                                            src="{{ asset($camara->tipoCamara->imagen) }}"
                                                            class="img-fluid img-thumbnail">
                                                        {{ $camara->tipoCamara->tipo }}
                                                    </td>

                                                    <td>{{ $camara->nombre }}</td>
                                                    <td>{{ $camara->observaciones }}</td>
                                                    <td>
                                                        <div>
                                                            @can('ver-camara')
                                                                <a class="btn btn-warning" href="#" data-toggle="modal"
                                                                    data-target="#ModalDetalle{{ $camara->id }}">Detalles</a>
                                                            @endcan
                                                        </div>
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
