@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading text-truncate">
            <i class="{{ $archivo->icono_extension }} mr-2"></i>
            {{ $archivo->nombre_original }}
        </h3>
        <div class="text-nowrap">
            <a href="{{ route('descargas.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('descargas.download', $archivo) }}" class="btn btn-success">
                <i class="fas fa-download"></i> Descargar
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            {{-- Información del archivo --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información del archivo</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th style="width: 150px;">Nombre original</th>
                                <td>{{ $archivo->nombre_original }}</td>
                            </tr>
                            <tr>
                                <th>Categoría</th>
                                <td>
                                    <span class="badge" style="background-color: {{ $archivo->categoria->color }}">
                                        <i class="{{ $archivo->categoria->icono }} mr-1"></i>
                                        {{ $archivo->categoria->nombre }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Tamaño</th>
                                <td>{{ $archivo->tamano_humano }}</td>
                            </tr>
                            <tr>
                                <th>Tipo</th>
                                <td><span class="badge badge-secondary">.{{ strtoupper($archivo->extension) }}</span></td>
                            </tr>
                            <tr>
                                <th>Subido por</th>
                                <td>{{ $archivo->user->name ?? 'Sistema' }}</td>
                            </tr>
                            <tr>
                                <th>Fecha de carga</th>
                                <td>{{ $archivo->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Descargas</th>
                                <td><span class="badge badge-info">{{ $archivo->descargas_count }}</span></td>
                            </tr>
                            @if($archivo->expira_at)
                                <tr>
                                    <th>Expiración</th>
                                    <td>
                                        @if($archivo->esta_expirado)
                                            <span class="badge badge-danger">Expirado</span>
                                        @else
                                            <span class="badge badge-warning">
                                                Expira en {{ $archivo->dias_para_expirar }} días
                                                ({{ $archivo->expira_at->format('d/m/Y H:i') }})
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @if($archivo->descripcion)
                                <tr>
                                    <th>Descripción</th>
                                    <td>{{ $archivo->descripcion }}</td>
                                </tr>
                            @endif
                            @if($archivo->tags->count() > 0)
                                <tr>
                                    <th>Etiquetas</th>
                                    <td>
                                        @foreach($archivo->tags as $tag)
                                            <span class="badge badge-light mr-1">{{ $tag->nombre }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                            @if($archivo->es_compartido)
                                <tr>
                                    <th>Compartido por</th>
                                    <td>
                                        <span class="badge badge-info">
                                            <i class="fas fa-share-alt"></i>
                                            {{ $archivo->compartidoPor->name ?? 'N/A' }}
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Fecha: {{ $archivo->updated_at->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- Vista previa --}}
                @if($archivo->es_previeweable)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-eye mr-2"></i>Vista previa</h5>
                        </div>
                        <div class="card-body text-center">
                            @if(in_array($archivo->extension, ['jpg', 'jpeg', 'png', 'gif']))
                                <img src="{{ route('descargas.preview', $archivo) }}" class="img-fluid" style="max-height: 500px;" alt="Preview">
                            @elseif($archivo->extension === 'pdf')
                                <iframe src="{{ route('descargas.preview', $archivo) }}" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Comentarios --}}
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-comments mr-2"></i>Comentarios ({{ $archivo->comentarios->count() }})</h5>
                    </div>
                    <div class="card-body">
                        @if($archivo->comentarios->count() > 0)
                            @foreach($archivo->comentarios as $comentario)
                                <div class="media mb-3 {{ $comentario->es_admin ? 'p-2 bg-light rounded' : '' }}">
                                    <div class="media-body">
                                        <h6 class="mt-0 mb-1">
                                            {{ $comentario->user->name ?? 'Usuario eliminado' }}
                                            @if($comentario->es_admin)
                                                <span class="badge badge-primary ml-2">Admin</span>
                                            @endif
                                            <small class="text-muted ml-2">{{ $comentario->created_at->diffForHumans() }}</small>
                                        </h6>
                                        <p class="mb-0">{{ $comentario->comentario }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center mb-3">No hay comentarios aún.</p>
                        @endif

                        <hr>

                        <form action="{{ route('descargas.comentar', $archivo) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Agregar comentario</label>
                                <textarea name="comentario" class="form-control" rows="3" required placeholder="Escribe tu comentario..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Enviar comentario
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-download mr-2"></i>Descargar</h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="{{ $archivo->icono_extension }} fa-4x mb-3"></i>
                        <h5>{{ $archivo->nombre_original }}</h5>
                        <p class="text-muted">{{ $archivo->tamano_humano }}</p>
                        <a href="{{ route('descargas.download', $archivo) }}" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-download"></i> Descargar archivo
                        </a>
                        @php
                            $esFavorito = \App\Models\DescargaFavorito::where('user_id', Auth::id())
                                ->where('archivo_id', $archivo->id)
                                ->exists();
                        @endphp
                        <button type="button" class="btn btn-{{ $esFavorito ? 'warning' : 'outline-warning' }} btn-lg btn-block mt-2 btn-toggle-favorito" 
                                data-archivo-id="{{ $archivo->id }}">
                            <i class="fas fa-star"></i> 
                            {{ $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' }}
                        </button>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt mr-2"></i>Permisos</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Roles que pueden descargar:</strong></p>
                        @foreach($archivo->roles as $rol)
                            <span class="badge badge-secondary mr-1 mb-1">{{ $rol->name }}</span>
                        @endforeach

                        @if($archivo->usuarios->count() > 0)
                            <hr>
                            <p class="mb-2"><strong>Usuarios con acceso directo:</strong></p>
                            @foreach($archivo->usuarios as $usuario)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-primary">
                                        <i class="fas fa-user"></i> {{ $usuario->name }}
                                    </span>
                                    @can('administrar-plataforma-descargas')
                                        <form action="{{ route('descargas.admin.revocar-acceso', [$archivo, $usuario]) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Revocar acceso a {{ $usuario->name }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Revocar acceso">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        @endif

                        @can('administrar-plataforma-descargas')
                            {{-- Nada adicional para admin --}}
                        @else
                            <hr>
                            <button type="button" class="btn btn-outline-primary btn-block" 
                                    data-toggle="modal" data-target="#modalCompartir">
                                <i class="fas fa-share-alt"></i> Solicitar compartir con otro usuario
                            </button>
                        @endcan
                    </div>
                </div>

                @can('administrar-plataforma-descargas')
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Administración</h5>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('descargas.admin.edit', $archivo) }}" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fas fa-edit"></i> Editar archivo
                            </a>
                            <a href="{{ route('descargas.admin.logs', ['archivo_id' => $archivo->id]) }}" class="btn btn-outline-info btn-block mb-2">
                                <i class="fas fa-history"></i> Ver historial
                            </a>
                            <button type="button" class="btn btn-outline-success btn-block mb-2" data-toggle="modal" data-target="#modalGenerarQr">
                                <i class="fas fa-qrcode"></i> Generar código QR
                            </button>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    $('.btn-toggle-favorito').click(function() {
        const button = $(this);
        const archivoId = button.data('archivo-id');
        const token = '{{ csrf_token() }}';

        $.ajax({
            url: `/descargas/${archivoId}/favorito`,
            method: 'POST',
            data: {
                _token: token
            },
            success: function(response) {
                if (response.success) {
                    // Cambiar el estado del botón
                    if (response.es_favorito) {
                        button.removeClass('btn-outline-warning').addClass('btn-warning');
                        button.html('<i class="fas fa-star"></i> Quitar de favoritos');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-outline-warning');
                        button.html('<i class="fas fa-star"></i> Agregar a favoritos');
                    }
                    
                    // Mostrar notificación
                    const alertClass = response.es_favorito ? 'success' : 'info';
                    const alertHtml = `
                        <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    `;
                    $('.section-body').prepend(alertHtml);
                    
                    // Auto-ocultar después de 3 segundos
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 3000);
                }
            },
            error: function(xhr) {
                alert('Error al actualizar favorito');
            }
        });
    });
});
</script>
@endpush

{{-- Modal para solicitar compartir --}}
@can('administrar-plataforma-descargas')
    {{-- No mostrar modal para admins --}}
@else
    <div class="modal fade" id="modalCompartir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('descargas.solicitar-compartir', $archivo) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-share-alt"></i> Solicitar compartir archivo
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Selecciona el usuario con quien deseas compartir este archivo:</p>
                        
                        <div class="form-group">
                            <label>Usuario destino *</label>
                            <select name="usuario_destino_id" class="form-control" required>
                                <option value="">Seleccionar usuario...</option>
                            </select>
                            <small class="form-text text-muted">
                                Un administrador revisará tu solicitud antes de aprobarla.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Motivo (opcional)</label>
                            <textarea name="motivo" class="form-control" rows="3" 
                                      placeholder="Explica por qué necesitas compartir este archivo..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> La solicitud será enviada a los administradores para su aprobación. 
                            El usuario seleccionado recibirá acceso directo al archivo una vez aprobada la solicitud.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Cargar usuarios en el modal
    $('#modalCompartir').on('show.bs.modal', function () {
        fetch('{{ route("usuarios.json") }}')
            .then(response => response.json())
            .then(data => {
                const select = $('select[name="usuario_destino_id"]');
                select.empty();
                select.append('<option value="">Seleccionar usuario...</option>');
                
                data.forEach(usuario => {
                    // No mostrar el usuario actual
                    if (usuario.id != {{ Auth::id() }}) {
                        select.append(`<option value="${usuario.id}">${usuario.name} (${usuario.email})</option>`);
                    }
                });
            })
            .catch(error => {
                console.error('Error cargando usuarios:', error);
                alert('Error al cargar la lista de usuarios');
    });
});
</script>
@endpush

{{-- Modal para generar QR --}}
@can('administrar-plataforma-descargas')
<div class="modal fade" id="modalGenerarQr" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>Generar Código QR</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('descargas.admin.generar-qr', $archivo) }}" method="POST" id="formGenerarQr">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Genera un código QR de un solo uso para descargar este archivo. 
                        El QR expirará después de ser utilizado o al vencer el tiempo configurado.
                    </p>

                    <div class="form-group">
                        <label>Tiempo de expiración (horas)</label>
                        <input type="number" name="expira_horas" class="form-control" 
                               value="{{ config('descargas.qr_default_expiracion_horas', 24) }}" 
                               min="1" max="720">
                        <small class="form-text text-muted">
                            El código QR expirará después de este tiempo (máximo 720 horas = 30 días)
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Contraseña (opcional)</label>
                        <input type="text" name="password" class="form-control" 
                               placeholder="Dejar vacío para no requerir contraseña">
                        <small class="form-text text-muted">
                            Si se establece, se requerirá esta contraseña para descargar el archivo
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Importante:</strong> El código QR será de un solo uso. Una vez descargado el archivo, 
                        el QR dejará de funcionar.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-qrcode"></i> Generar QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para mostrar QR generado --}}
<div class="modal fade" id="modalQrGenerado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>QR Generado Exitosamente</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="qrImageGenerado" src="" alt="Código QR" class="img-fluid mb-3" style="max-width: 300px;">
                <p class="text-muted small mb-3">
                    Escanea este código con tu dispositivo móvil para descargar el archivo
                </p>
                <div class="input-group mb-3">
                    <input type="text" id="qrUrlGenerado" class="form-control" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary btn-copiar-qr" type="button">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
                <a id="btnDescargarQr" href="" class="btn btn-primary btn-block" download>
                    <i class="fas fa-download"></i> Descargar Imagen QR
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#formGenerarQr').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Cerrar modal de generación
                    $('#modalGenerarQr').modal('hide');
                    
                    // Mostrar modal con QR generado
                    $('#qrImageGenerado').attr('src', response.qr_url);
                    $('#qrUrlGenerado').val(response.download_url);
                    $('#btnDescargarQr').attr('href', response.qr_url);
                    $('#modalQrGenerado').modal('show');
                    
                    // Mostrar mensaje de éxito
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i>
                            ${response.message} - Expira: ${response.expira_at}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    `;
                    $('.section-body').prepend(alertHtml);
                    
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 5000);
                } else {
                    alert(response.message || 'Error al generar el QR');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert(response?.message || 'Error al generar el QR');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('.btn-copiar-qr').click(function() {
        const input = $('#qrUrlGenerado')[0];
        input.select();
        document.execCommand('copy');
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-check"></i> Copiado');
        setTimeout(function() {
            btn.html(originalHtml);
        }, 2000);
    });
});
</script>
@endpush
@endcan
@endsection
