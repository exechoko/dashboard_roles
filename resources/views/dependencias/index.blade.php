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
                                        <option value="area">Áreas</option>
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
                                                        @if($dependencia->tipo === 'comisaria')
                                                            <button type="button"
                                                                class="btn btn-sm btn-warning btn-editar-jurisdiccion"
                                                                data-id="{{ $dependencia->id }}"
                                                                data-nombre="{{ $dependencia->nombre }}"
                                                                title="Editar jurisdicción en el mapa">
                                                                <i class="fas fa-draw-polygon"></i>
                                                            </button>
                                                        @endif
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
        @can('editar-dependencia')
            {{-- El tema "neón" aplica backdrop-filter/box-shadow/overflow a .modal-content y .card,
                 lo que rompe el render de un mapa Leaflet interactivo. Lo neutralizamos solo aquí. --}}
            <style>
                #modalJurisdiccion .modal-content {
                    backdrop-filter: none !important;
                    -webkit-backdrop-filter: none !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                }
                #jurisMap {
                    position: relative;
                    z-index: 0;
                }
                #jurisMap,
                #jurisMap * {
                    transition: none !important;
                    animation: none !important;
                }
                #jurisMap .leaflet-tile,
                #jurisMap img {
                    filter: none !important;
                    max-width: none !important;
                }
            </style>
            <!-- Modal: Editor de jurisdicción -->
            <div class="modal fade" id="modalJurisdiccion" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">
                                <i class="fas fa-draw-polygon"></i>
                                Jurisdicción: <span id="jurisNombre"></span>
                            </h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle"></i>
                                <strong>Mover puntos:</strong> arrastrá un vértice con el mouse.
                                <strong>Agregar puntos:</strong> arrastrá los puntos intermedios (más claros) del borde.
                                <strong>Eliminar un punto:</strong> hacé clic sobre el vértice.
                            </div>
                            <div class="btn-toolbar mb-2" role="toolbar">
                                <button type="button" class="btn btn-sm btn-primary mr-2" id="jurisDibujar">
                                    <i class="fas fa-pen"></i> Dibujar nuevo polígono
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary mr-2" id="jurisDeshacer">
                                    <i class="fas fa-undo"></i> Deshacer último punto
                                </button>
                                <button type="button" class="btn btn-sm btn-danger mr-2" id="jurisLimpiar">
                                    <i class="fas fa-trash"></i> Limpiar
                                </button>
                                <span class="ml-auto align-self-center text-muted">
                                    Vértices: <strong id="jurisCount">0</strong>
                                </span>
                            </div>
                            <div id="jurisMap" style="height: 480px; width: 100%; border-radius: 6px;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="jurisGuardar">
                                <i class="fas fa-save"></i> Guardar jurisdicción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
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

@section('scripts')
    <script>
        $(function () {
            const csrfToken = '{{ csrf_token() }}';
            const urlShow = '{{ url('dependencias') }}';
            const PARANA_CENTER = [-31.7413, -60.5115];

            let jurisMap = null;
            let jurisLayer = null;
            let currentId = null;

            // Mover el modal a <body> evita que estilos de contenedores del tema
            // (backdrop-filter, overflow, etc.) afecten el render del mapa.
            $('#modalJurisdiccion').appendTo('body');

            function updateCount() {
                let n = 0;
                if (jurisLayer) {
                    const ll = jurisLayer.getLatLngs();
                    n = (ll && ll[0]) ? ll[0].length : 0;
                }
                $('#jurisCount').text(n);
            }

            function destroyLayer() {
                if (jurisLayer) {
                    jurisLayer.remove();
                    jurisLayer = null;
                }
                updateCount();
            }

            function bindEditableEvents() {
                // Eliminar un vértice al hacer clic (el arrastre no dispara click)
                jurisMap.on('editable:vertex:click', function (e) {
                    const ring = jurisLayer ? (jurisLayer.getLatLngs()[0] || []) : [];
                    if (ring.length > 3) {
                        e.vertex.delete();
                    } else {
                        Swal.fire('Atención', 'El polígono debe conservar al menos 3 puntos.', 'warning');
                    }
                    setTimeout(updateCount, 50);
                });
                jurisMap.on('editable:vertex:dragend editable:vertex:new editable:drawing:end', updateCount);
            }

            function initMap() {
                if (jurisMap) {
                    return;
                }
                jurisMap = L.map('jurisMap', { editable: true }).setView(PARANA_CENTER, 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(jurisMap);
                bindEditableEvents();
            }

            function loadPolygon(puntos) {
                destroyLayer();
                jurisMap.invalidateSize();
                if (puntos && puntos.length) {
                    const latlngs = puntos.map(p => [p.lat, p.lng]);
                    jurisLayer = L.polygon(latlngs, { color: '#f57c00', weight: 3, fillOpacity: 0.15 }).addTo(jurisMap);
                    jurisLayer.enableEdit();
                    jurisMap.fitBounds(jurisLayer.getBounds(), { padding: [20, 20] });
                } else {
                    jurisMap.setView(PARANA_CENTER, 13);
                }
                updateCount();
            }

            // Abrir modal
            $(document).on('click', '.btn-editar-jurisdiccion', function () {
                currentId = $(this).data('id');
                $('#jurisNombre').text($(this).data('nombre'));
                $('#modalJurisdiccion').modal('show');
            });

            // Inicializar mapa al mostrarse (Leaflet necesita el contenedor visible)
            $('#modalJurisdiccion').on('shown.bs.modal', function () {
                initMap();
                // Recalcular tamaño tras la animación del modal para evitar render roto.
                setTimeout(function () { jurisMap.invalidateSize(); }, 200);
                $.getJSON(urlShow + '/' + currentId + '/jurisdiccion')
                    .done(function (res) {
                        loadPolygon(res.puntos || []);
                        if (!res.puntos || !res.puntos.length) {
                            Swal.fire('Sin jurisdicción', 'Esta comisaría no tiene polígono cargado. Usá "Dibujar nuevo polígono".', 'info');
                        }
                    })
                    .fail(function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.mensaje) || 'No se pudo cargar la jurisdicción.';
                        Swal.fire('Error', msg, 'error');
                    });
            });

            $('#modalJurisdiccion').on('hidden.bs.modal', function () {
                destroyLayer();
                currentId = null;
            });

            // Dibujar nuevo polígono desde cero
            $('#jurisDibujar').on('click', function () {
                destroyLayer();
                jurisLayer = jurisMap.editTools.startPolygon();
                jurisLayer.setStyle({ color: '#f57c00', weight: 3, fillOpacity: 0.15 });
            });

            // Deshacer último punto
            $('#jurisDeshacer').on('click', function () {
                if (!jurisLayer) { return; }
                const ring = jurisLayer.getLatLngs()[0];
                if (ring && ring.length > 0) {
                    ring.pop();
                    jurisLayer.setLatLngs([ring]);
                    jurisLayer.disableEdit();
                    jurisLayer.enableEdit();
                    updateCount();
                }
            });

            // Limpiar
            $('#jurisLimpiar').on('click', function () {
                destroyLayer();
            });

            // Guardar
            $('#jurisGuardar').on('click', function () {
                if (!jurisLayer) {
                    Swal.fire('Atención', 'No hay ningún polígono para guardar.', 'warning');
                    return;
                }
                const ring = jurisLayer.getLatLngs()[0] || [];
                if (ring.length < 3) {
                    Swal.fire('Atención', 'El polígono debe tener al menos 3 puntos.', 'warning');
                    return;
                }
                const puntos = ring.map(ll => ({ lat: ll.lat, lng: ll.lng }));
                const $btn = $(this).prop('disabled', true);

                $.ajax({
                    url: urlShow + '/' + currentId + '/jurisdiccion',
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: { puntos: puntos }
                }).done(function (res) {
                    $('#modalJurisdiccion').modal('hide');
                    Swal.fire('Guardado', res.mensaje || 'Jurisdicción actualizada.', 'success');
                }).fail(function (xhr) {
                    let msg = 'No se pudo guardar la jurisdicción.';
                    if (xhr.responseJSON) {
                        msg = xhr.responseJSON.mensaje
                            || (xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : msg);
                    }
                    Swal.fire('Error', msg, 'error');
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            });
        });
    </script>
@endsection
