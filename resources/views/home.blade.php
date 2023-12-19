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
                                                            id="btn-buscar-equipos-pg" style="color: rgb(253, 253, 253)">Ver más</a>
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
                                                            id="btn-buscar-equipos-stock" style="color: rgb(253, 253, 253)">Ver
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
                                    <div class="col-md-4 col-xl-4">
                                        <div class="card-item bg-c-violet order-card">
                                            <div class="card-block">
                                                <h5>Equipos sin funcionar</h5>

                                                <h2 class="text-right"><i
                                                        class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_sin_funcionar }}</span></h2>
                                                @can('ver-equipo')
                                                    <!--p class="m-b-0 text-right"><a href="/equipos" class="text-white">Ver más</a>
                                                    </p-->
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-xl-4">
                                        <div class="card-item bg-c-violet order-card">
                                            <div class="card-block">
                                                <h5>Equipos funcionales</h5>

                                                <h2 class="text-right"><i
                                                        class="fa fa-mobile f-left"></i><span>{{ $cant_equipos_funcionales }}</span></h2>
                                                @can('ver-equipo')
                                                    <!--p class="m-b-0 text-right"><a href="/equipos" class="text-white"
                                                            style="color: rgb(253, 253, 253)">Ver más</a>
                                                    </p-->
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                    <!--div class="col-md-4 col-xl-4">
                                        <div class="card-item bg-c-red order-card">
                                            <div class="card-block">
                                                <h5>Moto Patrulla</h5>

                                                <h2 class="text-right"><i
                                                        class="fas fa-motorcycle f-left"></i><span>{{ $cant_motos }}</span>
                                                </h2>
                                                @can('ver-equipo')
                                                    <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                            data-target="#modal-motos{{-- $vehiculo->id --}}"
                                                            id="btn-buscar-motopatrullas" style="color: rgb(253, 253, 253)">Ver
                                                            más</a>
                                                    </p>
                                                @endcan
                                            </div>
                                        </div>
                                    </div-->
                                    <!--div class="col-md-4 col-xl-4">
                                        <div class="card-item bg-c-gren-light order-card">
                                            <div class="card-block">
                                                <h5>Móviles</h5>

                                                <h2 class="text-right"><i
                                                        class="fas fa-car f-left"></i><span>{{ $cant_moviles }}</span></h2>
                                                @can('ver-equipo')
                                                    <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                            data-target="#modal-moviles{{-- $vehiculo->id --}}"
                                                            id="btn-buscar-moviles" style="color: rgb(253, 253, 253)">Ver más</a>
                                                    </p>
                                                @endcan
                                            </div>
                                        </div>
                                    </div-->
                                    <div class="col-md-4 col-xl-4">
                                        <div class="card-item bg-c-blue order-card">
                                            <div class="card-block">
                                                <h5>Cámaras instaladas</h5>

                                                <h2 class="text-right"><i
                                                        class="fas fa-video f-left"></i><span>{{ $cant_camaras }}</span></h2>
                                                @can('ver-camara')
                                                    <!--p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                            data-target="#modal-camaras{{-- $vehiculo->id --}}"
                                                            id="btn-buscar-camaras" style="color: rgb(253, 253, 253)">Ver más</a>
                                                    </p-->
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endcan

                        </div>
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

        <div id="modal-equipos-pg" class="modal fade " data-backdrop="false"
            style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h4 class="modal-title text-white">Equipos en Patagonia Green</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <!--div class="col-lg-2">
                                                                                                                            <button id="btn-buscar-equipos-pg" href="consultarEquiposPG"
                                                                                                                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                        </div-->
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
                        <!--div class="col-lg-2">
                                                                                                                            <button id="btn-buscar-equipos-pg" href="consultarEquiposPG"
                                                                                                                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                        </div-->
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
                        <!--div class="col-lg-2">
                                                                                                                            <button id="btn-buscar-equipos-pg" href="consultarEquiposPG"
                                                                                                                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                        </div-->
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
                        <!--div class="col-lg-2">
                                                                                                                            <button id="btn-buscar-equipos-pg" href="consultarEquiposPG"
                                                                                                                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                        </div-->
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
                        <!--div class="col-lg-2">
                                                                                                                            <button id="btn-buscar-equipos-pg" href="consultarEquiposPG"
                                                                                                                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                                                                                                                        </div-->
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
        $(document).ready(function() {
            $('#btn-buscar-moviles').click(function() {
                    consultarMoviles($(this).data('id'))
                }),

                $('#btn-buscar-motopatrullas').click(function() {
                    consultarMotopatrullas($(this).data('id'))
                }),

                $('#btn-buscar-equipos-pg').click(function() {
                    consultarEquiposPG($(this).data('id'))
                }),
                $('#btn-buscar-equipos-stock').click(function() {
                    consultarEquiposStock($(this).data('id'))
                }),
                $('#btn-buscar-equipos-departamental').click(function() {
                    consultarEquiposDepartamental($(this).data('id'))
                }),
                $('#btn-buscar-equipos-division-911').click(function() {
                    consultarEquiposDivision911($(this).data('id'))
                }),
                $('#btn-buscar-equipos-division-bancaria').click(function() {
                    consultarEquiposDivisionBancaria($(this).data('id'))
                }),
                $('#btn-buscar-desinstalaciones-parciales').click(function() {
                    consultarDesinstalacionesParciales($(this).data('id'))
                }),
                $('#btn-buscar-camaras').click(function() {
                    //consultarEquiposPG($(this).data('id'))
                })
        });

        /*function destino(i, row){
          var destino = false;
          if(row.destino_id != null){


          }
          if(!geo){
            return '<label class="label label-danger">DESCONOCIDO</label>';
          }

          var latlon = [row.latitud, row.longitud];
          var url = 'https://www.google.es/maps?q=' + latlon.join(',');
            return '<a href="' + url + '" target="_blank" class="btn btn-primary btn-xs">\
                      <i class="fa fa-map-marker"></i>\
                      <span style="margin-left:5px">MAPA</span>\
                    </a>';
        }*/
        function consultarCamaras(id) {
            $.post(
                "{{ route('get-equipos-PG-json') }}", {
                    _token: "{{ csrf_token() }}",
                },
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableCamaras('table-camaras', data)
                }).fail(function(data) {
                swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
            })
        }

        function consultarEquiposPG(id) {
            $.post(
                "{{ route('get-equipos-PG-json') }}", {
                    _token: "{{ csrf_token() }}",
                },
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposPG('table-equipos-pg', data)
                }).fail(function(data) {
                swal('Error', 'Ocurrio un error al guardar: ' + data.responseJSON.message, 'error');
            })
        }

        function consultarEquiposStock(id) {
            $.post(
                "{{ route('get-equipos-stock-json') }}", {
                    _token: "{{ csrf_token() }}",
                },
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposStock('table-equipos-stock', data)
                }).fail(function(data) {
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
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDepartamental('table-equipos-departamental', data)
                }).fail(function(data) {
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
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDivision911('table-equipos-division-911', data)
                }).fail(function(data) {
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
                function(data, textStatus, xhr) {
                    console.log('data', data);
                    setTableEquiposDivisionBancaria('table-equipos-division-bancaria', data)
                }).fail(function(data) {
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
                function(data, textStatus, xhr) {
                    setTableMotos('table-motos', data)
                }).fail(function(data) {
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
                function(data, textStatus, xhr) {

                    //blockUi('box-auditoria-historico')
                    setTableMoviles('table-moviles', data)

                }).fail(function(data) {
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
                function(data, textStatus, xhr) {

                    //blockUi('box-auditoria-historico')
                    setTableDesinstalacionesParciales('table-desinstalaciones-parciales', data)

                }).fail(function(data) {
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
