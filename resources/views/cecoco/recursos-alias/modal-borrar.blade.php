<div class="modal fade" id="ModalDeleteAlias{{ $alias->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalDeleteAliasLabel{{ $alias->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('cecoco.recursos-alias.destroy', $alias) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalDeleteAliasLabel{{ $alias->id }}">Eliminar mapeo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Desea eliminar el alias CECOCO <strong>{{ $alias->alias_cecoco }}</strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
