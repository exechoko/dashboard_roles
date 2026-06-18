@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Historia — Línea de tiempo</h3>
            <div>
                <a href="{{ route('web-admin.textos.edit') }}" class="btn btn-light">
                    <i class="fas fa-font"></i> Textos
                </a>
                <a href="{{ route('web-historia.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva tarjeta
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
                Tarjetas de la línea de tiempo de la página <strong>historia.html</strong>. El orden define cómo aparecen.
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th style="width:120px">Año</th>
                                    <th>Título</th>
                                    <th>Etiqueta</th>
                                    <th style="width:120px" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cards as $card)
                                    <tr>
                                        <td>{{ $card->orden }}</td>
                                        <td><span class="badge badge-primary">{{ $card->anio }}</span></td>
                                        <td>
                                            <strong>{{ $card->titulo }}</strong>
                                            <div class="text-muted small">{{ Str::limit($card->texto, 90) }}</div>
                                        </td>
                                        <td>@if ($card->tag)<span class="badge badge-info">{{ $card->tag }}</span>@endif</td>
                                        <td class="text-right">
                                            <a href="{{ route('web-historia.edit', $card) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('web-historia.destroy', $card) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar esta tarjeta? Se quitará también de la web.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay tarjetas cargadas.</td>
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
