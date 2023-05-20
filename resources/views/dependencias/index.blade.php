@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Dependencias</h3>
        </div>

        @can('crear-dependencia')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <a class="btn btn-success" href="{{ route('dependencias.create') }}">Nuevo</a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

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
                            <input class="form-control" id="inputDirecciones" type="text"
                                placeholder="Buscar direcciones">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableDirecciones">
                                    @foreach ($direcciones as $direccion)
                                        @include('dependencias.modal.editar_direccion')
                                        <tr>
                                            <td style="display: none;">{{ $direccion->id }}</td>
                                            <td style="font-weight:bold">{{ $direccion->nombre }}</td>
                                            <td>{{ $direccion->telefono }}</td>
                                            <td>{{ $direccion->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditar{{ $direccion->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
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
                            <input class="form-control" id="inputDepartamentales" type="text"
                                placeholder="Buscar departamentales">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableDepartamentales">
                                    @foreach ($departamentales as $departamental)
                                        @include('dependencias.modal.editar_departamental')
                                        <tr>
                                            <td style="display: none;">{{ $departamental->id }}</td>
                                            <td style="font-weight:bold">{{ $departamental->nombre }}</td>
                                            <td>{{ $departamental->telefono }}</td>
                                            <td>{{ $departamental->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarDptal{{ $departamental->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
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
                        <div class="table-responsive">
                            <input class="form-control" id="inputDivisiones" type="text"
                                placeholder="Buscar divisiones">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableDivisiones">
                                    @foreach ($divisiones as $division)
                                        @include('dependencias.modal.editar_division')
                                        <tr>
                                            <td style="display: none;">{{ $division->id }}</td>
                                            @if (!is_null($division->departamental))
                                                <td style="font-weight:bold">
                                                    {{ $division->nombre . ' - ' . $division->departamental->nombre }}</td>
                                            @elseif (!is_null($division->direccion))
                                                <td style="font-weight:bold">
                                                    {{ $division->nombre . ' - ' . $division->direccion->nombre }}</td>
                                            @endif

                                            <td>{{ $division->telefono }}</td>
                                            <td>{{ $division->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarDivision{{ $division->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
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
                        <div class="table-responsive">
                            <input class="form-control" id="inputComisarias" type="text"
                                placeholder="Buscar comisarias">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableComisarias">
                                    @foreach ($comisarias as $comisaria)
                                        @include('dependencias.modal.editar_comisaria')
                                        <tr>
                                            <td style="display: none;">{{ $comisaria->id }}</td>
                                            @if (!is_null($comisaria->departamental))
                                                <td style="font-weight:bold">
                                                    {{ $comisaria->nombre . ' - ' . $comisaria->departamental->nombre }}
                                                </td>
                                            @else
                                                <td style="font-weight:bold">
                                                    {{ $comisaria->nombre }}</td>
                                            @endif

                                            <td>{{ $comisaria->telefono }}</td>
                                            <td>{{ $comisaria->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarComisaria{{ $comisaria->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
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
                        <div class="table-responsive">
                            <input class="form-control" id="inputSecciones" type="text"
                                placeholder="Buscar secciones">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableSecciones">
                                    @foreach ($secciones as $seccion)
                                        @include('dependencias.modal.editar_seccion')
                                        <tr>
                                            <td style="display: none;">{{ $seccion->id }}</td>
                                            @if (!is_null($seccion->comisaria))
                                                <td style="font-weight:bold">
                                                    {{ $seccion->nombre . ' - ' . $seccion->comisaria->nombre }}</td>
                                            @elseif (!is_null($seccion->division))
                                                <td style="font-weight:bold">
                                                    {{ $seccion->nombre . ' - ' . $seccion->division->nombre }}</td>
                                            @elseif (!is_null($seccion->departamental))
                                                <td style="font-weight:bold">
                                                    {{ $seccion->nombre . ' - ' . $seccion->departamental->nombre }}</td>
                                            @elseif (!is_null($seccion->direccion))
                                                <td style="font-weight:bold">
                                                    {{ $seccion->nombre . ' - ' . $seccion->direccion->nombre }}</td>
                                            @endif

                                            <td>{{ $seccion->telefono }}</td>
                                            <td>{{ $seccion->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarSeccion{{ $seccion->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
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
                        <div class="table-responsive">
                            <input class="form-control" id="inputDestacamentos" type="text"
                                placeholder="Buscar destacamentos">
                            <table class="table table-striped table-hover mt-2">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="display: none;">ID</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody id="myTableDestacamentos">
                                    @foreach ($destacamentos as $destacamento)
                                        @include('dependencias.modal.editar_destacamento')
                                        <tr>
                                            <td style="display: none;">{{ $destacamento->id }}</td>
                                            @if (!is_null($destacamento->comisaria))
                                                <td style="font-weight:bold">
                                                    {{ $destacamento->nombre . ' - ' . $destacamento->comisaria->nombre }}
                                                </td>
                                            @elseif (!is_null($destacamento->division))
                                                <td style="font-weight:bold">
                                                    {{ $destacamento->nombre . ' - ' . $destacamento->division->nombre }}
                                                </td>
                                            @elseif (!is_null($destacamento->departamental))
                                                <td style="font-weight:bold">
                                                    {{ $destacamento->nombre . ' - ' . $destacamento->departamental->nombre }}
                                                </td>
                                            @elseif (!is_null($destacamento->direccion))
                                                <td style="font-weight:bold">
                                                    {{ $destacamento->nombre . ' - ' . $destacamento->direccion->nombre }}
                                                </td>
                                            @endif

                                            <td>{{ $destacamento->telefono }}</td>
                                            <td>{{ $destacamento->ubicacion }}</td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <form action="#" method="POST">
                                                        {{-- <a class="btn btn-info" href="#">Editar</a> --}}
                                                        <a class="btn btn-success" href="#" data-toggle="modal"
                                                            data-target="#ModalEditarDestacamento{{ $destacamento->id }}">Editar</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-dependencia')
                                                            <button type="submit" onclick="return confirm('Está seguro')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Accordion card -->
        </div>


    </section>
    <script>
        $(document).ready(function() {
            $("#inputDivisiones").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableDivisiones tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            $("#inputDepartamentales").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableDepartamentales tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            $("#inputDirecciones").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableDirecciones tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            $("#inputComisarias").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableComisarias tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            $("#inputSecciones").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableSecciones tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            $("#inputDestacamentos").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTableDestacamentos tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
@endsection
