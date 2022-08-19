@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Dependencias</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="">Direcciones</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($direcciones as $direccion)
                                            <tr>
                                                <td style="display: none;">{{ $direccion->id }}</td>
                                                <td>{{ $direccion->nombre }}</td>
                                                <td>
                                                    <form action="#"
                                                        method="POST">
                                                        @can('editar-dependencia')
                                                            <a class="btn btn-info"
                                                                href="#">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                        <button type="submit" onclick="return confirm('Está seguro')" class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="">Departamentales</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($departamentales as $departamental)
                                            <tr>
                                                <td style="display: none;">{{ $departamental->id }}</td>
                                                <td>{{ $departamental->nombre }}</td>
                                                <td>
                                                    <form action="#"
                                                        method="POST">
                                                        @can('editar-dependencia')
                                                            <a class="btn btn-info"
                                                                href="#">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                        <button type="submit" onclick="return confirm('Está seguro')" class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            @can('crear-dependencia')
                                <a class="btn btn-success" href="{{ route('dependencias.create') }}">Nuevo</a>
                            @endcan
                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Dependencia</th>
                                        <th style="color:#fff;">Modelo</th>
                                        <th style="color:#fff;">ISSI</th>
                                        <th style="color:#fff;">TEI</th>
                                        <th style="color: #fff">Estado</th>
                                        <th style="color: #fff">Ult. Mod.</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($dependencias as $dependencia)
                                            <tr>
                                                <td style="display: none;">{{ $dependencia->id }}</td>
                                                <td>{{ $dependencia->tipo_terminal->marca }}</td>
                                                <td>{{ $dependencia->tipo_terminal->modelo }}</td>
                                                <td>{{ $dependencia->issi }}</td>
                                                <td>{{ $dependencia->tei }}</td>
                                                <td>{{ $dependencia->estado->nombre }}</td>
                                                <td>{{ \Carbon\Carbon::parse($dependencia->fecha_estado)->format('d-m-Y') }}</td>
                                                <td>
                                                    <form action="{{ route('dependencias.destroy', $dependencia->id) }}"
                                                        method="POST">
                                                        @can('editar-dependencia')
                                                            <a class="btn btn-info"
                                                                href="{{ route('dependencias.edit', $dependencia->id) }}">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                        <button type="submit" onclick="return confirm('Está seguro')" class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
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
