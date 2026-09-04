@extends('layouts.movil')

@section('title', 'Dependencias')

@section('content')
    @php
        $etiquetasTipo = [
            'jefatura' => 'Jefatura',
            'subjefatura' => 'Subjefatura',
            'direccion' => 'Dirección',
            'departamental' => 'Departamental',
            'division' => 'División',
            'comisaria' => 'Comisaría',
            'seccion' => 'Sección',
            'destacamento' => 'Destacamento',
        ];
    @endphp

    <form method="GET" action="{{ route('movil.dependencias.index') }}" class="m-search">
        <input type="text" name="texto" value="{{ $texto }}" placeholder="Nombre, teléfono o dirección…">
        <button type="submit" class="m-btn"><i class="fas fa-search"></i></button>
    </form>

    @if ($dependencias->isEmpty())
        <div class="m-empty">
            <i class="fas fa-building" style="font-size:1.6rem;"></i>
            <p>No se encontraron dependencias.</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($dependencias as $dependencia)
                <div class="m-card">
                    <div class="m-card__title">{{ $dependencia->nombre }}</div>
                    @if ($dependencia->ubicacion)
                        <div class="m-card__subtitle"><i class="fas fa-map-marker-alt"></i> {{ $dependencia->ubicacion }}</div>
                    @endif
                    @if ($dependencia->tipo)
                        <div class="m-card__meta">
                            <span class="m-chip" style="background-color: {{ $dependencia->getBadgeColor() }}; color:#fff;">
                                {{ $etiquetasTipo[$dependencia->tipo] ?? ucfirst($dependencia->tipo) }}
                            </span>
                        </div>
                    @endif
                    @if ($dependencia->telefono)
                        <div class="m-card__subtitle" style="margin-top:.5rem; white-space:pre-line;"><i class="fas fa-phone"></i> {{ $dependencia->telefono }}</div>
                    @endif
                    <div class="m-card__meta">
                        <a href="{{ route('movil.dependencias.show', $dependencia->id) }}" class="m-btn m-btn--outline" style="padding:.35rem .8rem; font-size:.82rem;">
                            <i class="fas fa-info-circle"></i> Ver más
                        </a>
                        @if ($dependencia->getWhatsappUrl())
                            <a href="{{ $dependencia->getWhatsappUrl() }}" target="_blank" rel="noopener" class="m-btn m-btn--outline" style="padding:.35rem .8rem; font-size:.82rem;">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $dependencias->links() }}
        </div>
    @endif
@endsection
