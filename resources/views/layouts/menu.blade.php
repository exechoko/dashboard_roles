<li class="side-menus {{ Request::is('*') ? 'active' : '' }}">
    <a class="nav-link" href="/home">
        <i class=" fas fa-chart-line"></i><span>Dashboard</span>
    </a>
    <a class="nav-link" href="/equipos">
        <i class="fas fa-microchip"></i><span>Equipamientos</span>
    </a>
    <a class="nav-link" href="/recursos">
        <i class="fas fa-car"></i></i><span>Recursos</span>
    </a>
    <a class="nav-link" href="/dependencias">
        <i class="fas fa-building"></i><span>Dependencias</span>
    </a>
    <a class="nav-link" href="/terminales">
        <i class=" fas fa-satellite-dish"></i><span>Terminales</span>
    </a>
    <a class="nav-link" href="/vehiculos">
        <i class="fas fa-truck-pickup"></i><span>Vehiculos</span>
    </a>
    @can('ver-rol')
    <a class="nav-link" href="/roles">
        <i class=" fas fa-lock"></i><span>Roles</span>
    </a>
    @endcan
    @can('ver-usuario')
    <a class="nav-link" href="/usuarios">
        <i class=" fas fa-users"></i><span>Usuarios</span>
    </a>
    @endcan
</li>
