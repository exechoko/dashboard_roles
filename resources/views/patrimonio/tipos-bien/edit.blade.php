@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-edit"></i> Editar Tipo de Bien</h1>
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

            <form action="{{ route('patrimonio.tipos-bien.update', $tipo->id) }}" method="POST">
                @csrf
                @method('PUT')

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
                                        id="nombre" name="nombre" value="{{ old('nombre', $tipo->nombre) }}"
                                        required maxlength="100">
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                            id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $tipo->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr>

                                <h5 class="mb-3"><i class="fas fa-link"></i> Vinculación con Tablas Existentes</h5>

                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="tiene_tabla_propia"
                                        name="tiene_tabla_propia" value="1"
                                        {{ old('tiene_tabla_propia', $tipo->tiene_tabla_propia) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tiene_tabla_propia">
                                        Este tipo de bien tiene una tabla propia en el sistema
                                    </label>
                                </div>

                                <div id="tabla_referencia_container" class="form-group">
                                    <label for="tabla_referencia">Nombre de la Tabla de Referencia</label>
                                    <input type="text" class="form-control @error('tabla_referencia') is-invalid @enderror"
                                        id="tabla_referencia" name="tabla_referencia"
                                        value="{{ old('tabla_referencia', $tipo->tabla_referencia) }}"
                                        maxlength="100" placeholder="Ej: equipos, camaras, vehiculos">
                                    @error('tabla_referencia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Nombre de la tabla en la base de datos (sin prefijos)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-chart-bar"></i> Estadísticas</h4>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <h2 class="text-primary">{{ $tipo->bienes->count() }}</h2>
                                    <p class="mb-0">Bienes registrados con este tipo</p>
                                </div>

                                @if($tipo->bienes->count() > 0)
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Atención</h6>
                                        <p class="mb-0">Este tipo tiene bienes asociados. Los cambios afectarán a todos ellos.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Información</h4>
                            </div>
                            <div class="card-body">
                                <p><strong>Creado:</strong><br>{{ $tipo->created_at->format('d/m/Y H:i') }}</p>
                                <p><strong>Última actualización:</strong><br>{{ $tipo->updated_at->format('d/m/Y H:i') }}</p>
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
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Actualizar Tipo
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
</style>
@endpush
