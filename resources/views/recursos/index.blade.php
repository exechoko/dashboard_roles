@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Recursos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @can('crear-recurso')
                                <a class="btn btn-success" href="{{ route('recursos.create') }}">Nuevo</a>
                            @endcan

                            <form action="{{ route('recursos.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el nombre del recurso que desea buscar" value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Movil</th>
                                        <th style="color:#fff;">Tipo de Vehiculo</th>
                                        <th style="color:#fff;">Destino</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($recursos) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                        @foreach ($recursos as $recurso)
                                            @include('recursos.modal.detalle')
                                            @include('recursos.modal.borrar')
                                            {{-- @include('recursos.modal.editar') --}}
                                            <tr>
                                                <td style="display: none;">{{ $recurso->id }}</td>
                                                <td>{{ $recurso->nombre }}</td>
                                                <td>{{ $recurso->vehiculo->tipo_vehiculo }}</td>
                                                <td>{{ $recurso->destino->nombre }}</td>
                                                <td>
                                                    <form action="{{ route('recursos.destroy', $recurso->id) }}"
                                                        method="POST">

                                                        {{--<a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $recurso->id }}">Editar</a>--}}

                                                        <a class="btn btn-warning" href="#" data-toggle="modal"
                                                            data-target="#ModalDetalle{{ $recurso->id }}">Detalles</a>

                                                        @can('editar-recurso')
                                                            <a class="btn btn-info"
                                                                href="{{ route('recursos.edit', $recurso->id) }}">Editar</a>
                                                        @endcan

                                                        @can('borrar-recurso')
                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $recurso->id }}">Borrar</a>
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
                                {!! $recursos->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection