@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Vehiculo</h3>
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


                            <form action="{{ route('vehiculos.update', $vehiculo->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="tipo_vehiculo">Tipo de vehiculo</label>
                                            <label class="alert alert-info ml-3" for="tipo_terminal">Moto - Auto - Camioneta - Helicoptero</label>
                                            <input type="text" name="tipo_vehiculo" class="form-control"
                                                value="{{ $vehiculo->tipo_vehiculo }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="marca">Marca</label>
                                            <input type="text" name="marca" class="form-control"
                                            value="{{ $vehiculo->marca }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="modelo">Modelo</label>
                                            <input type="text" name="modelo" class="form-control"
                                            value="{{ $vehiculo->modelo }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="dominio">Dominio</label>
                                            <input type="text" name="dominio" class="form-control"
                                            value="{{ $vehiculo->dominio }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="nro_chasis">Número de chasis</label>
                                            <input type="text" name="nro_chasis" class="form-control"
                                            value="{{ $vehiculo->nro_chasis }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="color">Color</label>
                                            <input type="text" name="color" class="form-control"
                                            value="{{ $vehiculo->color }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="propiedad">Propiedad</label>
                                            <input type="text" name="propiedad" class="form-control"
                                            value="{{ $vehiculo->propiedad }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="detalles">Detalles</label>
                                            <input type="text" name="detalles" class="form-control"
                                            value="{{ $vehiculo->detalles }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ $vehiculo->observaciones }}</textarea>
                                        </div>
                                        <br>
                                        <button type="submit" class="btn btn-primary">Guardar</button>
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
