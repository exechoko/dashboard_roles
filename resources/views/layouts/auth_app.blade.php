<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <link rel="shortcut icon" type="image/ico" href="{{ asset('/img/logo.ico') }}">
    <link rel="shortcut icon" sizes="192x192" href="{{ asset('/img/logo.ico') }}">
    <title>@yield('title')</title>

    <!-- General CSS Files -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --auth-bg: #06101f;
            --auth-surface: rgba(7, 20, 38, 0.84);
            --auth-surface-strong: rgba(8, 27, 50, 0.94);
            --auth-border: rgba(0, 229, 255, 0.24);
            --auth-text: #eaf6ff;
            --auth-muted: #96abc3;
            --auth-cyan: #00e5ff;
            --auth-violet: #8b5cf6;
            --auth-danger: #ff355d;
            --auth-success: #00f2a6;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            background: var(--auth-bg) !important;
            color: var(--auth-text);
            overflow-x: hidden;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .video-background video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Video overlay for better content readability */
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 18% 18%, rgba(0, 229, 255, 0.24), transparent 28rem),
                radial-gradient(circle at 80% 72%, rgba(139, 92, 246, 0.22), transparent 26rem),
                linear-gradient(120deg, rgba(3, 8, 18, 0.9), rgba(5, 16, 31, 0.78));
            z-index: -1;
        }

        #app {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }

        .auth-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 42px 0;
        }

        .auth-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.72fr);
            gap: 28px;
            align-items: stretch;
        }

        .auth-hero,
        .auth-panel {
            border: 1px solid var(--auth-border);
            background: var(--auth-surface);
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.42), 0 0 42px rgba(0, 229, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }

        .auth-hero {
            position: relative;
            min-height: 580px;
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(0, 229, 255, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 229, 255, 0.08) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(120deg, rgba(0, 0, 0, 0.95), transparent 78%);
            pointer-events: none;
        }

        .auth-brand {
            position: relative;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .auth-logo {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(0, 229, 255, 0.38);
            box-shadow: 0 0 28px rgba(0, 229, 255, 0.32);
        }

        .auth-logo img {
            width: 58px;
            height: 58px;
            border-radius: 50%;
        }

        .auth-kicker {
            color: var(--auth-cyan);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .auth-title {
            color: #ffffff;
            font-size: clamp(2.1rem, 4vw, 4.2rem);
            font-weight: 900;
            line-height: 0.96;
            letter-spacing: -0.06em;
            margin-bottom: 18px;
        }

        .auth-copy {
            color: var(--auth-muted);
            max-width: 560px;
            font-size: 1.02rem;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .auth-credit {
            position: relative;
            max-width: 620px;
            padding: 18px 20px;
            border-radius: 18px;
            background: rgba(0, 229, 255, 0.08);
            border: 1px solid rgba(0, 229, 255, 0.18);
        }

        .auth-credit span {
            display: block;
            color: var(--auth-muted);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .auth-credit strong {
            display: block;
            color: #ffffff;
            font-size: 1rem;
            line-height: 1.4;
        }

        .auth-panel {
            background: var(--auth-surface-strong);
            padding: 30px;
            display: flex;
            align-items: center;
        }

        .auth-panel-inner {
            width: 100%;
        }

        .simple-footer {
            color: rgba(234, 246, 255, 0.62);
            text-align: center;
            font-size: 0.78rem;
            margin-top: 18px;
        }

        @media (max-width: 991.98px) {
            .auth-shell {
                grid-template-columns: 1fr;
                max-width: 460px;
                margin: 0 auto;
            }

            .auth-hero {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .video-background {
                display: none;
            }

            body {
                background-image: url('{{ asset('img/video_vigilancia_oscura.webp') }}') !important;
                background-size: cover;
                background-position: center center;
                background-attachment: fixed;
            }

            .auth-section {
                padding: 18px 0;
            }

            .auth-hero,
            .auth-panel {
                border-radius: 22px;
            }

            .auth-hero {
                padding: 26px;
            }

            .auth-panel {
                padding: 18px;
            }

        }

        @media (orientation: portrait) and (min-width: 769px) {
            .video-background video {
                width: auto;
                height: 100%;
                min-width: 100vw;
            }
        }
    </style>
</head>

<body>
    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="{{ asset('videos/video_car911.mp4') }}" type="video/mp4">
            <!--source src="{{-- asset('videos/car911login.mp4')--}}" type="video/mp4"-->
            <!-- Fallback for browsers that don't support video -->
            Your browser does not support the video tag.
        </video>
    </div>

    <!-- Video Overlay -->
    <div class="video-overlay"></div>

    <div id="app">
        <section class="auth-section">
            <div class="container">
                <div class="auth-shell">
                    <aside class="auth-hero">
                        <div class="auth-brand">
                            <div class="auth-logo">
                                <img src="{{ asset('img/logo.ico') }}" alt="C.A.R. 911">
                            </div>
                            <div>
                                <div class="auth-kicker">Centro de monitoreo</div>
                                <strong>C.A.R. 911</strong>
                            </div>
                        </div>

                        <div>
                            <div class="auth-kicker">Acceso operacional seguro</div>
                            <h1 class="auth-title">Control y Administración de Recursos 911</h1>
                            <p class="auth-copy">
                                Cámaras, móviles, eventos y llamados coordinados desde una única consola.
                            </p>
                        </div>

                        <div class="auth-credit">
                            <span>Desarrollado por</span>
                            <strong>Sección Técnica - División 911 y Videovigilancia</strong>
                        </div>
                    </aside>

                    <main class="auth-panel">
                        <div class="auth-panel-inner">
                            @yield('content')
                            <div class="simple-footer">
                                Acceso restringido a personal autorizado
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </section>
    </div>

    <!-- General JS Scripts -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.nicescroll.js') }}"></script>

    <!-- JS Libraies -->

    <!-- Template JS File -->
    <script src="{{ asset('web/js/stisla.js') }}"></script>
    <script src="{{ asset('web/js/scripts.js') }}"></script>

    <script>
        $(document).ready(function () {
            // Video performance optimization
            const video = document.querySelector('.video-background video');
            if (video) {
                // Pause video on mobile to save battery and data
                if (window.innerWidth <= 768) {
                    video.pause();
                    // Hide video container on mobile and use fallback background
                    $('.video-background').hide();
                }

                // Pause video when page is not visible (tab switching)
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        video.pause();
                    } else if (window.innerWidth > 768) {
                        video.play();
                    }
                });

                // Handle window resize
                $(window).resize(function () {
                    if (window.innerWidth <= 768) {
                        video.pause();
                        $('.video-background').hide();
                    } else {
                        $('.video-background').show();
                        video.play();
                    }
                });
            }
        });
    </script>

    <!-- Page Specific JS File -->
</body>

</html>
