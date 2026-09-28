<div class="modal fade" id="ModalDetalleAuditoria{{ $auditoria->id }}" tabindex="-1" data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <div>
                    <h5 class="modal-title text-white">Detalle de auditoría #{{ $auditoria->id }}</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Fecha:</strong> {{ $auditoria->created_at->format('d/m/Y H:i:s') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Usuario:</strong>
                        @if ($auditoria->user)
                            {{ $auditoria->user->apellido }} {{ $auditoria->user->name }}
                        @else
                            <span class="text-muted">Sistema / desconocido</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Tabla:</strong> <span class="badge badge-info">{{ $auditoria->nombre_tabla }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Acción:</strong> {{ $auditoria->accion }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>IP:</strong> {{ $auditoria->ip_address ?? '-' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Navegador:</strong>
                        <small class="text-muted" style="word-break: break-word;">{{ $auditoria->user_agent ?? '-' }}</small>
                    </div>
                </div>

                <hr>

                <strong>Cambios</strong>

                @php
                    $partes = $auditoria->cambios
                        ? preg_split('/,\s+(?=[a-zA-Z_][a-zA-Z0-9_]*:\s)/', $auditoria->cambios)
                        : [];
                @endphp

                @if (empty($partes))
                    <p class="text-muted mb-0 mt-2">Sin detalle registrado.</p>
                @else
                    <ul class="list-unstyled mb-0 mt-2" style="max-height: 50vh; overflow-y: auto;">
                        @foreach ($partes as $parte)
                            @php
                                [$campo, $valor] = array_pad(explode(':', trim($parte), 2), 2, null);
                            @endphp
                            <li style="word-break: break-word; border-bottom: 1px solid #eee; padding: 6px 0;">
                                <strong>{{ trim((string) $campo) }}:</strong>
                                @if ($valor !== null && Str::contains($valor, ' => '))
                                    @php [$antes, $despues] = explode(' => ', $valor, 2); @endphp
                                    <span class="text-muted">{{ trim($antes) }}</span>
                                    <i class="fas fa-arrow-right mx-1"></i>
                                    <span>{{ trim($despues) }}</span>
                                @else
                                    {{ trim((string) $valor) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn gray btn-outline-primary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
