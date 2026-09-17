<div class="modal fade" id="ModalDetalle{{ $antena->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">Información de la antena</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Nombre: <b>{{ $antena->nombre }}</b></li>
                    <li>Localidad: <b>{{ $antena->localidad ?? '-' }}</b></li>
                    <li>Ubicación: <b>{{ $antena->ubicacion ?? '-' }}</b></li>
                    <li>Lat / Long: <b>{{ $antena->latitud ?? '-' }} / {{ $antena->longitud ?? '-' }}</b></li>
                    <li>Altura: <b>{{ $antena->altura ?? '-' }}</b></li>
                    <li>Estado:
                        @if ($antena->activa)
                            <span class="badge badge-success">Activa</span>
                        @else
                            <span class="badge badge-danger">Inactiva</span>
                        @endif
                    </li>
                </ul>
                <hr>
                <p class="mb-1"><b>Observaciones:</b></p>
                <p>{!! $antena->observaciones ? nl2br(e($antena->observaciones)) : '-' !!}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-warning" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
