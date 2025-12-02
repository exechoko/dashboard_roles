@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-edit"></i> Editar Bien Patrimonial #{{ $bien->id }}</h1>
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

            <form action="{{ route('patrimonio.bienes.update', $bien->id) }}" method="POST">
                @csrf
                @method('PUT')

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
                                                    <option value="{{ $tipo->id }}"
                                                        {{ old('tipo_bien_id', $bien->tipo_bien_id) == $tipo->id ? 'selected' : '' }}>
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
                                            <label for="destino_id">Destino/Ubicación</label>
                                            <select class="form-control select2 @error('destino_id') is-invalid @enderror"
                                                    id="destino_id" name="destino_id">
                                                <option value="">Sin asignar</option>
                                                @foreach($destinos as $destino)
                                                    <option value="{{ $destino->id }}"
                                                        {{ old('destino_id', $bien->destino_id) == $destino->id ? 'selected' : '' }}>
                                                        {{ $destino->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('destino_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Si cambia el destino, se registrará automáticamente como traslado</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="siaf">Código SIAF</label>
                                            <input type="text" class="form-control @error('siaf') is-invalid @enderror"
                                                id="siaf" name="siaf" value="{{ old('siaf', $bien->siaf) }}"
                                                maxlength="100">
                                            @error('siaf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="numero_serie">Número de Serie</label>
                                            <input type="text" class="form-control @error('numero_serie') is-invalid @enderror"
                                                id="numero_serie" name="numero_serie" value="{{ old('numero_serie', $bien->numero_serie) }}"
                                                maxlength="255">
                                            @error('numero_serie')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                            id="descripcion" name="descripcion" rows="3" required>{{ old('descripcion', $bien->descripcion) }}</textarea>
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
                                                value="{{ old('fecha_alta', $bien->fecha_alta->format('Y-m-d')) }}" required>
                                            @error('fecha_alta')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Estado Actual</label>
                                            <input type="text" class="form-control" readonly
                                                value="{{ $bien->estado_formateado }}">
                                            <small class="text-muted">El estado no se puede cambiar desde aquí. Use las acciones de Traslado o Baja.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                            id="observaciones" name="observaciones" rows="3">{{ old('observaciones', $bien->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($bien->tabla_origen && $bien->id_origen)
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-link"></i> Vinculación</h6>
                                        <p class="mb-0">Este bien está vinculado con: <strong>{{ ucwords(str_replace('_', ' ', $bien->tabla_origen)) }}</strong> (ID: {{ $bien->id_origen }})</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-chart-bar"></i> Resumen</h4>
                            </div>
                            <div class="card-body">
                                <div class="summary-item">
                                    <h6>ID del Bien</h6>
                                    <h3 class="text-primary">#{{ $bien->id }}</h3>
                                </div>

                                <div class="summary-item">
                                    <h6>Total Movimientos</h6>
                                    <h4 class="text-info">{{ $bien->movimientos->count() }}</h4>
                                </div>

                                <div class="summary-item">
                                    <h6>Último Movimiento</h6>
                                    @if($bien->ultimoMovimiento)
                                        <p class="mb-0">
                                            <span class="badge badge-info">{{ $bien->ultimoMovimiento->tipo_formateado }}</span><br>
                                            <small>{{ $bien->ultimoMovimiento->fecha->format('d/m/Y H:i') }}</small>
                                        </p>
                                    @else
                                        <p class="text-muted">Sin movimientos</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Información</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Importante</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Si cambia el destino, se registrará automáticamente como traslado</li>
                                        <li>Para dar de baja use el botón específico</li>
                                        <li>Todos los cambios quedan registrados</li>
                                    </ul>
                                </div>

                                <p><strong>Creado:</strong><br>{{ $bien->created_at->format('d/m/Y H:i') }}</p>
                                <p><strong>Última actualización:</strong><br>{{ $bien->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
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
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Actualizar Bien
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
        $('.select2').select2({
            width: '100%'
        });

        // Advertir sobre cambio de destino
        const destinoOriginal = $('#destino_id').val();
        $('form').on('submit', function(e) {
            const destinoNuevo = $('#destino_id').val();

            if (destinoOriginal != destinoNuevo && destinoNuevo) {
                return confirm('Ha cambiado el destino del bien. Esto registrará automáticamente un TRASLADO en el historial. ¿Desea continuar?');
            }
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

    .summary-item {
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .summary-item h6 {
        margin-bottom: 5px;
        color: #6c757d;
    }
</style>
@endpush
