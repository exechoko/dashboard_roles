<li class="{{ request()->is('home*') ? 'active' : '' }}">
    <a class="nav-link" href="/home">
        <i class=" fas fa-chart-line"></i><span>Dashboard</span>
    </a>
</li>
@can('ver-flota')
    <li class="{{ request()->is('flota*') ? 'active' : '' }} {{ request()->is('*historico*') ? 'active' : '' }}">
        <a class="nav-link" href="/flota">
            <i class="fas fa-cog"></i><span>Flota de equipos</span>
        </a>
    </li>
@endcan
@can('ver-equipo')
    <li class="{{ request()->is('equipos*') ? 'active' : '' }}">
        <a class="nav-link" href="/equipos">
            <i class="fas fa-microchip"></i><span>Equipamientos</span>
        </a>
    </li>
@endcan
<!--li class="{{ request()->is('accesorios*') ? 'active' : '' }}">
    <a class="nav-link" href="/accesorios">
        <i class="fas fa-headphones-alt"></i><span>Accesorios</span>
    </a>
</li-->
@can('ver-recurso')
    <li class="{{ request()->is('recursos*') ? 'active' : '' }}">
        <a class="nav-link" href="/recursos">
            <i class="fas fa-car"></i></i><span>Recursos</span>
        </a>
    </li>
@endcan
@can('ver-dependencia')
    <li class="{{ request()->is('dependencias*') ? 'active' : '' }}">
        <a class="nav-link" href="/dependencias">
            <i class="fas fa-building"></i><span>Dependencias</span>
        </a>
    </li>
@endcan
@can('ver-vehiculo')
    <li class="{{ request()->is('vehiculos*') ? 'active' : '' }}">
        <a class="nav-link" href="/vehiculos">
            <i class="fas fa-truck-pickup"></i><span>Vehiculos</span>
        </a>
    </li>
@endcan
@can('ver-camara')
    <li class="{{ request()->is('camaras*') ? 'active' : '' }}">
        <a class="nav-link" href="/camaras">
            <i class="fas fa-video"></i><span>Cámaras</span>
        </a>
    </li>
@endcan
<li class="{{ request()->is('mapa*') ? 'active' : '' }}">
    <a class="nav-link" href="/mapa">
        <i class="fas fa-map-marked"></i><span>Mapa</span>
    </a>
</li>
@can('ver-terminal')
    <li class="{{ request()->is('terminales*') ? 'active' : '' }}">
        <a class="nav-link" href="/terminales">
            <i class=" fas fa-satellite-dish"></i><span>Tipos de Terminales</span>
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
@can('ver-rol')
    <li class="{{ request()->is('roles*') ? 'active' : '' }}">
        <a class="nav-link" href="/roles">
            <i class=" fas fa-lock"></i><span>Roles</span>
        </a>
    </li>
@endcan
@can('ver-usuario')
    <li class="{{ request()->is('usuarios*') ? 'active' : '' }}">
        <a class="nav-link" href="/usuarios">
            <i class=" fas fa-users"></i><span>Usuarios</span>
        </a>
    </li>
@endcan
@can('ver-auditoria')
    <li class="{{ request()->is('auditoria*') ? 'active' : '' }}">
        <a class="nav-link" href="/auditoria">
            <i class=" fas fa-search"></i><span>Auditoría</span>
        </a>
    </li>
@endcan
