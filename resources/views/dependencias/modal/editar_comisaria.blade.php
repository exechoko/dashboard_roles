<form action="{{ route('dependencias.update', $comisaria->id) }}" method="post" enctype="multipart/form-data">
    {{ method_field('patch') }}
    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditarComisaria{{$comisaria->id}}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar comisaría</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group" style="display: none;">
                            <strong>Tipo dependencia</strong>
                            {!! Form::text('tipo_dependencia', 'comisaria') !!}
                        </div>
                        <div class="form-group">
                            <strong>Nombre</strong>
                            {!! Form::text('nombre', $comisaria->nombre, array('placeholder' => $comisaria->nombre,'class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Teléfono</strong>
                            {!! Form::text('telefono', $comisaria->telefono, array('placeholder' => $comisaria->telefono,'class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Ubicación</strong>
                            {!! Form::text('ubicacion', $comisaria->ubicacion, array('placeholder' => $comisaria->ubicacion,'class' => 'form-control')) !!}
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
