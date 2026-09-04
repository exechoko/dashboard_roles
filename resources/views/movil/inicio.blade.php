@extends('layouts.movil')

@section('title', 'Inicio')

@section('content')
    <p class="m-card__subtitle" style="margin-bottom:1rem;">
        Hola, {{ auth()->user()->name ?? auth()->user()->email }}. Elegí qué querés consultar.
    </p>

    <div class="m-home-grid">
        @can('ver-flota')
            <a href="{{ route('movil.flota.index') }}" class="m-home-tile">
                <i class="fas fa-satellite-dish"></i>
                <span class="m-home-tile__title">Flota</span>
                <span class="m-home-tile__subtitle">Buscar equipo y ver movimientos</span>
            </a>
        @endcan

        @can('ver-camara')
            <a href="{{ route('movil.camaras.index') }}" class="m-home-tile">
                <i class="fas fa-video"></i>
                <span class="m-home-tile__title">Cámaras</span>
                <span class="m-home-tile__subtitle">Buscar y ver datos de una cámara</span>
            </a>

            <a href="{{ route('movil.mapa.index') }}" class="m-home-tile">
                <i class="fas fa-map-marked-alt"></i>
                <span class="m-home-tile__title">Mapa</span>
                <span class="m-home-tile__subtitle">Ubicación de las cámaras</span>
            </a>
        @endcan

        @can('ver-analizador-eventos-cecoco')
            <a href="{{ route('movil.eventos.index') }}" class="m-home-tile">
                <i class="fas fa-list-alt"></i>
                <span class="m-home-tile__title">Eventos CECOCO</span>
                <span class="m-home-tile__subtitle">Buscar eventos y expedientes</span>
            </a>
        @endcan
    </div>
@endsection
