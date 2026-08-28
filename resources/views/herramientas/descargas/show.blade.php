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
                            <a href="{{ route('descargas.admin.logs', ['archivo_id' => $archivo->id]) }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-history"></i> Ver historial
                            </a>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section>
@endsection
