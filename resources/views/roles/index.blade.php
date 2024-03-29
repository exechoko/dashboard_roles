@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Usuarios - Roles</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="">
                                @can('crear-rol')
                                    <a class="btn btn-success" href="{{ route('roles.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $roles->total() }}</label>
                            </div>

                            <table class="table table-striped mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="color:#fff;">Rol</th>
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $role)
                                        <tr>
                                            <td>{{ $role->name }}</td>
                                            <td>
                                                @can('editar-rol')
                                                    <a class="btn btn-primary" href="{{ route('roles.edit', $role->id) }}">Editar</a>
                                                @endcan

                                                @can('borrar-rol')
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['roles.destroy', $role->id], 'style' => 'display:inline']) !!}
                                                        {!! Form::submit('Borrar', ['class' => 'btn btn-danger']) !!}
                                                    {!! Form::close() !!}
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div>
                                {!! $roles->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
