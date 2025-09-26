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
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        /* Video background styles */
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
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        /* Ensure content is above video */
        #app {
            position: relative;
            z-index: 1;
        }

        /* Remove the old background image from body */
        body {
            background-image: none !important;
            background-color: #000;
        }

        /* Improve card visibility over video */
        .card,
        .login-brand {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        /* Make login brand background semi-transparent */
        .login-brand {
            padding: 20px;
            margin-bottom: 20px;
        }

        .login-brand h2 {
            color: #333 !important;
            /* Change text color to dark for better visibility */
        }

        /* Estilo para el fondo oscuro (keeping original overlay styles) */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }

        /* Estilo para el contenedor del logo */
        .logo-container {
            width: 100px;
            height: 100px;
            background-color: white;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        /* Estilo para la imagen del logo */
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
        }

        /* Responsive video adjustments */
        @media (max-width: 768px) {
            .video-background video {
                width: 100%;
                height: auto;
                min-height: 100vh;
            }

            /* Pause video on mobile to save battery */
            .video-background {
                display: none;
            }

            /* Fallback background for mobile */
            body {
                background-image: url('{{ asset('img/video_vigilancia_oscura.webp') }}') !important;
                background-size: cover;
                background-position: center center;
                background-attachment: fixed;
            }
        }

        @media (orientation: portrait) and (min-width: 769px) {
            .video-background video {
                width: auto;
                height: 100%;
                min-width: 100vw;
            }
        }

        /* Ensure footer is visible */
        .simple-footer {
            color: white;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
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
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="login-brand">
                            <div class="logo-container">
                                <img src="{{ asset('img/logo.ico') }}" alt="logo" class="logo-img">
                            </div>
                            <h2 style="margin-top: 10px; text-transform: none;">Control y Administración de Recursos 911
                            </h2>
                        </div>
                        @yield('content')
                        <div class="simple-footer">
                            {{-- Copyright &copy; {{ getSettingValue('application_name') }} {{ date('Y') }} --}}
                        </div>
                    </div>
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
