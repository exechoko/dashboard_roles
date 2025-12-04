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

            <form action="{{ route('patrimonio.bienes.store') }}" method="POST">
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
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
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

            // Si hay un valor old, cargar los items
            @if(old('tipo_bien_id'))
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
