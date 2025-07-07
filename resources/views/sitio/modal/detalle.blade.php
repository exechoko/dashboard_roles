<div class="modal fade" id="ModalDetalle{{ $sitio->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <div>
                    <h5 class="modal-title text-white">Información del sitio</h5>
                    <h4 class="text-white">{{ $sitio->nombre }}</h4>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Dependencia: <b>@if (!is_null($sitio->destino)) {{ $sitio->destino->nombre . ' - ' . $sitio->destino->dependeDe() }} @else - @endif</b></li>
                    <li>Localidad: <b>{{ $sitio->localidad }}</b>
                    </li>Observación: <b>{{ $sitio->observaciones }}</b></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-warning" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
