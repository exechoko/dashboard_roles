@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-times-circle"></i> Dar de Baja Bien Patrimonial</h1>
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

            <form action="{{ route('patrimonio.bienes.procesarBaja', $bien->id) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-box"></i> Información del Bien</h4>
                            </div>
                            <div class="card-body bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ID:</strong> #{{ $bien->id }}</p>
                                        <p><strong>Tipo:</strong> {{ $bien->tipoBien->nombre }}</p>
                                        <p><strong>SIAF:</strong> {{ $bien->siaf ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>N° Serie:</strong> {{ $bien->numero_serie ?? 'N/A' }}</p>
                                        <p><strong>Ubicación:</strong> {{ $bien->destino->nombre ?? 'Sin asignar' }}</p>
                                        <p><strong>Fecha Alta:</strong> {{ $bien->fecha_alta->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <p><strong>Descripción:</strong> {{ $bien->descripcion }}</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-clipboard-list"></i> Información de la Baja</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="tipo_baja">Motivo de Baja <span class="text-danger">*</span></label>
                                    <select class="form-control @error('tipo_baja') is-invalid @enderror"
                                            id="tipo_baja" name="tipo_baja" required>
                                        <option value="">Seleccione el motivo</option>
                                        <option value="baja_desuso" {{ old('tipo_baja') == 'baja_desuso' ? 'selected' : '' }}>
                                            <i class="fas fa-ban"></i> Desuso - El bien ya no se utiliza
                                        </option>
                                        <option value="baja_rotura" {{ old('tipo_baja') == 'baja_rotura' ? 'selected' : '' }}>
                                            <i class="fas fa-tools"></i> Rotura - El bien está dañado sin posibilidad de reparación
                                        </option>
                                        <option value="baja_transferencia" {{ old('tipo_baja') == 'baja_transferencia' ? 'selected' : '' }}>
                                            <i class="fas fa-exchange-alt"></i> Transferencia - El bien se transfiere a otra entidad
                                        </option>
                                    </select>
                                    @error('tipo_baja')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                            id="observaciones" name="observaciones" rows="5" required
                                            placeholder="Detalle el motivo de la baja: estado del bien, razón específica, destino en caso de transferencia, etc.">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Sea lo más específico posible para futuras auditorías</small>
                                </div>

                                <div id="info-desuso" class="alert alert-warning d-none">
                                    <h6><i class="fas fa-ban"></i> Baja por Desuso</h6>
                                    <p class="mb-0">Indique desde cuándo el bien no se utiliza y el motivo (obsoleto, reemplazado, etc.)</p>
                                </div>

                                <div id="info-rotura" class="alert alert-danger d-none">
                                    <h6><i class="fas fa-tools"></i> Baja por Rotura</h6>
                                    <p class="mb-0">Describa el daño, si se intentó reparar, y por qué no es viable su reparación</p>
                                </div>

                                <div id="info-transferencia" class="alert alert-info d-none">
                                    <h6><i class="fas fa-exchange-alt"></i> Baja por Transferencia</h6>
                                    <p class="mb-0">Indique la entidad/dependencia receptora, fecha de transferencia y documentación respaldatoria</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-exclamation-triangle text-danger"></i> Advertencia</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <h6><strong>ACCIÓN IMPORTANTE</strong></h6>
                                    <p>Al dar de baja este bien:</p>
                                    <ul class="mb-0 pl-3">
                                        <li>El estado cambiará a <strong>BAJA</strong></li>
                                        <li>No podrá editarse ni trasladarse</li>
                                        <li>Se registrará permanentemente en el historial</li>
                                        <li>La acción NO puede deshacerse</li>
                                    </ul>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Recomendaciones</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Verifique que el bien realmente debe darse de baja</li>
                                        <li>Complete las observaciones con detalle</li>
                                        <li>Conserve documentación respaldatoria</li>
                                        <li>Informe al responsable de patrimonio</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-clock"></i> Tiempo en Servicio</h4>
                            </div>
                            <div class="card-body text-center">
                                @php
                                    $diasServicio = $bien->fecha_alta->diffInDays(now());
                                    $anios = floor($diasServicio / 365);
                                    $meses = floor(($diasServicio % 365) / 30);
                                @endphp
                                <h2 class="text-primary">{{ $anios }}</h2>
                                <p class="mb-0">año(s) y {{ $meses }} mes(es) en servicio</p>
                                <small class="text-muted">Desde {{ $bien->fecha_alta->format('d/m/Y') }}</small>
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
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times-circle"></i> Confirmar Baja
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
        // Mostrar info según tipo de baja
        $('#tipo_baja').on('change', function() {
            const tipo = $(this).val();

            // Ocultar todos los mensajes
            $('.alert[id^="info-"]').addClass('d-none');

            // Mostrar el mensaje correspondiente
            if (tipo) {
                const infoId = tipo.replace('baja_', '');
                $(`#info-${infoId}`).removeClass('d-none');
            }
        });

        // Validación al enviar
        $('form').on('submit', function(e) {
            const tipo = $('#tipo_baja').val();
            const observaciones = $('#observaciones').val().trim();

            if (!tipo) {
                e.preventDefault();
                alert('Debe seleccionar un motivo de baja');
                return false;
            }

            if (!observaciones || observaciones.length < 20) {
                e.preventDefault();
                alert('Las observaciones deben tener al menos 20 caracteres');
                return false;
            }

            const tipoTexto = $('#tipo_baja option:selected').text();
            return confirm(`¿Está COMPLETAMENTE SEGURO de dar de baja este bien por ${tipoTexto}?\n\nEsta acción NO puede deshacerse y el bien quedará marcado permanentemente como BAJA.`);
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

    select option {
        padding: 10px;
    }

    .alert {
        border-radius: 8px;
    }
</style>
@endpush
