<form action="{{ route('vehiculos.destroy', $vehiculo->id) }}" method="post" enctype="multipart/form-data">
    {{ method_field('delete') }}
    {{ csrf_field() }}
    <div class="modal fade" id="ModalDelete{{$vehiculo->id}}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title text-white">Eliminar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">Está seguro que desea eliminar el vehiculo <br><b>{{$vehiculo->marca . ' ' . $vehiculo->modelo . ' Dominio: ' . $vehiculo->dominio }}</b>?</div>
                <div class="modal-footer">
                    <button type="button" class="btn gray btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-outline-danger">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</form>
