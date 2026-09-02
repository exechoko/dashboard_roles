@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-cogs mr-2"></i>Administración de Descargas</h3>
        <div>
            <a href="{{ route('descargas.admin.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Subir archivos
            </a>
            <a href="{{ route('descargas.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-eye"></i> Ver plataforma
            </a>
        </div>
    </div>

    <div class="section-body">
        {{-- Estadísticas --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Total archivos</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-primary">{{ $totalArchivos }}</h2>
                                    <p class="text-muted mb-0">Archivos activos</p>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-file fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Total descargas</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-success">{{ number_format($totalDescargas) }}</h2>
                                    <p class="text-muted mb-0">Descargas totales</p>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-download fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Compartidos</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-info">{{ $archivosCompartidos }}</h2>
                                    <p class="text-muted mb-0">Archivos compartidos</p>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-share-alt fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Solicitudes</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-{{ $solicitudesPendientes > 0 ? 'danger' : 'secondary' }}">
                                        {{ $solicitudesPendientes }}
                                    </h2>
                                    <p class="text-muted mb-0">Pendientes</p>
                                </div>
                                <div class="text-{{ $solicitudesPendientes > 0 ? 'danger' : 'secondary' }}">
                                    <i class="fas fa-envelope-open fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Accesos rápidos</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            @php
                                $solicitudesPendientes = \App\Models\DescargaSolicitudCompartir::where('estado', 'pendiente')->count();
                            @endphp
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.solicitudes') }}" class="btn btn-outline-{{ $solicitudesPendientes > 0 ? 'danger' : 'secondary' }} btn-lg btn-block position-relative">
                                    <i class="fas fa-envelope-open fa-2x mb-2"></i><br>
                                    Solicitudes
                                    @if($solicitudesPendientes > 0)
                                        <span class="badge badge-danger badge-pill">{{ $solicitudesPendientes }}</span>
                                    @endif
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.categorias') }}" class="btn btn-outline-primary btn-lg btn-block">
                                    <i class="fas fa-tags fa-2x mb-2"></i><br>
                                    Categorías
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.archivos') }}" class="btn btn-outline-info btn-lg btn-block">
                                    <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                                    Archivos
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.logs') }}" class="btn btn-outline-success btn-lg btn-block">
                                    <i class="fas fa-history fa-2x mb-2"></i><br>
                                    Historial
                                </a>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.links') }}" class="btn btn-outline-warning btn-lg btn-block">
                                    <i class="fas fa-link fa-2x mb-2"></i><br>
                                    Links públicos
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('descargas.admin.qrs') }}" class="btn btn-outline-warning btn-lg btn-block">
                                    <i class="fas fa-qrcode fa-2x mb-2"></i><br>
                                    Códigos QR
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Últimos archivos --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h4>Últimos archivos subidos</h4>
                        <a href="{{ route('descargas.admin.archivos') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
                    </div>
                    <div class="card-body p-0">
                        @if($ultimosArchivos->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Archivo</th>
                                            <th>Categoría</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ultimosArchivos as $archivo)
                                            <tr>
                                                <td>
                                                    <i class="{{ $archivo->icono_extension }} mr-1"></i>
                                                    {{ Str::limit($archivo->nombre_original, 30) }}
                                                </td>
                                                <td>
                                                    <span class="badge" style="background-color: {{ $archivo->categoria->color }}">
                                                        {{ $archivo->categoria->nombre }}
                                                    </span>
                                                </td>
                                                <td>{{ $archivo->created_at->format('d/m/Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">No hay archivos aún.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Archivos más descargados --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Archivos más descargados</h4>
                    </div>
                    <div class="card-body p-0">
                        @if($archivosPopulares->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Archivo</th>
                                            <th>Descargas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($archivosPopulares as $archivo)
                                            <tr>
                                                <td>
                                                    <i class="{{ $archivo->icono_extension }} mr-1"></i>
                                                    {{ Str::limit($archivo->nombre_original, 35) }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ $archivo->descargas_count }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">No hay descargas aún.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
