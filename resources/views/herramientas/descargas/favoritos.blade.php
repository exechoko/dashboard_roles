@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-star text-warning"></i> Mis Archivos Favoritos
                    </h4>
                    <div class="card-actions">
                        <a href="{{ route('descargas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Todos los Archivos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('descargas.mis-favoritos') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Buscar</label>
                                    <input type="text" name="buscar" class="form-control" 
                                           value="{{ request('buscar') }}" 
                                           placeholder="Nombre del archivo...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select name="categoria_id" class="form-control">
                                        <option value="">Todas</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" 
                                                    {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ordenar por</label>
                                    <select name="orden" class="form-control">
                                        <option value="recientes" {{ request('orden') == 'recientes' ? 'selected' : '' }}>Más recientes</option>
                                        <option value="antiguos" {{ request('orden') == 'antiguos' ? 'selected' : '' }}>Más antiguos</option>
                                        <option value="nombre" {{ request('orden') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                                        <option value="descargas" {{ request('orden') == 'descargas' ? 'selected' : '' }}>Más descargados</option>
                                        <option value="tamano" {{ request('orden') == 'tamano' ? 'selected' : '' }}>Tamaño</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-filter"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Lista de archivos --}}
                    @if($archivos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Categoría</th>
                                        <th>Subido por</th>
                                        <th>Tamaño</th>
                                        <th>Fecha</th>
                                        <th>Descargas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($archivos as $archivo)
                                        <tr>
                                            <td>
                                                <i class="{{ $archivo->icono_extension }}"></i>
                                                <a href="{{ route('descargas.show', $archivo) }}">
                                                    {{ Str::limit($archivo->nombre_original, 40) }}
                                                </a>
                                                <i class="fas fa-star text-warning ml-2" title="Favorito"></i>
                                                @if($archivo->destacado)
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-star"></i> Destacado
                                                    </span>
                                                @endif
                                                @if($archivo->expira_at && $archivo->expira_at->isFuture())
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> 
                                                        Expira: {{ $archivo->expira_at->format('d/m/Y') }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $archivo->categoria->color }}">
                                                    {{ $archivo->categoria->nombre }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $archivo->user->name ?? 'Sistema' }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $archivo->created_at->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td>{{ $archivo->tamano_humano }}</td>
                                            <td>{{ $archivo->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $archivo->descargas_count }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('descargas.show', $archivo) }}" 
                                                   class="btn btn-sm btn-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('descargas.download', $archivo) }}" 
                                                   class="btn btn-sm btn-success" title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @if($archivo->es_previeweable)
                                                    <a href="{{ route('descargas.preview', $archivo) }}" 
                                                       class="btn btn-sm btn-info" title="Vista previa" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-warning btn-toggle-favorito" 
                                                        data-archivo-id="{{ $archivo->id }}" title="Quitar de favoritos">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        <div class="d-flex justify-content-center">
                            {{ $archivos->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No tienes archivos favoritos aún</p>
                            <a href="{{ route('descargas.index') }}" class="btn btn-primary">
                                <i class="fas fa-search"></i> Explorar archivos
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
                    // Recargar la página para actualizar la lista
                    location.reload();
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
@endsection
