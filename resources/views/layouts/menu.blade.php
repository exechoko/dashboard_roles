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
        class="dropdown {{ request()->is('tipo-camara*') ? 'active' : '' }} {{ request()->is('camaras*') ? 'active' : '' }} {{ request()->is('sitio*') ? 'active' : '' }} {{ request()->is('camaras_fisicas*') ? 'active' : '' }}">
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
            @can('ver-camara')
                <li class="{{ request()->is('camaras_fisicas*') ? 'active' : '' }}">
                    <a class="nav-link" href="/camaras_fisicas">
                        <i class="fas fa-cog"></i><span>Cámaras Fisicas</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-bodycams')
    <li
        class="dropdown {{ request()->is('bodycam*') ? 'active' : '' }} ">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-mobile"></i><span>Bodycams</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-bodycam')
                <li class="{{ request()->is('bodycam*') ? 'active' : '' }}">
                    <a class="nav-link" href="/bodycams">
                        <i class="fas fa-cog"></i><span>Administración</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-entregas')
    <li
        class="dropdown {{ request()->is('entrega-equipos*') ? 'active' : '' }} {{ request()->is('entrega-bodycams*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-file-signature"></i><span>Entregas</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-entrega-equipos')
                <li class="{{ request()->is('entrega-equipos*') ? 'active' : '' }}">
                    <a class="nav-link" href="/entrega-equipos">
                        <i class="fas fa-satellite-dish"></i><span>Equipos de mano</span>
                    </a>
                </li>
            @endcan
            @can('ver-entrega-bodycams')
                <li class="{{ request()->is('entrega-bodycams*') ? 'active' : '' }}">
                    <a class="nav-link" href="/entrega-bodycams">
                        <i class="fas fa-mobile"></i><span>Bodycams</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-patrimonio')
<li class="dropdown {{ request()->is('patrimonio*') ? 'active' : '' }}">
    <a class="nav-link has-dropdown" href="#">
        <i class="fas fa-boxes"></i><span>Patrimonio</span>
    </a>
    <ul class="dropdown-menu">
        <li class="{{ request()->is('patrimonio/bienes*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('patrimonio.bienes.index') }}">
                <i class="fas fa-box"></i><span>Bienes</span>
            </a>
        </li>
        <li class="{{ request()->is('patrimonio/tipos-bien*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('patrimonio.tipos-bien.index') }}">
                <i class="fas fa-tags"></i><span>Tipos de Bien</span>
            </a>
        </li>
    </ul>
</li>
@endcan

@can('ver-menu-tareas')
    <li class="{{ request()->is('tareas*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('tareas.index') }}">
            <i class="fas fa-tasks"></i><span>Tareas</span>
        </a>
    </li>
@endcan

@can('ver-menu-dependencias')
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

@can('ver-menu-transcripcion')
    <li class="dropdown {{ request()->is('transcribir*') ? 'active' : '' }} {{ request()->is('transcription*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-microphone-alt"></i><span>Transcripción</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-menu-transcripcion')
                <li class="{{ request()->is('transcribir*') ? 'active' : '' }}">
                    <a class="nav-link" href="/transcribir">
                        <i class="fas fa-microphone-alt"></i><span>Transcribir audio</span>
                    </a>
                </li>
            @endcan
            @can('ver-menu-transcripcion-aws')
                <li class="{{ request()->is('transcription*') ? 'active' : '' }}">
                    <a class="nav-link" href="/transcription">
                        <i class="fab fa-aws"></i><span>Transcribir audio AWS</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-plano-edificio')
    <li class="{{ request()->is('plano-edificio*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('plano-edificio.index') }}">
            <i class="fas fa-building"></i><span>Plano 911</span>
        </a>
    </li>
@endcan

@can('ver-menu-gestor-claves')
<li class="{{ request()->is('password-vault*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('password-vault.index') }}">
        <i class="fas fa-lock"></i>
        <span>Gestor de Contraseñas</span>
    </a>
</li>
@endcan

@can('ver-menu-documentacion')
    <li class="{{ request()->is('manual_usuario*') ? 'active' : '' }}">
        <a class="nav-link"
            href="https://docs.google.com/document/d/1QSVj5kHVp7UL5eUn2zTJeg1Dsn_KmTCtThKPMHXrf2I/edit?usp=sharing"
            target="_blank">
            <i class="fas fa-book"></i><span>Manual de usuario</span>
        </a>
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
