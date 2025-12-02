@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nuevo Tipo de Bien</h1>
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

            <form action="{{ route('patrimonio.tipos-bien.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-tag"></i> Información del Tipo</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Tipo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                            id="nombre" name="nombre" value="{{ old('nombre') }}"
                                            required maxlength="100" placeholder="Ej: Computadoras, Cámaras, Equipos TETRA">
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                            id="descripcion" name="descripcion" rows="3"
                                            placeholder="Descripción opcional del tipo de bien">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr>

                                <h5 class="mb-3"><i class="fas fa-link"></i> Vinculación con Tablas Existentes</h5>

                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="tiene_tabla_propia"
                                        name="tiene_tabla_propia" value="1"
                                        {{ old('tiene_tabla_propia') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tiene_tabla_propia">
                                        Este tipo de bien tiene una tabla propia en el sistema
                                    </label>
                                </div>

                                <div id="tabla_referencia_container" class="form-group" style="display: none;">
                                    <label for="tabla_referencia">Nombre de la Tabla de Referencia</label>
                                    <input type="text" class="form-control @error('tabla_referencia') is-invalid @enderror"
                                        id="tabla_referencia" name="tabla_referencia" value="{{ old('tabla_referencia') }}"
                                        maxlength="100" placeholder="Ej: equipos, camaras, vehiculos">
                                    @error('tabla_referencia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Nombre de la tabla en la base de datos (sin prefijos). Esto permite vincular bienes patrimoniales con registros operativos.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Ayuda</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb"></i> ¿Tabla Propia?</h6>
                                    <p>Marque esta opción si los bienes de este tipo YA existen en otra tabla del sistema.</p>
                                    <p class="mb-0"><strong>Ejemplo:</strong> Las cámaras están en la tabla <code>camaras</code>, los equipos TETRA en <code>equipos</code>.</p>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-database"></i> Vinculación</h6>
                                    <p class="mb-0">Al vincular con una tabla existente, el sistema podrá enlazar el inventario patrimonial con los registros operativos, evitando duplicación de datos.</p>
                                </div>

                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle"></i> Ejemplos Comunes</h6>
                                    <ul class="mb-0 pl-3 small">
                                        <li><strong>Cámaras:</strong> Tabla <code>camaras</code></li>
                                        <li><strong>Equipos TETRA:</strong> Tabla <code>equipos</code></li>
                                        <li><strong>Vehículos:</strong> Tabla <code>vehiculos</code></li>
                                        <li><strong>Mobiliario:</strong> Sin tabla propia</li>
                                    </ul>
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
                                    <a href="{{ route('patrimonio.tipos-bien.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Crear Tipo
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
        // Toggle tabla referencia input
        function toggleTablaReferencia() {
            if ($('#tiene_tabla_propia').is(':checked')) {
                $('#tabla_referencia_container').slideDown();
            } else {
                $('#tabla_referencia_container').slideUp();
                $('#tabla_referencia').val('');
            }
        }

        toggleTablaReferencia();

        $('#tiene_tabla_propia').on('change', toggleTablaReferencia);
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

    code {
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        color: #e83e8c;
    }
</style>
@endpush
