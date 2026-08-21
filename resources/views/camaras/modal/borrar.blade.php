<div class="modal fade" id="ModalDelete{{$camara->id}}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('camaras.destroy', $camara->id) }}" method="post" enctype="multipart/form-data" style="display: contents;">
                {{ method_field('delete') }}
                {{ csrf_field() }}
                <div class="modal-header bg-danger">
                    <h4 class="modal-title text-white">Eliminar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">Está seguro que desea eliminar la camara <b>{{$camara->nombre}}</b>?</div>
                <div class="modal-footer">
                    <button type="button" class="btn gray btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    @can('borrar-camara')
                        <button type="submit" class="btn btn-outline-danger">Eliminar</button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</div>
