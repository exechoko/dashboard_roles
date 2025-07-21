<div class="modal fade" id="ModalDetalle{{ $f->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">Detalles del Equipamiento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Equipment Image -->
                    <div class="col-md-2 text-center">
                        <img alt="Imagen del equipo"
                             src="{{ asset($f->equipo->tipo_terminal->imagen ?? 'default/path/image.jpg') }}"
                             class="img-fluid img-thumbnail mb-3"
                             style="max-height: 200px;">
                    </div>

                    <!-- Equipment Details -->
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Información del Equipo</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Marca:</strong> {{ $f->equipo->tipo_terminal->marca ?? '-' }}</li>
                                    <li><strong>Modelo:</strong> {{ $f->equipo->tipo_terminal->modelo ?? '-' }}</li>
                                    <li><strong>Proveedor:</strong> {{ $f->equipo->provisto ?? '-' }}</li>
                                    <li><strong>TEI:</strong> {{ $f->equipo->tei ?? '-' }}</li>
                                    <li><strong>ISSI:</strong> {{ $f->equipo->issi ?? '-' }}</li>
                                    <li><strong>ID ISSI:</strong> {{ $f->equipo->nombre_issi ?? '-' }}</li>
                                    <li><strong>Tipo:</strong> {{ $f->equipo->tipo_terminal->tipo_uso->uso ?? '-' }}</li>
                                    <li><strong>Estado:</strong> {{ $f->equipo->estado->nombre ?? '-' }}</li>
                                </ul>
                            </div>

                            <!-- Movement Details -->
                            <div class="col-md-8">
                                <h5>Último Movimiento</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Fecha:</strong> {{ $f->fecha_ultimo_mov ?? '-' }}</li>
                                    <li><strong>Tipo:</strong> {{ $f->ultimo_movimiento ?? '-' }}</li>
                                    <li><strong>Recurso:</strong> {{ $f->recurso->nombre ?? '-' }}</li>
                                    <li><strong>Vehículo:</strong>
                                        @if (!is_null($f->recurso->vehiculo))
                                            {{ $f->recurso->vehiculo->tipo_vehiculo ?? '-' }}<br>
                                            {{  $f->recurso->vehiculo->marca . ' - ' . $f->recurso->vehiculo->modelo . ' - ' . $f->recurso->vehiculo->dominio ?? '' }}
                                        @else
                                            -
                                        @endif
                                    </li>
                                    <li><strong>Dependencia:</strong>
                                        {{ $f->destino->nombre ?? '-' }}<br>
                                        <small>{{ $f->destino->dependeDe() ?? '' }}</small>
                                    </li>
                                    <li><strong>Observaciones:</strong><br>
                                        <p class="text-muted">{{ $f->observaciones_ultimo_mov ?? '-' }}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
                <a href="{{ route('verHistorico', $f->id) }}"
                   target="_blank"
                   class="btn btn-info">
                   <i class="fas fa-history mr-1"></i> Ver Histórico
                </a>
            </div>
        </div>
    </div>
</div>
