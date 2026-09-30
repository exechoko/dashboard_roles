{{-- Navegación entre las secciones de la Plataforma de Descargas (lado usuario). --}}
<nav class="descargas-subnav">
    <a href="{{ route('descargas.index') }}" class="nav-link {{ request()->routeIs('descargas.index') ? 'active' : '' }}">
        <i class="fas fa-list"></i> Todos los archivos
    </a>
    <a href="{{ route('descargas.galeria') }}" class="nav-link {{ request()->routeIs('descargas.galeria') ? 'active' : '' }}">
        <i class="fas fa-images"></i> Galería
    </a>
    <a href="{{ route('descargas.compartidos-conmigo') }}" class="nav-link {{ request()->routeIs('descargas.compartidos-conmigo') ? 'active' : '' }}">
        <i class="fas fa-share-alt"></i> Compartidos conmigo
    </a>
    <a href="{{ route('descargas.mis-favoritos') }}" class="nav-link {{ request()->routeIs('descargas.mis-favoritos') ? 'active' : '' }}">
        <i class="fas fa-star"></i> Mis favoritos
    </a>
    <a href="{{ route('descargas.mi-historial') }}" class="nav-link {{ request()->routeIs('descargas.mi-historial') ? 'active' : '' }}">
        <i class="fas fa-history"></i> Mi historial
    </a>
    @can('administrar-plataforma-descargas')
        <a href="{{ route('descargas.admin.index') }}" class="nav-link {{ request()->routeIs('descargas.admin.*') ? 'active' : '' }} ml-auto">
            <i class="fas fa-cogs"></i> Administrar
        </a>
    @endcan
</nav>
