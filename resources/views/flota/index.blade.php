@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Flota</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            <a class="btn btn-success" href="{{ route('flota.create') }}">Nuevo</a>

                            <form action="{{ route('flota.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el nombre del flota que desea buscar" value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">ISSI</th>
                                        <th style="color:#fff;">Movil</th>
                                        <th style="color:#fff;">Dependencia</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($flota) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                        @foreach ($flota as $f)
                                            {{--@include('flota.modal.detalle')--}}
                                            @include('flota.modal.borrar')
                                            {{-- @include('flota.modal.editar') --}}
                                            <tr>
                                                <td style="display: none;">{{ $f->id }}</td>
                                                <td>{{ $f->equipo->issi }}</td>
                                                @if(is_null($f->recurso_id))
                                                    <td>-</td>
                                                @else
                                                    <td>{{ $f->recurso->nombre }}</td>
                                                @endif

                                                <td>{{ $f->destino->nombre . ' ' . $f->destino->dependeDe() }}</td>
                                                <td>
                                                    <form action="{{ route('flota.destroy', $f->id) }}"
                                                        method="POST">

                                                        {{--<a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $flota->id }}">Editar</a>--}}

                                                        <a class="btn btn-warning" href="#" data-toggle="modal"
                                                            data-target="#ModalDetalle{{ $f->id }}">Detalles</a>

                                                        @can('editar-flota')
                                                            <a class="btn btn-info"
                                                                href="{{ route('flota.edit', $f->id) }}">Editar</a>
                                                        @endcan

                                                        @can('borrar-flota')
                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $f->id }}">Borrar</a>
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
                                {{--!! $flota->links() !!--}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
