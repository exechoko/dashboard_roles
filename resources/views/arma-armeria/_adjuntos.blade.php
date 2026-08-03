{{-- Requiere: $item (con adjuntos.usuario cargado), $routeBase (ej. armas.armeria.armas) --}}
<div class="card">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-paperclip"></i> Imágenes y Documentos</h4>
    </div>
    <div class="card-body">
        @can('editar-armeria')
            <form action="{{ route($routeBase . '.adjuntos.store', $item) }}" method="POST" enctype="multipart/form-data" class="mb-3">
                @csrf
                <div class="form-row align-items-end">
                    <div class="col-md-8">
                        <label for="archivo">Adjuntar imagen o documento</label>
                        <input type="file" name="archivo" id="archivo" class="form-control-file @error('archivo') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" required>
                        @error('archivo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">JPG, PNG, WEBP, PDF, DOC o DOCX. Máximo 8 MB.</small>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-upload"></i> Subir
                        </button>
                    </div>
                </div>
            </form>
            <hr>
        @endcan

        @if ($item->adjuntos->isNotEmpty())
            <div class="row">
                @foreach ($item->adjuntos as $adjunto)
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <div class="armeria-adjunto-card">
                            @if ($adjunto->tipo === 'IMAGEN')
                                <a href="{{ $adjunto->url }}" target="_blank">
                                    <img src="{{ $adjunto->url }}" alt="{{ $adjunto->nombre_original }}">
                                </a>
                            @else
                                <a href="{{ $adjunto->url }}" target="_blank" class="d-block py-4">
                                    <i class="fas {{ $adjunto->icono }} fa-3x text-armeria-slate"></i>
                                </a>
                            @endif
                            <div class="mt-1 text-truncate small" title="{{ $adjunto->nombre_original }}">
                                {{ $adjunto->nombre_original }}
                            </div>
                            <small class="text-muted d-block">{{ $adjunto->usuario->name ?? 'Sistema' }} — {{ $adjunto->created_at->format('d/m/Y') }}</small>
                            @can('borrar-armeria')
                                <form action="{{ route($routeBase . '.adjuntos.destroy', [$item, $adjunto]) }}" method="POST" class="mt-1"
                                      onsubmit="return confirm('¿Eliminar este archivo adjunto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-block">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-muted py-3">
                <i class="fas fa-folder-open fa-2x mb-2"></i>
                <p>Sin archivos adjuntos.</p>
            </div>
        @endif
    </div>
</div>
