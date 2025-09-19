@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Bodycams - Administración</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center">
                                @can('crear-bodycam')
                                    <a class="btn btn-success" href="{{ route('bodycams.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $bodycams->total() }}</label>
                            </div>

                            <form action="{{ route('bodycams.index') }}" method="get">
                                <div class="row mt-4">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <input type="text" name="texto" class="form-control"
                                                placeholder="Buscar por código, serie, IMEI, marca o modelo"
                                                value="{{ $texto }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="estado" class="form-control">
                                            <option value="">Todos los estados</option>
                                            @foreach($estados as $key => $valor)
                                                <option value="{{ $key }}" {{ $estado == $key ? 'selected' : '' }}>
                                                    {{ $valor }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-hover mt-3">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="color:#fff;">Código</th>
                                        <th style="color:#fff;">Serie</th>
                                        <th style="color:#fff;">IMEI</th>
                                        <th style="color:#fff;">Marca/Modelo</th>
                                        <th style="color:#fff;">Estado</th>
                                        <th style="color:#fff;">Fecha Adq.</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @if (count($bodycams) <= 0)
                                            <tr>
                                                <td colspan="7">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($bodycams as $bodycam)
                                                @include('bodycams.modal.detalle')
                                                @include('bodycams.modal.borrar')
                                                <tr>
                                                    <td>{{ $bodycam->codigo }}</td>
                                                    <td>{{ $bodycam->numero_serie }}</td>
                                                    <td>{{ $bodycam->imei ?: '-' }}</td>
                                                    <td>{{ $bodycam->marca . ' / ' . $bodycam->modelo }}</td>
                                                    <td>
                                                        @switch($bodycam->estado)
                                                            @case('disponible')
                                                                <span class="badge badge-success">{{ $bodycam->estado_formateado }}</span>
                                                                @break
                                                            @case('entregada')
                                                                <span class="badge badge-warning">{{ $bodycam->estado_formateado }}</span>
                                                                @break
                                                            @case('perdida')
                                                                <span class="badge badge-danger">{{ $bodycam->estado_formateado }}</span>
                                                                @break
                                                            @case('mantenimiento')
                                                                <span class="badge badge-info">{{ $bodycam->estado_formateado }}</span>
                                                                @break
                                                            @case('dada_baja')
                                                                <span class="badge badge-dark">{{ $bodycam->estado_formateado }}</span>
                                                                @break
                                                        @endswitch
                                                    </td>
                                                    <td>{{ $bodycam->fecha_adquisicion ? $bodycam->fecha_adquisicion->format('d/m/Y') : '-' }}</td>
                                                    <td>
                                                        <form action="{{ route('bodycams.destroy', $bodycam->id) }}" method="POST">
                                                            <a class="btn btn-warning btn-sm" href="#" data-toggle="modal"
                                                                data-target="#ModalDetalle{{ $bodycam->id }}"><i class="fas fa-eye"></i></a>

                                                            @can('editar-bodycam')
                                                                <a class="btn btn-info btn-sm"
                                                                    href="{{ route('bodycams.edit', $bodycam->id) }}"><i class="fas fa-edit"></i></a>
                                                            @endcan

                                                            @can('borrar-bodycam')
                                                                @csrf
                                                                @method('DELETE')
                                                                <a class="btn btn-danger btn-sm" href="#" data-toggle="modal"
                                                                    data-target="#ModalDelete{{ $bodycam->id }}"><i
                                                                    class="far fa-trash-alt"></i></a>
                                                            @endcan
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="pagination justify-content-end">
                                {{ $bodycams->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
