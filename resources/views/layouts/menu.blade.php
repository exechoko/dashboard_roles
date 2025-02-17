@can('ver-menu-dashboard')
    <li class="{{ request()->is('home*') ? 'active' : '' }}">
        <a class="nav-link" href="/home">
            <i class=" fas fa-chart-line"></i><span>Dashboard</span>
        </a>
    </li>
@endcan

@can('ver-menu-equipamientos')
    <li
        class="dropdown {{ request()->is('equipos*') ? 'active' : '' }} {{ request()->is('busqueda-avanzada*') ? 'active' : '' }} {{ request()->is('flota*') ? 'active' : '' }} {{ request()->is('recursos*') ? 'active' : '' }} {{ request()->is('vehiculos*') ? 'active' : '' }} {{ request()->is('terminales*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-cog"></i><span>Equipamientos</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-flota')
                <li class="{{ request()->is('flota*') ? 'active' : '' }} {{ request()->is('*historico*') ? 'active' : '' }}">
                    <a class="nav-link" href="/flota"><i class="fas fa-wrench"></i><span>Administración</span></a>
                </li>
            @endcan
            @can('ver-flota')
                <li class="{{ request()->is('busqueda-avanzada*') ? 'active' : '' }}">
                    <a class="nav-link" href="/busqueda-avanzada"><i class="fas fa-search"></i><span>Búsq. Avanzada</span></a>
                </li>
            @endcan
            @can('ver-equipo')
                <li class="{{ request()->is('equipos*') ? 'active' : '' }}">
                    <a class="nav-link" href="/equipos">
                        <i class="fas fa-microchip"></i><span>Terminales</span>
                    </a>
                </li>
            @endcan
            @can('ver-recurso')
                <li class="{{ request()->is('recursos*') ? 'active' : '' }}">
                    <a class="nav-link" href="/recursos">
                        <i class="fas fa-car"></i></i><span>Recursos</span>
                    </a>
                </li>
            @endcan
            @can('ver-terminal')
                <li class="{{ request()->is('terminales*') ? 'active' : '' }}">
                    <a class="nav-link" href="/terminales">
                        <i class=" fas fa-satellite-dish"></i><span>Tipos de Term.</span>
                    </a>
                </li>
            @endcan
            @can('ver-vehiculo')
                <li class="{{ request()->is('vehiculos*') ? 'active' : '' }}">
                    <a class="nav-link" href="/vehiculos">
                        <i class="fas fa-truck-pickup"></i><span>Vehículos</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-camaras')
    <li
        class="dropdown {{ request()->is('tipo-camara*') ? 'active' : '' }} {{ request()->is('camaras*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-video"></i><span>Cámaras</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-camara')
                <li class="{{ request()->is('camaras*') ? 'active' : '' }}">
                    <a class="nav-link" href="/camaras">
                        <i class="fas fa-cog"></i><span>Administración</span>
                    </a>
                </li>
            @endcan
            @can('ver-tipo-camara')
                <li class="{{ request()->is('tipo-camara*') ? 'active' : '' }}">
                    <a class="nav-link" href="/tipo-camara">
                        <i class=" fas fa-camera-retro"></i><span>Tipos de Cámaras</span>
                    </a>
                </li>
            @endcan
            @can('ver-sitio')
                <li class="{{ request()->is('sitio*') ? 'active' : '' }}">
                    <a class="nav-link" href="/sitios">
                        <i class="fas fa-map-marker-alt"></i><span>Sitios</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan


<!--li class="{{ request()->is('accesorios*') ? 'active' : '' }}">
    <a class="nav-link" href="/accesorios">
        <i class="fas fa-headphones-alt"></i><span>Accesorios</span>
    </a>
</li-->
@can('ver-dependencia')
    <li class="{{ request()->is('dependencias*') ? 'active' : '' }}">
        <a class="nav-link" href="/dependencias">
            <i class="far fa-flag"></i><span>Dependencias</span>
        </a>
    </li>
@endcan

@can('ver-menu-cecoco')
<li
    class="dropdown {{ request()->is('indexMoviles*') ? 'active' : '' }} {{ request()->is('indexLlamadas*') ? 'active' : '' }}">
    <a class="nav-link has-dropdown" href="#">
        <i class="fas fa-life-ring"></i><span>CeCoCo</span>
    </a>
    <ul class="dropdown-menu">
        @can('ver-mapa-cecoco-en-vivo')
        <li class="">
            <a class="nav-link" href="/indexMapaCecocoEnVivo">
                <i class="fas fa-globe"></i><span>Mapa CeCoCo</span>
            </a>
        </li>
        @endcan
        @can('ver-mapa-calor-servicios-cecoco')
        <li class="">
            <a class="nav-link" href="/indexMapaCalor">
                <i class="fas fa-fire"></i><span>Mapa de eventos</span>
            </a>
        </li>
        @endcan
        @can('ver-llamadas-cecoco')
            <li class="">
                <a class="nav-link" href="/indexLlamadas">
                    <i class="fas fa-phone-alt"></i><span>Llamadas</span>
                </a>
            </li>
        @endcan
        @can('ver-moviles-cecoco')
            <li class="">
                <a class="nav-link" href="/indexMoviles">
                    <i class="fas fa-car"></i></i><span>Móviles</span>
                </a>
            </li>
        @endcan
        @can('ver-eventos-cecoco')
            <li class="">
                <a class="nav-link" href="/get-eventos">
                    <i class="far fa-file-alt"></i><span>Eventos</span>
                </a>
            </li>
        @endcan
    </ul>
</li>
@endcan

@can('ver-menu-mapa')
    <li class="{{ request()->is('mapa*') ? 'active' : '' }}">
        <a class="nav-link" href="/mapa">
            <i class="fas fa-map-marked"></i><span>Mapa</span>
        </a>
    </li>
@endcan

@can('ver-menu-usuarios')
    <li class="dropdown {{ request()->is('usuarios*') ? 'active' : '' }} {{ request()->is('roles*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-users"></i><span>Usuarios</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-usuario')
                <li class="{{ request()->is('usuarios*') ? 'active' : '' }}">
                    <a class="nav-link" href="/usuarios">
                        <i class="fas fa-cog"></i><span>Administración</span>
                    </a>
                </li>
            @endcan
            @can('ver-rol')
                <li class="{{ request()->is('roles*') ? 'active' : '' }}">
                    <a class="nav-link" href="/roles">
                        <i class="fas fa-lock"></i><span>Roles</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-auditoria')
    <li class="{{ request()->is('auditoria*') ? 'active' : '' }}">
        <a class="nav-link" href="/auditoria">
            <i class=" fas fa-search"></i><span>Auditoría</span>
        </a>
    </li>
@endcan
<!--Documentacion en GetStisla-->
