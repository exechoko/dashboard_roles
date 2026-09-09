@extends('layouts.movil')

@section('title', 'Cámaras')

@section('content')
    <form method="GET" action="{{ route('movil.camaras.index') }}" class="m-search">
        <input type="text" name="texto" value="{{ $texto }}" placeholder="Nombre o sitio…">
        <button type="submit" class="m-btn"><i class="fas fa-search"></i></button>
    </form>

    @if ($camaras->isEmpty())
        <div class="m-empty">
            <i class="fas fa-video" style="font-size:1.6rem;"></i>
            <p>{{ $texto ? 'No se encontraron cámaras.' : 'Buscá por nombre o sitio.' }}</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($camaras as $camara)
                <a href="{{ route('movil.camaras.show', $camara->id) }}" class="m-card">
                    <div class="m-card__title">{{ $camara->nombre }}</div>
                    <div class="m-card__subtitle">{{ $camara->sitio?->nombre ?? 'Sin sitio' }}</div>
                    <div class="m-card__meta">
                        @if ($camara->tipoCamara)
                            <span class="m-chip">{{ $camara->tipoCamara->tipo }}</span>
                        @endif
                        @if ($camara->sitio?->destino)
                            <span class="m-chip"><i class="fas fa-building"></i> {{ $camara->sitio->destino->nombre }}</span>
                        @endif
                        @if (!$camara->sitio?->activo)
                            <span class="m-chip" style="background:var(--m-danger); color:#fff;">Inactiva</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $camaras->links() }}
        </div>
    @endif
@endsection
