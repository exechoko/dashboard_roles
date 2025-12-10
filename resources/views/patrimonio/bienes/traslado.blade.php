@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-exchange-alt"></i> Trasladar Bien Patrimonial</h1>
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

            <form action="{{ route('patrimonio.bienes.procesarTraslado', $bien->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-box"></i> Información del Bien</h4>
                            </div>
                            <div class="card-body bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tipo:</strong> {{ $bien->tipoBien->nombre }}</p>
                                        <p><strong>SIAF:</strong> {{ $bien->siaf ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>N° Serie:</strong> {{ $bien->numero_serie ?? 'N/A' }}</p>
                                        <p><strong>Estado:</strong>
                                            <span class="badge badge-success">{{ $bien->estado_formateado }}</span>
                                        </p>
                                    </div>
                                </div>
                                <p><strong>Descripción:</strong> {{ Str::limit($bien->descripcion, 100) }}</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-map-marker-alt"></i> Ubicación Actual y Nueva</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Ubicación Actual</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Destino:</strong> {{ $bien->destino->nombre ?? 'Sin asignar' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Ubicación:</strong> {{ $bien->ubicacion ?? 'No especificada' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-4 mb-3"><i class="fas fa-arrow-right"></i> Nueva Ubicación</h5>

                                <div class="form-group">
                                    <label for="destino_hasta_id">Nuevo Destino <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('destino_hasta_id') is-invalid @enderror"
                                            id="destino_hasta_id" name="destino_hasta_id" required>
                                        <option value="">Seleccione el destino</option>
                                        @foreach($destinos as $destino)
                                            <option value="{{ $destino->id }}" {{ old('destino_hasta_id') == $destino->id ? 'selected' : '' }}>
                                                {{ $destino->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('destino_hasta_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="ubicacion_hasta">Nueva Ubicación Específica</label>
                                    <input type="text" class="form-control @error('ubicacion_hasta') is-invalid @enderror"
                                        id="ubicacion_hasta" name="ubicacion_hasta" value="{{ old('ubicacion_hasta') }}"
                                        maxlength="150" placeholder="Ej: Oficina 201, Estante A, Sala de Servidores">
                                    @error('ubicacion_hasta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ubicación física detallada dentro del nuevo destino</small>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones del Traslado</label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                            id="observaciones" name="observaciones" rows="4"
                                            placeholder="Motivo del traslado, responsable, autorización, etc.">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Opcional pero recomendado para auditoría y trazabilidad</small>
                                </div>
                            </div>
                        </div>

                        {{-- Nueva sección de Archivos Adjuntos --}}
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-paperclip"></i> Archivos Adjuntos del Traslado</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="archivo">
                                                <i class="fas fa-file"></i> Archivo adjunto
                                            </label>
                                            <input type="file" name="archivo" class="form-control @error('archivo') is-invalid @enderror"
                                                   accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                            @error('archivo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Formatos: PDF, DOC, DOCX, XLSX, ZIP, RAR (Máx. 2MB)</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="button" id="addImage" class="btn btn-success btn-block">
                                                <i class="fas fa-plus"></i> Agregar imagen
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2" id="imageContainer"></div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    <small>Los archivos e imágenes se adjuntarán al bien patrimonial como documentación del traslado.</small>
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
                                    <h6><i class="fas fa-lightbulb"></i> ¿Qué sucederá?</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Se actualizará el destino del bien</li>
                                        <li>Se actualizará la ubicación específica</li>
                                        <li>Se registrará un movimiento de <strong>TRASLADO</strong> en el historial</li>
                                        <li>La fecha del movimiento será la actual</li>
                                        <li>El bien permanecerá en estado <strong>ACTIVO</strong></li>
                                        <li>Los archivos e imágenes se guardarán como documentación</li>
                                    </ul>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Importante</h6>
                                    <p class="mb-0">Asegúrese de seleccionar el destino y ubicación correctos. Esta acción quedará registrada permanentemente en el historial del bien.</p>
                                </div>
                            </div>
                        </div>

                        @if($bien->movimientos->where('tipo_movimiento', 'traslado')->count() > 0)
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-history"></i> Traslados Previos</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Este bien ha sido trasladado <strong>{{ $bien->movimientos->where('tipo_movimiento', 'traslado')->count() }}</strong> veces.</p>

                                    <div class="list-group">
                                        @foreach($bien->movimientos->where('tipo_movimiento', 'traslado')->sortByDesc('fecha')->take(3) as $traslado)
                                            <div class="list-group-item list-group-item-action flex-column align-items-start">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <small class="text-muted">{{ $traslado->fecha->format('d/m/Y') }}</small>
                                                </div>
                                                <p class="mb-1 small">
                                                    <strong>De:</strong> {{ $traslado->destinoDesde->nombre ?? 'N/A' }}
                                                    @if($traslado->ubicacion_desde)
                                                        <br><small class="text-muted">{{ $traslado->ubicacion_desde }}</small>
                                                    @endif
                                                </p>
                                                <p class="mb-1 small">
                                                    <strong>A:</strong> {{ $traslado->destinoHasta->nombre ?? 'N/A' }}
                                                    @if($traslado->ubicacion_hasta)
                                                        <br><small class="text-muted">{{ $traslado->ubicacion_hasta }}</small>
                                                    @endif
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('patrimonio.bienes.show', $bien->id) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-exchange-alt"></i> Confirmar Traslado
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
    $(document).ready(function() {
        let imageCount = 0;

        // Agregar imagen
        document.getElementById('addImage').addEventListener('click', function () {
            imageCount++;

            // Máximo 3 imágenes
            if (imageCount > 3) {
                alert('Puede agregar un máximo de 3 imágenes');
                imageCount = 3;
                return;
            }

            const newImageDiv = document.createElement('div');
            newImageDiv.classList.add('col-md-4', 'image-upload-container');
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
                    <small class="text-muted">JPG, PNG, GIF (Máx. 2MB)</small>
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

        // Inicializar select2
        $('.select2').select2({
            width: '100%'
        });

        // Validación al enviar
        $('form').on('submit', function(e) {
            const destino = $('#destino_hasta_id').val();
            const ubicacion = $('#ubicacion_hasta').val();

            if (!destino) {
                e.preventDefault();
                alert('Debe seleccionar un destino para el traslado');
                return false;
            }

            const destinoNuevo = $('#destino_hasta_id option:selected').text();
            const ubicacionTexto = ubicacion ? ` - ${ubicacion}` : '';

            return confirm(`¿Está seguro de trasladar este bien a:\n\n${destinoNuevo}${ubicacionTexto}\n\nSe registrará en el historial patrimonial.`);
        });
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

    .list-group-item {
        border-radius: 8px !important;
        margin-bottom: 5px;
    }

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
