@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Dashboard</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                @can('ver-usuario')
                                    <div class="col-md-4 col-xl-4">
                                        <div class="card bg-c-blue order-card">
                                            <div class="card-block">
                                                <h5>Usuarios</h5>
                                                <h2 class="text-right"><i
                                                        class="fa fa-users f-left"></i><span>{{ $cant_usuarios }}</span></h2>
                                                <p class="m-b-0 text-right"><a href="/usuarios" class="text-white">Ver más</a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                                @can('ver-rol')
                                    <div class="col-md-4 col-xl-4">
                                        <div class="card bg-c-green order-card">
                                            <div class="card-block">
                                                <h5>Roles</h5>
                                                <h2 class="text-right"><i
                                                        class="fa fa-user-lock f-left"></i><span>{{ $cant_roles }}</span></h2>
                                                <p class="m-b-0 text-right"><a href="/roles" class="text-white">Ver más</a></p>
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-pink order-card">
                                        <div class="card-block">
                                            <h5>Equipos</h5>
                                            @php
                                                use App\Models\Equipo;
                                                $cant_equipos = Equipo::count();
                                            @endphp
                                            <h2 class="text-right"><i
                                                    class="fa fa-mobile f-left"></i><span>{{ $cant_equipos }}</span></h2>
                                            @can('ver-equipo')
                                                <p class="m-b-0 text-right"><a href="/equipos" class="text-white">Ver más</a>
                                                </p>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-red order-card">
                                        <div class="card-block">
                                            <h5>Moto Patrulla</h5>
                                            @php
                                                use App\Models\Recurso;
                                                $veh = 'Moto';
                                                $cant_motos = Recurso::whereHas('vehiculo', function ($query) use ($veh) {
                                                    $query->where('tipo_vehiculo', '=', $veh);
                                                })->count();
                                            @endphp
                                            <h2 class="text-right"><i
                                                    class="fas fa-motorcycle f-left"></i><span>{{ $cant_motos }}</span></h2>
                                            @can('ver-equipo')
                                                <p class="m-b-0 text-right"><a href="/recursos" class="text-white">Ver más</a>
                                                </p>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-gren-light order-card">
                                        <div class="card-block">
                                            <h5>Móviles</h5>
                                            @php
                                                $tipo_veh1 = 'Auto';
                                                $tipo_veh2 = 'Camioneta';
                                                $cant_moviles = Recurso::whereHas('vehiculo', function ($query) use ($tipo_veh1, $tipo_veh2) {
                                                    $query->where('tipo_vehiculo', '=', $tipo_veh1)->orWhere('tipo_vehiculo', '=', $tipo_veh2);
                                                })->count();
                                            @endphp
                                            <h2 class="text-right"><i
                                                    class="fas fa-truck-pickup f-left"></i><span>{{ $cant_moviles }}</span></h2>
                                            @can('ver-equipo')
                                                <p class="m-b-0 text-right"><a href="/recursos" class="text-white">Ver más</a>
                                                </p>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
