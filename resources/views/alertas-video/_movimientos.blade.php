{{-- Requiere: $item (DominioAlerta|PersonaAlerta con movimientos.usuario cargado), $routeBase (ej. alertas-video.dominios), $permisoEditar (ej. editar-alerta-dominio) --}}
<div class="card">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-history"></i> Historial</h4>
    </div>
    <div class="card-body">
        @can($permisoEditar)
            <form action="{{ route($routeBase . '.comentario', $item) }}" method="POST" class="mb-3">
                @csrf
                <div class="form-group mb-2">
                    <label class="text-muted"><i class="fas fa-comment"></i> Agregar nota</label>
                    <textarea name="comentario" class="form-control" rows="2" maxlength="500"
                              placeholder="Describa la novedad, gestión o detalle relevante..." required></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Agregar nota
                </button>
            </form>
            <hr>
        @endcan

        @if ($item->movimientos->isNotEmpty())
            <div class="alerta-timeline">
                @foreach ($item->movimientos as $mov)
                    <div class="alerta-timeline-item">
                        <div class="alerta-timeline-marker {{ $mov->accion_color }}">
                            <i class="fas {{ $mov->accion_icon }}"></i>
                        </div>
                        <div class="alerta-timeline-content">
                            <h6 class="mb-1"><strong>{{ $mov->accion_label }}</strong></h6>
                            <small class="text-muted">
                                <i class="fas fa-user"></i>
                                {{ $mov->usuario ? trim($mov->usuario->apellido . ' ' . $mov->usuario->name) : 'Sistema' }}
                                &mdash;
                                <i class="fas fa-clock"></i> {{ $mov->created_at->format('d/m/Y H:i') }}
                            </small>
                            @if ($mov->estado_anterior || $mov->estado_nuevo)
                                <div class="mt-1">
                                    <small>
                                        <i class="fas fa-toggle-on"></i>
                                        {{ ucfirst(strtolower($mov->estado_anterior ?? '-')) }}
                                        <i class="fas fa-arrow-right mx-1"></i>
                                        {{ ucfirst(strtolower($mov->estado_nuevo ?? '-')) }}
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
