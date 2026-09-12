<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#06101f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('services.webpush.public_key') }}">
    <title>@yield('title', 'Inicio') · C.A.R. 911 Móvil</title>

    <script>
        // Aplica el tema ANTES de pintar la página, para no hacer flash.
        // Preferencia guardada en el perfil (misma que el escritorio) o,
        // si no hay, la última elegida en este celular.
        (function () {
            var userTheme = @json(auth()->user()->theme ?? null);
            var theme = userTheme;
            if (!theme) {
                try { theme = localStorage.getItem('movil-theme'); } catch (e) {}
            }
            if (theme === 'dark' || theme === 'light') {
                document.documentElement.setAttribute('data-theme', theme);
            }
            if (userTheme) {
                try { localStorage.setItem('movil-theme', userTheme); } catch (e) {}
            }
        })();
    </script>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('img/pwa-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pwa-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">

    {{-- Sistema de diseño de la PWA (ver docs/design_pwa): tipografía e íconos --}}
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('css/movil.css') }}?v={{ filemtime(public_path('css/movil.css')) }}" rel="stylesheet" type="text/css">

    @yield('css')
    @stack('styles')
</head>

<body class="m-body @hasSection('hideNav') m-body--no-nav @endif">
    <header class="m-topbar">
        @hasSection('back')
            <a href="@yield('back')" class="m-topbar__back"><span class="material-symbols-outlined">arrow_back</span></a>
        @else
            <span class="m-topbar__back"></span>
        @endif
        <h1 class="m-topbar__title">@yield('title', 'C.A.R. 911 Móvil')</h1>
        <button type="button" class="m-topbar__theme" id="mThemeToggle" aria-label="Cambiar tema claro/oscuro">
            <span class="material-symbols-outlined" id="mThemeIcon">contrast</span>
        </button>
        <a href="{{ route('movil.logout') }}" class="m-topbar__theme" id="mLogoutBtn" aria-label="Cerrar sesión"
            onclick="event.preventDefault(); localStorage.clear(); document.getElementById('mLogoutForm').submit();">
            <span class="material-symbols-outlined">logout</span>
        </a>
        <form id="mLogoutForm" action="{{ route('movil.logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </header>

    <div class="m-install-banner" id="mInstallBanner" hidden>
        <span class="material-symbols-outlined">install_mobile</span>
        <span id="mInstallBannerText">Instalá esta app en tu celular para tenerla a mano.</span>
        <button type="button" id="mInstallBtn" class="m-btn" style="padding:.35rem .8rem; font-size:.82rem; display:none;">Instalar</button>
        <button type="button" id="mInstallDismiss" class="m-install-banner__close" aria-label="Cerrar">&times;</button>
    </div>

    <main class="m-page">
        @if (session('error'))
            <div class="m-alert m-alert--danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    @unless ($__env->hasSection('hideNav'))
        <nav class="m-bottomnav">
            <a href="{{ route('movil.index') }}" class="{{ request()->routeIs('movil.index') ? 'is-active' : '' }}">
                <span class="material-symbols-outlined">home</span><span>Inicio</span>
            </a>
            @can('ver-flota')
                <a href="{{ route('movil.flota.index') }}" class="{{ request()->routeIs('movil.flota.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">local_police</span><span>Flota</span>
                </a>
            @endcan
            @can('ver-camara')
                <a href="{{ route('movil.camaras.index') }}" class="{{ request()->routeIs('movil.camaras.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">videocam</span><span>Cámaras</span>
                </a>
                <a href="{{ route('movil.mapa.index') }}" class="{{ request()->routeIs('movil.mapa.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">map</span><span>Mapa</span>
                </a>
            @endcan
            @can('ver-analizador-eventos-cecoco')
                <a href="{{ route('movil.eventos.index') }}" class="{{ request()->routeIs('movil.eventos.*') ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">emergency</span><span>Eventos</span>
                </a>
            @endcan
        </nav>
    @endunless

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/movil' }).catch(function () {});
            });
        }
    </script>

    {{-- window.WebPush: definido ANTES de los scripts de cada página a
         propósito, las páginas de /movil/chat lo usan apenas cargan. --}}
    @include('partials.web-push')

    @yield('scripts')
    @stack('scripts')

    <script>
        (function () {
            function esOscuroActual() {
                var explicito = document.documentElement.getAttribute('data-theme');
                if (explicito === 'dark') return true;
                if (explicito === 'light') return false;
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            document.getElementById('mThemeToggle').addEventListener('click', function () {
                var nuevo = esOscuroActual() ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', nuevo);

                try { localStorage.setItem('movil-theme', nuevo); } catch (e) {}

                fetch('{{ route('profile.updateTheme') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ theme: nuevo }),
                }).catch(function () {});
            });
        })();
    </script>

    <script>
        // Botón explícito de "Instalar app": Chrome ya no muestra un aviso
        // automático, solo un ícono chico en la barra de direcciones que es
        // fácil no ver. Este banner lo hace visible dentro de la propia app.
        (function () {
            var LS_KEY = 'movil-install-dismissed';
            var banner = document.getElementById('mInstallBanner');
            var btn = document.getElementById('mInstallBtn');
            var text = document.getElementById('mInstallBannerText');
            var dismiss = document.getElementById('mInstallDismiss');
            var deferredPrompt = null;

            function yaInstalada() {
                return window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;
            }

            function fueDescartado() {
                try { return localStorage.getItem(LS_KEY) === '1'; } catch (e) { return false; }
            }

            function ocultar() {
                banner.hidden = true;
            }

            dismiss.addEventListener('click', function () {
                try { localStorage.setItem(LS_KEY, '1'); } catch (e) {}
                ocultar();
            });

            if (yaInstalada() || fueDescartado()) {
                // nada que mostrar
            } else {
                var esIOS = /iP(hone|od|ad)/.test(navigator.userAgent) && !window.MSStream;

                if (esIOS) {
                    // iOS Safari no dispara beforeinstallprompt: instalación manual.
                    text.textContent = 'Para instalarla: tocá Compartir y elegí "Agregar a inicio".';
                    banner.hidden = false;
                } else {
                    window.addEventListener('beforeinstallprompt', function (e) {
                        e.preventDefault();
                        deferredPrompt = e;
                        btn.style.display = '';
                        banner.hidden = false;
                    });
                }

                window.addEventListener('appinstalled', ocultar);
            }

            btn.addEventListener('click', function () {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                deferredPrompt.userChoice.finally(function () {
                    deferredPrompt = null;
                    ocultar();
                });
            });
        })();
    </script>
</body>

</html>
