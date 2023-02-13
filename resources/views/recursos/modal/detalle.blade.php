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
                    <li>Dependencia:
                        @if (is_null($recurso->destino))
                        <b> - </b>
                        @else
                        <b>{{ $recurso->destino->nombre }}</b>
                        @endif
                    </li>
                    <li>Nombre: <b>{{ $recurso->nombre }}</b></li>
                    @if (is_null($recurso->vehiculo))
                    <b> - </b>
                    @else
                    <li>Vehiculo Marca: <b>{{ $recurso->vehiculo->marca }}</b> - Modelo: <b>{{ $recurso->vehiculo->modelo }}</b></li>
                    <li>Dominio: <b>{{ $recurso->vehiculo->dominio }}</b></li>
                    @endif
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-warning" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
