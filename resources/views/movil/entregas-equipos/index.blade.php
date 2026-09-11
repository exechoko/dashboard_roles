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
                    <div class="m-card__subtitle">{{ $entrega->fecha_entrega->format('d/m/Y') }} · {{ $entrega->personal_receptor ?? 'Sin datos' }}</div>
                    <div class="m-card__meta"><span class="m-chip">{{ $entrega->equipos_pendientes }} equipo(s) sin devolver</span></div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
