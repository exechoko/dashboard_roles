{{-- Navegación entre las subpáginas del panel de administración de Descargas. --}}
@php
    $solicitudesPendientesNav = \App\Models\DescargaSolicitudCompartir::pendientes()->count();
@endphp
<nav class="descargas-subnav">
    <a href="{{ route('descargas.admin.index') }}" class="nav-link {{ request()->routeIs('descargas.admin.index') ? 'active' : '' }}">
        <i class="fas fa-th-large"></i> Resumen
    </a>
    <a href="{{ route('descargas.admin.archivos') }}" class="nav-link {{ request()->routeIs('descargas.admin.archivos') ? 'active' : '' }}">
        <i class="fas fa-file-alt"></i> Archivos
    </a>
    <a href="{{ route('descargas.admin.create') }}" class="nav-link {{ request()->routeIs('descargas.admin.create') ? 'active' : '' }}">
        <i class="fas fa-upload"></i> Subir
    </a>
    <a href="{{ route('descargas.admin.categorias') }}" class="nav-link {{ request()->routeIs('descargas.admin.categorias') ? 'active' : '' }}">
        <i class="fas fa-tags"></i> Categorías
    </a>
    <a href="{{ route('descargas.admin.solicitudes') }}" class="nav-link {{ request()->routeIs('descargas.admin.solicitudes') ? 'active' : '' }}">
        <i class="fas fa-envelope-open"></i> Solicitudes
        @if($solicitudesPendientesNav > 0)
            <span class="badge badge-danger badge-pill">{{ $solicitudesPendientesNav }}</span>
        @endif
    </a>
    <a href="{{ route('descargas.admin.links') }}" class="nav-link {{ request()->routeIs('descargas.admin.links') ? 'active' : '' }}">
        <i class="fas fa-link"></i> Links públicos
    </a>
    <a href="{{ route('descargas.admin.qrs') }}" class="nav-link {{ request()->routeIs('descargas.admin.qrs') ? 'active' : '' }}">
        <i class="fas fa-qrcode"></i> Códigos QR
    </a>
    <a href="{{ route('descargas.admin.logs') }}" class="nav-link {{ request()->routeIs('descargas.admin.logs') ? 'active' : '' }}">
        <i class="fas fa-history"></i> Logs
    </a>
    <a href="{{ route('descargas.index') }}" class="nav-link ml-auto">
        <i class="fas fa-eye"></i> Ver plataforma
    </a>
</nav>
