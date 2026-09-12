@extends('layouts.movil')

@section('title', 'Equipos entregados')
@section('back', route('movil.index'))

@section('content')
    @if ($entregas->isEmpty())
        <div class="m-empty"><p>No hay entregas de equipos activas.</p></div>
    @else
        <div class="m-list">
            @foreach ($entregas as $entrega)
                <div class="m-card">
                    <div class="m-card__title">{{ $entrega->dependencia ?? 'Sin dependencia' }}</div>
                    <div class="m-card__subtitle">{{ $entrega->fecha_entrega->format('d/m/Y') }} · Recibió: {{ $entrega->personal_receptor ?? 'Sin datos' }}</div>
                    <div class="m-card__meta">
                        <button type="button" class="m-chip m-chip--action"
                            data-modal-tpl="entrega-equipo-{{ $entrega->id }}"
                            data-modal-title="{{ $entrega->dependencia ?? 'Equipos entregados' }}">
                            <i class="fas fa-list"></i> {{ $entrega->equipos_pendientes->count() }} equipo(s) sin devolver
                        </button>
                    </div>
                </div>

                <template id="entrega-equipo-{{ $entrega->id }}">
                    <div class="m-modal__meta">
                        {{ $entrega->fecha_entrega->format('d/m/Y') }} · Recibió: {{ $entrega->personal_receptor ?? 'Sin datos' }}
                    </div>
                    <div class="m-list">
                        @foreach ($entrega->equipos_pendientes as $flotaGeneral)
                            <div class="m-card">
                                <div class="m-card__title">{{ $flotaGeneral->equipo->nombre_issi ?? ('Equipo #' . $flotaGeneral->equipo_id) }}</div>
                                <div class="m-card__subtitle">
                                    TEI: {{ $flotaGeneral->equipo->tei ?? 'N/A' }} · ISSI: {{ $flotaGeneral->equipo->issi ?? 'N/A' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
            @endforeach
        </div>
    @endif

    @include('movil.partials.modal')
@endsection
