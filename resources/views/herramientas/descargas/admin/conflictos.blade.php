@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-exclamation-triangle mr-2"></i>Conflictos de archivos</h3>
    </div>

    <div class="section-body">
        <div class="alert alert-warning">
            <i class="fas fa-info-circle mr-2"></i>
            Se encontraron archivos con el mismo nombre que ya existen en el sistema. Por favor, indica qué acción tomar para cada uno.
        </div>

        <form action="{{ route('descargas.admin.procesar_conflictos') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="data" value="{{ json_encode($data) }}">

            @foreach($conflictos as $index => $conflicto)
                @php
                    $archivo = $conflicto['archivo'];
                    $existente = $conflicto['conflicto'];
                @endphp
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-file mr-2"></i>
                            {{ $archivo->getClientOriginalName() }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Archivo existente:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nombre:</strong> {{ $existente->nombre_original }}</li>
                                    <li><strong>Tamaño:</strong> {{ $existente->tamano_humano }}</li>
                                    <li><strong>Fecha:</strong> {{ $existente->created_at->format('d/m/Y H:i') }}</li>
                                    <li><strong>Categoría:</strong> {{ $existente->categoria->nombre }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Nuevo archivo:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nombre:</strong> {{ $archivo->getClientOriginalName() }}</li>
                                    <li><strong>Tamaño:</strong> {{ number_format($archivo->getSize() / 1024, 2) }} KB</li>
                                    <li><strong>Fecha:</strong> Ahora</li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <h6>¿Qué deseas hacer?</h6>
                        <div class="form-group">
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" name="acciones[{{ $index }}][accion]" value="reemplazar" class="custom-control-input" id="reemplazar_{{ $index }}" checked>
                                <label class="custom-control-label" for="reemplazar_{{ $index }}">
                                    <strong>Reemplazar</strong> - El archivo nuevo reemplaza al existente (se guarda versión anterior)
                                </label>
                            </div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" name="acciones[{{ $index }}][accion]" value="copia" class="custom-control-input" id="copia_{{ $index }}">
                                <label class="custom-control-label" for="copia_{{ $index }}">
                                    <strong>Cargar como copia</strong> - Se guarda con un nombre diferente (ej: archivo(1).pdf)
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" name="acciones[{{ $index }}][accion]" value="cancelar" class="custom-control-input" id="cancelar_{{ $index }}">
                                <label class="custom-control-label" for="cancelar_{{ $index }}">
                                    <strong>Cancelar</strong> - No subir este archivo
                                </label>
                            </div>
                        </div>

                        <div class="form-group motivo-group">
                            <label>Motivo del reemplazo (opcional)</label>
                            <input type="text" name="acciones[{{ $index }}][motivo]" class="form-control" placeholder="Ej: Versión actualizada, corrección de errores...">
                        </div>

                        <input type="hidden" name="acciones[{{ $index }}][archivo]" value="">
                        <input type="hidden" name="acciones[{{ $index }}][conflicto_id]" value="{{ $existente->id }}">
                    </div>
                </div>
            @endforeach

            <div class="text-right">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check"></i> Procesar archivos
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
// Guardar archivos en el formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const dataTransfer = new DataTransfer();
    @foreach($conflictos as $index => $conflicto)
        // Los archivos ya están en el input original
    @endforeach
});

// Mostrar/ocultar motivo según la opción seleccionada
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const card = this.closest('.card-body');
        const motivoGroup = card.querySelector('.motivo-group');
        if (this.value === 'reemplazar') {
            motivoGroup.style.display = 'block';
        } else {
            motivoGroup.style.display = 'none';
        }
    });
});
</script>
@endpush
