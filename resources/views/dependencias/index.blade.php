@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Dependencias</h3>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        @can('crear-dependencia')
                            <a class="btn btn-success" href="{{ route('dependencias.create') }}">Nuevo</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4>Ultimas dependencias agregadas</h4>
                        <div class="table-responsive">
                            <table class="table table-striped mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Dependencia</th>
                                    <th style="color:#fff;">Telefono</th>
                                    <th style="color:#fff;">Dirección</th>
                                </thead>
                                <tbody>
                                    @foreach ($dependencias as $dependencia)
                                        <tr>
                                            <td style="display: none;">{{ $dependencia->id }}</td>
                                            <td style="font-weight:bold">{{ $dependencia->nombre }}</td>
                                            <td style="font-weight:bold">{{ $dependencia->telefono }}</td>
                                            <td style="font-weight:bold">{{ $dependencia->ubicacion }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--Accordion wrapper-->
        <div class="accordion md-accordion" id="accordionEx" role="tablist" aria-multiselectable="true">

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingOne1">
                    <a data-toggle="collapse" data-parent="#accordionEx" href="#collapseOne1" aria-expanded="true"
                        aria-controls="collapseOne1">
                        <h5 class="mb-0">
                            Direcciones <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseOne1" class="collapse show" role="tabpanel" aria-labelledby="headingOne1"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($direcciones as $direccion)
                                        @include('dependencias.modal.editar_direccion')
                                        <tr>
                                            <td style="display: none;">{{ $direccion->id }}</td>
                                            <td style="font-weight:bold">{{ $direccion->nombre }}</td>
                                            <td>{{ $direccion->telefono }}</td>
                                            <td>{{ $direccion->ubicacion }}</td>
                                            <td>
                                                <form action="#" method="POST">
                                                    @can('editar-dependencia')
                                                        {{--<a class="btn btn-info" href="#">Editar</a>--}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $direccion->id }}">Editar</a>
                                                    @endcan

                                                    @csrf
                                                    @method('DELETE')
                                                    @can('borrar-dependencia')
                                                        <button type="submit" onclick="return confirm('Está seguro')"
                                                            class="btn btn-danger">Borrar</button>
                                                    @endcan
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Accordion card -->

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingTwo2">
                    <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#collapseTwo2"
                        aria-expanded="false" aria-controls="collapseTwo2">
                        <h5 class="mb-0">
                            Departamentales <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseTwo2" class="collapse" role="tabpanel" aria-labelledby="headingTwo2"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($departamentales as $departamental)
                                        @include('dependencias.modal.editar_departamental')
                                        <tr>
                                            <td style="display: none;">{{ $departamental->id }}</td>
                                            <td style="font-weight:bold">{{ $departamental->nombre }}</td>
                                            <td>{{ $departamental->telefono }}</td>
                                            <td>{{ $departamental->ubicacion }}</td>
                                            <td>
                                                <form action="#" method="POST">
                                                    @can('editar-dependencia')
                                                        {{--<a class="btn btn-info" href="#">Editar</a>--}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarDptal{{ $departamental->id }}">Editar</a>
                                                    @endcan

                                                    @csrf
                                                    @method('DELETE')
                                                    @can('borrar-dependencia')
                                                        <button type="submit" onclick="return confirm('Está seguro')"
                                                            class="btn btn-danger">Borrar</button>
                                                    @endcan
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Accordion card -->

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingThree3">
                    <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#collapseThree3"
                        aria-expanded="false" aria-controls="collapseThree3">
                        <h5 class="mb-0">
                            Divisiones <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseThree3" class="collapse" role="tabpanel" aria-labelledby="headingThree3"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3
                        wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum
                        eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla
                        assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred
                        nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer
                        farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
                        labore sustainable VHS.
                    </div>
                </div>
            </div>
            <!-- Accordion card -->

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingThree4">
                    <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#collapseThree4"
                        aria-expanded="false" aria-controls="collapseThree4">
                        <h5 class="mb-0">
                            Comisarías <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseThree4" class="collapse" role="tabpanel" aria-labelledby="headingThree4"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        Anim pari Acordion 4 excepteur butcher vice lomo. Leggings occaecat craft beer
                        farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
                        labore sustainable VHS.
                    </div>
                </div>
            </div>
            <!-- Accordion card -->

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingThree5">
                    <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#collapseThree5"
                        aria-expanded="false" aria-controls="collapseThree5">
                        <h5 class="mb-0">
                            Secciones <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseThree5" class="collapse" role="tabpanel" aria-labelledby="headingThree5"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        Anim pari Acordion 4 excepteur butcher vice lomo. Leggings occaecat craft beer
                        farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
                        labore sustainable VHS.
                    </div>
                </div>
            </div>
            <!-- Accordion card -->

            <!-- Accordion card -->
            <div class="card">

                <!-- Card header -->
                <div class="card-header" role="tab" id="headingThree6">
                    <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#collapseThree6"
                        aria-expanded="false" aria-controls="collapseThree4">
                        <h5 class="mb-0">
                            Destacamentos <i class="fas fa-angle-down rotate-icon"></i>
                        </h5>
                    </a>
                </div>

                <!-- Card body -->
                <div id="collapseThree6" class="collapse" role="tabpanel" aria-labelledby="headingThree6"
                    data-parent="#accordionEx">
                    <div class="card-body">
                        Anim pari Acordion 4 excepteur butcher vice lomo. Leggings occaecat craft beer
                        farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus
                        labore sustainable VHS.
                    </div>
                </div>
            </div>
            <!-- Accordion card -->
        </div>


    </section>
@endsection
