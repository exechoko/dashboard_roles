<form action="{{ route('flota.update_historico', $h->id) }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditar{{ $h->id }}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar Histórico</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="container col-xs-12 col-sm-12 col-md-12">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6">
                            <div class="form-group">
                                <label for="archivo">Archivo adjunto</label>
                                <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="imagen1">Imagen 1</label>
                                <input type="file" name="imagen1" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="imagen2">Imagen 2</label>
                                <input type="file" name="imagen2" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <strong>Observaciones</strong>
                            <textarea name="observaciones" id="observaciones{{ $h->id }}" class="form-control" placeholder="{{ $h->observaciones }}" style="min-height: 200px;">{{ $h->observaciones }}</textarea>
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
