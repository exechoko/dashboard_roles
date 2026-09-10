@extends('layouts.movil')

@section('title', 'Inicio')

@section('content')
    @php
        $hora = now()->hour;
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
    @endphp
    <div class="m-home-header">
        <div class="m-home-header__name">{{ $saludo }}, {{ auth()->user()->name ?? auth()->user()->email }}</div>
    </div>

    <div class="m-home-grid">
        @can('ver-flota')
            <a href="{{ route('movil.flota.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--blue"><i class="fas fa-satellite-dish"></i></span>
                <span class="m-home-tile__title">Flota</span>
                <span class="m-home-tile__subtitle">Buscar equipo y ver movimientos</span>
            </a>
        @endcan

        @can('ver-camara')
            <a href="{{ route('movil.camaras.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--teal"><i class="fas fa-video"></i></span>
                <span class="m-home-tile__title">Cámaras</span>
                <span class="m-home-tile__subtitle">Buscar y ver datos de una cámara</span>
            </a>

            <a href="{{ route('movil.mapa.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--green"><i class="fas fa-map-marked-alt"></i></span>
                <span class="m-home-tile__title">Mapa</span>
                <span class="m-home-tile__subtitle">Ubicación de las cámaras</span>
            </a>
        @endcan

        @can('ver-analizador-eventos-cecoco')
            <a href="{{ route('movil.eventos.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--amber"><i class="fas fa-list-alt"></i></span>
                <span class="m-home-tile__title">Eventos CECOCO</span>
                <span class="m-home-tile__subtitle">Buscar eventos y expedientes</span>
            </a>
        @endcan

        @can('ver-dependencia')
            <a href="{{ route('movil.dependencias.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--purple"><i class="fas fa-building"></i></span>
                <span class="m-home-tile__title">Dependencias</span>
                <span class="m-home-tile__subtitle">Teléfonos de comisarías y divisiones</span>
            </a>
        @endcan

        @can('ver-chat')
            <a href="{{ route('movil.chat.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--pink"><i class="fas fa-comments"></i></span>
                <span class="m-home-tile__title">Chat</span>
                <span class="m-home-tile__subtitle">Conversaciones y notificaciones</span>
            </a>
        @endcan

        @can('ver-clave')
            <a href="{{ route('movil.password-vault.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--red"><i class="fas fa-lock"></i></span>
                <span class="m-home-tile__title">Contraseñas</span>
                <span class="m-home-tile__subtitle">Buscar y copiar credenciales guardadas</span>
            </a>
        @endcan

        @can('ver-infraestructura-workers')
            <a href="{{ route('movil.infraestructura.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--indigo"><i class="fas fa-cogs"></i></span>
                <span class="m-home-tile__title">Workers y BD</span>
                <span class="m-home-tile__subtitle">Estado de procesos y tamaño de bases de datos</span>
            </a>
        @endcan
    </div>
@endsection
