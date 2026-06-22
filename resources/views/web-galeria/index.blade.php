@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Galería — Imágenes</h3>
            <div>
                @can('editar-web-textos')
                    <a href="{{ route('web-admin.textos.edit') }}" class="btn btn-light">
                        <i class="fas fa-font"></i> Textos
                    </a>
                @endcan
                <button type="button" class="btn btn-outline-primary js-web-preview"
                        data-pagina="galeria.html" data-title="Galería">
                    <i class="fas fa-eye"></i> Vista previa
                </button>
                <a href="{{ route('web-galeria.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva imagen
                </a>
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Imágenes del slider de <strong>galeria.html</strong>. Cada imagen lleva un título/descripción y, opcionalmente, una categoría. El orden controla la posición en el carrusel.
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th style="width:110px">Imagen</th>
                                    <th>Título / descripción</th>
                                    <th style="width:160px">Categoría</th>
                                    <th style="width:120px" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($imagenes as $imagen)
                                    <tr>
                                        <td>{{ $imagen->orden }}</td>
                                        <td>
                                            <img src="{{ route('web-galeria.imagen', ['ruta' => $imagen->imagen]) }}" alt=""
                                                 style="width:90px;height:55px;object-fit:cover;border-radius:6px;">
                                        </td>
                                        <td><strong>{{ $imagen->titulo }}</strong></td>
                                        <td>
                                            @if ($imagen->categoria)
                                                <span class="badge badge-secondary">{{ $imagen->categoria }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('web-galeria.edit', $imagen) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('web-galeria.destroy', $imagen) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Quitar esta imagen de la galería? Se eliminará también de la web.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay imágenes cargadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $imagenes->links() }}
                </div>
            </div>
        </div>
    </section>

    @include('web-admin._preview')
@endsection
