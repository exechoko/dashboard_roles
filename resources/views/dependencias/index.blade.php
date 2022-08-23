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
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($direcciones as $direccion)
                                        <tr>
                                            <td style="display: none;">{{ $direccion->id }}</td>
                                            <td>{{ $direccion->nombre }}</td>
                                            <td>
                                                <form action="#" method="POST">
                                                    @can('editar-dependencia')
                                                        <a class="btn btn-info" href="#">Editar</a>
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
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($departamentales as $departamental)
                                        <tr>
                                            <td style="display: none;">{{ $departamental->id }}</td>
                                            <td>{{ $departamental->nombre }}</td>
                                            <td>
                                                <form action="#" method="POST">
                                                    @can('editar-dependencia')
                                                        <a class="btn btn-info" href="#">Editar</a>
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
                            Collapsible Group Item #3 <i class="fas fa-angle-down rotate-icon"></i>
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
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @can('crear-dependencia')
                            <a class="btn btn-success" href="{{ route('dependencias.create') }}">Nuevo</a>
                        @endcan
                        <div class="table-responsive">
                            <table class="table table-striped mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Dependencia</th>
                                    <th style="color:#fff;">Modelo</th>
                                    <th style="color:#fff;">ISSI</th>
                                    <th style="color:#fff;">TEI</th>
                                    <th style="color: #fff">Estado</th>
                                    <th style="color: #fff">Ult. Mod.</th>
                                    <th style="color:#fff;">Acciones</th>
                                </thead>
                                <tbody>
                                    @foreach ($dependencias as $dependencia)
                                        <tr>
                                            <td style="display: none;">{{ $dependencia->id }}</td>
                                            <td>{{ $dependencia->tipo_terminal->marca }}</td>
                                            <td>{{ $dependencia->tipo_terminal->modelo }}</td>
                                            <td>{{ $dependencia->issi }}</td>
                                            <td>{{ $dependencia->tei }}</td>
                                            <td>{{ $dependencia->estado->nombre }}</td>
                                            <td>{{ \Carbon\Carbon::parse($dependencia->fecha_estado)->format('d-m-Y') }}
                                            </td>
                                            <td>
                                                <form action="{{ route('dependencias.destroy', $dependencia->id) }}"
                                                    method="POST">
                                                    @can('editar-dependencia')
                                                        <a class="btn btn-info"
                                                            href="{{ route('dependencias.edit', $dependencia->id) }}">Editar</a>
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
        </div>
    </section>
@endsection
