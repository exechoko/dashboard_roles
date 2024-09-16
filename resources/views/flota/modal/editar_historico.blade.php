<form action="{{ route('flota.update_historico', $h->id) }}" method="post" enctype="multipart/form-data">

    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditar{{ $h->id }}" tabindex="-1" data-backdrop="false" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">

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
                            <strong>Observaciones</strong>
                            {!! Form::textarea('observaciones', $h->observaciones, ['placeholder' => $h->observaciones, 'class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <button type="submit" class="btn btn-success">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
