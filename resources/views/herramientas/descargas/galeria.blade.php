@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading">
            <i class="fas fa-images mr-2"></i>Galería de Imágenes
        </h3>
        <div>
            <a href="{{ route('descargas.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Vista Lista
            </a>
            @can('administrar-plataforma-descargas')
                <a href="{{ route('descargas.admin.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-cogs"></i> Administrar
                </a>
            @endcan
        </div>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.galeria') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Nombre del archivo..."
                                   value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Subido por</label>
                            <select name="user_id" class="form-control">
                                <option value="">Todos</option>
                                @foreach($usuarios as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Ordenar</label>
                            <select name="orden" class="form-control">
                                <option value="recientes" {{ request('orden') == 'recientes' ? 'selected' : '' }}>Más recientes</option>
                                <option value="antiguos" {{ request('orden') == 'antiguos' ? 'selected' : '' }}>Más antiguos</option>
                                <option value="nombre" {{ request('orden') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                                <option value="descargas" {{ request('orden') == 'descargas' ? 'selected' : '' }}>Más descargados</option>
                                <option value="tamano" {{ request('orden') == 'tamano' ? 'selected' : '' }}>Tamaño</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('descargas.galeria') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                    
                    {{-- Filtros avanzados --}}
                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="#" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#filtrosAvanzados">
                                <i class="fas fa-sliders-h"></i> Filtros avanzados
                            </a>
                        </div>
                    </div>
                    
                    <div class="collapse mt-3" id="filtrosAvanzados">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" name="fecha_subida_desde" class="form-control" 
                                       value="{{ request('fecha_subida_desde') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" name="fecha_subida_hasta" class="form-control" 
                                       value="{{ request('fecha_subida_hasta') }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Galería de imágenes --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-images mr-2"></i>
                    Imágenes disponibles
                    <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
                </h5>
            </div>
            <div class="card-body">
                @if($archivos->count() > 0)
                    <div class="row">
                        @foreach($archivos as $archivo)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card h-100 shadow-sm hover-shadow">
                                    <div class="card-img-top-wrapper" style="height: 200px; overflow: hidden; background-color: #f8f9fa;">
                                        <img src="{{ route('descargas.preview', $archivo) }}" 
                                             alt="{{ $archivo->nombre_original }}" 
                                             class="img-fluid w-100 h-100"
                                             style="object-fit: cover; cursor: pointer;"
                                             onclick="mostrarPreview('{{ route('descargas.preview', $archivo) }}', '{{ $archivo->nombre_original }}')">
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1 text-truncate" title="{{ $archivo->nombre_original }}">
                                            @if($archivo->destacado)
                                                <i class="fas fa-star text-warning" title="Destacado"></i>
                                            @endif
                                            {{ Str::limit($archivo->nombre_original, 30) }}
                                        </h6>
                                        <p class="card-text mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-folder"></i>
                                                <span class="badge" style="background-color: {{ $archivo->categoria->color }}; font-size: 0.7rem;">
                                                    {{ $archivo->categoria->nombre }}
                                                </span>
                                            </small>
                                        </p>
                                        <p class="card-text mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> {{ $archivo->user->name ?? 'Sistema' }}
                                            </small>
                                        </p>
                                        <p class="card-text mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-weight"></i> {{ $archivo->tamano_humano }}
                                                <span class="ml-2">
                                                    <i class="fas fa-download"></i> {{ $archivo->descargas_count }}
                                                </span>
                                            </small>
                                        </p>
                                        @if($archivo->descripcion)
                                            <p class="card-text mb-2">
                                                <small class="text-muted text-truncate d-block" title="{{ $archivo->descripcion }}">
                                                    {{ Str::limit($archivo->descripcion, 50) }}
                                                </small>
                                            </p>
                                        @endif
                                    </div>
                                    <div class="card-footer bg-white border-top-0 p-2">
                                        <div class="btn-group btn-group-sm w-100">
                                            <a href="{{ route('descargas.show', $archivo) }}" class="btn btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('descargas.download', $archivo) }}" class="btn btn-success" title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-primary btn-toggle-favorito" 
                                                    data-archivo-id="{{ $archivo->id }}" title="Favorito">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $archivos->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No se encontraron imágenes disponibles.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Modal para preview de imagen --}}
<div class="modal fade" id="modalPreview" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitulo">Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImagen" src="" alt="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mostrarPreview(url, titulo) {
    $('#previewImagen').attr('src', url);
    $('#previewTitulo').text(titulo);
    $('#modalPreview').modal('show');
}

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
                    const icon = button.find('i');
                    if (response.es_favorito) {
                        icon.removeClass('far').addClass('fas text-warning');
                    } else {
                        icon.removeClass('fas text-warning').addClass('far');
                    }
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

@push('styles')
<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.card-img-top-wrapper {
    position: relative;
}
.card-img-top-wrapper::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.1));
    pointer-events: none;
}
</style>
@endpush
@endsection
