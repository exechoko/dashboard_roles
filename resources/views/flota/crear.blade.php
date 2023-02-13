@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Flota</h3>
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

                            <form action="{{ route('flota.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="">Equipos</label>
                                            <select name="equipo" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Equipo</option>
                                                @foreach ($equipos as $equipo)
                                                    <option value="{{ $equipo->id }}">
                                                        {{ $equipo->tipo_terminal->tipo_uso->uso . ' ' . $equipo->issi . ' - ' . $equipo->tei . ' - '. $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="">Recurso</label>
                                            <select name="recurso" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Recurso</option>
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
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="">Dependencia</label>
                                            <select name="dependencia" id="" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar Dependencia</option>
                                                @foreach ($dependencias as $dependencia)
                                                    <option value="{{ $dependencia->id }}">
                                                        {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <div class="form-group">
                                            <label for="ticket_per">Ticket PER</label>
                                            <input type="text" name="ticket_per" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6" id="label_fecha_asignacion">
                                        <div class="form-group">
                                            <label for="fecha_asignacion">Fecha de asignación</label>
                                            {!! Form::date('fecha_asignacion', \Carbon\Carbon::now()) !!}
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
@endsection
