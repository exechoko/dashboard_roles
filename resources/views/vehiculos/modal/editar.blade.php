<form action="{{ route('vehiculos.update', $vehiculo->id) }}" method="post" enctype="multipart/form-data">
    {{ method_field('patch') }}
    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditar{{$vehiculo->id}}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <strong>Marca:</strong>
                            {!! Form::text('marca', $vehiculo->marca, array('placeholder' => $vehiculo->marca,'class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Modelo:</strong>
                            {!! Form::text('modelo', $vehiculo->modelo, array('placeholder' => $vehiculo->modelo,'class' => 'form-control')) !!}
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <button type="submit" class="btn btn-warning">Editar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
