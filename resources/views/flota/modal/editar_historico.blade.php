<form action="{{ route('flota.update_historico', $h->id) }}" method="post" enctype="multipart/form-data">

    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditar{{ $h->id }}" tabindex="-1" data-backdrop="false" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar Histórico</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-xs-12">
                        <div class="form-group">
                            @php
                                use App\Models\TipoMovimiento;
                                $tipos_movimiento = TipoMovimiento::all();
                            @endphp
                            <label for="">Tipo de movimiento</label>
                            <select name="tipo_movimiento" id="" class="form-control"
                                style="margin-bottom: 15px">
                                <option
                                    @if (!is_null($h->tipoMovimiento))
                                        value="{{ $h->tipoMovimiento->id }}"
                                    @else
                                        -
                                    @endif
                                >
                                    @if (!is_null($h->tipoMovimiento))
                                        {{ $h->tipoMovimiento->nombre }}
                                    @else
                                        -
                                    @endif
                                </option>
                                @foreach ($tipos_movimiento as $tipo_movimiento)
                                    <option value="{{ $tipo_movimiento->id }}">
                                        {{ $tipo_movimiento->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <strong>Fecha de asignación</strong>
                            {!! Form::date('fecha_asignacion', $h->fecha_asignacion) !!}
                        </div>
                        <div class="form-group">
                            @php
                                use App\Models\Recurso;
                                $recursos = Recurso::all();
                            @endphp
                            <strong><label for="">Recurso</label></strong>
                            <select name="recurso" id="" class="form-control" style="margin-bottom: 15px">
                                <option
                                    @if (!is_null($h->recurso))
                                        value="{{ $h->recurso->id }}"
                                    @else
                                        -
                                    @endif
                                >
                                    @if (!is_null($h->recurso))
                                        {{ $h->recurso->nombre }}
                                    @else
                                        -
                                    @endif
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
                        <div class="form-group">
                            @php
                                use App\Models\Destino;
                                $dependencias = Destino::all();
                            @endphp
                            <strong><label for="">Actualmente en</label></strong>
                            <select name="dependencia" id="" class="form-control" style="margin-bottom: 15px">
                                <option value="{{ $h->destino->id }}">{{ $h->destino->nombre }}</option>
                                @foreach ($dependencias as $dependencia)
                                    <option value="{{ $dependencia->id }}">
                                        {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <strong>Ticket PER</strong>
                            {!! Form::text('ticket_per', $h->ticket_per, ['placeholder' => $h->ticket_per, 'class' => 'form-control']) !!}
                        </div>
                        <div class="form-group">
                            <strong>Recurso anterior</strong>
                            {!! Form::text('recurso_desasignado', $h->recurso_desasignado, ['placeholder' => $h->recurso_desasignado, 'class' => 'form-control']) !!}
                        </div>
                        <div class="form-group">
                            <strong>Vehículo anterior</strong>
                            {!! Form::text('vehiculo_desasignado', $h->vehiculo_desasignado, ['placeholder' => $h->vehiculo_desasignado, 'class' => 'form-control']) !!}
                        </div>
                        <div class="form-group">
                            <strong>Observaciones</strong>
                            {!! Form::textarea('observaciones', $h->observaciones, ['placeholder' => $h->observaciones, 'class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <button type="submit" class="btn btn-success">Editar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
