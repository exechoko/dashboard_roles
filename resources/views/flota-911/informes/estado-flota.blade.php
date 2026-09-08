@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Estado de Flota Actual</h3>
    </div>
    <div class="section-body">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <form action="{{ route('flota-911.informes.estado-flota.generar') }}" method="POST">
            @csrf

            @foreach($secciones as $seccion)
            @php
                $recursos = $seccion->recursos->filter(fn($r) => $r->vehiculo !== null);
                if($recursos->isEmpty()) continue;

                // Preferencia guardada del usuario para esta sección
                $preferencia = \App\Models\VehiculoInformePreferencia::where('user_id', auth()->id())
                    ->where('destino_id', $seccion->id)
                    ->first();
                $preseleccionados = $preferencia?->vehiculo_ids ?? $recursos->pluck('vehiculo_id')->toArray();
            @endphp

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-users"></i></div>
                        <h5 class="header-title">{{ $seccion->nombre }}</h5>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="seleccionarTodos({{ $seccion->id }}, true)">
                            Todos
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ml-1"
                            onclick="seleccionarTodos({{ $seccion->id }}, false)">
                            Ninguno
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Campo oculto para destino_id al seleccionar radio --}}
                    <input type="radio" name="destino_id" value="{{ $seccion->id }}"
                        {{ $loop->first ? 'checked' : '' }} class="d-none destino-radio-{{ $seccion->id }}">

                    <div class="row" id="seccion{{ $seccion->id }}">
                        @foreach($recursos as $recurso)
                        @php $v = $recurso->vehiculo; @endphp
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input seccion-check-{{ $seccion->id }}"
                                    name="vehiculo_ids[]" value="{{ $v->id }}"
                                    id="veh{{ $v->id }}"
                                    {{ in_array($v->id, $preseleccionados) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="veh{{ $v->id }}">
                                    <strong>{{ $recurso->nombre }}</strong>
                                    <span class="tei-badge ml-1">{{ $v->dominio ?? '—' }}</span>
                                    @php $estado = $v->estadoSeccion; @endphp
                                    @if($estado && $estado->estado !== 'en_servicio')
                                        <span class="badge badge-{{ $estado->badgeClass }} ml-1">{{ $estado->label }}</span>
                                    @endif
                                    @if($v->novedadesPendientes->isNotEmpty())
                                        <span class="badge badge-danger ml-1">{{ $v->novedadesPendientes->count() }} nov.</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-3 text-right">
                        <button type="submit"
                            onclick="document.querySelector('.destino-radio-{{ $seccion->id }}').checked = true"
                            class="btn btn-primary">
                            <i class="fas fa-file-word mr-1"></i> Generar informe — {{ $seccion->nombre }}
                        </button>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="mb-4">
                <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>

        </form>
    </div>
</section>

@push('scripts')
<script>
function seleccionarTodos(seccionId, valor) {
    document.querySelectorAll('.seccion-check-' + seccionId).forEach(function(cb) {
        cb.checked = valor;
    });
}
</script>
@endpush
@endsection
