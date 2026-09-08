@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Parte Diario de Vehículos</h3>
    </div>
    <div class="section-body">

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
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recursos por sección --}}
            @foreach($secciones as $i => $seccion)
            @php
                $recursos = $seccion->recursos;
                if($recursos->isEmpty()) continue;
            @endphp
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-users"></i></div>
                        <h5 class="header-title">{{ $seccion->nombre }}</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th style="width:180px">Móvil / Dominio</th>
                                    <th style="width:200px">Estado del día</th>
                                    <th style="width:220px">Motivo (si no circula)</th>
                                    <th>Dotación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recursos as $j => $recurso)
                                @php
                                    $idx = "recursos[{$recurso->id}]";
                                    $estadoDiario = $recurso->estadoDiario->first();
                                    $estadoDia = $estadoDiario?->estado_dia ?? 'circula';
                                    $vehiculoActual = $recurso->vehiculoActual();
                                    $dotacionIds = $recurso->dotaciones->pluck('personal_id')->toArray();
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
                                        <input type="text" name="{{ $idx }}[motivo]" class="form-control form-control-sm"
                                            value="{{ $estadoDiario?->motivo }}" maxlength="200"
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Novedades generales --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-sticky-note"></i></div>
                        <h5 class="header-title">Novedades generales</h5>
                    </div>
                </div>
                <div class="card-body">
                    <textarea name="novedades_generales" class="form-control" rows="4"
                        maxlength="3000" placeholder="Novedades generales de la jornada..."></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-file-word mr-1"></i> Generar Parte Diario (.docx)
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
    $('.select2-personal').select2({
        width: '100%',
        language: 'es',
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
