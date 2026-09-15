@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Antenas (SBS)</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                @can('crear-antena')
                                    <a class="btn btn-success" href="{{ route('antenas.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $antenas->total() }}</label>
                            </div>

                            <form action="{{ route('antenas.index') }}" method="get">
                                <div class="input-group mb-4">
                                    <input type="text" name="texto" class="form-control"
                                        placeholder="Buscar por nombre o localidad" value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Localidad</th>
                                        <th style="color:#fff;">Ubicación</th>
                                        <th style="color:#fff;">Lat / Long</th>
                                        <th style="color:#fff;">Altura</th>
                                        <th style="color:#fff;">Estado</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($antenas as $antena)
                                            <tr>
                                                <td style="display: none;">{{ $antena->id }}</td>
                                                <td>{{ $antena->nombre }}</td>
                                                <td>{{ $antena->localidad ?? '-' }}</td>
                                                <td>{{ $antena->ubicacion ?? '-' }}</td>
                                                <td>{{ $antena->latitud ?? '-' }} / {{ $antena->longitud ?? '-' }}</td>
                                                <td>{{ $antena->altura ?? '-' }}</td>
                                                <td>
                                                    @if ($antena->activa)
                                                        <span class="badge badge-success">Activa</span>
                                                    @else
                                                        <span class="badge badge-danger">Inactiva</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <form action="{{ route('antenas.destroy', $antena->id) }}"
                                                        method="POST">
                                                        @can('editar-antena')
                                                            <a class="btn btn-info"
                                                                href="{{ route('antenas.edit', $antena->id) }}">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-antena')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {!! $antenas->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
