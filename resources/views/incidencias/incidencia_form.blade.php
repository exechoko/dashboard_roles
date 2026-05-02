@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title">
            <i class="fas fa-{{ isset($incidencia) ? 'edit' : 'plus-circle' }}"></i>
            {{ isset($incidencia) ? 'Editar' : 'Nueva' }} Incidencia — Período {{ $periodo->label }}
        </h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Volver al Período
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ isset($incidencia) ? 'Editar incidencia' : 'Agregar incidencia' }}
                            <span class="text-muted ml-2 small">{{ $periodo->fecha_inicio->format('d/m/Y') }} al {{ $periodo->fecha_fin->format('d/m/Y') }}</span>
                        </h4>
                    </div>
                    <div class="card-body">
                        @php
                            $action = isset($incidencia)
                                ? route('incidencias.incidencia.update', [$periodo->id, $incidencia->id])
                                : route('incidencias.incidencia.store', $periodo->id);
                            $method = isset($incidencia) ? 'PUT' : 'POST';
                            $modulos = \App\Models\Incidencia911::MODULOS;
                        @endphp
                        <form action="{{ $action }}" method="POST" id="formIncidencia">
                            @csrf @method($method)

                            {{-- Tipo e identificación --}}
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Tipo <span class="text-danger">*</span></label>
                                    <select name="tipo_incidencia" class="form-control">
                                        @foreach(\App\Models\Incidencia911::TIPOS as $k => $v)
                                            <option value="{{ $k }}" {{ old('tipo_incidencia', $incidencia->tipo_incidencia ?? 'transitoria') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Origen</label>
                                    <select name="hoja_origen" class="form-control">
                                        @foreach(\App\Models\Incidencia911::HOJAS_ORIGEN as $k => $v)
                                            <option value="{{ $k }}" {{ old('hoja_origen', $incidencia->hoja_origen ?? 'manual') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Prioridad</label>
                                    <select name="prioridad" class="form-control">
                                        @foreach(\App\Models\Incidencia911::PRIORIDADES as $k => $v)
                                            <option value="{{ $k }}" {{ old('prioridad', $incidencia->prioridad ?? 'medio') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Código Incidencia</label>
                                    <input type="text" name="incidencia_code" class="form-control"
                                        value="{{ old('incidencia_code', $incidencia->incidencia_code ?? '') }}" placeholder="PG/26-051">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Fecha inicio falla</label>
                                    <input type="datetime-local" name="fecha_inicio_falla" class="form-control"
                                        value="{{ old('fecha_inicio_falla', isset($incidencia->fecha_inicio_falla) ? $incidencia->fecha_inicio_falla->format('Y-m-d\TH:i') : '') }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Estado</label>
                                    <input type="text" name="estado" class="form-control"
                                        value="{{ old('estado', $incidencia->estado ?? 'resuelto') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tickets asociados</label>
                                <input type="text" name="tickets" class="form-control"
                                    value="{{ old('tickets', $incidencia->tickets ?? '') }}" placeholder="PG/26-051, PG/26-053, PG/26-054">
                            </div>

                            <hr class="my-3">
                            <h6>Sistema y Ponderación (Anexo V — Tabla de Ponderación)</h6>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Sistema (Módulo N1) <span class="text-danger">*</span></label>
                                    <select name="sistema" id="selectSistema" class="form-control">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($modulos as $sis => $cfg)
                                            <option value="{{ $sis }}" data-n1="{{ $cfg['n1_peso'] }}"
                                                {{ old('sistema', $incidencia->sistema ?? '') === $sis ? 'selected' : '' }}>
                                                {{ $sis }} ({{ $cfg['n1_peso'] }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Módulo N2 <span class="text-danger">*</span></label>
                                    <select name="modulo_n2" id="selectModuloN2" class="form-control">
                                        <option value="">— Seleccionar sistema primero —</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Peso N1 (%)</label>
                                    <input type="number" name="ponderacion_n1" id="ponderacion_n1" class="form-control bg-light"
                                        value="{{ old('ponderacion_n1', $incidencia->ponderacion_n1 ?? 0) }}" step="0.01" readonly>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Peso N2 (%)</label>
                                    <input type="number" name="ponderacion_n2" id="ponderacion_n2" class="form-control bg-light"
                                        value="{{ old('ponderacion_n2', $incidencia->ponderacion_n2 ?? 0) }}" step="0.01" readonly>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>N° Unidades afectadas <span class="text-danger">*</span></label>
                                    <input type="number" name="n_unidades_afectadas" id="n_unidades_afectadas" class="form-control @error('n_unidades_afectadas') is-invalid @enderror"
                                        value="{{ old('n_unidades_afectadas', $incidencia->n_unidades_afectadas ?? 1) }}" min="1" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>N° Total unidades <span class="text-danger">*</span></label>
                                    <input type="number" name="n_total_unidades" id="n_total_unidades" class="form-control @error('n_total_unidades') is-invalid @enderror"
                                        value="{{ old('n_total_unidades', $incidencia->n_total_unidades ?? 1) }}" min="1" required>
                                    <small class="text-muted">TETRA: {{ $periodo->n_total_tetra }}, CCTV: {{ $periodo->n_total_camaras }}, Puestos CCTV: {{ $periodo->n_total_puestos_cctv }}</small>
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6>Tiempo de falla</h6>

                            <div class="form-row align-items-end">
                                <div class="form-group col-md-3">
                                    <label>Minutos que falló <span class="text-danger">*</span></label>
                                    <input type="number" name="minutos_fallo" id="minutos_fallo" class="form-control @error('minutos_fallo') is-invalid @enderror"
                                        value="{{ old('minutos_fallo', $incidencia->minutos_fallo ?? 0) }}" step="0.01" min="0" required>
                                    <small class="text-muted">Período = {{ number_format($periodo->minutos_totales, 0) }} min</small>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>% Indisponibilidad (calc.)</label>
                                    <input type="text" id="pct_indisponibilidad" class="form-control bg-light" readonly value="0.00000%">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>% Deficiencia (calc.)</label>
                                    <input type="text" id="pct_deficiencia" class="form-control bg-light" readonly value="0.00000%">
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="aplica_calculo" name="aplica_calculo"
                                            {{ old('aplica_calculo', $incidencia->aplica_calculo ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="aplica_calculo">Aplica al cálculo</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Observaciones / Descripción</label>
                                <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $incidencia->observaciones ?? '') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
var modulos    = @json(\App\Models\Incidencia911::MODULOS);
var minutosPer = {{ $periodo->minutos_totales }};
var selSistema = document.getElementById('selectSistema');
var selModulo  = document.getElementById('selectModuloN2');
var inpN1      = document.getElementById('ponderacion_n1');
var inpN2      = document.getElementById('ponderacion_n2');
var inpN       = document.getElementById('n_unidades_afectadas');
var inpT       = document.getElementById('n_total_unidades');
var inpMin     = document.getElementById('minutos_fallo');
var outI       = document.getElementById('pct_indisponibilidad');
var outD       = document.getElementById('pct_deficiencia');

var currentModuloN2 = "{{ old('modulo_n2', $incidencia->modulo_n2 ?? '') }}";

function populateModulos(sistema, selectedN2) {
    selModulo.innerHTML = '<option value="">— Seleccionar —</option>';
    if (!sistema || !modulos[sistema]) return;
    var cfg = modulos[sistema];
    inpN1.value = cfg.n1_peso;
    Object.keys(cfg.modulos_n2).forEach(function(n2) {
        var opt = document.createElement('option');
        opt.value = n2;
        opt.text  = n2 + ' (' + cfg.modulos_n2[n2] + '%)';
        opt.dataset.n2 = cfg.modulos_n2[n2];
        if (selectedN2 && n2 === selectedN2) opt.selected = true;
        selModulo.appendChild(opt);
    });
    if (selectedN2) {
        var sel = selModulo.querySelector('option[value="' + CSS.escape(selectedN2) + '"]');
        if (sel) { inpN2.value = sel.dataset.n2; }
    }
}

selSistema.addEventListener('change', function() {
    populateModulos(this.value, null);
    recalcular();
});

selModulo.addEventListener('change', function() {
    var sel = this.options[this.selectedIndex];
    inpN2.value = sel ? (sel.dataset.n2 || 0) : 0;
    recalcular();
});

[inpN, inpT, inpMin].forEach(function(el) {
    el.addEventListener('input', recalcular);
});

function recalcular() {
    var n  = parseFloat(inpN.value) || 0;
    var t  = parseFloat(inpT.value) || 1;
    var m  = parseFloat(inpMin.value) || 0;
    var pi = (n / t) * (m / minutosPer) * 100;
    var pd = pi * 2;
    outI.value = pi.toFixed(5) + '%';
    outD.value = pd.toFixed(5) + '%';
}

// Inicializar con valores actuales
populateModulos(selSistema.value, currentModuloN2);
recalcular();

// Autocompletar N total al cambiar sistema
selSistema.addEventListener('change', function() {
    var sis = this.value;
    if (sis === 'TETRA') inpT.value = {{ $periodo->n_total_tetra ?: 622 }};
    else if (sis === 'CCTV') inpT.value = {{ $periodo->n_total_camaras ?: 336 }};
    else inpT.value = 1;
    recalcular();
});
</script>
@endpush
