@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Usuarios - Administración</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="">
                                @can('crear-usuario')
                                    <a class="btn btn-success" href="{{ route('usuarios.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $usuarios->total() }}</label>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color: #fff;">Nombre y Apellido</th>
                                        <th style="color: #fff;">L.P.</th>
                                        <th style="color: #fff;">E-mail</th>
                                        <th style="color: #fff;">Rol</th>
                                        <th style="color: #fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($usuarios as $usuario)
                                            <tr>
                                                <td style="display: none;">{{ $usuario->id }}</td>
                                                <td>{{ $usuario->name.' '.$usuario->apellido }}</td>
                                                <td>{{ $usuario->lp }}</td>
                                                <td>{{ $usuario->email }}</td>
                                                <td>
                                                    @if (!empty($usuario->getRoleNames()))
                                                        @foreach ($usuario->getRoleNames() as $rolName)
                                                            <h5>
                                                                <span class="badge" style="background-color: {{ $usuario->getRoleColor($rolName) ?? '#28a745' }}; color: white;">
                                                                    {{ $rolName }}
                                                                </span>
                                                            </h5>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td>
                                                    <a class="btn btn-info" href="{{ route('usuarios.edit', $usuario->id) }}">Editar</a>
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['usuarios.destroy', $usuario->id], 'style' => 'display:inline']) !!}
                                                        {!! Form::submit('Borrar', ['class'=> 'btn btn-danger']) !!}
                                                    {!! Form::close() !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>


                            <div class="pagination justify-content-end">
                                {!! $usuarios->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

