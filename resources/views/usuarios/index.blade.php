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
                                        <th style="color: #fff;">Foto</th>
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
                                                <td>
                                                    <img alt="{{ $usuario->name }}"
                                                         width="30px"
                                                         class="img-fluid img-thumbnail zoom-img"
                                                         src="{{ $usuario->photo ? asset($usuario->photo) : asset('img/user.png') }}"
                                                         style="cursor: pointer;">
                                                </td>
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

    <!-- Modal para zoom de imagen -->
    <div id="imageModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" style="max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>

    <style>
        .zoom-img:hover {
            opacity: 0.8;
            transform: scale(1.1);
            transition: all 0.3s ease;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Al hacer clic en cualquier imagen con clase zoom-img
            $('.zoom-img').on('click', function() {
                var imgSrc = $(this).attr('src');
                $('#modalImage').attr('src', imgSrc);
                $('#imageModal').modal('show');
            });
        });
    </script>
@endsection
