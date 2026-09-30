@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-share-alt mr-2"></i>Compartidos conmigo</h3>
    </div>

    <div class="section-body">
        @include('herramientas.descargas.partials._nav')

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.compartidos-conmigo') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Nombre del archivo..."
                                   value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Ordenar</label>
                            <select name="orden" class="form-control">
                                <option value="recientes" {{ request('orden') == 'recientes' ? 'selected' : '' }}>Más recientes</option>
                                <option value="antiguos" {{ request('orden') == 'antiguos' ? 'selected' : '' }}>Más antiguos</option>
                                <option value="nombre" {{ request('orden') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                                <option value="descargas" {{ request('orden') == 'descargas' ? 'selected' : '' }}>Más descargados</option>
                                <option value="tamano" {{ request('orden') == 'tamano' ? 'selected' : '' }}>Tamaño</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h5 class="mb-3">
            Archivos compartidos
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
                    <i class="fas fa-share-alt"></i>
                    <p class="mb-3">Todavía no hay archivos compartidos con vos.</p>
                    <a href="{{ route('descargas.index') }}" class="btn btn-primary">
                        <i class="fas fa-search"></i> Ver todos los archivos
                    </a>
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
                    } else {
                        button.removeClass('btn-warning').addClass('btn-outline-warning');
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
