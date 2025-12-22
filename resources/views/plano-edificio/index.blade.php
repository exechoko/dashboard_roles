@extends('layouts.app')

@section('css')
@include('plano-edificio.partials.styles')
@endsection

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="page__heading">
            <i class="fas fa-building"></i> Plano del Edificio 911
        </h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Plano del Edificio</div>
        </div>
    </div>

    <div class="section-body">
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $stats['total'] }}</h4>
                                <p class="card-text">Total Dispositivos</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-network-wired fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $stats['activos'] }}</h4>
                                <p class="card-text">Activos</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $stats['con_credenciales'] }}</h4>
                                <p class="card-text">Con Credenciales</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-key fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $stats['total'] - $stats['activos'] }}</h4>
                                <p class="card-text">Inactivos</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenedor Principal -->
        <div class="row">
            <!-- Panel de Control Izquierdo -->
            <div class="col-lg-3">
                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-filter"></i> Filtros
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="filtro-oficina">Oficina</label>
                            <input type="text" id="filtro-oficina" class="form-control" placeholder="Buscar oficina...">
                        </div>
                        <div class="form-group">
                            <label for="filtro-piso">Piso</label>
                            <select id="filtro-piso" class="form-control">
                                <option value="">Todos los pisos</option>
                                <option value="PB">Planta Baja</option>
                                <option value="1">Piso 1</option>
                                <option value="2">Piso 2</option>
                                <option value="3">Piso 3</option>
                                <option value="4">Piso 4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="filtro-activos" checked>
                                <label class="custom-control-label" for="filtro-activos">
                                    Solo activos
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm btn-block" onclick="aplicarFiltros()">
                            <i class="fas fa-search"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-plus-circle"></i> Acciones
                        </h4>
                    </div>
                    <div class="card-body">
                        @can('crear-plano-edificio')
                            <button class="btn btn-success btn-sm btn-block mb-2" onclick="abrirModalCrear()">
                                <i class="fas fa-plus"></i> Agregar Dispositivo
                            </button>
                        @endcan
                        @can('exportar-plano-edificio')
                            <button class="btn btn-info btn-sm btn-block mb-2" onclick="exportarDispositivos()">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        @endcan
                        <button class="btn btn-secondary btn-sm btn-block" onclick="resetearVista()">
                            <i class="fas fa-sync-alt"></i> Resetear Vista
                        </button>
                    </div>
                </div>
            </div>

            <!-- Visor del Plano -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-map"></i> Plano del Edificio
                        </h4>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="zoomIn()">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="zoomOut()">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="resetZoom()">
                                <i class="fas fa-compress"></i>
                            </button>
                            <button class="btn btn-outline-primary" id="fullscreen-btn" onclick="toggleFullscreen()">
                                <i class="fas fa-expand" id="fullscreen-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="plano-container" class="plano-container">
                            <div id="plano-viewport" class="plano-viewport">
                                <div id="plano-inner" class="plano-inner">
                                    <img id="svg-image" class="plano-svg" src="{{ asset('img/edificio_911_grande.svg') }}" alt="Plano del Edificio" draggable="false">

                                    <!-- Canvas para dispositivos -->
                                    <div id="dispositivos-layer" class="dispositivos-layer"></div>

                                    <div id="svg-loader" class="svg-loader">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando plano...
                                    </div>
                                </div>
                            </div>

                            <div class="fullscreen-controls" id="fullscreen-controls">
                                <button type="button" class="btn btn-light btn-sm" onclick="zoomIn()">
                                    <i class="fas fa-search-plus"></i> Acercar
                                </button>
                                <button type="button" class="btn btn-light btn-sm" onclick="zoomOut()">
                                    <i class="fas fa-search-minus"></i> Alejar
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetearVista()">
                                    <i class="fas fa-sync-alt"></i> Resetear vista
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="toggleFullscreen()">
                                    <i class="fas fa-compress"></i> Salir fullscreen
                                </button>
                            </div>

                            <!-- Control de capas (overlay) -->
                            @include('plano-edificio.partials.layer-control')

                            <!-- Tooltip flotante -->
                            <div id="device-tooltip" class="device-tooltip" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para crear/editar dispositivo -->
@include('plano-edificio.partials.device-modal')

@endsection

@section('scripts')
@include('plano-edificio.partials.scripts')
@endsection
