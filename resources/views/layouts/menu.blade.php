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
                <li class="{{ request()->is('equipos/estadisticas') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('equipos.estadisticas') }}">
                        <i class="fas fa-chart-pie"></i><span>Estadísticas</span>
                    </a>
                </li>
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
                <li class="{{ request()->is('equipos') || request()->is('equipos/*') && !request()->is('equipos/estadisticas') ? 'active' : '' }}">
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
    <li class="dropdown {{ request()->is('bodycam*') ? 'active' : '' }} ">
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
        class="dropdown {{ request()->is('entrega-equipos*') ? 'active' : '' }} {{ request()->is('entrega-bodycams*') ? 'active' : '' }} {{ request()->is('entrega-combustible*') ? 'active' : '' }}">
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
            @can('ver-entrega-combustible')
                <li class="{{ request()->is('entrega-combustible*') ? 'active' : '' }}">
                    <a class="nav-link" href="/entrega-combustible">
                        <i class="fas fa-gas-pump"></i><span>Combustible</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-incidencias-911')
    <li class="dropdown {{ request()->is('incidencias/periodos*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-chart-area"></i><span>Análisis 911</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-periodo-911')
            <li class="{{ request()->is('incidencias/periodos*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('incidencias.periodos.index') }}">
                    <i class="fas fa-calendar-alt"></i><span>Períodos</span>
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
            @can('ver-patrimonio-cargos')
                <li class="{{ request()->is('patrimonio/dashboard*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('patrimonio.dashboard') }}">
                        <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                    </a>
                </li>
                <li class="{{ request()->is('patrimonio/cargos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('patrimonio.cargos.index') }}">
                        <i class="fas fa-file-signature"></i><span>Cargos</span>
                    </a>
                </li>
            @endcan
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

@can('ver-menu-armamento')
    <li class="dropdown {{ request()->is('armas*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-shield-alt"></i><span>Control de Armas</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-arma-retencion')
                <li class="{{ request()->is('armas/retenciones*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.retenciones.index') }}">
                        <i class="fas fa-crosshairs"></i><span>Retenciones</span>
                    </a>
                </li>
            @endcan
            @can('ver-arma-motivo')
                <li class="{{ request()->is('armas/motivos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.motivos.index') }}">
                        <i class="fas fa-list"></i><span>Motivos</span>
                    </a>
                </li>
            @endcan
            @can('ver-arma-tipo')
                <li class="{{ request()->is('armas/tipos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.tipos.index') }}">
                        <i class="fas fa-tag"></i><span>Tipos de Arma</span>
                    </a>
                </li>
            @endcan
            @can('ver-personal')
                <li class="{{ request()->is('armas/personal*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.personal.index') }}">
                        <i class="fas fa-users"></i><span>Personal</span>
                    </a>
                </li>
            @endcan
            @can('ver-armeria')
                <li class="dropdown-divider"></li>
                <li class="dropdown-header">ARMERÍA</li>
                <li class="{{ request()->is('armas/armeria/armas*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.armeria.armas.index') }}">
                        <i class="fas fa-bullseye"></i><span>Armas Secundarias</span>
                    </a>
                </li>
                <li class="{{ request()->is('armas/armeria/chalecos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('armas.armeria.chalecos.index') }}">
                        <i class="fas fa-vest"></i><span>Chalecos</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-tareas')
    <li class="dropdown {{ request()->is('tareas*') || request()->is('incidencias/tickets-pg*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-tasks"></i><span>Tareas</span>
        </a>

        <ul class="dropdown-menu">

            @can('ver-tarea')
                <li class="{{ request()->is('tareas') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('tareas.index') }}">
                        <i class="fas fa-list"></i><span>Tareas</span>
                    </a>
                </li>
            @endcan
            @canany(['ver-ticket-pg', 'crear-ticket-pg', 'editar-ticket-pg', 'enviar-ticket-pg'])
                <li class="{{ request()->is('incidencias/tickets-pg*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('incidencias.tickets-pg.index') }}">
                        <i class="fas fa-ticket-alt"></i><span>Tickets PG</span>
                    </a>
                </li>
            @endcanany
            @can('ver-personal')
                {{-- PERSONAL EFECTIVO --}}
                <li class="{{ request()->is('tareas/personal-efectivo*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('personal.efectivo.index') }}">
                        <i class="fas fa-users"></i><span>Personal Efectivo</span>
                    </a>
                </li>
            @endcan
            @can('ver-activacion-totem')
                {{-- ACTIVACIONES TOTEM --}}
                <li class="{{ request()->is('tareas/activaciones-totem*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('activaciones-totem.index') }}">
                        <i class="fas fa-broadcast-tower"></i><span>Activaciones Tótem</span>
                    </a>
                </li>
            @endcan
        </ul>
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
        class="dropdown {{ request()->is('indexMoviles*') ? 'active' : '' }} {{ request()->is('indexLlamadas*') ? 'active' : '' }} {{ request()->is('cecoco/recursos-alias*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-life-ring"></i><span>CeCoCo</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-analitica-eventos-cecoco')
                <li class="{{ request()->routeIs('cecoco.analitica') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.analitica') }}">
                        <i class="fas fa-chart-bar"></i><span>Analítica de Delitos</span>
                    </a>
                </li>
            @endcan
            @can('ver-analizador-eventos-cecoco')
                <li class="{{ request()->routeIs('cecoco.index') || request()->routeIs('cecoco.show') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.index') }}">
                        <i class="fas fa-database"></i><span>Analizador de Eventos</span>
                    </a>
                </li>
            @endcan
            @can('ver-recurso-alias-cecoco')
                <li class="{{ request()->routeIs('cecoco.recursos-alias.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.recursos-alias.index') }}">
                        <i class="fas fa-project-diagram"></i><span>Mapeo de Recursos</span>
                    </a>
                </li>
            @endcan
            @can('ver-reporte-llamadas-central-telefonica')
                <li class="{{ request()->routeIs('cecoco.llamadas-central-telefonica*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.llamadas-central-telefonica') }}">
                        <i class="fas fa-phone-alt"></i><span>Reporte Central Telefónica</span>
                    </a>
                </li>
            @endcan
            @can('ver-mapa-calor-servicios-cecoco')
                <li class="{{ request()->routeIs('cecoco.mapa-calor') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.mapa-calor') }}">
                        <i class="fas fa-fire"></i><span>Mapa de Calor Analizador</span>
                    </a>
                </li>
            @endcan
            @can('ver-mapa-cecoco-en-vivo')
                <li class="">
                    <a class="nav-link" href="/indexMapaCecocoEnVivo">
                        <i class="fas fa-globe"></i><span>Mapa CeCoCo</span>
                    </a>
                </li>
            @endcan
            @can('ver-mapa-gis-cecoco')
                <li class="{{ request()->is('cecoco/mapa-gis') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.mapa-gis') }}">
                        <i class="fas fa-satellite-dish"></i><span>Mapa GIS CeCoCo</span>
                    </a>
                </li>
            @endcan
            @can('ver-mapa-gis-historico-cecoco')
                <li class="{{ request()->is('cecoco/mapa-gis-historico*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.mapa-gis-historico') }}">
                        <i class="fas fa-history"></i><span>GIS Histórico CeCoCo</span>
                    </a>
                </li>
            @endcan
            {{--
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
                        <i class="fas fa-car"></i><span>Móviles</span>
                    </a>
                </li>
            @endcan
            --}}
            @can('ver-historico-movil-cecoco')
                <li class="{{ request()->routeIs('cecoco.historico-movil') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.historico-movil') }}">
                        <i class="fas fa-file-import"></i><span>Procesar Histórico Excel</span>
                    </a>
                </li>
            @endcan
            @can('ver-historico-movil-gis-cecoco')
                <li class="{{ request()->routeIs('cecoco.historico-movil-gis') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cecoco.historico-movil-gis') }}">
                        <i class="fas fa-satellite-dish"></i><span>Procesar Histórico GIS</span>
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

@canany(['ver-menu-usuarios', 'ver-menu-constancias-credenciales'])
    <li class="dropdown {{ request()->is('usuarios*') || request()->is('roles*') || request()->is('constancias-credenciales*') ? 'active' : '' }}">
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
            @can('ver-menu-constancias-credenciales')
                <li class="{{ request()->is('constancias-credenciales*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('constancias-credenciales.index') }}">
                        <i class="fas fa-key"></i><span>Actas de Credenciales</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany

<!--
@can('ver-menu-transcripcion')
    <li
        class="dropdown {{ request()->is('transcribir*') ? 'active' : '' }} {{ request()->is('transcription*') ? 'active' : '' }}">
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
-->

@can('ver-menu-ia')
    <li class="dropdown {{ request()->is('rag*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-brain"></i><span>IA</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-rag')
                <li class="{{ request()->is('rag*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('rag.index') }}">
                        <i class="fas fa-database"></i><span>Base de Conocimiento</span>
                    </a>
                </li>
            @endcan
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

@can('ver-menu-infraestructura')
    <li class="dropdown {{ request()->is('infraestructura*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-network-wired"></i><span>Infraestructura</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-infraestructura-pcs')
                <li class="{{ request()->is('infraestructura/pcs*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.pcs') }}">
                        <i class="fas fa-desktop"></i><span>PCs Policiales</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-servidores')
                <li class="{{ request()->is('infraestructura/servidores*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.servidores') }}">
                        <i class="fas fa-server"></i><span>Servidores</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-camaras')
                <li class="{{ request()->is('infraestructura/camaras*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.camaras') }}">
                        <i class="fas fa-video"></i><span>Cámaras Internas</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-red')
                <li class="{{ request()->is('infraestructura/red*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.red') }}">
                        <i class="fas fa-project-diagram"></i><span>Routers / Switches</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-librenms')
                <li class="{{ request()->is('infraestructura/librenms*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.librenms') }}">
                        <i class="fas fa-chart-area"></i><span>LibreNMS</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-central-telefonica')
                <li class="{{ request()->is('infraestructura/central-telefonica*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.central-telefonica') }}">
                        <i class="fas fa-phone-alt"></i><span>Central Telefónica</span>
                    </a>
                </li>
            @endcan
            @can('ver-infraestructura-workers')
                <li class="{{ request()->is('infraestructura/workers*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('infraestructura.workers') }}">
                        <i class="fas fa-database"></i><span>Workers y Bases de Datos</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@can('ver-menu-chat')
    <li class="{{ request()->is('chat*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('chat.index') }}">
            <i class="fas fa-comments"></i>
            <span>Chat</span>
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

@canany(['ver-menu-manuales', 'ver-manual-usuario', 'ver-manuales-cecoco', 'ver-instructivos'])
    <li class="dropdown {{ request()->is('manuales*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-book-open"></i><span>Manuales</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-manual-usuario')
                <li>
                    <a class="nav-link"
                        href="{{ asset('manuales/manual-usuario.html') }}?v={{ filemtime(public_path('manuales/manual-usuario.html')) }}"
                        target="_blank" rel="noopener">
                        <i class="fas fa-book mr-1"></i><span>Manual de Usuario</span>
                    </a>
                </li>
            @endcan
            @can('ver-manuales-cecoco')
                <li class="{{ request()->is('manuales/cecoco*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('manuales.cecoco') }}">
                        <i class="fas fa-folder-open mr-1"></i><span>Manuales CeCoCo</span>
                    </a>
                </li>
            @endcan
            @can('ver-instructivos')
                <li class="{{ request()->is('manuales/instructivos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('manuales.instructivos') }}">
                        <i class="fas fa-file-alt mr-1"></i><span>Instructivos</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany

@canany(['ver-menu-herramientas', 'ver-hash-archivo', 'ver-visor-mails', 'administrar-visor-mails'])
    <li class="dropdown {{ request()->is('herramientas*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-tools"></i><span>Herramientas</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-hash-archivo')
                <li class="{{ request()->routeIs('herramientas.hash.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('herramientas.hash.index') }}">
                        <i class="fas fa-fingerprint"></i><span>Hashear Archivo</span>
                    </a>
                </li>
            @endcan
            @can('ver-visor-mails')
                <li class="{{ request()->routeIs('herramientas.mails.index') || request()->routeIs('herramientas.mails.show') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('herramientas.mails.index') }}">
                        <i class="fas fa-envelope-open-text"></i><span>Visor de Correos</span>
                    </a>
                </li>
            @endcan
            @can('administrar-visor-mails')
                <li class="{{ request()->routeIs('herramientas.mails.buzones.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('herramientas.mails.buzones.index') }}">
                        <i class="fas fa-inbox"></i><span>Buzones de Correo</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany

@can('ver-menu-descargas')
    <li class="dropdown {{ request()->is('descargas*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-download"></i><span>Descargas</span>
        </a>
        <ul class="dropdown-menu">
            <li class="{{ request()->routeIs('descargas.index') || request()->routeIs('descargas.show') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('descargas.index') }}">
                    <i class="fas fa-folder-open"></i><span>Archivos</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('descargas.compartidos-conmigo') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('descargas.compartidos-conmigo') }}">
                    <i class="fas fa-share-alt"></i><span>Compartidos conmigo</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('descargas.mis-favoritos') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('descargas.mis-favoritos') }}">
                    <i class="fas fa-star"></i><span>Mis Favoritos</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('descargas.mi-historial') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('descargas.mi-historial') }}">
                    <i class="fas fa-history"></i><span>Mi Historial</span>
                </a>
            </li>
            @can('administrar-plataforma-descargas')
                <li class="dropdown-divider"></li>
                <li class="{{ request()->routeIs('descargas.admin.solicitudes') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('descargas.admin.solicitudes') }}">
                        <i class="fas fa-envelope-open"></i><span>Solicitudes</span>
                        @php
                            $solicitudesPendientes = \App\Models\DescargaSolicitudCompartir::where('estado', 'pendiente')->count();
                        @endphp
                        @if($solicitudesPendientes > 0)
                            <span class="badge badge-danger badge-pill">{{ $solicitudesPendientes }}</span>
                        @endif
                    </a>
                </li>
                <li class="{{ request()->routeIs('descargas.admin.*') && !request()->routeIs('descargas.admin.solicitudes') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('descargas.admin.index') }}">
                        <i class="fas fa-cogs"></i><span>Administración</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan

@canany(['ver-menu-web', 'editar-web-contadores', 'editar-web-textos', 'editar-web-historia', 'editar-web-tecnologia', 'editar-web-dependencias', 'editar-web-galeria', 'crear-noticia', 'editar-noticia', 'eliminar-noticia'])
    <li class="{{ request()->is('web-admin*') || request()->is('noticias*') || request()->is('web-dependencias*') || request()->is('web-historia*') || request()->is('web-tecnologia*') || request()->is('web-galeria*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-globe"></i><span>Administrar Web</span>
        </a>
        <ul class="dropdown-menu">
            @can('editar-web-contadores')
                <li class="{{ request()->is('web-admin/contadores*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-admin.contadores.edit') }}">
                        <i class="fas fa-sort-numeric-up mr-1"></i><span>Contadores</span>
                    </a>
                </li>
            @endcan
            @can('editar-web-textos')
                <li class="{{ request()->is('web-admin/textos*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-admin.textos.edit') }}">
                        <i class="fas fa-font mr-1"></i><span>Textos</span>
                    </a>
                </li>
            @endcan
            @can('editar-web-historia')
                <li class="{{ request()->is('web-historia*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-historia.index') }}">
                        <i class="fas fa-stream mr-1"></i><span>Historia (timeline)</span>
                    </a>
                </li>
            @endcan
            @can('editar-web-tecnologia')
                <li class="{{ request()->is('web-tecnologia*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-tecnologia.index') }}">
                        <i class="fas fa-microchip mr-1"></i><span>Tecnología (cards)</span>
                    </a>
                </li>
            @endcan
            @can('editar-web-galeria')
                <li class="{{ request()->is('web-galeria*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-galeria.index') }}">
                        <i class="fas fa-images mr-1"></i><span>Galería</span>
                    </a>
                </li>
            @endcan
            @can('editar-web-dependencias')
                <li class="{{ request()->is('web-dependencias*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('web-dependencias.index') }}">
                        <i class="fas fa-building mr-1"></i><span>Dependencias</span>
                    </a>
                </li>
            @endcan
            @canany(['crear-noticia', 'editar-noticia', 'eliminar-noticia'])
                <li class="{{ request()->is('noticias*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('noticias.index') }}">
                        <i class="fas fa-newspaper mr-1"></i><span>Noticias</span>
                    </a>
                </li>
            @endcanany
        </ul>
    </li>
@endcanany

@canany(['ver-menu-auditoria', 'ver-configuracion-env', 'ver-configuracion-ia', 'ver-configuracion-workers', 'ver-configuracion-backup'])
    <li class="dropdown {{ request()->is('auditoria*') || request()->is('configuracion*') ? 'active' : '' }}">
        <a class="nav-link has-dropdown" href="#">
            <i class="fas fa-sliders-h"></i><span>Configuración del Sistema</span>
        </a>
        <ul class="dropdown-menu">
            @can('ver-menu-auditoria')
                <li class="{{ request()->is('auditoria*') ? 'active' : '' }}">
                    <a class="nav-link" href="/auditoria">
                        <i class="fas fa-search"></i><span>Auditoría</span>
                    </a>
                </li>
            @endcan
            @can('ver-configuracion-env')
                <li class="{{ request()->is('configuracion/env*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('configuracion.env') }}">
                        <i class="fas fa-file-alt"></i><span>Variables de Entorno</span>
                    </a>
                </li>
            @endcan
            @can('ver-configuracion-ia')
                <li class="{{ request()->is('configuracion/ia*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('configuracion.ia') }}">
                        <i class="fas fa-brain"></i><span>IA y API Keys</span>
                    </a>
                </li>
            @endcan
            @can('ver-configuracion-workers')
                <li class="{{ request()->is('configuracion/workers*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('configuracion.workers') }}">
                        <i class="fas fa-cogs"></i><span>Workers y Colas</span>
                    </a>
                </li>
            @endcan
            @can('ver-configuracion-backup')
                <li class="{{ request()->is('configuracion/backups*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('configuracion.backups') }}">
                        <i class="fas fa-database"></i><span>Backups de Base de Datos</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany

<!--Documentacion en GetStisla-->
