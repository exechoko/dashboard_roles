@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Cargar sitio</h3>
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

                            <form action="{{ route('sitios.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre</label>
                                            <input type="text" name="nombre" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="latitud">Latitud</label>
                                            <input type="text" name="latitud" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="longitud">Longitud</label>
                                            <input type="text" name="longitud" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Localidad</label>
                                            <select name="localidad" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar localidad</option>
                                                @foreach ($localidades as $l)
                                                    <option value="{{ $l }}">
                                                        {{ $l }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Dependencia</label>
                                            <select name="destino_id" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar la dependencia</option>
                                                @foreach ($dependencias as $d)
                                                    <option value="{{ $d->id }}">
                                                        {{ $d->nombre . ' - ' . $d->dependeDe() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Cartel señalizador</label>
                                            <select name="cartel" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar</option>
                                                @foreach ($con_carteles as $cc)
                                                    <option value="{{ $cc }}">
                                                        {{ $cc }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Toggle Switch para Estado Activo -->
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <div class="d-flex align-items-center">
                                                <label class="form-label mb-0 mr-3">Sitio activo</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="hidden" name="activo" value="0">
                                                    <input type="checkbox" name="activo" value="1" class="custom-control-input" id="activo_switch" {{ old('activo', 1) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="activo_switch"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px"></textarea>
                                        </div>
                                    </div>
                                    @can('editar-sitio')
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

    <style>
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #28a745;
            border-color: #28a745;
        }

        .custom-control-input:focus ~ .custom-control-label::before {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }
    </style>

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
