@extends('layouts.movil')

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

@section('title', $dependencia->nombre)
@section('back', route('movil.dependencias.index'))

@section('content')
    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            {{ $dependencia->nombre }}
            @if ($dependencia->tipo)
                <span class="m-chip" style="background-color: {{ $dependencia->getBadgeColor() }}; color:#fff;">
                    {{ $etiquetasTipo[$dependencia->tipo] ?? ucfirst($dependencia->tipo) }}
                </span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Teléfono</dt><dd style="white-space:pre-line;">{{ $dependencia->telefono ?: '—' }}</dd></div>
            <div class="m-detail__row"><dt>Ubicación</dt><dd>{{ $dependencia->ubicacion ?: '—' }}</dd></div>
            @if ($dependencia->observaciones)
                <div class="m-detail__row"><dt>Observaciones</dt><dd>{{ $dependencia->observaciones }}</dd></div>
            @endif
        </dl>
    </div>

    @if ($dependencia->getWhatsappUrl())
        <a href="{{ $dependencia->getWhatsappUrl() }}" target="_blank" rel="noopener" class="m-btn" style="width:100%; margin-bottom:1rem;">
            <i class="fab fa-whatsapp"></i> Enviar WhatsApp
        </a>
    @endif

    @if ($dependencia->padre)
        <div class="m-section-title">Depende de</div>
        <a href="{{ route('movil.dependencias.show', $dependencia->padre->id) }}" class="m-card">
            <div class="m-card__title">{{ $dependencia->padre->nombre }}</div>
            @if ($dependencia->padre->tipo)
                <div class="m-card__subtitle">{{ $etiquetasTipo[$dependencia->padre->tipo] ?? ucfirst($dependencia->padre->tipo) }}</div>
            @endif
        </a>
    @endif

    @if ($dependencia->hijos->isNotEmpty())
        <div class="m-section-title">Dependencias subordinadas ({{ $dependencia->hijos->count() }})</div>
        <div class="m-list">
            @foreach ($dependencia->hijos as $hijo)
                <a href="{{ route('movil.dependencias.show', $hijo->id) }}" class="m-card">
                    <div class="m-card__title">{{ $hijo->nombre }}</div>
                    @if ($hijo->tipo)
                        <div class="m-card__meta">
                            <span class="m-chip" style="background-color: {{ $hijo->getBadgeColor() }}; color:#fff;">
                                {{ $etiquetasTipo[$hijo->tipo] ?? ucfirst($hijo->tipo) }}
                            </span>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection
