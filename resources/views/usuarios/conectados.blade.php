@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Usuarios - Conectados</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="">
                                <label class="alert alert-dark mb-0" style="float: right;">Conectados ahora:
                                    {{ $usuarios->count() }}</label>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="color: #fff;">Foto</th>
                                        <th style="color: #fff;">Nombre y Apellido</th>
                                        <th style="color: #fff;">E-mail</th>
                                        <th style="color: #fff;">Rol</th>
                                        <th style="color: #fff;">Última actividad</th>
                                        <th style="color: #fff;">Sección</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($usuarios as $usuario)
                                            <tr>
                                                <td>
                                                    <img alt="{{ $usuario->name }}"
                                                         width="30px"
                                                         class="img-fluid img-thumbnail"
                                                         src="{{ $usuario->photo ? asset($usuario->photo) : asset('img/user.png') }}">
                                                </td>
                                                <td>
                                                    <span class="badge" style="background-color: #2eb85c; color: #fff; font-size: 0.65em;">
                                                        <i class="fas fa-circle"></i> En línea
                                                    </span>
                                                    {{ $usuario->name . ' ' . $usuario->apellido }}
                                                </td>
                                                <td>{{ $usuario->email }}</td>
                                                <td>
                                                    @foreach ($usuario->getRoleNames() as $rolName)
                                                        <span class="badge" style="background-color: {{ $usuario->getRoleColor($rolName) ?? '#28a745' }}; color: white;">
                                                            {{ $rolName }}
                                                        </span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $usuario->visto_en->diffForHumans() }}</td>
                                                <td>/{{ $usuario->ruta_actual }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No hay usuarios conectados en este momento.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
