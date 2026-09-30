@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-images mr-2"></i>Galería</h3>
    </div>

    <div class="section-body">
        @include('herramientas.descargas.partials._nav')

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

        <h5 class="mb-3">
            Imágenes y videos disponibles
            <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
        </h5>

        @if($archivos->count() > 0)
            <div class="descargas-grid mb-3">
                @foreach($archivos as $archivo)
                    <x-descarga-archivo-card :archivo="$archivo" mostrar-favorito
                        :favorito="in_array($archivo->id, $favoritosIds)" />
                @endforeach
            </div>
            <div class="d-flex justify-content-center">
                {{ $archivos->links() }}
            </div>
        @else
            <div class="descargas-empty card">
                <div class="card-body">
                    <i class="fas fa-images"></i>
                    <p class="mb-0">No se encontraron imágenes o videos disponibles con estos filtros.</p>
                </div>
            </div>
        @endif
    </div>
</section>

@include('herramientas.descargas.partials._styles')

@push('scripts')
@include('herramientas.descargas.partials._scripts')
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-toggle-favorito', function() {
        const button = $(this);
        const archivoId = button.data('archivo-id');

        $.ajax({
            url: `/descargas/${archivoId}/favorito`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    if (response.es_favorito) {
                        button.removeClass('btn-outline-warning').addClass('btn-warning');
                        button.attr('title', 'Quitar de favoritos');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-outline-warning');
                        button.attr('title', 'Agregar a favoritos');
                    }
                    descargasToast(response.message || 'Favoritos actualizados');
                }
            },
            error: function(xhr) {
                descargasErrorAjax(xhr, 'Error al actualizar favorito');
            }
        });
    });
});
</script>
@endpush
@endsection
