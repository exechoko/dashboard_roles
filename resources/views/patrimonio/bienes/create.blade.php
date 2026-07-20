@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nuevo Bien Patrimonial</h1>
        </div>

        <div class="section-body">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="mb-2"><i class="fas fa-exclamation-circle"></i> Revise los siguientes datos:</h6>
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-body py-3">
                    <label class="d-block mb-2"><strong><i class="fas fa-layer-group"></i> Modo de carga</strong></label>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-primary active">
                            <input type="radio" name="modo_carga" value="individual" checked> Individual
                        </label>
                        <label class="btn btn-outline-primary">
                            <input type="radio" name="modo_carga" value="masivo"> Carga masiva
                        </label>
                    </div>
                    <small class="d-block text-muted mt-2">
                        La carga masiva permite registrar varias unidades del mismo bien (misma descripción, tipo, destino)
                        cargando solo el SIAF y N° de serie de cada una. Disponible para tipos sin tabla vinculada.
                    </small>
                </div>
            </div>

            <div id="modo-individual">
            <form action="{{ route('patrimonio.bienes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-box"></i> Información del Bien</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tipo_bien_id">Tipo de Bien <span class="text-danger">*</span></label>
                                            <select class="form-control select2 @error('tipo_bien_id') is-invalid @enderror"
                                                id="tipo_bien_id" name="tipo_bien_id" required>
                                                <option value="">Seleccione un tipo</option>
                                                @foreach($tiposBien as $tipo)
                                                    <option value="{{ $tipo->id }}"
                                                        data-tiene-tabla="{{ $tipo->tiene_tabla_propia ? 'true' : 'false' }}"
                                                        data-tabla="{{ $tipo->tabla_referencia }}"
                                                        {{ old('tipo_bien_id') == $tipo->id ? 'selected' : '' }}>
                                                        {{ $tipo->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('tipo_bien_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group" id="item-origen-group" style="display: none;">
                                            <label for="item_origen_id">
                                                Seleccionar Item a Patrimoniar
                                                <span class="text-danger item-required" style="display: none;">*</span>
                                            </label>
                                            <select class="form-control select2-ajax @error('item_origen_id') is-invalid @enderror"
                                                id="item_origen_id" name="item_origen_id">
                                                <option value="">Primero seleccione tipo de bien</option>
                                            </select>
                                            @error('item_origen_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Items disponibles sin patrimoniar</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="destino_id">Destino</label>
                                            <select class="form-control select2 @error('destino_id') is-invalid @enderror"
                                                id="destino_id" name="destino_id">
                                                <option value="">Sin asignar</option>
                                                @foreach($destinos as $destino)
                                                    <option value="{{ $destino->id }}" {{ old('destino_id') == $destino->id ? 'selected' : '' }}>
                                                        {{ $destino->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('destino_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ubicacion">Ubicación Específica</label>
                                            <input type="text" class="form-control @error('ubicacion') is-invalid @enderror"
                                                id="ubicacion" name="ubicacion" value="{{ old('ubicacion') }}"
                                                maxlength="150" placeholder="Ej: Oficina 201, Estante A">
                                            @error('ubicacion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Ubicación física detallada</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="siaf">Código SIAF</label>
                                            <input type="text" class="form-control @error('siaf') is-invalid @enderror"
                                                id="siaf" name="siaf" value="{{ old('siaf') }}" maxlength="100"
                                                placeholder="Ej: 123456789">
                                            @error('siaf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Sistema Integrado de Administración Financiera</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="numero_serie">Número de Serie</label>
                                            <input type="text" class="form-control @error('numero_serie') is-invalid @enderror"
                                                id="numero_serie" name="numero_serie" value="{{ old('numero_serie') }}"
                                                maxlength="255" placeholder="Ej: SN123456">
                                            @error('numero_serie')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                        id="descripcion" name="descripcion" rows="3" required
                                        placeholder="Describa el bien con detalle (marca, modelo, características, etc.)">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_alta">Fecha de Alta <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('fecha_alta') is-invalid @enderror"
                                                id="fecha_alta" name="fecha_alta"
                                                value="{{ old('fecha_alta', date('Y-m-d')) }}" required>
                                            @error('fecha_alta')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                        id="observaciones" name="observaciones" rows="3"
                                        placeholder="Observaciones adicionales (opcional)">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Información</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb"></i> Consejos</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Complete todos los campos obligatorios (*)</li>
                                        <li>El código SIAF es importante para auditoría</li>
                                        <li>Asegúrese de registrar el número de serie si está disponible</li>
                                        <li>La ubicación específica ayuda a localizar el bien rápidamente</li>
                                        <li>La fecha de alta debe corresponder a la fecha real de ingreso del bien</li>
                                    </ul>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Importante</h6>
                                    <p class="mb-0">Al crear el bien, se registrará automáticamente como un movimiento de
                                        <strong>ALTA</strong> en el historial patrimonial.</p>
                                </div>

                                <div class="alert alert-success" id="vinculacion-info" style="display: none;">
                                    <h6><i class="fas fa-link"></i> Vinculación Automática</h6>
                                    <p class="mb-0">Este tipo de bien se vinculará automáticamente con un registro existente en el sistema.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5><i class="fas fa-paperclip"></i> Archivos Adjuntos</h5>
                        <hr>
                    </div>
                </div>

                <div class="container col-xs-12 col-sm-12 col-md-12">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6">
                            <div class="form-group">
                                <label for="archivo">
                                    <i class="fas fa-file"></i> Archivo adjunto
                                </label>
                                <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, XLSX, ZIP, RAR (Máx. 50MB)</small>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
                            <button type="button" id="addImage" class="btn btn-success">
                                <i class="fas fa-plus"></i> Agregar imagen
                            </button>
                        </div>
                    </div>

                    <div class="row mt-3" id="imageContainer"></div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('patrimonio.bienes.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Crear Bien
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            </div>

            <div id="modo-masivo" style="display: none;">
            <form action="{{ route('patrimonio.bienes.store-masivo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="modo_carga" value="masivo">
                <input type="hidden" id="masivo_old" value="{{ old('modo_carga') === 'masivo' ? '1' : '0' }}"
                    data-items='@json(old("items", []))'
                    data-tipo="{{ old('tipo_bien_id') }}"
                    data-destino="{{ old('destino_id') }}"
                    data-ubicacion="{{ old('ubicacion') }}"
                    data-descripcion="{{ old('descripcion') }}"
                    data-observaciones="{{ old('observaciones') }}">


                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-boxes"></i> Datos compartidos del lote</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="masivo_tipo_bien_id">Tipo de Bien <span class="text-danger">*</span></label>
                                            <select class="form-control" id="masivo_tipo_bien_id" name="tipo_bien_id" required>
                                                <option value="">Seleccione un tipo</option>
                                                @foreach($tiposBien->where('tiene_tabla_propia', false) as $tipo)
                                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Solo tipos sin tabla vinculada</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="masivo_destino_id">Destino</label>
                                            <select class="form-control" id="masivo_destino_id" name="destino_id">
                                                <option value="">Sin asignar</option>
                                                @foreach($destinos as $destino)
                                                    <option value="{{ $destino->id }}">{{ $destino->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="masivo_ubicacion">Ubicación Específica</label>
                                            <input type="text" class="form-control" id="masivo_ubicacion" name="ubicacion"
                                                maxlength="150" placeholder="Ej: Oficina 201, Estante A">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="masivo_fecha_alta">Fecha de Alta <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="masivo_fecha_alta" name="fecha_alta"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="masivo_descripcion">Descripción <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="masivo_descripcion" name="descripcion" rows="3"
                                        placeholder="Marca, modelo y características comunes a todas las unidades del lote"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="masivo_observaciones">Observaciones</label>
                                    <textarea class="form-control" id="masivo_observaciones" name="observaciones" rows="2"
                                        placeholder="Observaciones comunes (opcional)"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"><i class="fas fa-list-ol"></i> Unidades del lote</h4>
                                <div class="form-inline">
                                    <label for="masivo_cantidad" class="mr-2 mb-0">Cantidad</label>
                                    <input type="number" id="masivo_cantidad" min="1" max="100" value="2"
                                        class="form-control mr-2" style="width: 90px;">
                                    <button type="button" id="generarFilas" class="btn btn-success">
                                        <i class="fas fa-sync-alt"></i> Generar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Complete el SIAF y N° de serie propios de cada unidad. El resto de los
                                    datos se toma de la sección "Datos compartidos del lote".</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="tablaUnidades">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Código SIAF</th>
                                                <th>Número de Serie</th>
                                            </tr>
                                        </thead>
                                        <tbody id="unidadesBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-paperclip"></i> Adjuntos del lote</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info py-2">
                                    <small>Las imágenes y el archivo se asignan por igual a todas las unidades del lote.</small>
                                </div>
                                <div class="form-group">
                                    <label for="masivo_imagen1"><i class="fas fa-image"></i> Imagen 1</label>
                                    <input type="file" name="imagen1" id="masivo_imagen1" class="form-control" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label for="masivo_imagen2"><i class="fas fa-image"></i> Imagen 2</label>
                                    <input type="file" name="imagen2" id="masivo_imagen2" class="form-control" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label for="masivo_imagen3"><i class="fas fa-image"></i> Imagen 3</label>
                                    <input type="file" name="imagen3" id="masivo_imagen3" class="form-control" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label for="masivo_archivo"><i class="fas fa-file"></i> Archivo adjunto</label>
                                    <input type="file" name="archivo" id="masivo_archivo" class="form-control"
                                        accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                    <small class="text-muted">PDF, DOC, DOCX, XLSX, ZIP, RAR (Máx. 50MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('patrimonio.bienes.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Crear lote de bienes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });
            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    let select2Field = document.querySelector('.select2-container--open .select2-search__field');
                    if (select2Field) {
                        select2Field.focus();
                    }
                }, 0);
            });
            let imageCount = 0;

            document.getElementById('addImage').addEventListener('click', function () {
                imageCount++;

                // Máximo 3 imágenes
                if (imageCount > 3) {
                    alert('Puede agregar un máximo de 3 imágenes');
                    imageCount = 3;
                    return;
                }

                const newImageDiv = document.createElement('div');
                newImageDiv.classList.add('col-xs-12', 'col-sm-12', 'col-md-4', 'image-upload-container');
                newImageDiv.id = `image-container-${imageCount}`;

                newImageDiv.innerHTML = `
                <div class="form-group">
                    <label for="imagen${imageCount}">
                        <i class="fas fa-image"></i> Imagen ${imageCount}
                    </label>
                    <div class="input-group">
                        <input type="file" name="imagen${imageCount}" class="form-control" accept="image/*">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-danger remove-image" data-image="${imageCount}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">JPG, PNG, GIF, WEBP (Máx. 50MB)</small>
                </div>
            `;

                document.getElementById('imageContainer').appendChild(newImageDiv);

                // Ocultar botón si ya hay 3 imágenes
                if (imageCount >= 3) {
                    document.getElementById('addImage').style.display = 'none';
                }
            });
            // Remover imagen
            $(document).on('click', '.remove-image', function () {
                const imageNum = $(this).data('image');
                $(`#image-container-${imageNum}`).remove();

                // Recontear imágenes y mostrar botón si hay menos de 3
                imageCount = $('.image-upload-container').length;
                if (imageCount < 3) {
                    document.getElementById('addImage').style.display = 'block';
                }
            });

            // // Validación de tamaño de archivo
            // $(document).on('change', 'input[type="file"]', function () {
            //     const file = this.files[0];
            //     if (file) {
            //         alert('No hay archivo seleccionado');
            //         $(this).val('');
            //     }
            // });


            // Inicializar select2 normal
            $('#tipo_bien_id, #destino_id').select2({
                width: '100%'
            });

            // Manejar cambio de tipo de bien
            $('#tipo_bien_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const tieneTabla = selectedOption.data('tiene-tabla') === true;
                const tabla = selectedOption.data('tabla');

                if (tieneTabla && tabla) {
                    // Mostrar select de items
                    $('#item-origen-group').slideDown();
                    $('#vinculacion-info').slideDown();
                    $('.item-required').show();

                    // Cargar items disponibles
                    cargarItemsDisponibles($(this).val());
                } else {
                    // Ocultar select de items
                    $('#item-origen-group').slideUp();
                    $('#vinculacion-info').slideUp();
                    $('.item-required').hide();

                    // Limpiar select
                    $('#item_origen_id').empty().append('<option value="">No aplica</option>');
                }
            });

            // Función para cargar items disponibles
            function cargarItemsDisponibles(tipoBienId) {
                const $select = $('#item_origen_id');

                // Destruir select2 si existe
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                // Mostrar loading
                $select.prop('disabled', true).html('<option value="">Cargando...</option>');

                console.log('tipoBienId:', tipoBienId);
                $.ajax({
                    url: '{{ route("patrimonio.bienes.items-disponibles") }}',
                    method: 'GET',

                    data: { tipo_bien_id: tipoBienId },
                    success: function(data) {
                        $select.empty();

                        if (data.length === 0) {
                            $select.append('<option value="">No hay items disponibles</option>');
                        } else {
                            $select.append('<option value="">Seleccione un item</option>');

                            data.forEach(function(item) {
                                console.log('Item:', item);
                                $select.append(`<option value="${item.id}">${item.id} ${item.text}</option>`);
                            });
                        }
                    },
                    error: function(xhr) {
                        $select.empty().append('<option value="">Error al cargar items</option>');
                        console.error('Error:', xhr);
                    },
                    complete: function() {
                        $select.prop('disabled', false);

                        // Re-inicializar select2
                        $select.select2({
                            width: '100%',
                            placeholder: 'Buscar item...',
                            allowClear: true
                        });
                    }
                });
            }

            // ===== Modo de carga: individual / masivo =====
            let masivoSelect2Init = false;

            $('input[name="modo_carga"]').on('change', function () {
                if ($(this).val() === 'masivo') {
                    $('#modo-individual').hide();
                    $('#modo-masivo').show();

                    // Inicializar select2 recién cuando el contenedor es visible
                    if (!masivoSelect2Init) {
                        $('#masivo_tipo_bien_id, #masivo_destino_id').select2({
                            width: '100%'
                        });
                        masivoSelect2Init = true;
                    }

                    if ($('#unidadesBody tr').length === 0) {
                        generarFilas();
                    }
                } else {
                    $('#modo-masivo').hide();
                    $('#modo-individual').show();
                }
            });

            function generarFilas(seed) {
                const cantidad = parseInt($('#masivo_cantidad').val(), 10) || 0;
                if (cantidad < 1) {
                    alert('Ingrese una cantidad válida (mínimo 1)');
                    return;
                }

                // Conservar lo ya cargado en pantalla
                const valores = [];
                $('#unidadesBody tr').each(function (i) {
                    valores[i] = {
                        siaf: $(this).find('input[name$="[siaf]"]').val() || '',
                        serie: $(this).find('input[name$="[numero_serie]"]').val() || ''
                    };
                });

                const $body = $('#unidadesBody').empty();
                for (let i = 0; i < cantidad; i++) {
                    const base = (seed && seed[i])
                        ? { siaf: seed[i].siaf || '', serie: seed[i].numero_serie || '' }
                        : null;
                    const v = base || valores[i] || { siaf: '', serie: '' };

                    const $row = $(`
                        <tr>
                            <td class="text-center align-middle">${i + 1}</td>
                            <td><input type="text" class="form-control siaf-input" name="items[${i}][siaf]" maxlength="100" placeholder="SIAF"></td>
                            <td><input type="text" class="form-control serie-input" name="items[${i}][numero_serie]" maxlength="255" placeholder="N° de serie"></td>
                        </tr>
                    `);
                    $row.find('.siaf-input').val(v.siaf);
                    $row.find('.serie-input').val(v.serie);
                    $body.append($row);
                }
            }

            $('#generarFilas').on('click', function () {
                generarFilas();
            });

            // ===== Restaurar estado tras un rebote de validación =====
            const $oldMeta = $('#masivo_old');
            if ($oldMeta.length && $oldMeta.val() === '1') {
                // Repoblar campos compartidos
                $('#masivo_tipo_bien_id').val($oldMeta.data('tipo'));
                $('#masivo_destino_id').val($oldMeta.data('destino'));
                $('#masivo_ubicacion').val($oldMeta.data('ubicacion'));
                $('#masivo_descripcion').val($oldMeta.data('descripcion'));
                $('#masivo_observaciones').val($oldMeta.data('observaciones'));

                const oldItems = $oldMeta.data('items') || [];
                if (oldItems.length) {
                    $('#masivo_cantidad').val(oldItems.length);
                }

                // Activar el botón de modo masivo y mostrar el formulario
                $('input[name="modo_carga"]').filter('[value="individual"]').closest('label').removeClass('active');
                $('input[name="modo_carga"]').filter('[value="masivo"]').closest('label').addClass('active');
                $('#modo-individual').hide();
                $('#modo-masivo').show();
                if (!masivoSelect2Init) {
                    $('#masivo_tipo_bien_id, #masivo_destino_id').select2({ width: '100%' }).trigger('change');
                    masivoSelect2Init = true;
                }
                generarFilas(oldItems);
            }

            // Si hay un valor old (alta individual), cargar los items vinculados
            @if(old('tipo_bien_id') && old('modo_carga') !== 'masivo')
                $('#tipo_bien_id').trigger('change');

                @if(old('item_origen_id'))
                    setTimeout(function() {
                        $('#item_origen_id').val('{{ old("item_origen_id") }}').trigger('change');
                    }, 500);
                @endif
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .image-upload-container {
            margin-bottom: 15px;
        }

        .input-group-append .btn-danger {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .form-control[type="file"] {
            padding: 5px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush
