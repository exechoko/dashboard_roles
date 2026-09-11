@extends('layouts.movil')

@section('title', 'Tareas de hoy')
@section('back', route('movil.index'))

@section('content')
    @if ($tareas->isEmpty())
        <div class="m-empty"><p>No hay tareas pendientes ni en proceso para hoy.</p></div>
    @else
        <div class="m-list">
            @foreach ($tareas as $item)
                <div class="m-card">
                    <div class="m-card__title">{{ $item->tarea->nombre ?? 'Sin título' }}</div>
                    <div class="m-card__subtitle">{{ $item->fecha_programada->format('d/m/Y') }}</div>
                    <div class="m-card__meta">
                        <span class="m-chip">{{ \App\Models\TareaItem::ESTADOS[$item->estado] ?? $item->estado }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
