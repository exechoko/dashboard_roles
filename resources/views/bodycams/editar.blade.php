@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Bodycam</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-dark alert-dismissible fade show" role="alert">
                                    <strong>¡Revise los campos!</strong>
                                    @foreach ($errors->all() as $error)
                                        <span class="badge badge-danger">{{ $error }}</span>
                                    @endforeach
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('bodycams.update', $bodycam->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="codigo">Código *</label>
                                            <input type="text" name="codigo" class="form-control"
                                                value="{{ $bodycam->codigo }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="numero_serie">Número de Serie *</label>
                                            <input type="text" name="numero_serie" class="form-control"
                                                value="{{ $bodycam->numero_serie }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="imei">IMEI</label>
                                            <input type="text" name="imei" class="form-control"
                                                value="{{ $bodycam->imei }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="marca">Marca *</label>
                                            <input type="text" name="marca" class="form-control"
                                                value="{{ $bodycam->marca }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="modelo">Modelo *</label>
                                            <input type="text" name="modelo" class="form-control"
                                                value="{{ $bodycam->modelo }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="numero_tarjeta_sd">Número Tarjeta SD</label>
                                            <input type="text" name="numero_tarjeta_sd" class="form-control"
                                                value="{{ $bodycam->numero_tarjeta_sd }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="numero_bateria">Número Batería</label>
                                            <input type="text" name="numero_bateria" class="form-control"
                                                value="{{ $bodycam->numero_bateria }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="estado">Estado *</label>
                                            <select name="estado" class="form-control" required>
                                                <option value="">Seleccione estado</option>
                                                <option value="disponible" {{ $bodycam->estado == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                                <option value="entregada" {{ $bodycam->estado == 'entregada' ? 'selected' : '' }}>Entregada</option>
                                                <option value="perdida" {{ $bodycam->estado == 'perdida' ? 'selected' : '' }}>
                                                    Perdida</option>
                                                <option value="mantenimiento" {{ $bodycam->estado == 'mantenimiento' ? 'selected' : '' }}>En Mantenimiento</option>
                                                <option value="dada_baja" {{ $bodycam->estado == 'dada_baja' ? 'selected' : '' }}>Dada de Baja</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_adquisicion">Fecha de Adquisición</label>
                                            <input type="date" name="fecha_adquisicion" class="form-control"
                                                value="{{ $bodycam->fecha_adquisicion ? $bodycam->fecha_adquisicion->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones"
                                                style="height: 100px">{{ $bodycam->observaciones }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <button type="submit" class="btn btn-primary">Actualizar</button>
                                    <a href="{{ route('bodycams.index') }}" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
