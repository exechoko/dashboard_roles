@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Vehiculos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @can('crear-vehiculo')
                                <a class="btn btn-success" href="{{ route('vehiculos.create') }}">Nuevo</a>
                            @endcan

                            <form action="{{ route('vehiculos.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese Marca, Modelo o Dominio que desea buscar" value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th class="col-1" style="color:#fff;">Marca</th>
                                        <th class="col-2" style="color:#fff;">Modelo</th>
                                        <th class="col-3" style="color:#fff;">Dominio</th>
                                        <th class="col-4" style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($vehiculos) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                        @foreach ($vehiculos as $vehiculo)
                                            @include('vehiculos.modal.detalle')
                                            @include('vehiculos.modal.borrar')
                                            {{-- @include('equipos.modal.editar') --}}
                                            <tr>
                                                <td style="display: none;">{{ $vehiculo->id }}</td>
                                                <td>{{ $vehiculo->marca }}</td>
                                                <td>{{ $vehiculo->modelo }}</td>
                                                <td>{{ $vehiculo->dominio }}</td>
                                                <td>
                                                    <form action="{{ route('vehiculos.destroy', $vehiculo->id) }}"
                                                        method="POST">

                                                        {{--<a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $equipo->id }}">Editar</a>--}}

                                                        <a class="btn btn-warning" href="#" data-toggle="modal"
                                                            data-target="#ModalDetalle{{ $vehiculo->id }}">Detalles</a>

                                                        @can('editar-vehiculo')
                                                            <a class="btn btn-info"
                                                                href="{{ route('vehiculos.edit', $vehiculo->id) }}">Editar</a>
                                                        @endcan

                                                        @can('borrar-vehiculo')
                                                            <a class="btn btn-danger" href="#" data-toggle="modal"
                                                                data-target="#ModalDelete{{ $vehiculo->id }}">Borrar</a>
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
                                {!! $vehiculos->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                /*"columnDefs": [
                    { "width": "10%", "targets": 0 }, // aquí puedes cambiar los valores de ancho y los objetivos de las columnas
                    { "width": "10%", "targets": 1 },
                    { "width": "10%", "targets": 2 },
                    { "width": "10%", "targets": 2 }
                ],*/
                "paging": false,
                "searching": false,
                "info": false,
                "order": [[0, "asc"]] // aquí puedes especificar las columnas y el tipo de ordenamiento
            });
        });
    </script>
@endsection
