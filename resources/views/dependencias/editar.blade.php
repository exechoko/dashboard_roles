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
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="issi">ISSI</label>
                                            <input type="text" name="issi" class="form-control"
                                                value="{{ $equipo->issi }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="tei">TEI</label>
                                            <input type="text" name="tei" class="form-control"
                                                value="{{ $equipo->tei }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Estado</label>
                                            {!! Form::select('estados[]', $estados, [], ['placeholder' => 'Selecciona el estado', 'class' => 'form-control']) !!}
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="propietario">Propietario</label>
                                            <input type="text" name="propietario" class="form-control"
                                                value="{{ $equipo->propietario }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-2">
                                        <div class="form-group">
                                            <label for="con_garantia">Con garantía</label>
                                            {!! Form::checkbox('con_garantia', 'con_garantia', $equipo->con_garantia == 1 ? true : false) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-10" id="label_fecha_venc_garantia">
                                        <div class="form-group">
                                            <label for="fecha_venc_garantia">Fecha de vencimiento de la garantía</label>
                                            {!! Form::date('fecha_venc_garantia', \Carbon\Carbon::now()) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="gps">Con GPS</label>
                                            {!! Form::checkbox('gps', 'gps', $equipo->gps == 1 ? true : false) !!}
                                        </div>
                                        <div class="form-group">
                                            <label for="desc_gps">Descripción GPS</label>
                                            <input type="text" name="desc_gps" class="form-control"
                                                value="{{ $equipo->desc_gps }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="frente_remoto">Con Frente Remoto</label>
                                            {!! Form::checkbox('frente_remoto', 'frente_remoto', $equipo->frente_remoto == 1 ? true : false) !!}
                                        </div>
                                        <div class="form-group">
                                            <label for="desc_frente">Descripción Frente Remoto</label>
                                            <input type="text" name="desc_frente" class="form-control"
                                                value="{{ $equipo->desc_frente }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="rf">Con Antena R.F. </label>
                                            {!! Form::checkbox('rf', 'rf', $equipo->rf == 1 ? true : false) !!}
                                        </div>
                                        <div class="form-group">
                                            <label for="desc_rf">Descripción Antena R.F.</label>
                                            <input type="text" name="desc_rf" class="form-control"
                                                value="{{ $equipo->desc_rf }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="kit_inst">Con Kit de instalación </label>
                                            {!! Form::checkbox('kit_inst', 'kit_inst', $equipo->kit_inst == 1 ? true : false) !!}
                                        </div>
                                        <div class="form-group">
                                            <label for="desc_kit_inst">Descripción del kit de instalación</label>
                                            <input type="text" name="desc_kit_inst" class="form-control"
                                                value="{{ $equipo->desc_kit_inst }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="operativo">Operativo</label>
                                            {!! Form::checkbox('operativo', 'operativo', $equipo->operativo == 1 ? true : false) !!}
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
