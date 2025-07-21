@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar cámara</h3>
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


                            <form action="{{ route('camaras.update', $camara->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-8">
                                        <div class="form-group">
                                            <label for="nombre">Nombre</label>
                                            <input type="text" name="nombre" class="form-control"
                                                value="{{ $camara->nombre }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="ip">IP</label>
                                            <input type="text" name="ip" class="form-control"
                                                value="{{ $camara->ip }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Sitio</label>
                                            <select name="sitio_id" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                @if (!is_null($camara->sitio_id))
                                                    <option value="{{ $camara->sitio->id }}">
                                                        {{ $camara->sitio->nombre . ' - ' . $camara->sitio->localidad . ' - ' . $camara->sitio->destino->nombre . ' ' . $camara->sitio->destino->dependeDe() }}
                                                    </option>
                                                @else
                                                    <option value="">Seleccionar</option>
                                                @endif
                                                <option value="">Seleccionar</option>
                                                @foreach ($sitios as $s)
                                                    <option value="{{ $s->id }}">
                                                        {{ $s->nombre . ' - ' . $s->localidad . ' - ' . $s->destino->nombre . ' '. $s->destino->dependeDe() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Tipo de cámaras</label>
                                            <select name="tipo_camara_id" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                @if (!is_null($camara->tipo_camara_id))
                                                    <option value="{{ $camara->tipoCamara->id }}">
                                                        {{ $camara->tipoCamara->tipo . ' - ' . $camara->tipoCamara->marca . ' - ' . $camara->tipoCamara->modelo }}
                                                    </option>
                                                @else
                                                    <option value="">Seleccione un tipo de cámara</option>
                                                @endif
                                                @foreach ($tipoCamara as $tipo)
                                                    <option value="{{ $tipo->id }}">
                                                        {{ $tipo->tipo . ' - ' . $tipo->marca . ' - ' . $tipo->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Orientación</label>
                                            <select name="orientacion" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $camara->orientacion }}">{{ $camara->orientacion }}</option>
                                                @foreach ($orientaciones as $orientacion)
                                                    <option value="{{ $orientacion }}">
                                                        {{ $orientacion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="angulo">Ángulo de visión (°)</label>
                                            <input type="text" name="angulo" class="form-control"
                                                value="{{ $camara->angulo }}">
                                        </div>
                                    </div>
                                    <div class=
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="nro_serie">Nro. de Serie</label>
                                            <input type="text" name="nro_serie" class="form-control"
                                                value="{{ $camara->nro_serie }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12" id="label_fecha_asignacion">
                                        <div class="form-group">
                                            <label for="fecha_instalacion">Fecha de Instalación</label>
                                            {!! Form::date(
                                                'fecha_instalacion',
                                                $camara->fecha_instalacion ? $camara->fecha_instalacion : \Carbon\Carbon::now(),
                                            ) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="etapa">Etapa de instalación</label>
                                            <input type="text" name="etapa" class="form-control"
                                                value="{{ $camara->etapa }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="inteligencia">Inteligencia</label>
                                            <input type="text" name="inteligencia" class="form-control"
                                                value="{{ $camara->inteligencia }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ $camara->observaciones }}</textarea>
                                        </div>
                                    </div>
                                    @can('editar-camara')
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <button type="submit" class="btn btn-primary">Guardar</button>
                                        </div>
                                    @endcan
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });

            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });
        });
    </script>
@endsection
