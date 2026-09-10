@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Parte Diario de Vehículos</h3>
    </div>
    <div class="section-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <form action="{{ route('flota-911.informes.parte-diario.generar') }}" method="POST" id="formParteDiario">
            @csrf

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-calendar-alt"></i></div>
                        <h5 class="header-title">Configuración</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Fecha del parte <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control"
                                    value="{{ $fecha }}" required id="inputFecha">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Guardia <span class="text-danger">*</span></label>
                                <select name="guardia" class="form-control" required id="inputGuardia">
                                    <option value="">Seleccione...</option>
                                    @foreach(\App\Models\RecursoEstadoDiario::$guardias as $k => $label)
                                        <option value="{{ $k }}" {{ $guardia === $k ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Horario <span class="text-danger">*</span></label>
                                <select name="horario" class="form-control" required id="inputHorario">
                                    @foreach(\App\Models\RecursoEstadoDiario::$horarios as $k => $label)
                                        <option value="{{ $k }}" {{ $horario === $k ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Inicio del turno <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="fecha_inicio" class="form-control"
                                    value="{{ $fechaInicio->format('Y-m-d\TH:i') }}" required id="inputFechaInicio">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Fin del turno <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="fecha_fin" class="form-control"
                                    value="{{ $fechaFin->format('Y-m-d\TH:i') }}" required id="inputFechaFin">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                El inicio y fin se autocompletan según fecha y horario. Ajústelos si el cambio de guardia se movió.
                            </p>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnCargarTurno">
                                <i class="fas fa-sync-alt mr-1"></i> Cargar parte guardado de este turno
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" id="btnPreArmar">
                                <i class="fas fa-magic mr-1"></i> Pre-armar desde la guardia
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="btnDesdeGuardia">
                                <i class="fas fa-history mr-1"></i> Traer del último parte de esta guardia
                            </button>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-lightbulb mr-1"></i>
                        "Pre-armar" trae de la base de personal 911 la guardia interna, las licencias y las novedades de sala.
                        "Traer del último parte de esta guardia" copia el parte anterior de la guardia elegida
                        (recursos, dotación, zona/HT, asignaciones y novedades) para que solo ajustes las diferencias.
                    </p>
                </div>
            </div>

            {{-- Recursos por sección --}}
            @foreach($secciones as $seccion)
            @php
                $recursos = $seccion->recursos;
                if($recursos->isEmpty()) continue;
                $esMotos = ($tiposPorSeccion[$seccion->id] ?? 'moviles') === 'motos';
                $parteSeccion = $partesPorSeccion[$seccion->id] ?? null;
                $asignacionesGuardadas = collect(optional($parteSeccion)->asignaciones)
                    ->mapWithKeys(fn($a) => [trim($a->grupo.'|'.$a->nombre) => $a->asignacion_texto]);
            @endphp
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-users"></i></div>
                        <h5 class="header-title">
                            {{ $seccion->nombre }}
                            <span class="badge badge-{{ $esMotos ? 'warning' : 'primary' }} ml-1">
                                {{ $esMotos ? 'Motos' : 'Móviles' }}
                            </span>
                        </h5>
                    </div>
                </div>
                <div class="card-body">
                    @if($parteSeccion)
                    <a href="{{ route('flota-911.informes.parte-diario.docx', ['seccion' => $seccion->id, 'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i')]) }}"
                       class="btn btn-success btn-sm mb-3">
                        <i class="fas fa-file-word mr-1"></i>
                        Descargar parte de {{ $esMotos ? 'motos' : 'móviles' }} (.docx)
                    </a>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th style="width:150px">Recurso</th>
                                    <th style="width:150px">Estado del día</th>
                                    <th style="width:70px">Zona</th>
                                    <th style="width:110px">HT</th>
                                    <th style="width:180px">Motivo (si no circula)</th>
                                    <th>Dotación</th>
                                    <th style="width:200px">Chofer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recursos as $recurso)
                                @php
                                    $idx = "recursos[{$recurso->id}]";
                                    $estadoDiario = $recurso->estadoDiario->first();
                                    $estadoDia = $estadoDiario?->estado_dia ?? 'circula';
                                    $vehiculoActual = $recurso->vehiculoActual();
                                    $dotacionIds = $recurso->dotaciones->pluck('personal_id')->all();
                                    $choferId = $recurso->dotaciones->firstWhere('es_chofer', true)?->personal_id;
                                @endphp
                                <tr>
                                    <td>
                                        <input type="hidden" name="{{ $idx }}[id]" value="{{ $recurso->id }}">
                                        <strong>{{ $recurso->nombre }}</strong><br>
                                        @if($vehiculoActual)
                                            <span class="tei-badge">{{ $vehiculoActual->dominio ?? '—' }}</span>
                                        @else
                                            <span class="badge badge-light text-muted">Sin ficha</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[estado_dia]" class="form-control form-control-sm estado-dia-select"
                                            data-recurso="{{ $recurso->id }}">
                                            @foreach(\App\Models\RecursoEstadoDiario::$estados as $key => $label)
                                                <option value="{{ $key }}" {{ $estadoDia === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[zona]" class="form-control form-control-sm">
                                            <option value="">—</option>
                                            @for($z = 1; $z <= 4; $z++)
                                                <option value="{{ $z }}" {{ (string)($estadoDiario?->zona) === (string)$z ? 'selected' : '' }}>{{ $z }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="{{ $idx }}[ht]" class="form-control form-control-sm"
                                            value="{{ $estadoDiario?->ht }}" maxlength="50" placeholder="HT / MP">
                                    </td>
                                    <td>
                                        <input type="text" name="{{ $idx }}[motivo]" class="form-control form-control-sm"
                                            value="{{ $estadoDiario?->motivo }}" maxlength="500"
                                            placeholder="Motivo..."
                                            {{ $estadoDia === 'circula' ? 'style=display:none' : '' }}
                                            id="motivo{{ $recurso->id }}">
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[dotacion][]" class="form-control select2-personal"
                                            multiple data-placeholder="Buscar personal..."
                                            id="dotacion{{ $recurso->id }}">
                                            @foreach($personal as $p)
                                                <option value="{{ $p->id }}"
                                                    {{ in_array($p->id, $dotacionIds) ? 'selected' : '' }}>
                                                    {{ $p->getNombreCompletoAttribute() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[chofer_id]" class="form-control select2-chofer"
                                            data-placeholder="Chofer...">
                                            <option value="">— Sin chofer —</option>
                                            @foreach($personal as $p)
                                                <option value="{{ $p->id }}" {{ (string)$choferId === (string)$p->id ? 'selected' : '' }}>
                                                    {{ $p->getNombreCompletoAttribute() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Guardia (suboficiales de guardia interna)</label>
                            <textarea name="secciones[{{ $seccion->id }}][guardia_interna]" class="form-control" rows="2"
                                maxlength="1000">{{ optional($parteSeccion)->guardia_interna }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Personal de Licencia Ordinaria</label>
                            <textarea name="secciones[{{ $seccion->id }}][licencia_ordinaria]" class="form-control" rows="2"
                                maxlength="1000">{{ optional($parteSeccion)->licencia_ordinaria }}</textarea>
                        </div>
                    </div>

                    @if($esMotos)
                    <div class="mt-3">
                        <label class="small font-weight-bold">Asignación de servicios</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2">
                                <thead>
                                    <tr>
                                        <th style="width:160px">Grupo</th>
                                        <th style="width:200px">Consigna</th>
                                        <th>Asignación (móvil / moto / HT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consignas as $i => $c)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="secciones[{{ $seccion->id }}][asignaciones][{{ $i }}][grupo]" value="{{ $c->grupo }}">
                                            <small class="text-muted">{{ $c->grupo ?: '—' }}</small>
                                        </td>
                                        <td>
                                            <input type="hidden" name="secciones[{{ $seccion->id }}][asignaciones][{{ $i }}][nombre]" value="{{ $c->nombre }}">
                                            {{ $c->nombre }}
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                name="secciones[{{ $seccion->id }}][asignaciones][{{ $i }}][asignacion_texto]"
                                                value="{{ $asignacionesGuardadas[trim($c->grupo.'|'.$c->nombre)] ?? '' }}"
                                                maxlength="255" placeholder="ej. 31 ht 05">
                                        </td>
                                    </tr>
                                    @endforeach
                                    @for($e = 0; $e < 3; $e++)
                                    @php $ei = $consignas->count() + $e; @endphp
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm" name="secciones[{{ $seccion->id }}][asignaciones][{{ $ei }}][grupo]" maxlength="80" placeholder="(opcional)"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="secciones[{{ $seccion->id }}][asignaciones][{{ $ei }}][nombre]" maxlength="120" placeholder="Consigna extra"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="secciones[{{ $seccion->id }}][asignaciones][{{ $ei }}][asignacion_texto]" maxlength="255"></td>
                                    </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        <label class="small font-weight-bold">NOVEDADES (pie del parte de motos)</label>
                        <textarea name="secciones[{{ $seccion->id }}][novedades_pie]" class="form-control" rows="2"
                            maxlength="2000">{{ optional($parteSeccion)->novedades_pie }}</textarea>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            {{-- NOVEDADES (hoja de la División, una por guardia) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <h5 class="header-title">NOVEDADES — División 911</h5>
                            <small class="text-muted">Una por guardia. Vacío = "Sin Novedad".</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @php $contenidoNovedades = optional($novedades)->contenido ?? []; @endphp
                    <div class="row">
                        @foreach(\App\Models\ParteDiarioNovedades::RUBROS as $clave => $etiqueta)
                        @php $esPersonal = in_array($clave, \App\Models\ParteDiarioNovedades::RUBROS_PERSONAL, true); @endphp
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold mb-1">{{ $etiqueta }}</label>
                                @if($esPersonal)
                                <select class="form-control form-control-sm select2-novedad-personal mb-1"
                                    data-rubro="{{ $clave }}" data-placeholder="Agregar funcionario...">
                                    <option value=""></option>
                                    @foreach($personal as $p)
                                        <option value="{{ $p->id }}"
                                            data-corto="{{ trim($p->jerarquia.' '.$p->apellido.' '.$p->nombre) }}">
                                            {{ $p->getNombreCompletoAttribute() }}
                                        </option>
                                    @endforeach
                                </select>
                                @endif
                                <textarea name="novedades[{{ $clave }}]" class="form-control form-control-sm"
                                    rows="{{ $esPersonal ? 2 : 1 }}" maxlength="2000">{{ $contenidoNovedades[$clave] ?? '' }}</textarea>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-1"></i> Guardar parte
                </button>
            </div>

        </form>

    </div>
</section>

@push('scripts')
<script>
(function() {
    const inputFecha = document.getElementById('inputFecha');
    const inputHorario = document.getElementById('inputHorario');
    const inputInicio = document.getElementById('inputFechaInicio');
    const inputFin = document.getElementById('inputFechaFin');

    function pad(n) { return String(n).padStart(2, '0'); }

    function actualizarFechasPorHorario() {
        const fecha = inputFecha.value;
        const horario = inputHorario.value;
        if (!fecha || !horario) { return; }
        if (horario === '07_19') {
            inputInicio.value = fecha + 'T07:00';
            inputFin.value = fecha + 'T19:00';
        } else {
            const fin = new Date(fecha + 'T00:00');
            fin.setDate(fin.getDate() + 1);
            const finStr = fin.getFullYear() + '-' + pad(fin.getMonth() + 1) + '-' + pad(fin.getDate());
            inputInicio.value = fecha + 'T19:00';
            inputFin.value = finStr + 'T07:00';
        }
    }

    inputFecha.addEventListener('change', actualizarFechasPorHorario);
    inputHorario.addEventListener('change', actualizarFechasPorHorario);

    document.getElementById('btnCargarTurno').addEventListener('click', function() {
        const params = new URLSearchParams({
            fecha: inputFecha.value,
            guardia: document.getElementById('inputGuardia').value,
            horario: inputHorario.value,
            fecha_inicio: inputInicio.value,
            fecha_fin: inputFin.value,
        });
        window.location.search = params.toString();
    });

    document.getElementById('btnPreArmar').addEventListener('click', function() {
        const guardia = document.getElementById('inputGuardia').value;
        if (!guardia) {
            iziToast.warning({ title: 'Falta la guardia', message: 'Seleccione la guardia antes de pre-armar.', position: 'topRight' });
            return;
        }
        const btn = this;
        btn.disabled = true;
        fetch('{{ route('flota-911.informes.parte-diario.pre-armar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ guardia: guardia, fecha_inicio: inputInicio.value }),
        })
        .then(function(r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function(data) {
            Object.entries(data.novedades || {}).forEach(function(entry) {
                const el = document.querySelector('textarea[name="novedades[' + entry[0] + ']"]');
                if (el && !el.value.trim()) { el.value = entry[1]; }
            });
            Object.entries(data.secciones || {}).forEach(function(entry) {
                const sid = entry[0], vals = entry[1];
                ['guardia_interna', 'licencia_ordinaria'].forEach(function(campo) {
                    const el = document.querySelector('textarea[name="secciones[' + sid + '][' + campo + ']"]');
                    if (el && !el.value.trim() && vals[campo]) { el.value = vals[campo]; }
                });
            });
            iziToast.success({ title: 'Pre-armado', message: 'Se completaron guardia interna, licencias y novedades de sala.', position: 'topRight' });
        })
        .catch(function() {
            iziToast.error({ title: 'Error', message: 'No se pudo pre-armar el parte.', position: 'topRight' });
        })
        .finally(function() { btn.disabled = false; });
    });

    function setSelect2(el, valor) {
        if (!el) { return; }
        el.value = valor == null ? '' : String(valor);
        if (window.jQuery) { window.jQuery(el).trigger('change'); }
    }

    function rellenarDesdeUltimaGuardia(data) {
        // Novedades — 14 rubros de la División
        Object.entries(data.novedades || {}).forEach(function(entry) {
            var ta = document.querySelector('textarea[name="novedades[' + entry[0] + ']"]');
            if (ta) { ta.value = entry[1] || ''; }
        });

        Object.entries(data.secciones || {}).forEach(function(entry) {
            var sid = entry[0], sec = entry[1];

            ['guardia_interna', 'licencia_ordinaria', 'novedades_pie'].forEach(function(campo) {
                var ta = document.querySelector('textarea[name="secciones[' + sid + '][' + campo + ']"]');
                if (ta && sec[campo] != null) { ta.value = sec[campo]; }
            });

            (sec.asignaciones || []).forEach(function(asig) {
                var filas = document.querySelectorAll('input[name^="secciones[' + sid + '][asignaciones]["][name$="][asignacion_texto]"]');
                for (var i = 0; i < filas.length; i++) {
                    var base = filas[i].name.replace('[asignacion_texto]', '');
                    var grupo = (document.querySelector('[name="' + base + '[grupo]"]') || {}).value || '';
                    var nombre = (document.querySelector('[name="' + base + '[nombre]"]') || {}).value || '';
                    if (grupo === (asig.grupo || '') && nombre === (asig.nombre || '')) {
                        filas[i].value = asig.asignacion_texto || '';
                        return;
                    }
                }
                // Sin coincidencia: primera fila extra vacía
                for (var j = 0; j < filas.length; j++) {
                    var b2 = filas[j].name.replace('[asignacion_texto]', '');
                    var gInput = document.querySelector('input[name="' + b2 + '[grupo]"]');
                    var nInput = document.querySelector('input[name="' + b2 + '[nombre]"]');
                    if (gInput && nInput && !nInput.value && !filas[j].value) {
                        gInput.value = asig.grupo || '';
                        nInput.value = asig.nombre || '';
                        filas[j].value = asig.asignacion_texto || '';
                        return;
                    }
                }
            });

            Object.entries(sec.recursos || {}).forEach(function(rEntry) {
                var rid = rEntry[0], r = rEntry[1];

                var estado = document.querySelector('select[name="recursos[' + rid + '][estado_dia]"]');
                if (estado && r.estado_dia) {
                    estado.value = r.estado_dia;
                    var motivo = document.getElementById('motivo' + rid);
                    if (motivo) { motivo.style.display = r.estado_dia === 'circula' ? 'none' : ''; }
                }

                var zona = document.querySelector('select[name="recursos[' + rid + '][zona]"]');
                if (zona) { zona.value = r.zona || ''; }

                var ht = document.querySelector('input[name="recursos[' + rid + '][ht]"]');
                if (ht) { ht.value = r.ht || ''; }

                var motivoInput = document.getElementById('motivo' + rid);
                if (motivoInput) { motivoInput.value = r.motivo || ''; }

                setSelect2(document.getElementById('dotacion' + rid), null);
                var dot = document.getElementById('dotacion' + rid);
                if (dot && window.jQuery) {
                    window.jQuery(dot).val((r.dotacion || []).map(String)).trigger('change');
                }

                var chofer = document.querySelector('select[name="recursos[' + rid + '][chofer_id]"]');
                setSelect2(chofer, r.chofer_id || '');
            });
        });
    }

    document.getElementById('btnDesdeGuardia').addEventListener('click', function() {
        var guardia = document.getElementById('inputGuardia').value;
        if (!guardia) {
            iziToast.warning({ title: 'Falta la guardia', message: 'Seleccione la guardia antes de traer el último parte.', position: 'topRight' });
            return;
        }
        var btn = this;
        btn.disabled = true;
        fetch('{{ route('flota-911.informes.parte-diario.desde-guardia') }}?guardia=' + encodeURIComponent(guardia), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function(r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function(data) {
            if (!data.encontrado) {
                iziToast.info({ title: 'Sin antecedentes', message: 'No hay partes previos de esta guardia.', position: 'topRight' });
                return;
            }
            rellenarDesdeUltimaGuardia(data);
            iziToast.success({
                title: 'Cargado',
                message: 'Datos del parte del ' + (data.referencia && data.referencia.fecha ? data.referencia.fecha : 'último turno') + ' (' + (data.referencia ? data.referencia.guardia_label : '') + ').',
                position: 'topRight',
            });
        })
        .catch(function() {
            iziToast.error({ title: 'Error', message: 'No se pudo traer el último parte de la guardia.', position: 'topRight' });
        })
        .finally(function() { btn.disabled = false; });
    });
})();

document.querySelectorAll('.estado-dia-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var recursoId = this.dataset.recurso;
        var motivo = document.getElementById('motivo' + recursoId);
        if (motivo) {
            motivo.style.display = this.value === 'circula' ? 'none' : '';
        }
    });
});

$(document).ready(function() {
    $('.select2-personal').select2({ width: '100%', language: 'es' });
    $('.select2-chofer').select2({ width: '100%', language: 'es', allowClear: true });

    // Rubros de NOVEDADES sobre personal: el select es un "agregar" al textarea, que queda editable.
    $('.select2-novedad-personal').select2({ width: '100%', language: 'es', placeholder: 'Agregar funcionario...' });
    $('.select2-novedad-personal').on('select2:select', function(e) {
        var corto = (e.params.data.element && e.params.data.element.getAttribute('data-corto')) || e.params.data.text;
        var ta = document.querySelector('textarea[name="novedades[' + $(this).data('rubro') + ']"]');
        if (ta && corto) {
            var actual = ta.value.trim();
            if (actual.indexOf(corto) === -1) {
                ta.value = actual ? actual + '; ' + corto : corto;
            }
        }
        $(this).val(null).trigger('change');
    });

    $('.select2-personal').on('select2:selecting', function(e) {
        const nuevoId = String(e.params.args.data.id);
        const yaSeleccionado = $('.select2-personal').not(this)
            .toArray()
            .some(function(sel) {
                return ($(sel).val() || []).includes(nuevoId);
            });
        if (yaSeleccionado) {
            e.preventDefault();
            iziToast.warning({
                title: 'Funcionario duplicado',
                message: 'Este funcionario ya figura en la dotación de otro recurso.',
                position: 'topRight',
                timeout: 3500,
            });
        }
    });
});
</script>
@endpush
@endsection
