@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Cámaras - Administración</h3>
            <div>
                <div class="stats-labels" style="float: right;">
                    <label class="alert alert-dark" for="">Cámaras: {{ $totalCam }} / Canales: {{ $cantidadCanales }}
                    </label>
                    <label class="alert alert-info ml-5" for="">Fijas: {{ $fijas }}</label>
                    <label class="alert alert-warning" for="">Fijas FR: {{ $fijasFR }}</label>
                    <label class="alert alert-danger" for="">Fijas LPR: {{ $fijasLPR }}</label>
                    <label class="alert alert-success" for="">Domos: {{ $domos }}</label>
                    <label class="alert alert-primary" for="">Domos Duales: {{ $domosDuales }}</label>
                    <label class="alert alert-info" for="">BDE (Totem): {{ $bde }}</label>
                </div>
            </div>
        </div>
        <div class="section-body">

            @can('crear-camara')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="">
                                    <a class="btn btn-success" href="{{ route('camaras.create') }}">Nuevo</a>
                                    <label class="alert alert-secondary mb-0" style="float: right; color: black;">Registros:
                                        {{ $camaras->total() }}</label>
                                </div>
                                <form method="POST" action="{{ route('camaras.import') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="input-group mt-4">
                                        <input type="file" name="excel_file" accept=".xlsx,.xls">
                                        <button type="submit" class="btn btn-danger">Importar</button>
                                    </div>
                                </form>
                                <div class="text-right">
                                    <form action="{{ route('camaras.export') }}" method="GET" style="display: inline;">
                                        <button type="submit" class="btn btn-primary">Exportar Listado Cámaras</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('camaras.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Ingrese el sitio"
                                        value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Tipo</th>
                                        <th style="color:#fff;">Nombre</th>
                                        <th style="color:#fff;">Modelo</th>
                                        @can('ver-camara')
                                            <th style="color:#fff;">Acciones</th>
                                        @endcan

                                    </thead>
                                    <tbody>
                                        @if (count($camaras) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($camaras as $camara)
                                                @include('camaras.modal.detalle')
                                                @include('camaras.modal.borrar')
                                                {{-- @include('equipos.modal.editar') --}}
                                                <tr>
                                                    <td style="display: none;">{{ $camara->id }}</td>
                                                    <td><img alt="" width="70px" id="myImg"
                                                            src="{{ asset($camara->tipoCamara->imagen) }}"
                                                            class="img-fluid img-thumbnail">
                                                        {{ $camara->tipoCamara->tipo }}
                                                    </td>

                                                    <td>{{ $camara->nombre }}</td>
                                                    <td>{{ $camara->modelo }}</td>
                                                    <td>
                                                        {{-- Formulario de reinicio --}}
                                                        @can('reiniciar-camara')
                                                            <form action="{{ route('camaras.reiniciar', $camara->id) }}" method="POST"
                                                                style="display:inline;">
                                                                @csrf
                                                                <button class="btn btn-secondary" title="Reiniciar"
                                                                    onclick="return confirm('¿Seguro que desea reiniciar la cámara?')">
                                                                    <i class="fas fa-sync-alt"></i>
                                                                </button>
                                                            </form>
                                                        @endcan

                                                        {{-- Botón de detalles --}}
                                                        @can('ver-camara')
                                                            <a class="btn btn-warning" href="#" data-toggle="modal"
                                                                data-target="#ModalDetalle{{ $camara->id }}" title="Detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endcan

                                                        {{-- Botón de editar --}}
                                                        @can('editar-camara')
                                                            <a class="btn btn-info" href="{{ route('camaras.edit', $camara->id) }}"
                                                                title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        @endcan

                                                        @can('borrar-camara')
                                                        <form action="{{ route('camaras.destroy', $camara->id) }}" method="POST"
                                                            style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-danger"
                                                                onclick="return confirm('¿Seguro que desea borrar esta cámara?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                        @endcan
                                                    </td>

                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {!! $camaras->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Script para abrir nueva pestaña cuando se reinicia cámara --}}
    @if(session('open_url'))
        <script>
            // Abrir nueva pestaña con la URL de reinicio
            window.open('{{ session('open_url') }}', '_blank');
        </script>
    @endif
@endsection
