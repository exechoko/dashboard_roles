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
                                {{--
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
                                --}}
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-green order-card">
                                        <div class="card-block">
                                            <h5>Equipos sin funcionar</h5>
                                            @php
                                                use App\Models\Equipo;
                                                $cant_equipos = Equipo::where('estado_id', 3)->count();
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
                                    <div class="card bg-c-violet order-card">
                                        <div class="card-block">
                                            <h5>Equipos funcionales</h5>
                                            @php
                                                $cant_equipos = Equipo::where('estado_id', '<>', 3)->count();
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
                                    <div class="card bg-c-orange order-card">
                                        <div class="card-block">
                                            <h5>Equipos derivados a P.G.</h5>
                                            @php
                                                use App\Models\Historico;
                                                $cant_equipos = Historico::where('destino_id', 233)
                                                    ->where('fecha_desasignacion', null)
                                                    ->count();
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
                                                    class="fas fa-motorcycle f-left"></i><span>{{ $cant_motos }}</span>
                                            </h2>
                                            @can('ver-equipo')
                                                <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                    data-target="#modal-motos{{-- $vehiculo->id --}}">Ver más</a>
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
                                                    class="fas fa-car f-left"></i><span>{{ $cant_moviles }}</span></h2>
                                            @can('ver-equipo')
                                                <p class="m-b-0 text-right"><a href="#" data-toggle="modal"
                                                        data-target="#modal-moviles{{-- $vehiculo->id --}}">Ver más</a>
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


        <div id="modal-moviles" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-green">
                        <div class="col-lg-10">
                            <h3 class="modal-title upper-case" id="title">Móviles</h3>
                        </div>
                        <div class="col-lg-2">
                            <button id="close" class="close" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times text-white"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="btn-buscar-moviles" href="consultarMoviles"
                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-moviles"
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

        <div id="modal-motos" class="modal fade " data-backdrop="false" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
            <div id="dialog" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-green">
                        <div class="col-lg-10">
                            <h3 class="modal-title upper-case" id="title">Moto patrullas</h3>
                        </div>
                        <div class="col-lg-2">
                            <button id="close" class="close" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times text-white"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" style="min-height: 500px">
                        <div class="col-lg-2">
                            <button id="btn-buscar-motopatrullas" href="consultarMotoPatrullas"
                                class="btn gray btn-outline-warning btn-buscar" style="margin-top:5px">Buscar</button>
                        </div>
                        <div class="col-lg-12" style="margin-top:20px; padding:0; min-height: 400px;">
                            <table id="table-motos"
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

    </section>

    <script>
        $(document).ready(function() {
            $('#btn-buscar-moviles').click(function() {
                consultarMoviles($(this).data('id'))
            }),

            $('#btn-buscar-motopatrullas').click(function() {
                consultarMotopatrullas($(this).data('id'))
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
    </script>

@endsection


