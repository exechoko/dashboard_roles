@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Cargar Equipo</h3>
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

                            <form action="{{ route('equipos.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-8">
                                        <div class="form-group">
                                            <label for="">Terminal</label>
                                            <select name="terminal" id="" class="form-control"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Terminal</option>
                                                @foreach ($terminales as $terminal)
                                                    <option value="{{ $terminal->id }}">
                                                        {{ $terminal->marca . ' ' . $terminal->modelo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Estado</label>
                                            {!! Form::select(
                                                'estados[]',
                                                $estados,
                                                [],
                                                ['placeholder' => 'Selecciona el estado', 'class' => 'form-control'],
                                            ) !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="issi">ISSI</label>
                                            <input type="text" name="issi" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="nombre_issi">ID ISSI</label>
                                            <input type="text" name="nombre_issi" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="tei">TEI</label>
                                            <input type="text" name="tei" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="provisto">Provisto por</label>
                                            <input type="text" name="provisto" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="propietario">Propietario</label>
                                            <input type="text" name="propietario" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-2">
                                        <div class="form-group">
                                            <label for="con_garantia">Con garantía</label>
                                            {!! Form::checkbox('con_garantia', 'con_garantia') !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-10" id="label_fecha_venc_garantia">
                                        <div class="form-group">
                                            <label for="fecha_venc_garantia">Fecha de vencimiento de la garantía</label>
                                            {!! Form::date('fecha_venc_garantia', \Carbon\Carbon::now()) !!}
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px"></textarea>
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
