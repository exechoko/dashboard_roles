@extends('layouts.movil')

@section('title', 'Flota')

@section('content')
    <form method="GET" action="{{ route('movil.flota.index') }}" class="m-search" id="mFlotaBusqueda">
        <input type="text" name="texto" id="mFlotaTexto" value="{{ $texto }}" placeholder="TEI, ISSI, móvil o destino…">
        <button type="button" class="m-btn m-btn--outline" id="mFlotaBtnEscanear" aria-label="Escanear código">
            <i class="fas fa-barcode"></i>
        </button>
        <button type="submit" class="m-btn"><i class="fas fa-search"></i></button>
    </form>

    @include('movil.partials.scanner')

    @if ($flota->isEmpty())
        <div class="m-empty">
            <i class="fas fa-satellite-dish" style="font-size:1.6rem;"></i>
            <p>{{ $texto ? 'No se encontraron equipos.' : 'Buscá por TEI, ISSI, móvil o destino.' }}</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($flota as $f)
                @php $mov = $f->ultimo_movimiento_calculado; @endphp
                <a href="{{ route('movil.flota.show', $f->id) }}" class="m-card">
                    <div class="m-card__title">
                        {{ $f->equipo->tei ?? '—' }}
                        @if ($f->equipo?->issi)
                            <span class="m-card__subtitle">· ISSI {{ $f->equipo->issi }}</span>
                        @endif
                    </div>
                    <div class="m-card__subtitle">
                        {{ $f->equipo?->tipo_terminal?->marca }} {{ $f->equipo?->tipo_terminal?->modelo }}
                    </div>
                    <div class="m-card__meta">
                        @if ($f->equipo?->estado)
                            <span class="m-chip">{{ $f->equipo->estado->nombre }}</span>
                        @endif
                        @if ($f->recurso)
                            <span class="m-chip"><i class="fas fa-car-side"></i> {{ $f->recurso->nombre }}</span>
                        @endif
                        @if ($f->destino)
                            <span class="m-chip"><i class="fas fa-building"></i> {{ $f->destino->nombre }}</span>
                        @endif
                        @if ($mov)
                            <span class="m-chip" style="background-color: {{ $mov->tipoMovimiento->color ?? '#6777ef' }}; color:#fff;">
                                {{ $mov->tipoMovimiento->nombre ?? 'Movimiento' }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $flota->links() }}
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        document.getElementById('mFlotaBtnEscanear').addEventListener('click', function () {
            mAbrirEscaner(function (texto) {
                document.getElementById('mFlotaTexto').value = texto.trim();
                document.getElementById('mFlotaBusqueda').submit();
            });
        });
    </script>
@endsection
