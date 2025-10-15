{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('css')

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{ asset('/plugins/JQueryUi/jquery-ui.css') }}" rel="stylesheet">
<link href="{{ asset('/plugins/bootstrap-dialog/bootstrap-dialog.min.css') }}" rel="stylesheet">
<link href="{{ asset('/plugins/bootstrap-toggle/bootstrap-toggle.min.css') }}" rel="stylesheet">

<style>
    .card-item {
        position: relative;
        background-color: #000;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: transform .5s;
    }

    .card-item:hover {
        transform: translateY(-5px);
        /*translate: 0 -20px;
                                                                                                                                                                                                                                                                                box-shadow: 5px 3px rgb(217 220 242 / 75%),
                                                                                                                                                                                                                                                                                    10px 6px rgb(44 217 255 / 50%),
                                                                                                                                                                                                                                                                                    15px 9px rgb(126 255 178 / 25%),*/
    }

    /*.card-item::before {
                                                                                                                                                                                                                                                                            content: '';
                                                                                                                                                                                                                                                                            position: absolute;
                                                                                                                                                                                                                                                                            inset: 0;
                                                                                                                                                                                                                                                                            transform: scaleY(0.75);
                                                                                                                                                                                                                                                                            transform-origin: bottom;
                                                                                                                                                                                                                                                                            background: linear-gradient(transparent,
                                                                                                                                                                                                                                                                                    rgba(0, 0, 0, 0.02), #000);
                                                                                                                                                                                                                                                                            transition: transform 0.25s;
                                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                                        .card-item:hover::before {
                                                                                                                                                                                                                                                                            transform: scale(1);
                                                                                                                                                                                                                                                                        }*/
</style>

@stop

@section('content')
    <section class="section">
        <div class="section-header">
            @can('ver-menu-dashboard')
                <h3 class="page__heading">Dashboard</h3>
            @endcan
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    @can('ver-menu-dashboard')
                        <ul class="nav nav-pills" id="myTab3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="terminales-tab3" data-toggle="tab" href="#terminales3" role="tab"
                                    aria-controls="home" aria-selected="true">Terminales</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="recursos-tab3" data-toggle="tab" href="#recursos3" role="tab"
                                    aria-controls="profile" aria-selected="false">Recursos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="camaras-tab3" data-toggle="tab" href="#camaras3" role="tab"
                                    aria-controls="contact" aria-selected="false">Cámaras</a>
                            </li>
                        </ul>
                    @endcan()
                    <div class="card">
                        <div class="card-body">
                            @php
                                use App\Models\Equipo;
                                use App\Models\Historico;
                                use App\Models\Destino;
                                use App\Models\Recurso;
                                use App\Models\TipoMovimiento;
                                use App\Models\Camara;
                                use App\Models\FlotaGeneral;

                                $veh = 'Moto';
                                $cant_motos = Recurso::whereHas('vehiculo', function ($query) use ($veh) {
                                    $query->where('tipo_vehiculo', '=', $veh);
                                })->count();

                                $tipo_veh1 = 'Auto';
                                $tipo_veh2 = 'Camioneta';
                                $cant_moviles = Recurso::whereHas('vehiculo', function ($query) use ($tipo_veh1, $tipo_veh2) {
                                    $query->where('tipo_vehiculo', '=', $tipo_veh1)->orWhere('tipo_vehiculo', '=', $tipo_veh2);
                                })->count();
                            @endphp

                            @can('ver-menu-dashboard')
                                <div class="tab-content" id="myTabContent2">
                                    <!-- TAB Terminales -->
                                    <div class="tab-pane fade show active" id="terminales3" role="tabpanel"
                                        aria-labelledby="terminales-tab3">
                                        <div class="row">
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-c-violet order-card">
                                                    <div class="card-block">
                                                        <h5>
                                                            Funcionales
                                                            <i class="fas fa-info-circle ml-1" data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Equipos con estado Nuevo, Usado y Reparado"></i>
                                                        </h5>
                                                        <h2 class="text-right"><i
                                                                class="fas fa-check f-left"></i><span>{{ $cant_equipos_funcionales }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-funcionales"
                                                                    id="btn-buscar-equipos-funcionales"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-c-red order-card">
                                                    <div class="card-block">
                                                        <h5>
                                                            Sin funcionar
                                                            <i class="fas fa-info-circle ml-1" data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Equipos con estado No funciona"></i>
                                                        </h5>
                                                        <h2 class="text-right">
                                                            <i class="fas fa-times f-left"></i>
                                                            <span>{{ $cant_equipos_sin_funcionar }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right">
                                                                <a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-sin-funcionar"
                                                                    id="btn-buscar-equipos-sin-funcionar"
                                                                    style="color: rgb(253, 253, 253)">
                                                                    Ver más
                                                                </a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-blue order-card">
                                                    <div class="card-block">
                                                        <h5>
                                                            Baja
                                                            <i class="fas fa-info-circle ml-1" data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Equipos con estado Baja, Perdido y Recambio (TELECOM)"></i>
                                                        </h5>
                                                        <h2 class="text-right"><i
                                                                class="fas fa-trash-alt f-left"></i><span>{{ $cant_equipos_baja }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-baja" id="btn-buscar-equipos-baja"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-indigo order-card">
                                                    <div class="card-block">
                                                        <h5>
                                                            En revisión
                                                            <i class="fas fa-info-circle ml-1" data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="Equipos con estado En revisión (en reparación o diagnóstico)"></i>
                                                        </h5>
                                                        <h2 class="text-right">
                                                            <i class="fas fa-tools f-left"></i>
                                                            <span>{{ $cant_equipos_en_revision }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right">
                                                                <a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-en-revision"
                                                                    id="btn-buscar-equipos-en-revision"
                                                                    onclick="consultarEquiposEnRevision()"
                                                                    style="color: rgb(253, 253, 253)">
                                                                    Ver más
                                                                </a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-c-green order-card">
                                                    <div class="card-block">
                                                        <h5>Temporales</h5>
                                                        <h2 class="text-right"><i
                                                                class="fas fa-wind f-left"></i><span>{{ $cant_equipos_temporales }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#" id="btn-buscar-equipos-baja"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-c-orange order-card">
                                                    <div class="card-block">
                                                        <h5>Provistos por P.G.</h5>
                                                        <h2 class="text-right"><img src="{{ asset('img/patagonia_logo.png') }}"
                                                                alt="Telecom Logo" class="f-left" width="40"
                                                                height="40"><span>{{ $cant_equipos_provisto_por_pg }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-provistos-por-pg"
                                                                    id="btn-buscar-equipos-provistos-por-pg"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-3">
                                                <div class="card-item bg-c-blue order-card">
                                                    <div class="card-block">
                                                        <h5>Provistos por TELECOM</h5>
                                                        <h2 class="text-right"><img
                                                                src="{{ asset('img/telecom_logo_202.png') }}" alt="Telecom Logo"
                                                                class="f-left"
                                                                width="60"><span>{{ $cant_equipos_provisto_por_telecom }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-provistos-por-telecom"
                                                                    id="btn-buscar-equipos-provistos-por-telecom"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row justify-content-end">
                                            <div class="col-md-12 text-right">
                                                <form action="{{ route('equipos.export') }}" method="GET">
                                                    <button id="exportar-a-excel-todos-los-terminales" class="btn btn-success">
                                                        <i class="fa fa-file-excel"> Exportar todos los terminales</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- TAB Recursos -->
                                    <div class="tab-pane fade" id="recursos3" role="tabpanel" aria-labelledby="recursos-tab3">
                                        <div class="row">
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-orange order-card">
                                                    <div class="card-block">
                                                        <h5>Equipos derivados a P.G.</h5>

                                                        <h2 class="text-right"><i
                                                                class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_en_pg }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-pg{{-- $vehiculo->id --}}"
                                                                    id="btn-buscar-equipos-pg" style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-green order-card">
                                                    <div class="card-block">
                                                        <h5>Equipos en Stock 911</h5>

                                                        <h2 class="text-right"><i
                                                                class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_en_stock }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-stock{{-- $vehiculo->id --}}"
                                                                    id="btn-buscar-equipos-stock"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-black order-card">
                                                    <div class="card-block">
                                                        <h5>Desinstalaciones parciales</h5>

                                                        <h2 class="text-right"><i
                                                                class="fas fa-wrench f-left"></i><span>{{ $cant_desinstalaciones }}</span>
                                                        </h2>
                                                        <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                data-target="#modal-desinstalaciones-parciales{{-- $vehiculo->id --}}"
                                                                id="btn-buscar-desinstalaciones-parciales"
                                                                style="color: rgb(253, 253, 253)">Ver más</a>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-gren-light order-card">
                                                    <div class="card-block">
                                                        <h5>Equipos en J.D.P.</h5>
                                                        <h2 class="text-right"><i
                                                                class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_en_departamental }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-departamental{{-- $vehiculo->id --}}"
                                                                    id="btn-buscar-equipos-departamental"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-yellow order-card">
                                                    <div class="card-block">
                                                        <h5>Equipos en División 911</h5>
                                                        <h2 class="text-right"><i
                                                                class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_en_div_911 }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-division-911{{-- $vehiculo->id --}}"
                                                                    id="btn-buscar-equipos-division-911"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-pink order-card">
                                                    <div class="card-block">
                                                        <h5>Equipos en División Bancaria</h5>
                                                        <h2 class="text-right"><i
                                                                class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_en_div_bancaria }}</span>
                                                        </h2>
                                                        @can('ver-equipo')
                                                            <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                    data-target="#modal-equipos-division-bancaria{{-- $vehiculo->id --}}"
                                                                    id="btn-buscar-equipos-division-bancaria"
                                                                    style="color: rgb(253, 253, 253)">Ver
                                                                    más</a>
                                                            </p>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- TAB Camaras -->
                                    <div class="tab-pane fade" id="camaras3" role="tabpanel" aria-labelledby="camaras-tab3">
                                        <div class="row">
                                            <div class="col-md-4 col-xl-4">
                                                <div class="card-item bg-c-blue order-card">
                                                    <div class="card-block">
                                                        <h5>Cámaras instaladas</h5>
                                                        <h2 class="text-right"><i
                                                                class="fas fa-video f-left"></i><span>{{ $cant_camaras }}</span>
                                                        </h2>
                                                        @can('ver-camara')
                                                            <!--p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                                                                                                                                                                                                                                                                                                                                                 data-target="#modal-camaras{{-- $vehiculo->id --}}"id="btn-buscar-camaras" style="color: rgb(253, 253, 253)">Ver más</a></p-->
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endcan

                            @can('ver-menu-dashboard')
                            @endcan

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-sin-funcionar" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xs">
                <div class="modal-content">
                    <div class="modal-header bg-c-red">
                        <h4 class="modal-title text-white">Equipos sin funcionar</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <ul id="equiposSinFuncionarList" class="mt-3">
                            <!-- La lista de equipos se agregará aquí dinámicamente -->
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-funcionales" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-c-violet">
                        <h4 class="modal-title text-white">Equipos Funcionales</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <!--ul id="equiposFuncionalesList" class="mt-3">
                                </ul>
                                <hr-->
                        <h5>Equipos para móviles</h5>
                        <ul id="cantidadTotalEquiposMoviles" class="mt-3">
                            <!-- Lista de equipos -->
                        </ul>
                        <hr>
                        <h5>Equipos de mano</h5>
                        <ul id="cantidadTotalEquiposDeMano" class="mt-3">
                            <!-- Lista de equipos -->
                        </ul>
                        <hr>
                        <h5>Equipos base</h5>
                        <ul id="cantidadTotalEquipoBase" class="mt-3">
                            <!-- Lista de equipos -->
                        </ul>
                        <hr>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-baja" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xs">
                <div class="modal-content">
                    <div class="modal-header bg-blue">
                        <h4 class="modal-title text-white">Equipos dados de Baja o Recambio</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <ul id="equiposBajaList" class="mt-3">
                            <!-- La lista de equipos se agregará aquí dinámicamente -->
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-en-revision" class="modal fade" data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-indigo">
                        <h4 class="modal-title text-white">Equipos en Revisión</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <h5>Equipos para móviles</h5>
                        <ul id="equiposEnRevisionMoviles" class="mt-3"></ul>
                        <hr>
                        <h5>Equipos de mano</h5>
                        <ul id="equiposEnRevisionDeMano" class="mt-3"></ul>
                        <hr>
                        <h5>Equipos base</h5>
                        <ul id="equiposEnRevisionBase" class="mt-3"></ul>
                        <hr>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-provistos-por-pg" class="modal fade" data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-c-orange">
                        <h4 class="modal-title text-white">Equipos provistos por P.G.</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <h5>Totales por Marca y Modelo</h5>
                        <ul id="cantidadesTotalesPorMarcaYModeloListPG" class="mt-3">
                            <!-- Lista de cantidades totales por marca y modelo -->
                        </ul>
                        <hr>
                        <h5>Equipos por Estado</h5>
                        <ul id="equiposProvistosPorPgList" class="mt-3">
                            <!-- Lista de equipos provistos por PG -->
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-provistos-por-telecom" class="modal fade" data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-c-violet">
                        <h4 class="modal-title text-white">Equipos provistos por TELECOM</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <h5>Totales por Marca y Modelo</h5>
                        <ul id="cantidadesTotalesPorMarcaYModeloList" class="mt-3">
                            <!-- La lista de cantidades totales se agregará aquí dinámicamente -->
                        </ul>
                        <hr>
                        <h5>Equipos por Estado</h5>
                        <ul id="equiposProvistosPorTelecomList" class="mt-3">
                            <!-- La lista de equipos por estado se agregará aquí dinámicamente -->
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-desinstalaciones-parciales" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-c-black">
                        <h4 class="modal-title text-white">Desinstalaciones Parciales</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-desinstalaciones-parciales"
                                class="table table-condensed table-bordered table-stripped"></table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-moviles" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h4 class="modal-title text-white">Móviles</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <!--div class="col-lg-2">
                                                                                                                                                                                                                                                                                                <button id="btn-buscar-moviles" href="consultarMoviles"
                                                                                                                                                                                                                                                                                                    class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                                                                                                                                                                                            </div-->
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-moviles" class="table table-condensed table-bordered table-stripped"></table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-pg" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h4 class="modal-title text-white">Equipos en Patagonia Green</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="exportar-a-excel-equipos-pg" class="btn btn-success">
                                <i class="fa fa-file-excel"></i>
                            </button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-equipos-pg" class="table table-condensed table-bordered table-stripped">
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-stock" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-c-green">
                        <h4 class="modal-title text-white">Equipos en Stock 911</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="exportar-a-excel-equipos-stock" class="btn btn-success">
                                <i class="fa fa-file-excel"></i>
                            </button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-equipos-stock" class="table table-condensed table-bordered table-stripped">
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-departamental" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-c-gren-light">
                        <h4 class="modal-title text-white">Equipos en Departamental Paraná</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="exportar-a-excel-equipos-departamental" class="btn btn-success">
                                <i class="fa fa-file-excel"></i>
                            </button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-equipos-departamental"
                                class="table table-condensed table-bordered table-stripped">
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-division-911" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-c-yellow">
                        <h4 class="modal-title text-white">Equipos en División 911</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="exportar-a-excel-equipos-division-911" class="btn btn-success">
                                <i class="fa fa-file-excel"></i>
                            </button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-equipos-division-911"
                                class="table table-condensed table-bordered table-stripped">
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-equipos-division-bancaria" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-c-pink">
                        <h4 class="modal-title text-white">Equipos en División Bancaria</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="exportar-a-excel-equipos-division-bancaria" class="btn btn-success">
                                <i class="fa fa-file-excel"></i>
                            </button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-equipos-division-bancaria"
                                class="table table-condensed table-bordered table-stripped">
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-motos" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h4 class="modal-title text-white">Moto Patrullas</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <!--div class="col-lg-2">
                                                                                                                                                                                                                                                                                                <button id="btn-buscar-motopatrullas" href="consultarMotoPatrullas"
                                                                                                                                                                                                                                                                                                    class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                                                                                                                                                                                            </div-->
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-motos" class="table table-condensed table-bordered table-stripped"></table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-camaras" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);"
            role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-blue">
                        <h4 class="modal-title text-white">Cámaras</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <!--div class="col-lg-2">
                                                                                                                                                                                                                                                                                                <button id="btn-buscar-motopatrullas" href="consultarMotoPatrullas"
                                                                                                                                                                                                                                                                                                    class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                                                                                                                                                                                            </div-->
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-camaras" class="table table-condensed table-bordered table-stripped"></table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                            <span> Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </section>



    <script>
        $(document).ready(function () {
            // Definir funciones de manejo de eventos
            function handleClickEvent(id, consultarFunction) {
                $(id).click(function () {
                    consultarFunction($(this).data('id'));
                });
            }

            // Asignar manejadores de eventos

            handleClickEvent('#btn-buscar-equipos-sin-funcionar', consultarEquiposSinFuncionar);
            handleClickEvent('#btn-buscar-equipos-baja', consultarEquiposBaja);
            handleClickEvent('#btn-buscar-equipos-funcionales', consultarEquiposFuncionales);
            handleClickEvent('#btn-buscar-equipos-provistos-por-pg', consultarEquiposProvistosPorPG);
            handleClickEvent('#btn-buscar-equipos-provistos-por-telecom', consultarEquiposProvistosPorTELECOM);
            handleClickEvent('#btn-buscar-moviles', consultarMoviles);
            handleClickEvent('#btn-buscar-motopatrullas', consultarMotopatrullas);
            handleClickEvent('#btn-buscar-equipos-pg', consultarEquiposPG);
            handleClickEvent('#btn-buscar-equipos-stock', consultarEquiposStock);
            handleClickEvent('#btn-buscar-equipos-departamental', consultarEquiposDepartamental);
            handleClickEvent('#btn-buscar-equipos-division-911', consultarEquiposDivision911);
            handleClickEvent('#btn-buscar-equipos-division-bancaria', consultarEquiposDivisionBancaria);
            handleClickEvent('#btn-buscar-desinstalaciones-parciales', consultarDesinstalacionesParciales);
            handleClickEvent('#btn-buscar-camaras', function () {
                // Tu función de consulta para cámaras, si es diferente
            });

            $('#exportar-a-excel-todos-los-terminales').click(function () {
                exportarTodosLosTerminales();
            });
            $('#exportar-a-excel-equipos-departamental').click(function () {
                exportarAExcel('table-equipos-departamental', 'equipos_departamental', ['fecha', 'marca',
                    'modelo', 'issi', 'tei', 'nombre_recurso', 'ticket_per', 'observaciones'
                ]);
            });
            $('#exportar-a-excel-equipos-stock').click(function () {
                exportarAExcel('table-equipos-stock', 'equipos_stock', ['fecha', 'marca',
                    'modelo', 'issi', 'tei', 'nombre_recurso', 'ticket_per', 'observaciones'
                ]);
            });
            $('#exportar-a-excel-equipos-pg').click(function () {
                exportarAExcel('table-equipos-pg', 'equipos_pg', ['fecha', 'marca',
                    'modelo', 'issi', 'tei', 'nombre_recurso', 'ticket_per', 'observaciones'
                ]);
            });
            $('#exportar-a-excel-equipos-division-911').click(function () {
                exportarAExcel('table-equipos-division-911', 'equipos_911', ['fecha', 'marca',
                    'modelo', 'issi', 'tei', 'nombre_recurso', 'ticket_per', 'observaciones'
                ]);
            });
            $('#exportar-a-excel-equipos-division-bancaria').click(function () {
                exportarAExcel('table-equipos-division-bancaria', 'equipos_bancaria', ['fecha', 'marca',
                    'modelo', 'issi', 'tei', 'nombre_recurso', 'ticket_per', 'observaciones'
                ]);
            });
        });

        function exportarTodosLosTerminales() {
            $.ajax({
                type: 'GET',
                url: '/export-equipos',
                success: function (response) {
                    // La respuesta puede contener el archivo descargable, o puedes manejarla de acuerdo a tus necesidades
                    console.log(response);
                },
                error: function (error) {
                    // Manejar errores si es necesario
                    console.error(error);
                }
            });
        }

        function exportarAExcel(idTabla, nombreArchivo, camposExportar) {
            // Obtén los datos de la tabla
            var tableData = $('#' + idTabla).bootstrapTable('getData');

            // Filtra los datos para incluir solo los campos seleccionados
            var filteredData = tableData.map(function (row) {
                var filteredRow = {};
                camposExportar.forEach(function (campo) {
                    filteredRow[campo] = row[campo];
                });
                return filteredRow;
            });

            // Crea un nuevo libro de Excel
            var workbook = XLSX.utils.book_new();
            var worksheet = XLSX.utils.json_to_sheet(filteredData);

            // Ajusta el formato de las celdas para evitar la notación científica
            worksheet['!doctype'] = 'html';
            worksheet['!format'] = 'html';

            // Agrega la hoja al libro
            XLSX.utils.book_append_sheet(workbook, worksheet, nombreArchivo);

            // Guarda el libro como un archivo Excel
            XLSX.writeFile(workbook, nombreArchivo + '.xlsx');
        }

        function consultarEquiposFuncionales(id) {
            $.post(
                "{{ route('get-equipos-funcionales-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    //$("#equiposFuncionalesList").empty();
                    $("#cantidadTotalEquiposMoviles").empty();
                    $("#cantidadTotalEquiposDeMano").empty();
                    $("#cantidadTotalEquipoBase").empty();

                    data.forEach(function (equipo) {
                        var listItem = $("<li>").html(equipo.marca + " " + equipo.modelo + " (" +
                            equipo.provisto + "): <strong>" + equipo.cantidad + "</strong> (Usados: <strong>" +
                            equipo.cantidad_en_uso + "</strong> - Stock: <strong>" + equipo
                                .cantidad_en_stock + "</strong>)");

                        // Agregar a la lista principal
                        //$("#equiposFuncionalesList").append(listItem);

                        // Segregar por categoría
                        if (equipo.categoria === 'Movil') {
                            $("#cantidadTotalEquiposMoviles").append(listItem.clone());
                        } else if (equipo.categoria === 'Portatil') {
                            $("#cantidadTotalEquiposDeMano").append(listItem.clone());
                        } else if (equipo.categoria === 'Base') {
                            $("#cantidadTotalEquipoBase").append(listItem.clone());
                        }
                    });
                }
            ).fail(function (data) {
                swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
            });
        }

        function consultarEquiposSinFuncionar(id) {
            $.post(
                "{{ route('get-equipos-sin-funcionar-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    $("#equiposSinFuncionarList").empty();
                    data.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").text(equipo.marca + " " + equipo.modelo + " (" + equipo.provisto +
                                "): "),
                            $("<span>").css({
                                'font-weight': 'bold', // Hace que el texto sea negrita
                                'font-size': 'larger' // Ajusta el tamaño de la letra
                            }).text(equipo.cantidad)
                        );
                        $("#equiposSinFuncionarList").append(listItem);
                    });
                }).fail(function (data) {
                    swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
                });
        }

        function consultarEquiposBaja(id) {
            $.post(
                "{{ route('get-equipos-baja-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    $("#equiposBajaList").empty();
                    data.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").text(equipo.marca + " " + equipo.modelo + " (" + equipo.provisto +
                                "): "),
                            $("<span>").css({
                                'font-weight': 'bold', // Hace que el texto sea negrita
                                'font-size': 'larger' // Ajusta el tamaño de la letra
                            }).text(equipo.cantidad)
                        );
                        $("#equiposBajaList").append(listItem);
                    });
                }).fail(function (data) {
                    swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
                });
        }

        function consultarEquiposEnRevision(id) {
            $.post(
                "{{ route('get-equipos-en-revision-json') }}",
                {
                    _token: "{{ csrf_token() }}",
                },
                function (data, textStatus, xhr) {
                    console.log('data', data);

                    // Limpiar las listas antes de agregar nuevos datos
                    $("#equiposEnRevisionMoviles").empty();
                    $("#equiposEnRevisionDeMano").empty();
                    $("#equiposEnRevisionBase").empty();

                    // Recorrer los datos recibidos del backend
                    data.forEach(function (equipo) {
                        var listItem = $("<li>").html(
                            equipo.marca + " " + equipo.modelo + " (" + equipo.provisto + "): " +
                            "<strong>" + equipo.cantidad + "</strong>"
                        );

                        // Clasificar por categoría
                        if (equipo.categoria === 'Movil') {
                            $("#equiposEnRevisionMoviles").append(listItem.clone());
                        } else if (equipo.categoria === 'Portatil') {
                            $("#equiposEnRevisionDeMano").append(listItem.clone());
                        } else if (equipo.categoria === 'Base') {
                            $("#equiposEnRevisionBase").append(listItem.clone());
                        }
                    });
                }
            ).fail(function (data) {
                swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
            });
        }

        function consultarEquiposProvistosPorPG(id) {
            $.post(
                "{{ route('get-equipos-provistos-por-pg-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);

                    // Mostrar equipos provistos por PG
                    $("#equiposProvistosPorPgList").empty();
                    data.records.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").css({
                                //'font-size': 'larger'
                            }).text(equipo.marca + " " + equipo.modelo + " - "),
                            $("<span>").css({
                                'font-weight': 'bold',
                            }).text(equipo.estado),
                            $("<span>").text(" (" + equipo.provisto + "): "),
                            $("<span>").css({
                                'font-weight': 'bold',
                                //'font-size': 'larger'
                            }).text(equipo.cantidad)
                        );
                        $("#equiposProvistosPorPgList").append(listItem);
                    });

                    // Mostrar cantidades totales por marca y modelo
                    $("#cantidadesTotalesPorMarcaYModeloListPG").empty();
                    data.recordsTotales.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").css({
                                //'font-size': 'larger'
                            }).text(equipo.marca + " " + equipo.modelo + ": "),
                            $("<span>").css({
                                'font-weight': 'bold',
                                //'font-size': 'larger'
                            }).text(equipo.cantidad)
                        );
                        $("#cantidadesTotalesPorMarcaYModeloListPG").append(listItem);
                    });
                }).fail(function (data) {
                    swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
                });
        }

        function consultarEquiposProvistosPorTELECOM(id) {
            $.post(
                "{{ route('get-equipos-provistos-por-telecom-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);

                    $("#equiposProvistosPorTelecomList").empty();
                    data.records.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").css({
                                //'font-size': 'larger'
                            }).text(equipo.marca + " " + equipo.modelo + " - "),
                            $("<span>").css({
                                'font-weight': 'bold',
                            }).text(equipo.estado),
                            $("<span>").text(" (" + equipo.provisto + "): "),
                            $("<span>").css({
                                'font-weight': 'bold',
                                //'font-size': 'larger'
                            }).text(equipo.cantidad)
                        );
                        $("#equiposProvistosPorTelecomList").append(listItem);
                    });

                    // Mostrar cantidades totales
                    $("#cantidadesTotalesPorMarcaYModeloList").empty();
                    data.recordsTotales.forEach(function (equipo) {
                        var listItem = $("<li>");
                        listItem.append(
                            $("<span>").css({
                                //'font-size': 'larger'
                            }).text(equipo.marca + " " + equipo.modelo + ": "),
                            $("<span>").css({
                                'font-weight': 'bold',
                                //'font-size': 'larger'
                            }).text(equipo.cantidad)
                        );
                        $("#cantidadesTotalesPorMarcaYModeloList").append(listItem);
                    });
                }).fail(function (data) {
                    swal('Error', 'Ocurrió un error al obtener los datos: ' + data.responseJSON.message, 'error');
                });
        }

        function consultarCamaras(id) {
            $.post(
                "{{ route('get-equipos-PG-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableCamaras('table-camaras', data)
                }).fail(function (data) {
                    swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
                })
        }

        function consultarEquiposPG(id) {
            $.post(
                "{{ route('get-equipos-PG-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposPG('table-equipos-pg', data)
                }).fail(function (data) {
                    swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
                })
        }

        function consultarEquiposStock(id) {
            $.post(
                "{{ route('get-equipos-stock-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposStock('table-equipos-stock', data)
                }).fail(function (data) {
                    if (data.status === 404) {
                        swal('Error', 'Recurso Stock 911 no encontrado', 'error');
                    } else {
                        swal('Error', 'Ocurrió un error al cargar los equipos: ' + data.responseJSON.message, 'error');
                    }
                });
        }

        function consultarEquiposDepartamental(id) {
            $.post(
                "{{ route('get-equipos-departamental-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDepartamental('table-equipos-departamental', data)
                }).fail(function (data) {
                    if (data.status === 404) {
                        swal('Error', 'Departamental no encontrada', 'error');
                    } else {
                        swal('Error', 'Ocurrió un error al cargar los equipos: ' + data.responseJSON.message, 'error');
                    }
                });
        }

        function consultarEquiposDivision911(id) {
            $.post(
                "{{ route('get-equipos-division-911-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDivision911('table-equipos-division-911', data)
                }).fail(function (data) {
                    if (data.status === 404) {
                        swal('Error', 'Division no encontrada', 'error');
                    } else {
                        swal('Error', 'Ocurrió un error al cargar los equipos: ' + data.responseJSON.message, 'error');
                    }
                });
        }

        function consultarEquiposDivisionBancaria(id) {
            $.post(
                "{{ route('get-equipos-division-bancaria-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDivisionBancaria('table-equipos-division-bancaria', data)
                }).fail(function (data) {
                    if (data.status === 404) {
                        swal('Error', 'Division no encontrada', 'error');
                    } else {
                        swal('Error', 'Ocurrió un error al cargar los equipos: ' + data.responseJSON.message, 'error');
                    }
                });
        }

        function consultarMotopatrullas(id) {
            $.post(
                "{{ route('get-motos-json') }}", {
                _token: "{{ csrf_token() }}",
            },
                function (data, textStatus, xhr) {
                    setTableMotos('table-motos', data)
                }).fail(function (data) {
                    swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
                })
        }

        function consultarMoviles(id) {
            //$('#box-auditoria-historico').css({height:'400px'})
            //blockUi('box-auditoria-historico', true)
            $.post(
                "{{ route('get-moviles-json') }}", {
                _token: "{{ csrf_token() }}",
                //distribuidor_id: id,
                //fecha: $('#fecha-auditoria-historico').val()
            },
                function (data, textStatus, xhr) {

                    //blockUi('box-auditoria-historico')
                    setTableMoviles('table-moviles', data)

                }).fail(function (data) {
                    //hideShowBlockUi('box-auditoria')
                    swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
                })
        }

        function consultarDesinstalacionesParciales(id) {
            $.post(
                "{{ route('get-desinstalaciones-parciales-json') }}", {
                _token: "{{ csrf_token() }}",
                //distribuidor_id: id,
                //fecha: $('#fecha-auditoria-historico').val()
            },
                function (data, textStatus, xhr) {

                    //blockUi('box-auditoria-historico')
                    setTableDesinstalacionesParciales('table-desinstalaciones-parciales', data)

                }).fail(function (data) {
                    //hideShowBlockUi('box-auditoria')
                    swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
                })
        }

        function setTableDesinstalacionesParciales(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true,
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'recurso_asignado',
                sortable: true,
            })
            columns.push({
                title: 'Vehiculo',
                field: 'vehiculo_asignado',
                sortable: true
            })

            console.log('ROWS', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableMoviles(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Nro',
                field: 'id',
                sortable: true,
            })
            columns.push({
                title: 'Destino',
                field: 'nombre',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
                /*formatter: function(i, row, index) {
                    return destino(i, row)
                }*/
            })
            columns.push({
                title: 'Vehiculo',
                field: 'tipo_vehiculo',
                sortable: true
            })
            /*columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true
            })*/
            /*columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true
            })*/
            /*columns.push({
                title: 'Version App',
                field: 'version_app',
                sortable: true,
                align: ''
            })*/
            /*columns.push({
                title: 'Version Android',
                field: 'version_os',
                sortable: true
            })*/
            /*columns.push({
                title: 'Red',
                field: 'red',
                sortable: true,
                align: 'center',
                formatter: function(i, row, index) {
                    return wifi(i, row)
                }
            })*/
            /*columns.push({
                title: 'Internet',
                field: 'internet',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return internet(i, row)
                }
            })*/
            /*columns.push({
                title: 'Señal',
                field: 'signal',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return signal(i, row)
                }
            })*/
            /*columns.push({
                title: 'GPS',
                field: 'gps',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return gps(i, row)
                }
            })*/
            /*columns.push({
                title: 'Ubicación',
                field: 'mapa',
                align: 'center',
                formatter: function(i, row, index) {
                    return mapa(i, row)
                }
            })*/
            /*columns.push({
                title: 'Altitud',
                field: 'altutud',
                sortable: true,
                align: 'right'
            })*/
            /*columns.push({
                title: 'Ram Total',
                field: 'ram_total',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return totalRam(i, row)
                }
            })*/
            /*columns.push({
                title: 'Ram Disponible',
                field: 'ram_disponible',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return ram(i, row)
                }
            })*/
            /*columns.push({
                title: 'Storage Total',
                field: 'storage_total',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return totalStorage(i, row)
                }
            })*/
            /*columns.push({
                title: 'Storage Disponible',
                field: 'storage_disponible',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return storage(i, row)
                }
            })*/

            /*columns.push({
                title: 'Bateria',
                field: 'niver_bateria',
                align: 'center',
                sortable: true,
                formatter: function(i, row, index) {
                    return battery(i, row)
                }
            })*/
            /*columns.push({
                title: 'Emp. Prestadora',
                field: 'empresa_prestadora',
                sortable: true
            })*/
            /*columns.push({
                title: 'Permisos',
                field: 'permisos',
                sortable: true
            })*/


            // if(!isMobile()){
            //   fixedColumnTable(table_id, 1, {fondo: '#DEF1F1',texto: '#000000'})
            // }


            console.log('ROWS', rows)
            table.bootstrapTable({
                striped: true,
                //pagination: true,
                fixedColumns: true,
                fixedNumber: 1,
                // showColumns: true,
                // showToggle: true,
                // showExport: true,
                sortable: true,
                paginationVAlign: 'both',
                //pageSize: 10,
                //pageList: [10, 25, 50, 100, 'ALL'],
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableMotos(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Nro',
                field: 'id',
                sortable: true,
            })
            columns.push({
                title: 'Destino',
                field: 'nombre',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,

            })
            columns.push({
                title: 'Vehiculo',
                field: 'tipo_vehiculo',
                sortable: true
            })

            console.log('ROWS', rows)
            table.bootstrapTable({
                striped: true,
                //pagination: true,
                fixedColumns: true,
                fixedNumber: 1,
                // showColumns: true,
                // showToggle: true,
                // showExport: true,
                sortable: true,
                paginationVAlign: 'both',
                //pageSize: 10,
                //pageList: [10, 25, 50, 100, 'ALL'],
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableEquiposPG(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
                cellStyle: function cellStyle(value, row, index, field) {
                    // Limita la altura de la celda y muestra solo una línea de texto
                    return {
                        css: {
                            'max-height': '30px', // Ajusta la altura máxima según tus necesidades
                            'overflow': 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap'
                        }
                    };
                },
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableEquiposStock(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Recurso anterior',
                field: 'recurso_anterior',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
                cellStyle: function cellStyle(value, row, index, field) {
                    // Limita la altura de la celda y muestra solo una línea de texto
                    return {
                        css: {
                            'max-height': '30px', // Ajusta la altura máxima según tus necesidades
                            'overflow': 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap'
                        }
                    };
                },
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableEquiposDepartamental(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
                cellStyle: function cellStyle(value, row, index, field) {
                    // Limita la altura de la celda y muestra solo una línea de texto
                    return {
                        css: {
                            'max-height': '30px', // Ajusta la altura máxima según tus necesidades
                            'overflow': 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap'
                        }
                    };
                },
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableEquiposDivision911(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
                cellStyle: function cellStyle(value, row, index, field) {
                    // Limita la altura de la celda y muestra solo una línea de texto
                    return {
                        css: {
                            'max-height': '30px', // Ajusta la altura máxima según tus necesidades
                            'overflow': 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap'
                        }
                    };
                },
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableEquiposDivisionBancaria(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Fecha',
                field: 'fecha',
                sortable: true,
            })
            columns.push({
                title: 'Marca',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
                cellStyle: function cellStyle(value, row, index, field) {
                    // Limita la altura de la celda y muestra solo una línea de texto
                    return {
                        css: {
                            'max-height': '30px', // Ajusta la altura máxima según tus necesidades
                            'overflow': 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap'
                        }
                    };
                },
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                fixedColumns: true,
                fixedNumber: 1,
                sortable: true,
                paginationVAlign: 'both',
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }

        function setTableCamaras(table_id, rows) {
            var table = $('#' + table_id)
            var columns = [];
            table.bootstrapTable('destroy')

            columns.push({
                title: 'Nombre',
                field: 'nombre',
                sortable: true,
            })
            columns.push({
                title: 'Sitio',
                field: 'marca',
                sortable: true,
            })
            columns.push({
                title: 'Modelo',
                field: 'modelo',
                sortable: true,
            })
            columns.push({
                title: 'ISSI',
                field: 'issi',
                sortable: true
            })
            columns.push({
                title: 'TEI',
                field: 'tei',
                sortable: true
            })
            columns.push({
                title: 'Nombre recurso',
                field: 'nombre_recurso',
                sortable: true,
            })
            columns.push({
                title: 'Ticket PER',
                field: 'ticket_per',
                sortable: true,
            })
            columns.push({
                title: 'Observaciones',
                field: 'observaciones',
                sortable: false,
            })

            console.log('ROWS_TABLA', rows)
            table.bootstrapTable({
                striped: true,
                //pagination: true,
                fixedColumns: true,
                fixedNumber: 1,
                // showColumns: true,
                // showToggle: true,
                // showExport: true,
                sortable: true,
                paginationVAlign: 'both',
                //pageSize: 10,
                //pageList: [10, 25, 50, 100, 'ALL'],
                columns: columns,
                data: rows
            });

            table.find('thead').css({
                backgroundColor: 'white'
            })
            table.closest('.fixed-table-body').css({
                height: '400px'
            })
        }
    </script>
@endsection
