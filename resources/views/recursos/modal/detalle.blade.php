<div class="modal fade" id="ModalDetalle{{ $recurso->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning" >
                <h5 class="modal-title text-white">Información del recurso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="mt-3">
                    <li>Dependencia: <b> </b> - Modelo:
                        <b> </b></li>
                    <li>Nombre: <b>{{ $recurso->nombre }}</b></li>

                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-warning" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
