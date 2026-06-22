@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Noticias</h3>
            <div>
                <button type="button" class="btn btn-outline-primary js-web-preview"
                        data-pagina="noticias.html" data-title="Noticias">
                    <i class="fas fa-eye"></i> Vista previa
                </button>
                @can('crear-noticia')
                    <a href="{{ route('noticias.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva noticia
                    </a>
                @endcan
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

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:90px">Miniatura</th>
                                    <th>Título</th>
                                    <th style="width:160px">Fecha</th>
                                    <th style="width:110px">Estado</th>
                                    <th style="width:160px" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($noticias as $noticia)
                                    <tr>
                                        <td>
                                            @if ($noticia->miniatura)
                                                <img src="{{ route('noticias.imagen', $noticia->miniatura->archivo) }}"
                                                     alt="" style="width:70px;height:50px;object-fit:cover;border-radius:6px;">
                                            @else
                                                <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $noticia->titulo }}</strong>
                                            @if ($noticia->bajada)
                                                <div class="text-muted small">{{ Str::limit($noticia->bajada, 80) }}</div>
                                            @endif
                                        </td>
                                        <td>{{ optional($noticia->fecha_publicacion)->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($noticia->publicada)
                                                <span class="badge badge-success">Publicada</span>
                                            @else
                                                <span class="badge badge-secondary">Borrador</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @can('editar-noticia')
                                                <a href="{{ route('noticias.edit', $noticia) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('eliminar-noticia')
                                                <form action="{{ route('noticias.destroy', $noticia) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Eliminar esta noticia? Se borrará también de la web.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay noticias cargadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $noticias->links() }}
                </div>
            </div>
        </div>
    </section>

    @include('web-admin._preview')
@endsection
