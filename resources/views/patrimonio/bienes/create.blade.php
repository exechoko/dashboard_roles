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
                                                    <option value="{{ $tipo->id }}" {{ old('tipo_bien_id') == $tipo->id ? 'selected' : '' }}>
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
                                </div>

                                <div class="form-group">
                                    <label for="ubicacion">Ubicación Específica</label>
                                    <input type="text" class="form-control @error('ubicacion') is-invalid @enderror"
                                        id="ubicacion" name="ubicacion" value="{{ old('ubicacion') }}"
                                        maxlength="150" placeholder="Ej: Oficina 201, Estante A, Sala de Servidores">
                                    @error('ubicacion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ubicación física detallada del bien</small>
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
                                            <small class="text-muted">Código del Sistema Integrado de Administración Financiera</small>
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

                                <div class="alert alert-success">
                                    <h6><i class="fas fa-map-marker-alt"></i> Ubicación</h6>
                                    <p class="mb-0"><strong>Destino:</strong> Área general<br>
                                    <strong>Ubicación:</strong> Lugar específico dentro del destino</p>
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
            $('.select2').select2({
                width: '100%'
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
    </style>
@endpush
