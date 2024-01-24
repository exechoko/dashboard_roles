<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>@yield('title')</title>

    <!-- General CSS Files -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css"/>
</head>

<body style="background-image: url('{{ asset('img/video_vigilancia_oscura.webp') }}'); background-size: cover; background-position: center center; background-attachment: fixed;">
<div id="app">
    <section class="section">
        <div class="container mt-5">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <div class="login-brand">
                        <img src="{{ asset('img/logo.ico') }}" alt="logo" width="100">
                        <h2 style="margin-top: 10px; text-transform: none; color: white;">Control y Administración de Recursos 911</h2>
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
<style>
    /* Estilo para el fondo oscuro */
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Ajusta el color y la opacidad según tus necesidades */
        pointer-events: none; /* Permite hacer clic a través del fondo oscuro */
    }
    /* Estilo para el contenedor del logo */
    .logo-container {
        width: 100px; /* Ajusta el tamaño del contenedor según tus necesidades */
        height: 100px;
        background-color: white; /* Color del círculo blanco */
        border-radius: 50%; /* Hace que el contenedor sea un círculo */
        overflow: hidden; /* Recorta cualquier contenido que se salga del círculo */
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto; /* Centra el contenedor en el eje horizontal */
    }

    /* Estilo para la imagen del logo */
    .logo-img {
        width: 80px; /* Ajusta el tamaño de la imagen según tus necesidades */
        height: 80px;
        border-radius: 50%; /* Hace que la imagen sea un círculo */
    }
</style>

<!-- General JS Scripts -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.nicescroll.js') }}"></script>

<!-- JS Libraies -->

<!-- Template JS File -->
<script src="{{ asset('web/js/stisla.js') }}"></script>
<script src="{{ asset('web/js/scripts.js') }}"></script>
<!-- Page Specific JS File -->
</body>
</html>
