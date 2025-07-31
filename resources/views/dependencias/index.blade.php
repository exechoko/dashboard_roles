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
                            <a class="btn btn-success" href="{{ route('dependencias.crear-general') }}">
                                <i class="fas fa-plus"></i> Nueva Dependencia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Filtros y búsqueda global -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="filtroTipo">Filtrar por tipo:</label>
                                    <select id="filtroTipo" class="form-control">
                                        <option value="">Todos los tipos</option>
                                        <option value="direccion">Direcciones</option>
                                        <option value="departamental">Departamentales</option>
                                        <option value="division">Divisiones</option>
                                        <option value="comisaria">Comisarías</option>
                                        <option value="seccion">Secciones</option>
                                        <option value="destacamento">Destacamentos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="busquedaGlobal">Búsqueda global:</label>
                                    <input type="text" id="busquedaGlobal" class="form-control"
                                        placeholder="Buscar por nombre, teléfono o ubicación...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla unificada de dependencias -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4>Todas las Dependencias</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mt-2" id="tablaDependencias">
                                <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                    <th style="color:#fff;">Tipo</th>
                                    <th style="color:#fff;">Nombre</th>
                                    <th style="color:#fff;">Teléfono</th>
                                    <th style="color:#fff;">Ubicación</th>
                                    <th style="color:#fff;">Depende de</th>
                                    @can('editar-dependencia')
                                        <th style="color:#fff;">Acciones</th>
                                    @endcan
                                </thead>
                                <tbody>
                                    @foreach ($todasDependencias as $dependencia)
                                        <tr data-tipo="{{ $dependencia->tipo }}">
                                            <td>
                                                <span class="badge badge-{{ $dependencia->getBadgeClass() }}">
                                                    {{ ucfirst($dependencia->tipo) }}
                                                </span>
                                            </td>
                                            <td style="font-weight:bold">
                                                {{ $dependencia->nombre }}
                                                @if($dependencia->hijos->count() > 0)
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-sitemap"></i>
                                                        {{ $dependencia->hijos->count() }} subordinada(s)
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $dependencia->telefono }}
                                                @if($dependencia->getWhatsappUrl())
                                                    <a href="{{ $dependencia->getWhatsappUrl() }}" target="_blank"
                                                        title="Enviar mensaje por WhatsApp">
                                                        <i class="fab fa-whatsapp text-success ml-2"></i>
                                                    </a>
                                                @endif
                                            </td>
                                            <td>{{ $dependencia->ubicacion }}</td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $dependencia->dependeDe() }}
                                                </small>
                                            </td>
                                            @can('editar-dependencia')
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a class="btn btn-sm btn-info"
                                                            href="{{ route('dependencias.show', $dependencia->id) }}"
                                                            title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-success"
                                                            href="{{ route('dependencias.edit', $dependencia->id) }}"
                                                            title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($dependencia->puedeSerEliminada())
                                                            @can('borrar-dependencia')
                                                                <form action="{{ route('dependencias.destroy', $dependencia->id) }}"
                                                                    method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        onclick="return confirm('¿Está seguro de eliminar esta dependencia?')"
                                                                        class="btn btn-sm btn-danger" title="Eliminar">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación si usas paginación -->
                        @if(method_exists($todasDependencias, 'links'))
                            <div class="d-flex justify-content-center">
                                {{ $todasDependencias->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen estadístico -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Resumen por Tipo</h5>
                        <div class="row">
                            @foreach(['direccion', 'departamental', 'division', 'comisaria', 'seccion', 'destacamento'] as $tipo)
                                @if(isset($estadisticas[$tipo]))
                                    <div class="col-md-2">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">{{ ucfirst($tipo) }}s</h6>
                                                <h4 class="text-primary">{{ $estadisticas[$tipo] }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script>
        $(document).ready(function () {
            // Filtro por tipo
            $("#filtroTipo").on("change", function () {
                var tipoSeleccionado = $(this).val().toLowerCase();
                $("#tablaDependencias tbody tr").each(function () {
                    var tipoFila = $(this).data('tipo');
                    if (tipoSeleccionado === '' || tipoFila === tipoSeleccionado) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                aplicarBusquedaGlobal(); // Reaplicar búsqueda después del filtro
            });

            // Búsqueda global
            $("#busquedaGlobal").on("keyup", function () {
                aplicarBusquedaGlobal();
            });

            function aplicarBusquedaGlobal() {
                var value = $("#busquedaGlobal").val().toLowerCase();
                var tipoSeleccionado = $("#filtroTipo").val().toLowerCase();

                $("#tablaDependencias tbody tr").each(function () {
                    var tipoFila = $(this).data('tipo');
                    var textoFila = $(this).text().toLowerCase();

                    var cumpleFiltroTipo = (tipoSeleccionado === '' || tipoFila === tipoSeleccionado);
                    var cumpleBusqueda = (value === '' || textoFila.indexOf(value) > -1);

                    if (cumpleFiltroTipo && cumpleBusqueda) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            // Tooltip para botones
            $('[title]').tooltip();
        });
    </script>

    <style>
        .badge-direccion {
            background-color: #6c757d;
        }

        .badge-departamental {
            background-color: #007bff;
        }

        .badge-division {
            background-color: #28a745;
        }

        .badge-comisaria {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-seccion {
            background-color: #17a2b8;
        }

        .badge-destacamento {
            background-color: #dc3545;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .table th {
            border-top: none;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
    </style>
@endsection
