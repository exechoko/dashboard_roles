<div class="modal fade" id="ModalDetalle{{ $equipo->id }}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Información del equipo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Marca: <b>{{ $equipo->tipo_terminal->marca }}</b> - Modelo:
                        <b>{{ $equipo->tipo_terminal->modelo }}</b></li>
                    <li>Estado: <b>{{ $equipo->estado->nombre }}</b></li>
                    <li>TEI: <b>{{ $equipo->tei }}</b>
                        @if (!is_null($equipo->issi))
                            - ISSI: <b>{{ $equipo->issi }}</b>
                    </li>
                @else
                    - ISSI: <b>Sin asignar</b></li>
                    @endif
                    <li>GPS: <b>{{ $equipo->gps == '1' ? 'Posee' : 'No posee' }}</b></li>
                    <li>Antena R.F.: <b>{{ $equipo->rf == '1' ? 'Posee' : 'No posee' }}</b></li>


                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-success" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>

</div>
