@extends('layouts.movil')

@section('title', 'Bodycams entregadas')
@section('back', route('movil.index'))

@section('content')
    @if ($entregas->isEmpty())
        <div class="m-empty"><p>No hay entregas de bodycams activas.</p></div>
    @else
        <div class="m-list">
            @foreach ($entregas as $entrega)
                <div class="m-card">
                    <div class="m-card__title">{{ $entrega->dependencia ?? 'Sin dependencia' }}</div>
                    <div class="m-card__subtitle">{{ $entrega->fecha_entrega->format('d/m/Y') }} · Recibió: {{ $entrega->personal_receptor ?? 'Sin datos' }}</div>
                    <div class="m-card__meta">
                        <button type="button" class="m-chip m-chip--action"
                            data-modal-tpl="entrega-bodycam-{{ $entrega->id }}"
                            data-modal-title="{{ $entrega->dependencia ?? 'Bodycams entregadas' }}">
                            <i class="fas fa-list"></i> {{ $entrega->bodycams_pendientes->count() }} bodycam(s) sin devolver
                        </button>
                    </div>
                </div>

                <template id="entrega-bodycam-{{ $entrega->id }}">
                    <div class="m-modal__meta">
                        {{ $entrega->fecha_entrega->format('d/m/Y') }} · Recibió: {{ $entrega->personal_receptor ?? 'Sin datos' }}
                    </div>
                    <div class="m-list">
                        @foreach ($entrega->bodycams_pendientes as $bodycam)
                            <div class="m-card">
                                <div class="m-card__title">{{ $bodycam->codigo ?? ('Bodycam #' . $bodycam->id) }}</div>
                                <div class="m-card__subtitle">N° serie: {{ $bodycam->numero_serie ?? 'N/A' }}</div>
                            </div>
                        @endforeach
                    </div>
                </template>
            @endforeach
        </div>
    @endif

    @include('movil.partials.modal')
@endsection
