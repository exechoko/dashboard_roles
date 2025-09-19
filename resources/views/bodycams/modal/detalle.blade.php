<div class="modal fade" id="ModalDetalle{{ $bodycam->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalDetalle">Detalle de Bodycam</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Código:</strong> {{ $bodycam->codigo }}</p>
                        <p><strong>Número de Serie:</strong> {{ $bodycam->numero_serie }}</p>
                        <p><strong>IMEI:</strong> {{ $bodycam->imei ?: '-' }}</p>
                        <p><strong>Marca:</strong> {{ $bodycam->marca }}</p>
                        <p><strong>Modelo:</strong> {{ $bodycam->modelo }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Número Tarjeta SD:</strong> {{ $bodycam->numero_tarjeta_sd ?: '-' }}</p>
                        <p><strong>Número Batería:</strong> {{ $bodycam->numero_bateria ?: '-' }}</p>
                        <p><strong>Estado:</strong>
                            @switch($bodycam->estado)
                                @case('disponible')
                                    <span class="badge badge-success">{{ $bodycam->estado_formateado }}</span>
                                    @break
                                @case('entregada')
                                    <span class="badge badge-warning">{{ $bodycam->estado_formateado }}</span>
                                    @break
                                @case('perdida')
                                    <span class="badge badge-danger">{{ $bodycam->estado_formateado }}</span>
                                    @break
                                @case('mantenimiento')
                                    <span class="badge badge-info">{{ $bodycam->estado_formateado }}</span>
                                    @break
                                @case('dada_baja')
                                    <span class="badge badge-dark">{{ $bodycam->estado_formateado }}</span>
                                    @break
                            @endswitch
                        </p>
                        <p><strong>Fecha de Adquisición:</strong> {{ $bodycam->fecha_adquisicion ? $bodycam->fecha_adquisicion->format('d/m/Y') : '-' }}</p>
                    </div>
                </div>
                @if($bodycam->observaciones)
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Observaciones:</strong></p>
                            <p class="text-muted">{{ $bodycam->observaciones }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
