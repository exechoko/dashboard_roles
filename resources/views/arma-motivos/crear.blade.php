@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Nuevo Motivo</h3>
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

                    <form action="{{ route('armas.motivos.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Motivo <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ej: 862, A.R.T, 106, J.M.S, PSICOLÓGICO, EMBARAZO, LICENCIA</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_asignado">Tipo Asignado <span class="text-danger">*</span></label>
                                    <select name="tipo_asignado" id="tipo_asignado" class="form-control @error('tipo_asignado') is-invalid @enderror" required>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="RETENCIÓN" {{ old('tipo_asignado') == 'RETENCIÓN' ? 'selected' : '' }}>RETENCIÓN</option>
                                        <option value="REGULACIÓN" {{ old('tipo_asignado') == 'REGULACIÓN' ? 'selected' : '' }}>REGULACIÓN</option>
                                        <option value="RESGUARDO" {{ old('tipo_asignado') == 'RESGUARDO' ? 'selected' : '' }}>RESGUARDO</option>
                                    </select>
                                    @error('tipo_asignado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="texto_acta">Texto para el Acta de Retención</label>
                                    <textarea name="texto_acta" id="texto_acta" class="form-control @error('texto_acta') is-invalid @enderror" rows="2" maxlength="500">{{ old('texto_acta') }}</textarea>
                                    @error('texto_acta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Reemplaza a "{{ '${MOTIVO_RETENCION}' }}" en el acta, en la frase "...la presente medida se lleva a cabo <strong>[este texto]</strong>."
                                        Ej: "de la suspensión preventiva dispuesta por Resolución N° 123/26". Si se deja vacío, se usa el nombre del motivo tal cual.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dias">Días para Elevación <span class="text-danger">*</span></label>
                                    <input type="number" name="dias" id="dias" class="form-control @error('dias') is-invalid @enderror"
                                           value="{{ old('dias', 0) }}" min="0" max="365" required>
                                    @error('dias')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Días restantes para la elevación (0 si no aplica)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="activo">Estado</label>
                                    <select name="activo" id="activo" class="form-control @error('activo') is-invalid @enderror">
                                        <option value="1" {{ old('activo', 1) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('activo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <a href="{{ route('armas.motivos.index') }}" class="btn btn-secondary">
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
