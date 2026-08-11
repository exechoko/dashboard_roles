@extends('layouts.app')

@section('css')
@include('plano-edificio.partials.styles')
@include('plano-edificio.partials.styles-3d')
@endsection

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="page__heading">
            <i class="fas fa-cube"></i> Plano 3D del Edificio 911
        </h1>
    </div>

    <div class="section-body">
        <div class="row">
            <!-- Visor 3D -->
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-cubes"></i> Edificio en 3D
                        </h4>
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary" href="{{ route('plano-edificio.index') }}">
                                <i class="fas fa-map"></i> Vista 2D
                            </a>
                            <button class="btn btn-outline-primary" id="btn-autorotate" onclick="toggleAutoRotate()" title="Rotación automática">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="resetVista3D()" title="Restablecer cámara">
                                <i class="fas fa-compress"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="plano3d-container" class="plano3d-container">
                            <canvas id="plano3d-canvas"></canvas>

                            <!-- Control de capas (overlay) -->
                            @include('plano-edificio.partials.layer-control-3d')

                            <!-- Tooltip flotante -->
                            <div id="device-tooltip" class="device-tooltip" style="display: none;"></div>

                            <div id="plano3d-loader" class="loading-overlay">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        <i class="fas fa-mouse"></i> Arrastrar: rotar &nbsp;·&nbsp; Rueda: zoom &nbsp;·&nbsp; Click derecho: desplazar &nbsp;·&nbsp; Click en pin: detalle &nbsp;·&nbsp; Doble click en piso: agregar dispositivo
                    </div>
                </div>
            </div>

            <!-- Panel de Control -->
            <div class="col-lg-2">
                <div class="card">
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
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="filtro-activos" checked>
                                <label class="custom-control-label" for="filtro-activos">
                                    Solo activos
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm btn-block" onclick="aplicarFiltros3D()">
                            <i class="fas fa-search"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-layer-group"></i> Pisos
                        </h4>
                    </div>
                    <div class="card-body" id="pisos-control">
                        <!-- Checkbox por piso generado por JS -->
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-sliders-h"></i> Vista
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="separacion-pisos">Separación de pisos</label>
                            <input type="range" class="custom-range" id="separacion-pisos"
                                   min="0.5" max="2.2" step="0.1" value="1">
                        </div>
                        <button class="btn btn-secondary btn-sm btn-block" onclick="resetVista3D()">
                            <i class="fas fa-compress"></i> Restablecer cámara
                        </button>
                    </div>
                </div>

                @can('crear-plano-edificio')
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-plus-circle"></i> Acciones
                            </h4>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-success btn-sm btn-block mb-2" onclick="abrirModalCrear()">
                                <i class="fas fa-plus"></i> Agregar Dispositivo
                            </button>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section>

<!-- Modal para crear/editar dispositivo -->
@include('plano-edificio.partials.device-modal')

<!-- Modal genérico para detalles / credenciales -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoModalTitle">Detalle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="infoModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/three/three.min.js') }}"></script>
<script src="{{ asset('vendor/three/OrbitControls.js') }}"></script>
@include('plano-edificio.partials.scripts-3d')
@endsection
