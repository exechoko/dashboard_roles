@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Motivo</h3>
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

                    <form action="{{ route('armas.motivos.update', $armaMotivo) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Motivo <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $armaMotivo->nombre) }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_asignado">Tipo Asignado <span class="text-danger">*</span></label>
                                    <select name="tipo_asignado" id="tipo_asignado" class="form-control @error('tipo_asignado') is-invalid @enderror" required>
                                        <option value="">Seleccione un tipo</option>
                                        <option value="RETENCIÓN" {{ old('tipo_asignado', $armaMotivo->tipo_asignado) == 'RETENCIÓN' ? 'selected' : '' }}>RETENCIÓN</option>
                                        <option value="REGULACIÓN" {{ old('tipo_asignado', $armaMotivo->tipo_asignado) == 'REGULACIÓN' ? 'selected' : '' }}>REGULACIÓN</option>
                                        <option value="RESGUARDO" {{ old('tipo_asignado', $armaMotivo->tipo_asignado) == 'RESGUARDO' ? 'selected' : '' }}>RESGUARDO</option>
                                    </select>
                                    @error('tipo_asignado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dias">Días para Elevación <span class="text-danger">*</span></label>
                                    <input type="number" name="dias" id="dias" class="form-control @error('dias') is-invalid @enderror"
                                           value="{{ old('dias', $armaMotivo->dias) }}" min="0" max="365" required>
                                    @error('dias')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="activo">Estado</label>
                                    <select name="activo" id="activo" class="form-control @error('activo') is-invalid @enderror">
                                        <option value="1" {{ old('activo', $armaMotivo->activo) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('activo', $armaMotivo->activo) == 0 ? 'selected' : '' }}>Inactivo</option>
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
                                    <i class="fas fa-save"></i> Actualizar
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
