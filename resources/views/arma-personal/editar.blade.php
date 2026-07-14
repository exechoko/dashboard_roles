@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Funcionario</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('armas.personal.update', $personal) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="apellido" class="form-control"
                                           value="{{ old('apellido', $personal->apellido) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="nombre" class="form-control"
                                           value="{{ old('nombre', $personal->nombre) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lp">Legajo Policial (LP) <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="lp" class="form-control"
                                           value="{{ old('lp', $personal->lp) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía <span class="text-danger">*</span></label>
                                    <input type="text" name="jerarquia" id="jerarquia" class="form-control @error('jerarquia') is-invalid @enderror"
                                           value="{{ old('jerarquia', $personal->jerarquia) }}" required>
                                    @error('jerarquia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="text-primary"><i class="fas fa-gun"></i> Arma Asignada</h5>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Si el funcionario cambia de arma, marque "Cambiar arma" y complete los nuevos datos.
                            El arma anterior se registrará automáticamente en el historial.
                        </div>

                        @if($personal->personal911_id)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Este funcionario proviene de Personal 911. Si corrige el arma o chaleco, la sincronización diaria no pisará ese cambio local y mostrará una discrepancia mientras la fuente siga distinta.
                            </div>
                        @endif

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="cambiar_arma_check" name="cambiar_arma" value="1" {{ old('cambiar_arma') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="cambiar_arma_check">
                                    <strong>Cambiar arma</strong>
                                </label>
                            </div>
                        </div>

                        <div id="arma_actual" class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                         <strong>Arma actual:</strong>
                                         {{ $personal->numeracion_arma ?? 'Sin asignar' }}
                                        @if($personal->tipoArma)
                                            - {{ $personal->tipoArma->nombre }}
                                        @endif
                                         @if($personal->nro_chaleco)
                                             | Chaleco: {{ $personal->nro_chaleco }}
                                         @endif
                                        @if($personal->arma_importacion_bloqueada || $personal->chaleco_importacion_bloqueada)
                                            <div class="small text-warning mt-1">
                                                <i class="fas fa-lock"></i> Corrección local protegida desde {{ optional($personal->inventario_bloqueado_en)->format('d/m/Y H:i') ?? 'fecha no disponible' }}.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="arma_nueva" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numeracion_arma">Nueva Numeración del Arma <span class="text-danger">*</span></label>
                                        <input type="text" name="numeracion_arma" id="numeracion_arma" class="form-control @error('numeracion_arma') is-invalid @enderror"
                                               value="{{ old('numeracion_arma', $personal->numeracion_arma) }}">
                                        @error('numeracion_arma')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="arma_tipo_id">Nuevo Tipo de Arma <span class="text-danger">*</span></label>
                                        <select name="arma_tipo_id" id="arma_tipo_id" class="form-control @error('arma_tipo_id') is-invalid @enderror">
                                            <option value="">Seleccione un tipo</option>
                                            @foreach ($armaTipos as $tipo)
                                                <option value="{{ $tipo->id }}" {{ old('arma_tipo_id', $personal->arma_tipo_id) == $tipo->id ? 'selected' : '' }}>
                                                    {{ $tipo->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('arma_tipo_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nro_chaleco">Número de Chaleco</label>
                                        <input type="text" name="nro_chaleco" id="nro_chaleco" class="form-control @error('nro_chaleco') is-invalid @enderror"
                                               value="{{ old('nro_chaleco', $personal->nro_chaleco) }}">
                                        @error('nro_chaleco')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="motivo_cambio">Motivo del Cambio <span class="text-danger">*</span></label>
                                        <textarea name="motivo_cambio" id="motivo_cambio" class="form-control @error('motivo_cambio') is-invalid @enderror" rows="2">{{ old('motivo_cambio') }}</textarea>
                                        @error('motivo_cambio')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                                <a href="{{ route('armas.personal.show', $personal) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('cambiar_arma_check');
    const armaActual = document.getElementById('arma_actual');
    const armaNueva = document.getElementById('arma_nueva');

    function alternarCambioArma() {
        if (checkbox.checked) {
            armaActual.style.display = 'none';
            armaNueva.style.display = 'block';
        } else {
            armaActual.style.display = 'block';
            armaNueva.style.display = 'none';
        }
    }

    checkbox.addEventListener('change', function() {
        alternarCambioArma();
    });

    alternarCambioArma();
});
</script>
@endpush
