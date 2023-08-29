<li id="dashboard" class="{{ request()->is('home*') ? 'active' : '' }}">
    <a  class="nav-link" href="/home">
        <i class=" fas fa-chart-line"></i><span>Dashboard</span>
        <!--i><ion-icon name="heart-outline"></ion-icon></i><span>Dashboard</span-->
    </a>
</li>

<li id="equipamientos"
    class="dropdown {{ request()->is('equipos*') ? 'active' : '' }} {{ request()->is('flota*') ? 'active' : '' }} {{ request()->is('recursos*') ? 'active' : '' }} {{ request()->is('vehiculos*') ? 'active' : '' }} {{ request()->is('terminales*') ? 'active' : '' }}">
    <a class="nav-link has-dropdown" href="#">
        <i class="fas fa-cog"></i><span>Equipamientos</span>
    </a>
    <ul class="dropdown-menu">
        @can('ver-flota')
            <li id="flota" class="{{ request()->is('ver-historico*') ? 'active' : '' }} {{ request()->is('flota*') ? 'active' : '' }}">
                <a class="nav-link" href="/flota"><i class="fas fa-wrench"></i><span>Administración</span></a>
            </li>
        @endcan
        @can('ver-equipo')
            <li id="equipos" class="{{ request()->is('equipos*') ? 'active' : '' }}">
                <a class="nav-link" href="/equipos">
                    <i class="fas fa-microchip"></i><span>Terminales</span>
                </a>
            </li>
        @endcan
        @can('ver-recurso')
            <li id="recursos" class="{{ request()->is('recursos*') ? 'active' : '' }}">
                <a class="nav-link" href="/recursos">
                    <i class="fas fa-car"></i></i><span>Recursos</span>
                </a>
            </li>
        @endcan
        @can('ver-terminal')
            <li id="terminales" class="{{ request()->is('terminales*') ? 'active' : '' }}">
                <a class="nav-link" href="/terminales">
                    <i class=" fas fa-satellite-dish"></i><span>Tipos de Term.</span>
                </a>
            </li>
        @endcan
        @can('ver-vehiculo')
            <li id="vehiculos" class="{{ request()->is('vehiculos*') ? 'active' : '' }}">
                <a class="nav-link" href="/vehiculos">
                    <i class="fas fa-truck-pickup"></i><span>Vehículos</span>
                </a>
            </li>
        @endcan
    </ul>
</li>

<li id="camaras"
    class="dropdown {{ request()->is('tipo-camara*') ? 'active' : '' }} {{ request()->is('camaras*') ? 'active' : '' }}">
    <a class="nav-link has-dropdown" href="#">
        <i class="fas fa-video"></i><span>Cámaras</span>
    </a>
    <ul class="dropdown-menu">
        @can('ver-camara')
            <li id="camaras_administracion" class="{{ request()->is('camaras*') ? 'active' : '' }}">
                <a class="nav-link" href="/camaras">
                    <i class="fas fa-cog"></i><span>Administración</span>
                </a>
            </li>
        @endcan
        @can('ver-tipo-camara')
            <li id="tipo_camaras" class="{{ request()->is('tipo-camara*') ? 'active' : '' }}">
                <a class="nav-link" href="/tipo-camara">
                    <i class=" fas fa-camera-retro"></i><span>Tipos de Cámaras</span>
                </a>
            </li>
        @endcan
    </ul>
</li>


<!--li class="{{ request()->is('accesorios*') ? 'active' : '' }}">
    <a class="nav-link" href="/accesorios">
        <i class="fas fa-headphones-alt"></i><span>Accesorios</span>
    </a>
</li-->
@can('ver-dependencia')
    <li id="dependencias" class="{{ request()->is('dependencias*') ? 'active' : '' }}">
        <a class="nav-link" href="/dependencias">
            <i class="far fa-flag"></i><span>Dependencias</span>
        </a>
    </li>
@endcan

<li id="mapa" class="{{ request()->is('mapa*') ? 'active' : '' }}">
    <a class="nav-link" href="/mapa">
        <i class="fas fa-map-marked"></i><span>Mapa</span>
    </a>
</li>

<li id="usuarios" class="dropdown {{ request()->is('usuarios*') ? 'active' : '' }} {{ request()->is('roles*') ? 'active' : '' }}">
    <a class="nav-link has-dropdown" href="#">
        <i class="fas fa-users"></i><span>Usuarios</span>
    </a>
    <ul class="dropdown-menu">
        @can('ver-usuario')
            <li id="usuarios_administracion" class="{{ request()->is('usuarios*') ? 'active' : '' }}">
                <a class="nav-link" href="/usuarios">
                    <i class="fas fa-cog"></i><span>Administración</span>
                </a>
            </li>
        @endcan
        @can('ver-rol')
            <li id="roles" class="{{ request()->is('roles*') ? 'active' : '' }}">
                <a class="nav-link" href="/roles">
                    <i class="fas fa-lock"></i><span>Roles</span>
                </a>
            </li>
        @endcan
    </ul>
</li>

@can('ver-auditoria')
    <li id="auditoria" class="{{ request()->is('auditoria*') ? 'active' : '' }}">
        <a class="nav-link" href="/auditoria">
            <i class=" fas fa-search"></i><span>Auditoría</span>
        </a>
    </li>
@endcan
<!--Documentacion en GetStisla-->
