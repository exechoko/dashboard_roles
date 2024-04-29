@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar sitio</h3>
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


                            <form action="{{ route('sitios.update', $sitio->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre</label>
                                            <input type="text" name="nombre" class="form-control"
                                                value="{{ $sitio->nombre }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="latitud">Latitud</label>
                                            <input type="text" name="latitud" class="form-control"
                                                value="{{ $sitio->latitud }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="longitud">Longitud</label>
                                            <input type="text" name="longitud" class="form-control"
                                                value="{{ $sitio->longitud }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Localidad</label>
                                            <select name="localidad" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $sitio->localidad }}">{{ $sitio->localidad }}</option>
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
                                                @if (!is_null($sitio->destino_id))
                                                    <option value="{{ $sitio->destino->id }}">
                                                        {{ $sitio->destino->nombre . ' - ' . $sitio->destino->dependeDe() }}
                                                    </option>
                                                @else
                                                    <option value="">Seleccione la dependencia</option>
                                                @endif
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
                                                <option value="{{ $sitio->cartel }}">{{ ($sitio->cartel) ? 'SI' : 'NO' }}</option>
                                                @foreach ($con_carteles as $cc)
                                                    <option value="{{ $cc }}">
                                                        {{ $cc }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ $sitio->observaciones }}</textarea>
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
@endsection

<!--script>
    var msg = '{{ Session::get('alert') }}';
    var exist = '{{ Session::has('alert') }}';
    if(exist){
      alert(msg);
    }
</script-->
