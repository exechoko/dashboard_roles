@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Nuevo Funcionario</h3>
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

                    <form action="{{ route('armas.personal.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido <span class="text-danger">*</span></label>
                                    <input type="text" name="apellido" id="apellido" class="form-control @error('apellido') is-invalid @enderror"
                                           value="{{ old('apellido') }}" required>
                                    @error('apellido')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lp">Legajo Policial (LP) <span class="text-danger">*</span></label>
                                    <input type="text" name="lp" id="lp" class="form-control @error('lp') is-invalid @enderror"
                                           value="{{ old('lp') }}" required maxlength="5" pattern="\d{5}" inputmode="numeric"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                    @error('lp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Debe tener exactamente 5 dígitos</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="dni">DNI</label>
                                    <input type="text" name="dni" id="dni" class="form-control @error('dni') is-invalid @enderror"
                                           value="{{ old('dni') }}" maxlength="8" pattern="\d{7,8}" inputmode="numeric"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Opcional. Necesario para generar el acta de retención.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía <span class="text-danger">*</span></label>
                                    <input type="text" name="jerarquia" id="jerarquia" class="form-control @error('jerarquia') is-invalid @enderror"
                                           value="{{ old('jerarquia') }}" required>
                                    @error('jerarquia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ej: Oficial, Sargento, Subcomisario, Comisario</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="text-primary"><i class="fas fa-gun"></i> Arma Asignada</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numeracion_arma">Numeración del Arma <span class="text-danger">*</span></label>
                                    <input type="text" name="numeracion_arma" id="numeracion_arma" class="form-control @error('numeracion_arma') is-invalid @enderror"
                                           value="{{ old('numeracion_arma') }}" required>
                                    @error('numeracion_arma')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="arma_tipo_id">Tipo de Arma <span class="text-danger">*</span></label>
                                    <select name="arma_tipo_id" id="arma_tipo_id" class="form-control @error('arma_tipo_id') is-invalid @enderror" required>
                                        <option value="">Seleccione un tipo</option>
                                        @foreach ($armaTipos as $tipo)
                                            <option value="{{ $tipo->id }}" {{ old('arma_tipo_id') == $tipo->id ? 'selected' : '' }}>
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
                                           value="{{ old('nro_chaleco') }}">
                                    @error('nro_chaleco')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Opcional. Solo si el funcionario tiene chaleco asignado.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <a href="{{ route('armas.personal.index') }}" class="btn btn-secondary">
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
