@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Flota</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @if (Session::has('warning'))
                                <div class="alert alert-warning">
                                    {{ Session::get('warning') }}
                                </div>
                            @endif

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

                            <form action="{{ route('flota.update', $flota->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Equipo</label>
                                            <select name="equipo" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $flota->equipo_id }}">
                                                    {{ $flota->equipo->tipo_terminal->tipo_uso->uso . ' ' . $flota->equipo->issi . ' ' . $flota->equipo->tipo_terminal->marca . ' ' . $flota->equipo->tipo_terminal->modelo }}
                                                </option>
                                                @foreach ($equipos as $equipo)
                                                    <option value="{{ $equipo->id }}">
                                                        {{ $equipo->tipo_terminal->tipo_uso->uso . ' ' . $equipo->issi . ' - ' . $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Recurso al que se asigna</label>
                                            <select name="recurso" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="{{ $flota->recurso_id }}">
                                                    {{ isset($flota->recurso->nombre) ? $flota->recurso->nombre : 'Seleccionar recurso' }}
                                                </option>
                                                @foreach ($recursos as $recurso)
                                                    <option value="{{ $recurso->id }}">
                                                        @if (is_null($recurso->vehiculo))
                                                        {{ $recurso->nombre }}
                                                        @else
                                                        {{ $recurso->nombre . ' - ' . $recurso->vehiculo->tipo_vehiculo }}
                                                        @endif
                                                        </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Pertenece a</label>
                                            <label for="" class="form-control">{{ $flota->destino->nombre . ' - ' . $flota->destino->dependeDe() }}</label>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Actualmente está en</label>
                                            @if (is_null($hist))
                                            <label for="" class="form-control">{{ $flota->destino->nombre . ' - ' . $flota->destino->dependeDe() }}</label>
                                            @else
                                            <label for="" class="form-control">{{ $hist->destino->nombre . ' - ' . $hist->destino->dependeDe() }}</label>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Tipo de movimiento</label>
                                            <select name="tipo_movimiento" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar tipo de movimiento</option>
                                                @foreach ($tipos_movimiento as $tipo_movimiento)
                                                    <option value="{{ $tipo_movimiento->id }}">
                                                        {{ $tipo_movimiento->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="">Dependencia o lugar al que se asigna</label>
                                            <select name="dependencia" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar destino/dependencia</option>
                                                @foreach ($dependencias as $dependencia)
                                                    <option value="{{ $dependencia->id }}">
                                                        {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" style="height: 100px">{{ $flota->observaciones }}</textarea>
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
@endsection

<!--script>
    var msg = '{{ Session::get('alert') }}';
    var exist = '{{ Session::has('alert') }}';
    if(exist){
      alert(msg);
    }
</script-->
