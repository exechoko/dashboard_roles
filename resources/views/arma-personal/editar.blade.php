@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Corregir Arma/Chaleco — {{ $personal->apellido }}, {{ $personal->nombre }}</h3>
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

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Los datos personales de {{ $personal->apellido }}, {{ $personal->nombre }} (nombre, jerarquía, LP, contacto)
                        vienen de Personal 911 y se actualizan solos todos los días — no se editan desde acá.
                        Esta pantalla es solo para corregir el arma o el chaleco cuando el dato de Personal 911 está mal.
                    </div>

                    @if($personal->personal911_id)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Al guardar, la sincronización diaria no volverá a pisar este arma/chaleco y va a mostrar
                            una discrepancia mientras la fuente de Personal 911 siga distinta.
                        </div>
                    @endif

                    <div class="card bg-light mb-3">
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

                    <form action="{{ route('armas.personal.update', $personal) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numeracion_arma">Numeración del Arma <span class="text-danger">*</span></label>
                                    <input type="text" name="numeracion_arma" id="numeracion_arma" class="form-control @error('numeracion_arma') is-invalid @enderror"
                                           value="{{ old('numeracion_arma', $personal->numeracion_arma) }}">
                                    @error('numeracion_arma')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="arma_tipo_id">Tipo de Arma <span class="text-danger">*</span></label>
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
                                    <label for="motivo_cambio">Motivo de la Corrección <span class="text-danger">*</span></label>
                                    <textarea name="motivo_cambio" id="motivo_cambio" class="form-control @error('motivo_cambio') is-invalid @enderror" rows="2">{{ old('motivo_cambio') }}</textarea>
                                    @error('motivo_cambio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar corrección
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
