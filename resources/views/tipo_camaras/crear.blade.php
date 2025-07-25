@extends('layouts.app')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tipo').select2({
                placeholder: "Seleccione un tipo de cámara",
                allowClear: true
            });
        });
    </script>
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Carga de Tipo de cámaras</h3>
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

                            <form action="{{ route('tipo-camara.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="tipo">Tipo</label>
                                            <select name="tipo" id="tipo" class="form-control">
                                                <option value="">Seleccione un tipo</option>
                                                @foreach($tipos as $tipo)
                                                    <option value="{{ $tipo }}">{{ $tipo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="marca">Marca</label>
                                            <input type="text" name="marca" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="modelo">Modelo</label>
                                            <input type="text" name="modelo" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="canales">Cantidad de canales</label>
                                            <input type="number" name="canales" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-6 d-flex">
                                        <div class="col-sm-4 col-md-6 pl-0 form-group">
                                            <label>Imagen:</label>
                                            <br>
                                            <label for="imagen" class="image__file-upload btn btn-dark text-white"
                                                tabindex="2"> Elegir
                                                <input type="file" name="imagen" id="imagen" class="d-none">
                                            </label>
                                        </div>
                                        <div class="col-sm-3 preview-image-video-container float-right mt-1">
                                            <img id='edit_preview_photo'
                                                class="img-thumbnail user-img user-profile-img profilePicture"
                                                src="{{asset('img/logo.png')}}" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones"
                                                style="height: 100px"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
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
