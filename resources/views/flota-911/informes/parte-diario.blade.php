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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha del parte <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control"
                                    value="{{ $fecha }}" required id="inputFecha">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vehículos por sección --}}
            @foreach($secciones as $i => $seccion)
            @php
                $recursos = $seccion->recursos->filter(fn($r) => $r->vehiculo !== null);
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
                                    $v = $recurso->vehiculo;
                                    $idx = "vehiculos[{$v->id}]";
                                    $estadoDiario = $v->estadoDiario->first();
                                    $estadoDia = $estadoDiario?->estado_dia ?? 'circula';
                                    $dotacionIds = $v->dotaciones
                                        ->filter(fn($d) => $d->fecha?->toDateString() === $fecha)
                                        ->pluck('personal_id')->toArray();
                                @endphp
                                <tr>
                                    <td>
                                        <input type="hidden" name="{{ $idx }}[id]" value="{{ $v->id }}">
                                        <strong>{{ $recurso->nombre }}</strong><br>
                                        <span class="tei-badge">{{ $v->dominio ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[estado_dia]" class="form-control form-control-sm estado-dia-select"
                                            data-vehiculo="{{ $v->id }}">
                                            @foreach(\App\Models\VehiculoEstadoDiario::$estados as $key => $label)
                                                <option value="{{ $key }}" {{ $estadoDia === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="{{ $idx }}[motivo]" class="form-control form-control-sm"
                                            value="{{ $estadoDiario?->motivo }}" maxlength="200"
                                            placeholder="Motivo..."
                                            {{ $estadoDia === 'circula' ? 'style=display:none' : '' }}
                                            id="motivo{{ $v->id }}">
                                    </td>
                                    <td>
                                        <select name="{{ $idx }}[dotacion][]" class="form-control select2-personal"
                                            multiple data-placeholder="Buscar personal..."
                                            id="dotacion{{ $v->id }}">
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
// Mostrar/ocultar campo motivo según estado
document.querySelectorAll('.estado-dia-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var vehiculoId = this.dataset.vehiculo;
        var motivo = document.getElementById('motivo' + vehiculoId);
        if (motivo) {
            motivo.style.display = this.value === 'circula' ? 'none' : '';
        }
    });
});

// Select2 para personal
$(document).ready(function() {
    $('.select2-personal').select2({
        width: '100%',
        language: 'es',
    });
});
</script>
@endpush
@endsection
