<form action="{{ route('dependencias.update', $destacamento->id) }}" method="post" enctype="multipart/form-data">
    {{ method_field('patch') }}
    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditarDestacamento{{$destacamento->id}}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar destacamento</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group" style="display: none;">
                            <strong>Tipo dependencia</strong>
                            {!! Form::text('tipo_dependencia', 'destacamento') !!}
                        </div>
                        <div class="form-group">
                            <strong>Nombre</strong>
                            {!! Form::text('nombre', $destacamento->nombre, array('placeholder' => $destacamento->nombre,'class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Teléfono</strong>
                            {!! Form::text('telefono', $destacamento->telefono, array('placeholder' => $destacamento->telefono,'class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Ubicación</strong>
                            {!! Form::text('ubicacion', $destacamento->ubicacion, array('placeholder' => $destacamento->ubicacion,'class' => 'form-control')) !!}
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
