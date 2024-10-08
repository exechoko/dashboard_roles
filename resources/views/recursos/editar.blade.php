@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Recurso</h3>
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


                            <form action="{{ route('recursos.update', $recurso->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="">Dependencia</label>
                                            <select name="dependencia" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $recurso->destino_id }}">{{ $recurso->destino->nombre }}
                                                </option>
                                                @foreach ($dependencias as $dependencia)
                                                    <option value="{{ $dependencia->id }}">
                                                        {{ $dependencia->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="nombre">Nombre</label>
                                            <input type="text" name="nombre" class="form-control"
                                                value="{{ $recurso->nombre }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Vehiculo</label>
                                            <select name="vehiculo" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                @if (is_null($recurso->vehiculo_id))
                                                    <option value="">-</option>
                                                @else
                                                    <option value="{{ $recurso->vehiculo_id }}">
                                                        {{ $recurso->vehiculo->marca . ' ' . $recurso->vehiculo->modelo . ' ' . $recurso->vehiculo->dominio }}
                                                    </option>
                                                @endif
                                                @foreach ($vehiculos as $vehiculo)
                                                    <option value="{{ $vehiculo->id }}">
                                                        {{ $vehiculo->marca . ' ' . $vehiculo->modelo . ' ' . $vehiculo->dominio }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <div class="control-label">Permite múltiples equipos</div>
                                            <label class="custom-switch mt-2">
                                                <input type="checkbox" name="multi_equipos"
                                                    class="custom-switch-input"
                                                    @if ($recurso->multi_equipos)
                                                        checked
                                                    @endif>
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" style="height: 100px">{{ $recurso->observaciones }}</textarea>
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
