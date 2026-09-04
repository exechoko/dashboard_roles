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
    <link href="{{ asset('css/movil.css') }}" rel="stylesheet" type="text/css">

    @yield('css')
    @stack('styles')
</head>

<body class="m-body @hasSection('hideNav') m-body--no-nav @endif">
    <header class="m-topbar">
        @hasSection('back')
            <a href="@yield('back')" class="m-topbar__back"><i class="fas fa-arrow-left"></i></a>
        @else
            <span class="m-topbar__back"></span>
        @endif
        <h1 class="m-topbar__title">@yield('title', 'C.A.R. 911 Móvil')</h1>
        <button type="button" class="m-topbar__theme" id="mThemeToggle" aria-label="Cambiar tema claro/oscuro">
            <i class="fas fa-adjust" id="mThemeIcon"></i>
        </button>
    </header>

    <div class="m-install-banner" id="mInstallBanner" hidden>
        <i class="fas fa-mobile-alt"></i>
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
            @can('ver-dependencia')
                <a href="{{ route('movil.dependencias.index') }}" class="{{ request()->routeIs('movil.dependencias.*') ? 'is-active' : '' }}">
                    <i class="fas fa-building"></i><span>Dependencias</span>
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

    <script>
        // Helper compartido para activar/desactivar notificaciones push del
        // chat. Definido ANTES de los scripts de cada página a propósito:
        // las páginas de /movil/chat lo usan apenas cargan.
        window.MovilPush = (function () {
            function base64UrlToUint8Array(base64Url) {
                var padding = '='.repeat((4 - base64Url.length % 4) % 4);
                var base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
                var raw = atob(base64);
                var salida = new Uint8Array(raw.length);
                for (var i = 0; i < raw.length; ++i) {
                    salida[i] = raw.charCodeAt(i);
                }
                return salida;
            }

            function csrfHeaders(extra) {
                return Object.assign({
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                }, extra || {});
            }

            function soportado() {
                return 'serviceWorker' in navigator && 'PushManager' in window;
            }

            function suscripcionActual() {
                if (!soportado()) return Promise.resolve(null);
                return navigator.serviceWorker.ready.then(function (reg) {
                    return reg.pushManager.getSubscription();
                });
            }

            function activar() {
                if (!soportado()) return Promise.reject(new Error('no soportado'));

                return Notification.requestPermission().then(function (permiso) {
                    if (permiso !== 'granted') {
                        throw new Error('permiso denegado');
                    }
                    return navigator.serviceWorker.ready;
                }).then(function (reg) {
                    var vapidKey = document.querySelector('meta[name="vapid-public-key"]').content;
                    return reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: base64UrlToUint8Array(vapidKey),
                    });
                }).then(function (suscripcion) {
                    return fetch('{{ route('movil.push.store') }}', {
                        method: 'POST',
                        headers: csrfHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                        body: JSON.stringify(suscripcion.toJSON()),
                    }).then(function () { return suscripcion; });
                });
            }

            function desactivar() {
                return suscripcionActual().then(function (suscripcion) {
                    if (!suscripcion) return;
                    var endpoint = suscripcion.endpoint;
                    return suscripcion.unsubscribe().then(function () {
                        return fetch('{{ route('movil.push.destroy') }}', {
                            method: 'DELETE',
                            headers: csrfHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                            body: JSON.stringify({ endpoint: endpoint }),
                        });
                    });
                });
            }

            return {
                soportado: soportado,
                suscripcionActual: suscripcionActual,
                activar: activar,
                desactivar: desactivar,
            };
        })();
    </script>

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
