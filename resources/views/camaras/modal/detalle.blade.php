<div class="modal fade" id="ModalDetalle{{ $camara->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <div>
                    <h5 class="modal-title text-white">Información de la cámara</h5>
                    <h4 class="text-white">{{ $camara->nombre }}</h4>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img src="{{asset( $camara->tipoCamara->imagen )}}" class="card-img-top" alt="">
                <ul>
                    <li>Sitio: <b>@if(!is_null($camara->sitio)) {{ $camara->sitio->nombre }} @else - @endif</b> - IP: <b>@if (!is_null($camara->ip)) {{ $camara->ip }} @else - @endif</b></li>
                    <li>Dependencia: <b>@if (!is_null($camara->sitio)) {{ $camara->sitio->destino->nombre . ' - ' . $camara->sitio->destino->dependeDe() }} @else - @endif</b></li>
                    <li>Marca: <b>{{ $camara->tipoCamara->marca }}</b> - Modelo: <b>@if (!is_null($camara->tipoCamara->modelo)) {{ $camara->tipoCamara->modelo }} @else - @endif</b></li>
                    <li>Tipo: <b>{{ $camara->tipoCamara->tipo }}</b></li>
                    <li>Nro de Serie: <b>@if (!is_null($camara->nro_serie)) {{ $camara->nro_serie}} @else - @endif</b></li>
                    <li>Inteligencia: <b>{{ $camara->inteligencia }}</b></li>
                    <li>Etapa: <b>{{ $camara->etapa }}</b> - Fecha de instalación: <b>@if (!is_null($camara->fecha_instalacion)) {{ $camara->fecha_instalacion }} @else - @endif</b></li>
                    <li>Observaciones: <b>@if (!is_null($camara->observaciones)) {{ $camara->observaciones }} @else - @endif</b></li>
                </ul>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-warning" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
