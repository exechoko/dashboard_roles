@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Editar antena (SBS)</h3>
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

                            <form action="{{ route('antenas.update', $antena->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" name="nombre" class="form-control"
                                                value="{{ old('nombre', $antena->nombre) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="localidad">Localidad</label>
                                            <input type="text" name="localidad" class="form-control"
                                                value="{{ old('localidad', $antena->localidad) }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="ubicacion">Ubicación</label>
                                            <input type="text" name="ubicacion" class="form-control"
                                                value="{{ old('ubicacion', $antena->ubicacion) }}"
                                                placeholder="Ej: dirección o referencia">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="latitud">Latitud</label>
                                            <input type="text" name="latitud" class="form-control"
                                                value="{{ old('latitud', $antena->latitud) }}"
                                                placeholder="Ej: -31.72652">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="longitud">Longitud</label>
                                            <input type="text" name="longitud" class="form-control"
                                                value="{{ old('longitud', $antena->longitud) }}"
                                                placeholder="Ej: -60.53293">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="altura">Altura (m)</label>
                                            <input type="text" name="altura" class="form-control"
                                                value="{{ old('altura', $antena->altura) }}">
                                        </div>
                                    </div>
                                    <!-- Toggle Switch para Estado Activa -->
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <div class="d-flex align-items-center">
                                                <label class="form-label mb-0 mr-3">Antena activa</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="hidden" name="activa" value="0">
                                                    <input type="checkbox" name="activa" value="1" class="custom-control-input"
                                                        id="activa_switch" {{ old('activa', $antena->activa) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="activa_switch"></label>
                                                </div>
                                                <span class="ml-2 text-muted">
                                                    <small id="activa_text">{{ old('activa', $antena->activa) ? 'Activa' : 'Inactiva' }}</small>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones"
                                                style="height: 100px">{{ old('observaciones', $antena->observaciones) }}</textarea>
                                        </div>
                                    </div>
                                    @can('editar-antena')
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Actualizar
                                            </button>
                                            <a href="{{ route('antenas.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
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
            $('#activa_switch').on('change', function() {
                const isChecked = $(this).is(':checked');
                const textElement = $('#activa_text');

                if (isChecked) {
                    textElement.text('Activa').removeClass('text-danger').addClass('text-success');
                } else {
                    textElement.text('Inactiva').removeClass('text-success').addClass('text-danger');
                }
            });

            const isChecked = $('#activa_switch').is(':checked');
            const textElement = $('#activa_text');
            if (isChecked) {
                textElement.addClass('text-success');
            } else {
                textElement.addClass('text-danger');
            }
        });
    </script>
@endsection
