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
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="sitio">Sitio</label>
                                            <input type="text" name="sitio" class="form-control"
                                            value="{{ $camara->sitio }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="latitud">Latitud</label>
                                            <input type="text" name="latitud" class="form-control"
                                            value="{{ $camara->latitud }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="longitud">Longitud</label>
                                            <input type="text" name="longitud" class="form-control"
                                            value="{{ $camara->longitud }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-8">
                                        <div class="form-group">
                                            <label for="">Tipo de cámaras</label>
                                            <select name="tipo_camara_id" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                @if (!is_null($camara->tipo_camara_id))
                                                <option value="{{ $camara->tipoCamara->id }}">{{ $camara->tipoCamara->tipo . ' - ' . $camara->tipoCamara->marca . ' - ' . $camara->tipoCamara->modelo }}</option>
                                                @else
                                                <option value="">Seleccione un tipo de cámara</option>
                                                @endif
                                                @foreach ($tipoCamara as $tipo)
                                                    <option value="{{ $tipo->id }}">
                                                        {{ $tipo->tipo . ' - ' . $tipo->marca . ' - ' . $tipo->modelo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="nro_serie">Nro. de Serie</label>
                                            <input type="text" name="nro_serie" class="form-control"
                                            value="{{ $camara->nro_serie }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12" id="label_fecha_asignacion">
                                        <div class="form-group">
                                            <label for="fecha_instalacion">Fecha de Instalación</label>
                                            {!! Form::date('fecha_instalacion', ($camara->fecha_instalacion) ? $camara->fecha_instalacion : \Carbon\Carbon::now()) !!}
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
                                        <div class="form-group">
                                            <label for="">Dependencia</label>
                                            <select name="destino_id" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                @if (!is_null($camara->destino_id))
                                                <option value="{{ $camara->destino->id }}">{{ $camara->destino->nombre . ' - ' . $camara->destino->dependeDe() }}</option>
                                                @else
                                                <option value="">Seleccione la dependencia</option>
                                                @endif
                                                <option value="">Seleccionar la dependencia</option>
                                                @foreach ($dependencias as $d)
                                                    <option value="{{ $d->id }}">
                                                        {{ $d->nombre . ' - ' . $d->dependeDe() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px"
                                            value="{{ $camara->observaciones }}"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

<!--script>
    var msg = '{{Session::get('alert')}}';
    var exist = '{{Session::has('alert')}}';
    if(exist){
      alert(msg);
    }
</script-->
