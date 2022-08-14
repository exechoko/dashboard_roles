@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Terminales</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @can('crear-terminal')
                                <a class="btn btn-success" href="{{ route('terminales.create') }}">Nuevo</a>
                            @endcan

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Uso</th>
                                        <th style="color:#fff;">Marca</th>
                                        <th style="color:#fff;">Modelo</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($terminales as $terminal)
                                            <tr>
                                                <td style="display: none;">{{ $terminal->id }}</td>
                                                <td>{{ $terminal->tipo_uso->uso }}</td>
                                                <td>{{ $terminal->marca }}</td>
                                                <td>{{ $terminal->modelo }}</td>
                                                <td>
                                                    <form action="{{ route('terminales.destroy', $terminal->id) }}"
                                                        method="POST">
                                                        @can('editar-terminal')
                                                            <a class="btn btn-info"
                                                                href="{{ route('terminales.edit', $terminal->id) }}">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-terminal')
                                                            <button type="submit" onclick="return confirm('Está seguro')" class="btn btn-danger">Borrar</button>
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
                                {!! $terminales->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
