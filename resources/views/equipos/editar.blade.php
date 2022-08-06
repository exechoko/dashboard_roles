@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Equipo</h3>
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


                            <form action="{{ route('equipos.update', $equipo->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <!--div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Marca</label>
                                            {!! Form::select('marca_terminal[]', $marca_terminal, [], ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Modelo</label>
                                            {!! Form::select('modelo_terminal[]', $modelo_terminal, [], ['class' => 'form-control']) !!}
                                        </div>
                                    </div-->
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="issi">ISSI</label>
                                            <input type="text" name="issi" class="form-control"
                                                value="{{ $equipo->issi }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Estado</label>
                                            {!! Form::select('estados[]', $estados, [], ['placeholder' => 'Selecciona el estado', 'class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="tei">TEI</label>
                                            <input type="text" name="tei" class="form-control"
                                                value="{{ $equipo->tei }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="propietario">Propietario</label>
                                            <input type="text" name="Propietario" class="form-control"
                                                value="{{ $equipo->propietario }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="con_garantia">Con garantía</label>
                                            {!! Form::checkbox('con_garantia', 'SI') !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12" id="label_fecha_venc_garantia">
                                        <div class="form-group">
                                            <label for="fecha_venc_garantia">Fecha de vencimiento de la garantía</label>
                                            {!! Form::date('fecha_venc_garantia', \Carbon\Carbon::now()) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">

                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ $equipo->observaciones }}</textarea>

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
