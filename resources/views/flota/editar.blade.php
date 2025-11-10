@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Agregar movimiento</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @if (Session::has('warning'))
                                <div class="alert alert-warning">
                                    {{ Session::get('warning') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-dark alert-dismissible fade show" role="alert">
                                    <strong>¡Revise los campos!</strong>
                                    @foreach ($errors->all() as $error)
                                        <span class="badge badge-danger">{{ $error }}</span>
                                    @endforeach
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('flota.update', $flota->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="card" style="background-color: #e0f5c4;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-12 col-md-6">
                                                    <div class="form-group">
                                                        <label for="">Equipo</label>
                                                        <select name="equipo" id="" class="form-control select2"
                                                            style="margin-bottom: 15px">
                                                            <option value="{{ $flota->equipo_id }}">
                                                                {{ $flota->equipo->tei . ' ' . $flota->equipo->tipo_terminal->tipo_uso->uso . ' ' . $flota->equipo->issi . ' ' . $flota->equipo->tipo_terminal->marca . ' ' . $flota->equipo->tipo_terminal->modelo }}
                                                            </option>
                                                            @foreach ($equipos as $equipo)
                                                                <option value="{{ $equipo->id }}">
                                                                    {{ $equipo->tei . ' ' . $equipo->tipo_terminal->tipo_uso->uso . ' ' . $equipo->issi . ' - ' . $equipo->tipo_terminal->marca . ' ' . $equipo->tipo_terminal->modelo }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-12 col-md-6">
                                                    <div class="form-group">
                                                        <label for="">Recurso actual</label>
                                                        <label for=""
                                                            class="form-control">{{ isset($flota->recurso->nombre) ? $flota->recurso->nombre : 'Sin recurso asignado' }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-12 col-md-6">
                                                    <div class="form-group">
                                                        <label for="">Pertenece a</label>
                                                        <label for=""
                                                            class="form-control">{{ $flota->destino->nombre . ' - ' . $flota->destino->dependeDe() }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-12 col-md-6">
                                                    <div class="form-group">
                                                        <label for="">Actualmente está en</label>
                                                        @if (is_null($hist->destino))
                                                            <label for=""
                                                                class="form-control">{{ $flota->destino->nombre . ' - ' . $flota->destino->dependeDe() }}</label>
                                                        @else
                                                            <label for=""
                                                                class="form-control">{{ $hist->destino->nombre . ' - ' . $hist->destino->dependeDe() }}</label>
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6" id="label_fecha_asignacion">
                                        <div class="form-group">
                                            <label for="fecha_asignacion">Fecha y Hora</label>
                                            {!! Form::datetimeLocal('fecha_asignacion', '') !!}
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group d-flex align-items-center">
                                            <label class="mr-2">Solo modificar historico</label>
                                            <label class="custom-switch mt-2 mb-0">
                                                <input type="checkbox" name="solo_modificar_historico"
                                                    class="custom-switch-input">
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="">Tipo de movimiento</label>
                                            <select name="tipo_movimiento" id="tipoMovimiento" class="form-control select2"
                                                style="margin-bottom: 15px">
                                                <option value="">Seleccionar tipo de movimiento</option>
                                                @foreach ($tipos_movimiento as $tipo_movimiento)
                                                    <option value="{{ $tipo_movimiento }}">
                                                        {{ $tipo_movimiento->nombre . ' (' . $tipo_movimiento->detalles . ')' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-4">
                                        <div class="form-group">
                                            <label for="ticket_per">Ticket PER</label>
                                            <input type="text" name="ticket_per" class="form-control" value="">
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12" id="equipoReemplazo">
                                        <div class="form-group">
                                            <label for="equipoReemplazo">Equipo por el que se reemplaza</label>
                                            <select name="equipoReemplazo" id="equipoReemplazo" class="form-control select2"
                                                style="width: 100%; margin-bottom: 15px">
                                                <option value="">Seleccionar un equipo que estan en stock</option>
                                                @foreach ($flotas_stock as $flota)
                                                    <option value="{{ $flota->equipo->id }}">
                                                        {{ $flota->equipo->tei . ' ' . $flota->equipo->tipo_terminal->tipo_uso->uso . ' ' . $flota->equipo->issi . ' - ' . $flota->equipo->tipo_terminal->marca . ' ' . $flota->equipo->tipo_terminal->modelo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6" id="dependenciaDestino">
                                        <div class="form-group">
                                            <label for="">Dependencia o lugar al que se asigna</label>
                                            <select name="dependencia" id="dependencia" class="form-control select2"
                                                style="margin-bottom: 15px; width: 100%;">
                                                <option value="">Seleccionar destino/dependencia</option>
                                                @foreach ($dependencias as $dependencia)
                                                    <option value="{{ $dependencia->id }}">
                                                        {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6" id="recursoDestino">
                                        <div class="form-group">
                                            <label for="recurso">Recurso al que se asigna</label>
                                            <select class="form-control select2" name="recurso" id="recurso"
                                                style="margin-bottom: 15px; width: 100%;"></select>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6" id="nuevoIssi">
                                        <div class="form-group">
                                            <label for="">Nuevo ISSI</label>
                                            <input type="text" name="nuevoIssi" class="form-control" value="">
                                        </div>
                                    </div>
                                    <div class="container col-xs-12 col-sm-12 col-md-12">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-12 col-md-6">
                                                <div class="form-group">
                                                    <label for="archivo">Archivo adjunto</label>
                                                    <input type="file" name="archivo" class="form-control"
                                                        accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                                </div>
                                            </div>

                                            <div class="col-xs-12 col-sm-12 col-md-12">
                                                <button type="button" id="addImage" class="btn btn-success">
                                                    <i class="fas fa-plus"></i> Agregar imagen
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row" id="imageContainer"></div>
                                        <!-- Aquí se añadirán los nuevos campos -->
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
                                        <div class="form-floating">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones"
                                                style="height: 100px"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });
            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });
            let imageCount = 0;

            document.getElementById('addImage').addEventListener('click', function () {
                imageCount++;
                const newImageDiv = document.createElement('div');
                newImageDiv.classList.add('col-xs-12', 'col-sm-12', 'col-md-4');

                newImageDiv.innerHTML = `
                    <div class="form-group">
                        <label for="imagen${imageCount}">Imagen ${imageCount}</label>
                        <input type="file"
                            name="imagen${imageCount}"
                            class="form-control"
                            accept="image/*"
                            capture="environment">
                        <button type="button" class="btn btn-sm btn-danger mt-2 remove-image">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                `;

                document.getElementById('imageContainer').appendChild(newImageDiv);

                // Agregar evento para eliminar la imagen
                newImageDiv.querySelector('.remove-image').addEventListener('click', function () {
                    newImageDiv.remove();
                });
            });
            $('#dependencia').on('change', function () {
                var dependenciaId = this.value;
                $('#recurso').html('');
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: '{{ route('getRecursosJSON') }}?destino_id=' + dependenciaId,
                    type: 'get',
                    success: function (res) {
                        $('#recurso').html('<option value="">Seleccionar Recurso</option>');
                        $.each(res, function (key, value) {
                            // Verificar si el recurso tiene un vehículo asociado
                            if (value.vehiculo) {
                                var label = value.nombre + ' - ' + value.vehiculo
                                    .marca + ' ' + value.vehiculo.modelo + ': ' + value
                                        .vehiculo.dominio;
                                $('#recurso').append('<option value="' + value.id +
                                    '">' + label + '</option>');
                            } else {
                                $('#recurso').append('<option value="' + value.id +
                                    '">' + value.nombre + '</option>');
                            }
                        });
                    }
                });
            });


            var dependenciaSelect = $("#dependenciaDestino");
            var recursoSelect = $("#recursoDestino");
            var equipoReemplazoSelect = $("#equipoReemplazo");
            var tipoMovimientoSelect = $("#tipoMovimiento");

            console.log("tipo_movimiento", dependenciaSelect);

            function toggleDependenciaRecursoFields(selectedTipoMovimiento) {
                // Convertir el JSON en un objeto JavaScript
                var tipoMovimiento = JSON.parse(selectedTipoMovimiento);

                if (tipoMovimiento.nombre === "Reemplazo" || tipoMovimiento.nombre === "Recambio") {
                    console.log("entro al if reemplazo", "SI");
                    equipoReemplazoSelect.show();
                    dependenciaSelect.hide();
                    recursoSelect.hide();
                } else {
                    equipoReemplazoSelect.hide();
                    if (tipoMovimiento.habilita_campos === 1) { // Cambia este valor según tus necesidades
                        console.log("entro al if", "SI");
                        dependenciaSelect.show();
                        recursoSelect.show();
                    } else {
                        console.log("entro al else", "SI");
                        dependenciaSelect.hide();
                        recursoSelect.hide();
                    }
                }
            }

            tipoMovimientoSelect.on("change", function () {
                var selectedTipoMovimiento = $(this).val();
                console.log("Tipo de movimiento seleccionado:", selectedTipoMovimiento);
                toggleDependenciaRecursoFields(selectedTipoMovimiento);
            });

            // Llamada inicial para establecer la visibilidad basada en la selección inicial
            toggleDependenciaRecursoFields(JSON.stringify(tipoMovimientoSelect.val())); // Convertir el valor a JSON
        });
    </script>
@endsection
