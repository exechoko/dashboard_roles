@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Tecnología — Cards</h3>
            <div>
                <a href="{{ route('web-admin.textos.edit') }}" class="btn btn-light">
                    <i class="fas fa-font"></i> Textos
                </a>
                <a href="{{ route('web-tecnologia.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva card
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
                Cards de la página <strong>tecnologia.html</strong>. Podés ponerle una imagen de cabecera a cada una; si no, se muestra el color.
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th style="width:90px">Imagen</th>
                                    <th>Título</th>
                                    <th style="width:110px">Color</th>
                                    <th style="width:120px" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cards as $card)
                                    <tr>
                                        <td>{{ $card->orden }}</td>
                                        <td>
                                            @if ($card->imagen)
                                                <img src="{{ route('web-tecnologia.imagen', $card->imagen) }}" alt=""
                                                     style="width:70px;height:45px;object-fit:cover;border-radius:6px;">
                                            @else
                                                <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $card->titulo }}</strong>
                                            <div class="text-muted small">{{ Str::limit($card->texto, 90) }}</div>
                                        </td>
                                        <td><span class="badge badge-secondary">{{ $colores[$card->color] ?? $card->color }}</span></td>
                                        <td class="text-right">
                                            <a href="{{ route('web-tecnologia.edit', $card) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('web-tecnologia.destroy', $card) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar esta card? Se quitará también de la web.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay cards cargadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $cards->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
