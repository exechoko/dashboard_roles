@extends('layouts.movil')

@section('title', 'Inicio')

@section('content')
    @php
        $hora = now()->hour;
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
        $rol = auth()->user()->getRoleNames()->first();
    @endphp
    <div class="m-home-header">
        <div>
            <h1 class="m-home-header__name">{{ $saludo }}, {{ auth()->user()->name ?? auth()->user()->email }}</h1>
            @if($rol)
                <div class="m-home-header__sub">{{ $rol }}</div>
            @endif
        </div>
        <div class="m-home-header__status" id="mConnStatus">
            <span class="dot"></span><span id="mConnStatusText">En línea</span>
        </div>
    </div>

    @php
        $hayNovedades = (auth()->user()->can('ver-entrega-equipos') && $cantEquiposEntregados > 0)
            || (auth()->user()->can('ver-entrega-bodycams') && $cantBodycamsEntregadas > 0)
            || (auth()->user()->canAny(['ver-tarea', 'crear-tarea', 'editar-tarea', 'borrar-tarea']) && $cantTareasHoy > 0)
            || (auth()->user()->can('ver-activacion-totem') && $cantActivacionesTotemPendientes > 0);
    @endphp

    @if($hayNovedades)
        <div class="m-section-title"><span class="material-symbols-outlined">priority_high</span> Novedades</div>
        <div class="m-list">
            @can('ver-entrega-equipos')
                @if($cantEquiposEntregados > 0)
                    <a href="{{ route('movil.entregas-equipos.index') }}" class="m-metric-strip m-metric-strip--alert">
                        <span class="m-metric-strip__icon m-tile-icon--amber"><span class="material-symbols-outlined">devices</span></span>
                        <span class="m-metric-strip__body">
                            <span class="m-metric-strip__title">Equipos entregados</span>
                            <span class="m-metric-strip__row">
                                <span class="m-metric-strip__value">{{ $cantEquiposEntregados }}</span>
                                <span class="m-metric-strip__label">sin devolver</span>
                            </span>
                        </span>
                        <span class="m-metric-strip__chevron material-symbols-outlined">chevron_right</span>
                    </a>
                @endif
            @endcan

            @can('ver-entrega-bodycams')
                @if($cantBodycamsEntregadas > 0)
                    <a href="{{ route('movil.entregas-bodycams.index') }}" class="m-metric-strip m-metric-strip--alert">
                        <span class="m-metric-strip__icon m-tile-icon--amber"><span class="material-symbols-outlined">photo_camera</span></span>
                        <span class="m-metric-strip__body">
                            <span class="m-metric-strip__title">Bodycams entregadas</span>
                            <span class="m-metric-strip__row">
                                <span class="m-metric-strip__value">{{ $cantBodycamsEntregadas }}</span>
                                <span class="m-metric-strip__label">sin devolver</span>
                            </span>
                        </span>
                        <span class="m-metric-strip__chevron material-symbols-outlined">chevron_right</span>
                    </a>
                @endif
            @endcan

            @canany(['ver-tarea', 'crear-tarea', 'editar-tarea', 'borrar-tarea'])
                @if($cantTareasHoy > 0)
                    <a href="{{ route('movil.tareas.index') }}" class="m-metric-strip">
                        <span class="m-metric-strip__icon m-tile-icon--blue"><span class="material-symbols-outlined">assignment_turned_in</span></span>
                        <span class="m-metric-strip__body">
                            <span class="m-metric-strip__title">Tareas de hoy</span>
                            <span class="m-metric-strip__row">
                                <span class="m-metric-strip__value">{{ $cantTareasHoy }}</span>
                                <span class="m-metric-strip__label">pendiente(s) o en proceso</span>
                            </span>
                        </span>
                        <span class="m-metric-strip__chevron material-symbols-outlined">chevron_right</span>
                    </a>
                @endif
            @endcanany

            @can('ver-activacion-totem')
                @if($cantActivacionesTotemPendientes > 0)
                    <a href="{{ route('movil.activaciones-totem.index') }}" class="m-metric-strip">
                        <span class="m-metric-strip__icon m-tile-icon--teal"><span class="material-symbols-outlined">cell_tower</span></span>
                        <span class="m-metric-strip__body">
                            <span class="m-metric-strip__title">Activaciones Tótem</span>
                            <span class="m-metric-strip__row">
                                <span class="m-metric-strip__value">{{ $cantActivacionesTotemPendientes }}</span>
                                <span class="m-metric-strip__label">pendiente(s)</span>
                                @if($cantActivacionesTotemVencidas > 0)
                                    <span class="m-chip">{{ $cantActivacionesTotemVencidas }} vencida(s)</span>
                                @endif
                            </span>
                        </span>
                        <span class="m-metric-strip__chevron material-symbols-outlined">chevron_right</span>
                    </a>
                @endif
            @endcan
        </div>
    @endif

    @php
        $hayAccesos = auth()->user()->canAny([
            'ver-dependencia', 'ver-personal', 'ver-chat', 'ver-clave',
            'ver-historico-movil-gis-cecoco', 'ver-infraestructura-workers',
        ]);
    @endphp

    {{-- Flota, Cámaras, Mapa y Eventos CECOCO no se repiten acá: ya están en
         el menú inferior (layouts/movil.blade.php). Dependencias y Personal
         se movieron al grid para aliviar el menú inferior a 5 ítems. --}}
    @if($hayAccesos)
        <div class="m-section-title"><span class="material-symbols-outlined">apps</span> Accesos</div>
        <div class="m-home-grid">
        @can('ver-dependencia')
            <a href="{{ route('movil.dependencias.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--purple"><span class="material-symbols-outlined">domain</span></span>
                <span class="m-home-tile__title">Dependencias</span>
                <span class="m-home-tile__subtitle">Teléfonos de comisarías y divisiones</span>
            </a>
        @endcan

        @can('ver-personal')
            <a href="{{ route('movil.personal.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--green"><span class="material-symbols-outlined">group</span></span>
                <span class="m-home-tile__title">Personal</span>
                <span class="m-home-tile__subtitle">Buscar legajos y datos de contacto</span>
            </a>
        @endcan

        @can('ver-chat')
            <a href="{{ route('movil.chat.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--pink"><span class="material-symbols-outlined">forum</span></span>
                <span class="m-home-tile__title">Chat</span>
                <span class="m-home-tile__subtitle">Conversaciones y notificaciones</span>
            </a>
        @endcan

        @can('ver-clave')
            <a href="{{ route('movil.password-vault.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--red"><span class="material-symbols-outlined">lock</span></span>
                <span class="m-home-tile__title">Contraseñas</span>
                <span class="m-home-tile__subtitle">Buscar y copiar credenciales guardadas</span>
            </a>
        @endcan

        @can('ver-historico-movil-gis-cecoco')
            <a href="{{ route('movil.historico-movil-gis.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--indigo"><span class="material-symbols-outlined">route</span></span>
                <span class="m-home-tile__title">Histórico Móvil GIS</span>
                <span class="m-home-tile__subtitle">Recorrido de un móvil por GPS</span>
            </a>
        @endcan

        @can('ver-infraestructura-workers')
            <a href="{{ route('movil.infraestructura.index') }}" class="m-home-tile">
                <span class="m-tile-icon m-tile-icon--indigo"><span class="material-symbols-outlined">settings</span></span>
                <span class="m-home-tile__title">Workers y BD</span>
                <span class="m-home-tile__subtitle">Estado de procesos y tamaño de bases de datos</span>
            </a>
        @endcan
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        (function () {
            var status = document.getElementById('mConnStatus');
            var text = document.getElementById('mConnStatusText');

            function actualizarEstadoConexion() {
                var online = navigator.onLine;
                status.classList.toggle('m-home-header__status--offline', !online);
                text.textContent = online ? 'En línea' : 'Sin conexión';
            }

            window.addEventListener('online', actualizarEstadoConexion);
            window.addEventListener('offline', actualizarEstadoConexion);
            actualizarEstadoConexion();
        })();
    </script>
@endsection
