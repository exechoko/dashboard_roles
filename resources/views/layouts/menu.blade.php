<li class="side-menus {{ Request::is('*') ? 'active' : '' }}">
    <a class="nav-link" href="/home">
        <i class=" fas fa-chart-line"></i><span>Dashboard</span>
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
    <a class="nav-link" href="/terminales">
        <i class=" fas fa-satellite-dish"></i><span>Terminales</span>
    </a>
    <a class="nav-link" href="/dependencias">
        <i class="fas fa-building"></i><span>Dependencias</span>
    </a>
    @endcan

    <!--a class="nav-link" href="/blogs">
        <i class=" fas fa-blog"></i><span>Blogs</span>
    </a-->
    <a class="nav-link" href="/equipos">
        <i class="fas fa-mobile"></i><span>Equipamiento</span>
    </a>
</li>
