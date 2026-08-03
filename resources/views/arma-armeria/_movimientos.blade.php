{{-- Requiere: $item (ArmeriaArma|ArmeriaChaleco con movimientos.usuario cargado), $routeBase (ej. armas.armeria.armas) --}}
<div class="card">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-history"></i> Historial de Movimientos</h4>
    </div>
    <div class="card-body">
        @can('editar-armeria')
            <form action="{{ route($routeBase . '.comentario', $item) }}" method="POST" class="mb-3">
                @csrf
                <div class="form-row">
                    <div class="col-md-9">
                        <div class="form-group mb-2">
                            <label class="text-muted"><i class="fas fa-comment"></i> Agregar nota o comentario</label>
                            <textarea name="comentario" class="form-control" rows="2" maxlength="500"
                                      placeholder="Describa la novedad, observación o detalle relevante..." required></textarea>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="text-muted">Fecha y hora del hecho</label>
                            <input type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                            <small class="form-text text-muted">Si la nota es atrasada, indique cuándo ocurrió.</small>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Agregar nota
                </button>
            </form>
            <hr>
        @endcan

        @if ($item->movimientos->isNotEmpty())
            <div class="armeria-timeline">
                @foreach ($item->movimientos as $mov)
                    <div class="armeria-timeline-item">
                        <div class="armeria-timeline-marker {{ $mov->accion_color }}">
                            <i class="fas {{ $mov->accion_icon }}"></i>
                        </div>
                        <div class="armeria-timeline-content {{ $mov->accion_color }}">
                            <h6 class="mb-1"><strong>{{ $mov->accion_label }}</strong></h6>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> {{ $mov->usuario->name ?? 'Sistema' }}
                                &mdash;
                                <i class="fas fa-clock"></i> {{ $mov->created_at->format('d/m/Y H:i') }}
                            </small>
                            @if ($mov->ubicacion_anterior || $mov->ubicacion_nueva)
                                <div class="mt-1">
                                    <small>
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $mov->ubicacion_anterior ? ($mov->ubicacion_anterior === 'DIVISION_911' ? 'Armería División 911' : 'Armería Jefatura Central') : 'Sin ubicación previa' }}
                                        <i class="fas fa-arrow-right mx-1"></i>
                                        {{ $mov->ubicacion_nueva === 'DIVISION_911' ? 'Armería División 911' : 'Armería Jefatura Central' }}
                                    </small>
                                </div>
                            @endif
                            @if ($mov->estado_anterior || $mov->estado_nuevo)
                                <div class="mt-1">
                                    <small>
                                        <i class="fas fa-sliders-h"></i>
                                        {{ ucfirst(strtolower(str_replace('_', ' ', $mov->estado_anterior ?? '-'))) }}
                                        <i class="fas fa-arrow-right mx-1"></i>
                                        {{ ucfirst(strtolower(str_replace('_', ' ', $mov->estado_nuevo ?? '-'))) }}
                                    </small>
                                </div>
                            @endif
                            @if ($mov->comentario)
                                <div class="mt-1 p-2 bg-white rounded border">
                                    <small>{{ $mov->comentario }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-muted py-3">
                <i class="fas fa-history fa-2x mb-2"></i>
                <p>Sin movimientos registrados.</p>
            </div>
        @endif
    </div>
</div>
