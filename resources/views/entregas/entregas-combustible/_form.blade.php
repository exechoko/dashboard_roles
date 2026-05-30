<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-gas-pump"></i> Información de la Entrega</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror" id="fecha_entrega" name="fecha_entrega" value="{{ old('fecha_entrega', isset($entrega) ? $entrega->fecha_entrega->format('Y-m-d') : date('Y-m-d')) }}" required>
                    @error('fecha_entrega')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="hora_entrega">Hora de Entrega <span class="text-danger">*</span></label>
                    <input type="time" class="form-control @error('hora_entrega') is-invalid @enderror" id="hora_entrega" name="hora_entrega" value="{{ old('hora_entrega', isset($entrega) ? substr($entrega->hora_entrega, 0, 5) : date('H:i')) }}" required>
                    @error('hora_entrega')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="ticket">Ticket <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ticket') is-invalid @enderror" id="ticket" name="ticket" value="{{ old('ticket', $entrega->ticket ?? '') }}" maxlength="255" required>
                    @error('ticket')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="remito">Remito (opcional)</label>
                    <input type="text" class="form-control @error('remito') is-invalid @enderror" id="remito" name="remito" value="{{ old('remito', $entrega->remito ?? '') }}" maxlength="100" pattern="[A-Za-z0-9\-]+" placeholder="Ej.: 0001-00012345">
                    <small class="text-muted">Número de remito del ticket. Letras, números y guiones, sin espacios.</small>
                    @error('remito')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label for="empresa_soporte">Empresa de Soporte <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('empresa_soporte') is-invalid @enderror" id="empresa_soporte" name="empresa_soporte" value="{{ old('empresa_soporte', $entrega->empresa_soporte ?? 'Patagonia Green') }}" maxlength="255" required>
                    @error('empresa_soporte')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="personal_receptor">Personal Receptor <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('personal_receptor') is-invalid @enderror" id="personal_receptor" name="personal_receptor" value="{{ old('personal_receptor', $entrega->personal_receptor ?? '') }}" maxlength="255" required>
                    @error('personal_receptor')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="legajo_receptor">L.P. Receptor</label>
                    <input type="text" class="form-control @error('legajo_receptor') is-invalid @enderror" id="legajo_receptor" name="legajo_receptor" value="{{ old('legajo_receptor', $entrega->legajo_receptor ?? '') }}" maxlength="50">
                    @error('legajo_receptor')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="personal_entrega">Personal que Entrega <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('personal_entrega') is-invalid @enderror" id="personal_entrega" name="personal_entrega" value="{{ old('personal_entrega', $entrega->personal_entrega ?? '') }}" maxlength="255" required>
                    @error('personal_entrega')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="legajo_entrega">L.P. del Personal que Entrega</label>
                    <input type="text" class="form-control @error('legajo_entrega') is-invalid @enderror" id="legajo_entrega" name="legajo_entrega" value="{{ old('legajo_entrega', $entrega->legajo_entrega ?? '') }}" maxlength="50">
                    @error('legajo_entrega')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="cantidad_bidones">Bidones <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('cantidad_bidones') is-invalid @enderror" id="cantidad_bidones" name="cantidad_bidones" value="{{ old('cantidad_bidones', $entrega->cantidad_bidones ?? 2) }}" min="1" max="999" required>
                    @error('cantidad_bidones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="litros_por_bidon">Litros por Bidón <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('litros_por_bidon') is-invalid @enderror" id="litros_por_bidon" name="litros_por_bidon" value="{{ old('litros_por_bidon', $entrega->litros_por_bidon ?? 20) }}" min="1" max="999" required>
                    @error('litros_por_bidon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="cantidad_litros">Litros (total) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('cantidad_litros') is-invalid @enderror" id="cantidad_litros" name="cantidad_litros" value="{{ old('cantidad_litros', $entrega->cantidad_litros ?? 40) }}" min="1" max="9999" required>
                    <small class="text-muted">Se autocalcula: bidones × litros por bidón. Editable si difiere.</small>
                    @error('cantidad_litros')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="combustible">Combustible <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('combustible') is-invalid @enderror" id="combustible" name="combustible" value="{{ old('combustible', $entrega->combustible ?? 'Puma Diesel 500') }}" maxlength="255" required>
                    @error('combustible')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="estacion_servicio">Estación de Servicio donde se cargó</label>
            <input type="text" class="form-control @error('estacion_servicio') is-invalid @enderror" id="estacion_servicio" name="estacion_servicio" value="{{ old('estacion_servicio', $entrega->estacion_servicio ?? '') }}" maxlength="255" placeholder="Ej.: YPF Bv. Racedo, Shell Av. Almafuerte...">
            <small class="text-muted">Sólo para registro interno. No se incluye en el acta de entrega.</small>
            @error('estacion_servicio')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="3">{{ old('observaciones', $entrega->observaciones ?? '') }}</textarea>
            @error('observaciones')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var bidones = document.getElementById('cantidad_bidones');
        var litrosPorBidon = document.getElementById('litros_por_bidon');
        var totalLitros = document.getElementById('cantidad_litros');
        var manualEdit = false;

        if (!bidones || !litrosPorBidon || !totalLitros) {
            return;
        }

        function recalcular() {
            if (manualEdit) {
                return;
            }
            var b = parseInt(bidones.value, 10) || 0;
            var l = parseInt(litrosPorBidon.value, 10) || 0;
            totalLitros.value = b * l;
        }

        bidones.addEventListener('input', recalcular);
        litrosPorBidon.addEventListener('input', recalcular);
        totalLitros.addEventListener('input', function () {
            manualEdit = true;
        });
    })();
</script>
@endpush
