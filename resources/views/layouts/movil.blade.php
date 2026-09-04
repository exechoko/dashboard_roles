<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#06101f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · C.A.R. 911 Móvil</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('img/pwa-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pwa-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/movil.css') }}" rel="stylesheet" type="text/css">

    @yield('css')
    @stack('styles')
</head>

<body class="m-body">
    <header class="m-topbar">
        @hasSection('back')
            <a href="@yield('back')" class="m-topbar__back"><i class="fas fa-arrow-left"></i></a>
        @else
            <span class="m-topbar__back"></span>
        @endif
        <h1 class="m-topbar__title">@yield('title', 'C.A.R. 911 Móvil')</h1>
    </header>

    <main class="m-page">
        @if (session('error'))
            <div class="m-alert m-alert--danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <nav class="m-bottomnav">
        <a href="{{ route('movil.index') }}" class="{{ request()->routeIs('movil.index') ? 'is-active' : '' }}">
            <i class="fas fa-home"></i><span>Inicio</span>
        </a>
        @can('ver-flota')
            <a href="{{ route('movil.flota.index') }}" class="{{ request()->routeIs('movil.flota.*') ? 'is-active' : '' }}">
                <i class="fas fa-satellite-dish"></i><span>Flota</span>
            </a>
        @endcan
        @can('ver-camara')
            <a href="{{ route('movil.camaras.index') }}" class="{{ request()->routeIs('movil.camaras.*') ? 'is-active' : '' }}">
                <i class="fas fa-video"></i><span>Cámaras</span>
            </a>
            <a href="{{ route('movil.mapa.index') }}" class="{{ request()->routeIs('movil.mapa.*') ? 'is-active' : '' }}">
                <i class="fas fa-map-marked-alt"></i><span>Mapa</span>
            </a>
        @endcan
        @can('ver-analizador-eventos-cecoco')
            <a href="{{ route('movil.eventos.index') }}" class="{{ request()->routeIs('movil.eventos.*') ? 'is-active' : '' }}">
                <i class="fas fa-list-alt"></i><span>Eventos</span>
            </a>
        @endcan
    </nav>

    @yield('scripts')
    @stack('scripts')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/movil' }).catch(function () {});
            });
        }
    </script>
</body>

</html>
