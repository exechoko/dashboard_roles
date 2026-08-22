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
                                    <div class="col-xs-12 col-sm-12 col-md-2">
                                        <div class="form-group">
                                            <label for="issi">ISSI</label>
                                            <input type="text" name="issi" class="form-control"
                                                value="{{ $equipo->issi }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-2">
                                        <div class="form-group">
                                            <label for="nombre_issi">ID ISSI</label>
                                            <input type="text" name="nombre_issi" class="form-control"
                                                value="{{ $equipo->nombre_issi }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-2">
                                        <div class="form-group">
                                            <label for="tei">TEI</label>
                                            <input type="text" name="tei" class="form-control"
                                                value="{{ $equipo->tei }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="numero_bateria">Nro. batería</label>
                                            <input type="text" name="numero_bateria" class="form-control"
                                                value="{{ $equipo->numero_bateria }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="numero_segunda_bateria">Nro. 2da. batería</label>
                                            <input type="text" name="numero_segunda_bateria" class="form-control"
                                                value="{{ $equipo->numero_segunda_bateria }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="estado">Estado</label>
                                            <select name="estado" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $equipo->estado->id }}">{{ $equipo->estado->nombre }}
                                                </option>
                                                @foreach ($estados as $estado)
                                                    <option value="{{ $estado->id }}">
                                                        {{ $estado->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="propietario">Propietario</label>
                                            <input type="text" name="propietario" class="form-control"
                                                value="{{ $equipo->propietario }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="provisto">Provisto por</label>
                                            <input type="text" name="provisto" class="form-control"
                                                value="{{ $equipo->provisto }}">
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
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <hr>
                                        <h5>Accesorios</h5>
                                        <p class="text-muted" style="font-size: .875rem;">
                                            "Le falta" marca el equipo como <strong>degradado</strong>: el transceptor
                                            funciona, pero sin ese accesorio no puede salir a la calle y no cuenta
                                            como disponible en las estadísticas.
                                        </p>
                                    </div>
@foreach (\App\Models\Equipo::ACCESORIOS as $campo => $etiqueta)
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="{{ $campo }}">{{ $etiqueta }}</label>
                                            <select name="{{ $campo }}" class="form-control">
                                                <option value="" @if (is_null($equipo->$campo)) selected @endif>Sin relevar</option>
                                                <option value="1" @if ($equipo->$campo === true) selected @endif>Lo tiene</option>
                                                <option value="0" @if ($equipo->$campo === false) selected @endif>Le falta</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="{{ $campo }}_desc">Observación</label>
                                            <input type="text" name="{{ \App\Models\Equipo::descripcionCampo($campo) }}"
                                                class="form-control"
                                                value="{{ $equipo->{\App\Models\Equipo::descripcionCampo($campo)} }}">
                                        </div>
                                    </div>
@endforeach
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
                setTimeout(() => {
                    let select2Field = document.querySelector('.select2-container--open .select2-search__field');
                    if (select2Field) {
                        select2Field.focus();
                    }
                }, 0);
            });
        });
    </script>
@endsection
