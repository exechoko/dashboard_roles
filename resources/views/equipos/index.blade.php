@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @can('crear-equipo')
                                <a class="btn btn-success" href="{{ route('equipos.create') }}">Nuevo</a>
                            @endcan

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background-color:#5468fb">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Marca</th>
                                        <th style="color:#fff;">Modelo</th>
                                        <th style="color:#fff;">ISSI</th>
                                        <th style="color:#fff;">TEI</th>
                                        <th style="color: #fff">Estado</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($equipos as $equipo)
                                            <tr>
                                                <td style="display: none;">{{ $equipo->id }}</td>
                                                <td>{{ $equipo->tipo_terminal->marca }}</td>
                                                <td>{{ $equipo->tipo_terminal->modelo }}</td>
                                                <td>{{ $equipo->issi }}</td>
                                                <td>{{ $equipo->tei }}</td>
                                                <td>{{ $equipo->estado->nombre }}</td>
                                                <td>
                                                    <form action="{{ route('equipos.destroy', $equipo->id) }}"
                                                        method="POST">
                                                        @can('editar-equipo')
                                                            <a class="btn btn-info"
                                                                href="{{ route('equipos.edit', $equipo->id) }}">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-equipo')
                                                            <button type="submit" class="btn btn-danger">Borrar</button>
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
                                {!! $equipos->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
