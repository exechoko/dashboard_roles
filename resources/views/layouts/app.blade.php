<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Sistema 911</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 4.1.1 -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="//fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
        integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="" />

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.Default.css" />
    <link href="{{ asset('leaflet/geocoder/geocoder.css') }}" rel="stylesheet">
    <!--link href="{{ asset('leaflet/lib/leaflet-dist/leaflet.css') }}" rel="stylesheet"-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-editable/1.1.0/Leaflet.Editable.min.js"></script>
    <script src="{{ asset('leaflet/geocoder/esri-leaflet.js') }}"></script>
    <script src="{{ asset('leaflet/geocoder/esri-leaflet-geocoder.min.js') }}"></script>
    <script src="{{ asset('leaflet/geocoder/gpx.min.js') }}"></script>
    <!-- Enlace a la biblioteca de pantalla completa -->
    <script src="https://unpkg.com/leaflet-fullscreen@1.6.0/dist/Leaflet.fullscreen.min.js"></script>


    @yield('page_css')
    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css') }}">
    @yield('page_css')

    @yield('css')
</head>

<body>

    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar">
                @include('layouts.header')

            </nav>
            <div class="main-sidebar main-sidebar-position">
                @include('layouts.sidebar')
            </div>
            <!-- Main Content -->
            <div class="main-content">
                @include('layouts.alerts')
                <div id="dynamic-content">
                    @yield('content')
                </div>

            </div>
            <footer class="main-footer">
                @include('layouts.footer')
            </footer>
        </div>
    </div>

    @include('profile.change_password')
    @include('profile.edit_profile')

</body>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/iziToast.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.nicescroll.js') }}"></script>
<link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.css">
<script src="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>

<!-- Template JS File -->
<script src="{{ asset('web/js/stisla.js') }}"></script>
<script src="{{ asset('web/js/scripts.js') }}"></script>
<script src="{{ mix('assets/js/profile.js') }}"></script>
<script src="{{ mix('assets/js/custom/custom.js') }}"></script>
@yield('page_js')
@yield('scripts')
<script>
    let loggedInUser = @json(\Illuminate\Support\Facades\Auth::user());
    let loginUrl = '{{ route('login') }}';
    const userUrl = '{{ url('users') }}';
    // Loading button plugin (removed from BS4)
    (function($) {
        $.fn.button = function(action) {
            if (action === 'loading' && this.data('loading-text')) {
                this.data('original-text', this.html()).html(this.data('loading-text')).prop('disabled', true);
            }
            if (action === 'reset' && this.data('original-text')) {
                this.html(this.data('original-text')).prop('disabled', false);
            }
        };
    }(jQuery));
</script>
<script>
    $(document).ready(function() {
        var activeDropdown = null; // Variable para almacenar el menú desplegable activo

        $(window).on('popstate', function(event) {
            var url = location.href;
            loadPage(url);
        });

        // Captura los enlaces del sidebar
        $('ul.sidebar-menu li a').on('click', function(event) {
            var $this = $(this);
            var hasDropdown = $this.parent().hasClass('dropdown');

            // Si es un elemento de menú con desplegable, no recargues la página
            if (hasDropdown) {
                activeDropdown = $this.parent().hasClass('dropdown');
                return;
            }

            var url = $this.attr('href');
            var menuItemId = $this.closest('li').attr('id'); // Obtener el ID del elemento del menú

            if (menuItemId !== 'dashboard'){
                event.preventDefault();
                loadPage(url, menuItemId, hasDropdown);
            }

            history.pushState(null, null, url); // Actualiza la URL en la barra de direcciones
        });
    });

    function loadPage(url, menuItemId, hasDropdown) {
        console.log("url: ", url);
        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                console.log("data", data);
                // Busca el contenido de la sección 'content' en el HTML cargado
                var content = $(data).find('#dynamic-content').html();
                console.log("content", content);
                // Actualiza el contenido del div 'dynamic-content'
                $('#dynamic-content').html(content);

                // Marcar el elemento del menú principal como activo
                /*$('ul.sidebar-menu li').removeClass('active');
                $('#' + menuItemId).addClass('active');*/
                //console.log('hasDropdown', hasDropdown);
                if (hasDropdown){
                    $('ul.sidebar-menu li').removeClass('active');
                    $('#' + menuItemId).addClass('active');
                }

                // Marcar el elemento del submenú como activo
                $('#' + menuItemId + ' ul.dropdown-menu li').removeClass('active');
                $('#' + menuItemId + ' ul.dropdown-menu li').addClass('active');

            },
            error: function(xhr, status, error) {
                console.log(error);
            }
        });
    }
    iziToast.show({
        title: 'Bienvenido ',
        message: 'What would you like to add?'
    });
</script>

</html>
