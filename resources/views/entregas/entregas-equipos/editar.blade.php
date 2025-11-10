@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-edit"></i> Editar Entrega de Equipos - Acta N° {{ $entrega->id }}</h1>
        </div>

        <div class="section-body">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($equiposDisponibles->isEmpty())
                <div class="alert alert-warning" role="alert">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>No hay equipos disponibles</h4>
                        <p>Actualmente no hay equipos disponibles para agregar a esta entrega.</p>
                        <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Detalle
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('entrega-equipos.update', $entrega->id) }}" method="POST" id="editarEntregaForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Información de la Entrega --}}
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-file-alt"></i> Información de la Entrega</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fecha_entrega">Fecha de Entrega <span
                                                        class="text-danger">*</span></label>
                                                <input type="date"
                                                    class="form-control @error('fecha_entrega') is-invalid @enderror"
                                                    id="fecha_entrega" name="fecha_entrega"
                                                    value="{{ old('fecha_entrega', $entrega->fecha_entrega->format('Y-m-d')) }}" required>
                                                @error('fecha_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hora_entrega">Hora de Entrega <span
                                                        class="text-danger">*</span></label>
                                                <input type="time"
                                                    class="form-control @error('hora_entrega') is-invalid @enderror"
                                                    id="hora_entrega" name="hora_entrega"
                                                    value="{{ old('hora_entrega', $entrega->hora_entrega) }}" required>
                                                @error('hora_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Opciones de Entrega --}}
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h5><i class="fas fa-check-circle"></i> Opciones de Entrega</h5>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="con_segunda_bateria" name="con_segunda_bateria" value="1"
                                                            {{ old('con_segunda_bateria', $entrega->con_2_baterias ?? false) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="con_segunda_bateria">
                                                            <i class="fas fa-battery-full"></i> Con segunda batería
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="con_cuna_cargadora" name="con_cuna_cargadora" value="1"
                                                            {{ old('con_cuna_cargadora', $entrega->cunasCargadoras->count() ? true : false) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="con_cuna_cargadora">
                                                            <i class="fas fa-battery-half"></i> Con cuna cargadora
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="con_transformador" name="con_transformador" value="1"
                                                            {{ old('con_transformador', $entrega->transformadores->sum('cantidad') > 0 ? true : false) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="con_transformador">
                                                            <i class="fas fa-plug"></i> Con transformador
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="dependencia">Dependencia <span class="text-danger">*</span></label>
                                                <select class="form-control select2 @error('dependencia') is-invalid @enderror" id="dependencia" name="dependencia"
                                                    required>
                                                    <option value="">Seleccione una dependencia</option>
                                                    @foreach($destinos as $destino)
                                                        <option value="{{ $destino->nombre }}" {{ old('dependencia', $entrega->dependencia ?? '') == $destino->nombre ? 'selected' : '' }}>
                                                            {{ $destino->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('dependencia')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="personal_receptor">Personal Receptor <span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control @error('personal_receptor') is-invalid @enderror"
                                                    id="personal_receptor" name="personal_receptor"
                                                    value="{{ old('personal_receptor', $entrega->personal_receptor) }}"
                                                    maxlength="255" required placeholder="Nombre completo del receptor">
                                                @error('personal_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="legajo_receptor">Legajo Receptor</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_receptor') is-invalid @enderror"
                                                    id="legajo_receptor" name="legajo_receptor"
                                                    value="{{ old('legajo_receptor', $entrega->legajo_receptor) }}"
                                                    maxlength="50" placeholder="Número de legajo (opcional)">
                                                @error('legajo_receptor')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="personal_entrega">Personal que entregó <span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control @error('personal_entrega') is-invalid @enderror"
                                                    id="personal_entrega" name="personal_entrega"
                                                    value="{{ old('personal_entrega', $entrega->personal_entrega) }}"
                                                    maxlength="255" required placeholder="Nombre del personal que entregó">
                                                @error('personal_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="legajo_entrega">Legajo que entregó</label>
                                                <input type="text"
                                                    class="form-control @error('legajo_entrega') is-invalid @enderror"
                                                    id="legajo_entrega" name="legajo_entrega"
                                                    value="{{ old('legajo_entrega', $entrega->legajo_entrega) }}"
                                                    maxlength="50" placeholder="Número de legajo (opcional)">
                                                @error('legajo_entrega')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="motivo_operativo">Motivo Operativo <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control @error('motivo_operativo') is-invalid @enderror" id="motivo_operativo"
                                            name="motivo_operativo" rows="3" required placeholder="Describa el motivo de la entrega de equipos">{{ old('motivo_operativo', $entrega->motivo_operativo) }}</textarea>
                                        @error('motivo_operativo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones"
                                            rows="3" placeholder="Observaciones adicionales (opcional)">{{ old('observaciones', $entrega->observaciones) }}</textarea>
                                        @error('observaciones')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selección de Equipos --}}
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-radio"></i> Equipos Disponibles</h4>
                                    <div class="card-header-action">
                                        <span class="badge badge-success"
                                            id="totalDisponibles">{{ $equiposDisponibles->count() }} disponibles</span>
                                        <span class="badge badge-info" id="contadorSeleccionados">0 seleccionados</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label><i class="fas fa-search"></i> Buscar equipos:</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control" id="buscarEquipo"
                                                placeholder="TEI, ISSI, ID o cualquier texto...">
                                        </div>
                                    </div>

                                    <div class="contador-seleccionados">
                                        <span id="resumenSeleccion">Selecciona equipos para continuar</span>
                                    </div>

                                    <div style="max-height: 400px; overflow-y: auto;" id="listaEquipos">
                                        @foreach ($equiposDisponibles as $flota)
                                            @php
                                                $isEntregado = $entrega->equipos->contains('id', $flota->id);
                                                $equiposSeleccionados = old('equipos_seleccionados', $entrega->equipos->pluck('id')->toArray());
                                            @endphp
                                            <div class="equipo-item" data-id="{{ $flota->equipo->id }}"
                                                data-tei="{{ $flota->equipo->tei ?? '' }}"
                                                data-issi="{{ $flota->equipo->issi ?? '' }}"
                                                data-id_equipo="{{ $flota->equipo->id_equipo ?? '' }}"
                                                data-numero_bateria="{{ $flota->equipo->numero_bateria ?? '' }}"
                                                data-numero_segunda_bateria="{{ $flota->equipo->numero_segunda_bateria ?? '' }}">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="equipo_{{ $flota->id }}" name="equipos_seleccionados[]"
                                                        value="{{ $flota->id }}"
                                                        {{ in_array($flota->id, $equiposSeleccionados) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="equipo_{{ $flota->id }}">
                                                        <div class="equipo-info">
                                                            <div><strong>ID:</strong> {{ $flota->equipo->nombre_issi ?? 'N/A' }}
                                                            </div>
                                                            <div><strong>TEI:</strong> {{ $flota->equipo->tei ?? 'N/A' }}
                                                            </div>
                                                            <div><strong>ISSI:</strong> {{ $flota->equipo->issi ?? 'N/A' }}
                                                            </div>
                                                            @if ($flota->equipo->numero_bateria)
                                                                <div><strong>Batería:</strong>
                                                                    {{ $flota->equipo->numero_bateria }}</div>
                                                            @endif
                                                            @if ($flota->equipo->numero_segunda_bateria)
                                                                <div class="segunda-bateria-info" style="display: none;">
                                                                    <strong>Segunda Batería:</strong>
                                                                    {{ $flota->equipo->numero_segunda_bateria }}
                                                                </div>
                                                            @endif
                                                            <div class="mt-1">
                                                                @if($isEntregado)
                                                                    <span class="badge badge-warning badge-sm">En esta entrega</span>
                                                                @else
                                                                    <span class="badge badge-success badge-sm">Disponible</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div id="noEquiposFound" class="no-equipos-found" style="display: none;">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No se encontraron equipos</h5>
                                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                                    </div>

                                    @error('equipos_seleccionados')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                    @error('equipos_seleccionados.*')
                                        <div class="text-danger mt-2">
                                            <small><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Accesorios --}}
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-plug"></i> Accesorios</h4>
                                </div>
                                <div class="card-body">
                                    {{-- Cunas Cargadoras --}}
                                    <div class="row" id="cunasSection" style="display: none;">
                                        <div class="col-md-12">
                                            <h5><i class="fas fa-battery-half"></i> Cunas Cargadoras</h5>
                                            <div class="form-group">
                                                <button type="button" id="addCunaCargadora" class="btn btn-sm btn-info">
                                                    <i class="fas fa-plus"></i> Agregar Cuna Cargadora
                                                </button>
                                            </div>
                                            <div id="cunasContainer">
                                                {{-- Aquí puedes cargar las cunas existentes si las hay --}}
                                                @if(isset($entrega->cunasCargadoras))
                                                    @foreach($entrega->cunasCargadoras as $i => $cuna)
                                                        <div class="cuna-item mb-3 p-3 border rounded" id="cuna-{{ $i+1 }}">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <h6 class="mb-0"><i class="fas fa-battery-half"></i> Cuna Cargadora #{{ $i+1 }}</h6>
                                                                <button type="button" class="btn btn-sm btn-danger remove-cuna" data-cuna="{{ $i+1 }}">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label for="cunas[{{ $i }}][marca]">Marca <span class="text-danger">*</span></label>
                                                                        <select class="form-control" name="cunas[{{ $i }}][marca]" required>
                                                                            <option value="">Seleccionar marca</option>
                                                                            <option value="Sepura" {{ $cuna->marca == 'Sepura' ? 'selected' : '' }}>Sepura</option>
                                                                            <option value="Teltronic" {{ $cuna->marca == 'Teltronic' ? 'selected' : '' }}>Teltronic</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label for="cunas[{{ $i }}][numero_serie]">Número de Serie <span class="text-danger">*</span></label>
                                                                        <input type="text" class="form-control" name="cunas[{{ $i }}][numero_serie]"
                                                                            value="{{ $cuna->numero_serie }}" maxlength="255" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="form-group">
                                                                        <label for="cunas[{{ $i }}][cantidad]">Cantidad</label>
                                                                        <input type="number" class="form-control" name="cunas[{{ $i }}][cantidad]"
                                                                            value="{{ $cuna->cantidad }}" min="1" max="1">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label for="cunas[{{ $i }}][observaciones]">Observaciones</label>
                                                                        <input type="text" class="form-control" name="cunas[{{ $i }}][observaciones]"
                                                                            value="{{ $cuna->observaciones }}" maxlength="255">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Transformadores --}}
                                    <div class="row" id="transformadoresSection" style="display: none;">
                                        <div class="col-md-12">
                                            <h5><i class="fas fa-plug"></i> Transformadores 12V</h5>
                                            <div class="form-group">
                                                <label for="cantidad_transformadores">Cantidad de Transformadores</label>
                                                <input type="number" class="form-control" id="cantidad_transformadores" name="cantidad_transformadores"
                                                    min="0" value="{{ old('cantidad_transformadores', isset($entrega->transformadores) ? $entrega->transformadores->sum('cantidad') : 0) }}" placeholder="0">
                                                <small class="text-muted">Los transformadores no requieren número de serie</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Mensaje cuando no hay accesorios seleccionados --}}
                                    <div id="noAccesoriosMessage">
                                        <p class="text-muted text-center">
                                            <i class="fas fa-info-circle"></i> Selecciona las opciones de entrega arriba para configurar accesorios
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Imágenes y archivos adjuntos --}}
                    <div class="container col-xs-12 col-sm-12 col-md-12">
                        <div class="row">
                            <!-- Archivo adjunto -->
                            <div class="col-xs-12 col-sm-12 col-md-6">
                                <div class="form-group">
                                    <label for="archivo">Archivo adjunto</label>
                                    <input type="file" name="archivo" class="form-control"
                                        accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                </div>
                            </div>
                        </div>

                        <!-- Sección de imágenes -->
                        <div class="row">
                            <div class="col-xs-12 col-sm-12 col-md-12">
                                <h6><i class="fas fa-camera"></i> Imágenes (máximo 3)</h6>
                                <div id="imageContainer">
                                    <!-- Los campos de imagen se generarán aquí -->
                                </div>
                                <button type="button" id="addImageBtn" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Agregar imagen
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Equipos Seleccionados --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4><i class="fas fa-list-check"></i> Equipos Seleccionados</h4>
                        </div>
                        <div class="card-body" id="equiposSeleccionadosContainer">
                            <p class="text-muted">Aún no hay equipos seleccionados.</p>
                            <ul class="list-group" id="equiposSeleccionadosList"></ul>
                        </div>
                    </div>

                    {{-- Botones de acción --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancelar
                                            </a>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted">Los cambios en equipos modificarán automáticamente sus estados</small>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-warning" id="btnActualizar" disabled>
                                                <i class="fas fa-save"></i> Actualizar Entrega
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Accesorios
            // Mostrar/ocultar accesorios según opciones de entrega
            function toggleAccesorios() {
                const conCuna = $('#con_cuna_cargadora').is(':checked');
                const conTransformador = $('#con_transformador').is(':checked');
                const conSegundaBateria = $('#con_segunda_bateria').is(':checked');

                if (conCuna) {
                    $('#cunasSection').show();
                } else {
                    $('#cunasSection').hide();
                }

                if (conTransformador) {
                    $('#transformadoresSection').show();
                } else {
                    $('#transformadoresSection').hide();
                }

                if (!conCuna && !conTransformador) {
                    $('#noAccesoriosMessage').show();
                } else {
                    $('#noAccesoriosMessage').hide();
                }
                // Mostrar/ocultar información de segunda batería en equipos
                if (conSegundaBateria) {
                    $('.segunda-bateria-info').show();
                } else {
                    $('.segunda-bateria-info').hide();
                }
            }

            $('#con_cuna_cargadora, #con_transformador').on('change', toggleAccesorios);
            toggleAccesorios();

            // Agregar cuna cargadora dinámicamente
            let cunaCount = $('#cunasContainer .cuna-item').length;
            $('#addCunaCargadora').on('click', function() {
                cunaCount++;
                const cunaHtml = `
                    <div class="cuna-item mb-3 p-3 border rounded" id="cuna-${cunaCount}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-battery-half"></i> Cuna Cargadora #${cunaCount}</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-cuna" data-cuna="${cunaCount}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="cunas[${cunaCount}][marca]">Marca <span class="text-danger">*</span></label>
                                    <select class="form-control" name="cunas[${cunaCount}][marca]" required>
                                        <option value="">Seleccionar marca</option>
                                        <option value="Sepura">Sepura</option>
                                        <option value="Teltronic">Teltronic</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="cunas[${cunaCount}][numero_serie]">Número de Serie <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="cunas[${cunaCount}][numero_serie]" maxlength="255" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="cunas[${cunaCount}][cantidad]">Cantidad</label>
                                    <input type="number" class="form-control" name="cunas[${cunaCount}][cantidad]" value="1" min="1" max="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cunas[${cunaCount}][observaciones]">Observaciones</label>
                                    <input type="text" class="form-control" name="cunas[${cunaCount}][observaciones]" maxlength="255">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#cunasContainer').append(cunaHtml);
            });

            // Eliminar cuna cargadora
            $(document).on('click', '.remove-cuna', function() {
                const cunaId = $(this).data('cuna');
                $('#cuna-' + cunaId).remove();
            });

            // Mostrar/ocultar accesorios al cargar la página
            toggleAccesorios();
            // Fin Accesorios
            //Carga de imagenes y archivo adjuntoslet
            let imageCount = 0;
            const maxImages = 3;

            // Función para agregar campo de imagen
            function addImageField() {
                if (imageCount >= maxImages) {
                    alert('Máximo 3 imágenes permitidas');
                    return;
                }

                imageCount++;
                const imageHtml = `
                    <div class="image-upload-item" id="imageItem${imageCount}">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label for="imagen${imageCount}">Imagen ${imageCount}</label>
                                <input type="file" name="imagen${imageCount}" class="form-control image-input"
                                    accept="image/*;capture=camera"
                                    onchange="previewImage(this, ${imageCount})">
                                <small class="text-muted">JPG, PNG, GIF (máx. 2MB)</small>
                            </div>
                            <div class="col-md-4">
                                <div id="preview${imageCount}"></div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="remove-image-btn" onclick="removeImageField(${imageCount})">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                $('#imageContainer').append(imageHtml);
                updateAddButton();
            }

            // Función para eliminar campo de imagen
            window.removeImageField = function(id) {
                $(`#imageItem${id}`).remove();
                imageCount--;
                updateAddButton();
            }

            // Función para previsualizar imagen
            window.previewImage = function(input, id) {
                const preview = $(`#preview${id}`);
                preview.empty();

                if (input.files && input.files[0]) {
                    const file = input.files[0];

                    // Validar tamaño (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen es muy grande. Máximo 2MB permitido.');
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html(`
                            <img src="${e.target.result}" class="image-preview" alt="Preview">
                            <br><small class="text-success">✓ Imagen cargada</small>
                        `);
                    }
                    reader.readAsDataURL(file);
                }
            }

            // Actualizar estado del botón agregar
            function updateAddButton() {
                const btn = $('#addImageBtn');
                if (imageCount >= maxImages) {
                    btn.prop('disabled', true).html('<i class="fas fa-check"></i> Máximo alcanzado');
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Agregar imagen');
                }
            }

            // Event listener para agregar imagen
            $('#addImageBtn').on('click', addImageField);

            // Agregar una imagen por defecto si no hay ninguna
            if ($('.image-upload-item').length === 0) {
                addImageField();
            }

            $(document).on('click', '.quitar-equipo', function() {
                const id = $(this).data('id');
                const checkbox = $(`#equipo_${id}`);
                checkbox.prop('checked', false).trigger('change');
            });

            function renderEquiposSeleccionados() {
                console.log('Renderizando equipos seleccionados...');
                const lista = $('#equiposSeleccionadosList');
                lista.empty();

                const seleccionados = $('input[name="equipos_seleccionados[]"]:checked');

                if (seleccionados.length === 0) {
                    $('#equiposSeleccionadosContainer p').show();
                    return;
                }

                $('#equiposSeleccionadosContainer p').hide();

                seleccionados.each(function() {
                    const $checkbox = $(this);
                    const equipoItem = $checkbox.closest('.equipo-item');

                    const tei = equipoItem.data('tei') || 'TEI N/A';
                    const issi = equipoItem.data('issi') || 'ISSI N/A';
                    const id = equipoItem.data('id') || 'ID N/A';
                    const numeroBateria = equipoItem.data('numero_bateria') || '';
                    const numeroSegundaBateria = equipoItem.data('numero_segunda_bateria') || '';
                    const conSegundaBateria = $('#con_segunda_bateria').is(':checked');

                    let bateriaInfo = '';
                    if (numeroBateria) {
                        bateriaInfo += `<div><small><strong>Batería:</strong> ${numeroBateria}</small></div>`;
                    }
                    if (conSegundaBateria && numeroSegundaBateria) {
                        bateriaInfo += `<div><small><strong>Segunda Batería:</strong> ${numeroSegundaBateria}</small></div>`;
                    }

                    const li = $(`
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span><strong>${tei}</strong> - ${issi}</span>
                                ${bateriaInfo}
                            </div>
                            <button class="btn btn-sm btn-danger quitar-equipo" data-id="${$checkbox.val()}">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                    `);
                    lista.append(li);
                });
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Variables para tracking
            let equiposDisponibles = [];
            let equiposFiltrados = [];

            // Inicializar datos de equipos desde el DOM
            function inicializarEquipos() {
                equiposDisponibles = [];
                $('.equipo-item').each(function() {
                    const $item = $(this);
                    const equipoData = {
                        id: $item.data('id'),
                        id_equipo: $item.data('id_equipo'),
                        tei: $item.data('tei'),
                        issi: $item.data('issi'),
                        numero_bateria: $item.data('numero_bateria'),
                        element: $item
                    };
                    equiposDisponibles.push(equipoData);
                    // Mostrar todos los equipos inicialmente
                    $item.show();
                });
                equiposFiltrados = [...equiposDisponibles];
            }

            // Inicializar equipos
            inicializarEquipos();

            // Función para actualizar contador
            function actualizarContador() {
                const seleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;
                const total = equiposDisponibles.length;

                $('#contadorSeleccionados').text(`${seleccionados} seleccionados`);
                $('#totalDisponibles').text(`${total} disponibles`);

                // Actualizar resumen
                if (seleccionados === 0) {
                    $('#resumenSeleccion').text('Selecciona equipos para continuar');
                } else {
                    $('#resumenSeleccion').html(`
                        <i class="fas fa-check-circle text-success"></i>
                        ${seleccionados} equipo${seleccionados !== 1 ? 's' : ''} seleccionado${seleccionados !== 1 ? 's' : ''}
                    `);
                }

                // Validar formulario
                validarFormulario();
            }

            // Función para filtrar equipos
            function filtrarEquipos(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let equiposEncontrados = 0;

                // Limpiar la lista de equipos filtrados
                equiposFiltrados = [];

                $('.equipo-item').each(function() {
                    const $item = $(this);
                    const id = $item.data('id').toString().toLowerCase();
                    const idEquipo = ($item.data('nombre_issi') || '').toString().toLowerCase();
                    const tei = ($item.data('tei') || '').toString().toLowerCase();
                    const issi = ($item.data('issi') || '').toString().toLowerCase();
                    const bateria = ($item.data('numero_bateria') || '').toString().toLowerCase();

                    const coincide = term === '' ||
                        id.includes(term) ||
                        idEquipo.includes(term) ||
                        tei.includes(term) ||
                        issi.includes(term) ||
                        bateria.includes(term);

                    if (coincide) {
                        $item.show();
                        equiposEncontrados++;
                        // Agregar a la lista de equipos filtrados
                        equiposFiltrados.push({
                            id: $item.data('id'),
                            element: $item
                        });
                    } else {
                        $item.hide();
                    }
                });

                // Mostrar/ocultar mensaje de "no encontrados"
                if (equiposEncontrados === 0 && term !== '') {
                    $('#noEquiposFound').show();
                } else {
                    $('#noEquiposFound').hide();
                }
            }

            // Función para validar formulario
            function validarFormulario() {
                const dependencia = $('#dependencia').val().trim();
                const personalReceptor = $('#personal_receptor').val().trim();
                const motivoOperativo = $('#motivo_operativo').val().trim();
                const equiposSeleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;

                const isValid = dependencia && personalReceptor && motivoOperativo && equiposSeleccionados > 0;
                $('#btnActualizar').prop('disabled', !isValid);
            }

            // Event listeners

            // Actualizar contador cuando cambie la selección
            $(document).on('change', 'input[name="equipos_seleccionados[]"]', function() {
                const $equipoItem = $(this).closest('.equipo-item');
                console.log('Checkbox cambiado:', $equipoItem.data('id'));
                if ($(this).is(':checked')) {
                    $equipoItem.addClass('selected');
                } else {
                    $equipoItem.removeClass('selected');
                }
                actualizarContador();
                renderEquiposSeleccionados();
            });

            // Click en equipo-item para seleccionar
            $(document).on('click', '.equipo-item', function(e) {
                if (e.target.type !== 'checkbox' && !$(e.target).is('label')) {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Funcionalidad de búsqueda
            $('#buscarEquipo').on('input', function() {
                const searchTerm = $(this).val();
                filtrarEquipos(searchTerm);
            });

            // Seleccionar todos los equipos
            $('#seleccionarTodos').on('click', function() {
                $('.equipo-item input[type="checkbox"]').prop('checked', true).trigger('change');
            });

            // Deseleccionar todos los equipos
            $('#deseleccionarTodos').on('click', function() {
                $('.equipo-item input[type="checkbox"]').prop('checked', false).trigger('change');
            });

            // Seleccionar equipos visibles
            $('#seleccionarVisibles').on('click', function() {
                equiposFiltrados.forEach(equipo => {
                    equipo.element.find('input[type="checkbox"]').prop('checked', true).trigger('change');
                });
            });

            // Validación en tiempo real
            $('#dependencia, #personal_receptor, #motivo_operativo').on('input', function() {
                validarFormulario();
            });

            // Validación del formulario antes de enviar
            $('#editarEntregaForm').on('submit', function(e) {
                const equiposSeleccionados = $('input[name="equipos_seleccionados[]"]:checked').length;

                if (equiposSeleccionados === 0) {
                    e.preventDefault();
                    alert('Debe seleccionar al menos un equipo para la entrega.');
                    return false;
                }

                // Confirmar actualización
                if (!confirm(
                        `¿Está seguro de actualizar esta entrega con ${equiposSeleccionados} equipo(s)? Se modificarán los estados de los equipos según corresponde.`
                    )) {
                    e.preventDefault();
                    return false;
                }

                // Mostrar loading en el botón
                $('#btnActualizar').html('<i class="fas fa-spinner fa-spin"></i> Actualizando...').prop('disabled', true);
            });

            // Inicializar contador y validación
            actualizarContador();

            // Renderizar equipos seleccionados inicialmente
            renderEquiposSeleccionados();

            // Marcar equipos como seleccionados si están checked
            $('input[name="equipos_seleccionados[]"]:checked').each(function() {
                $(this).closest('.equipo-item').addClass('selected');
            });

            // Scroll suave a la sección de equipos si hay errores
            @if ($errors->has('equipos_seleccionados') || $errors->has('equipos_seleccionados.*'))
                $('html, body').animate({
                    scrollTop: $('#listaEquipos').offset().top - 100
                }, 500);
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .equipo-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .equipo-item:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .equipo-item.selected {
            background-color: #e3f2fd;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }

        .equipo-info {
            font-size: 13px;
            line-height: 1.4;
        }

        .equipo-info strong {
            color: #495057;
            font-weight: 600;
        }

        .badge-sm {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .custom-control-label {
            cursor: pointer;
            width: 100%;
            padding-left: 5px;
        }

        .custom-control-input:checked~.custom-control-label::before {
            background-color: #007bff;
            border-color: #007bff;
        }

        #listaEquipos {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
        }

        .card-header h4 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border: none;
            color: #212529;
        }

        .btn-warning:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 12px 15px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .contador-seleccionados {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
            color: #495057;
        }

        .no-equipos-found {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }

        .btn-group-sm .btn {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Scrollbar personalizada */
        #listaEquipos::-webkit-scrollbar {
            width: 6px;
        }

        #listaEquipos::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #listaEquipos::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        #listaEquipos::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .section-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .section-header h1 {
            color: #495057;
            margin-bottom: 10px;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: #495057;
        }

        /* Indicador visual para equipos que ya están en la entrega */
        .equipo-item .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        /* Animación suave para cambios de estado */
        .equipo-item {
            transition: all 0.3s ease;
        }

        .equipo-item.selected {
            transform: scale(1.02);
        }

        /* Estilos para lista de equipos seleccionados */
        .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .list-group-item .btn-danger {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-upload-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .remove-image-btn {
            background: #dc3545;
            border: none;
            color: white;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-image-btn:hover {
            background: #c82333;
        }

        .segunda-bateria-info {
            color: #28a745;
            font-weight: 500;
        }
    </style>
@endpush
