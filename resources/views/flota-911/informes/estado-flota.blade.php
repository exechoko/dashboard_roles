@extends('layouts.app')

@section('content')
@php $bitCat = \App\Models\RecursoBitacora::CATEGORIAS; @endphp
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Estado de Flota — División 911</h3>
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

        {{-- ─── Buscador + listado de recursos ─────────────────────────── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div>
                        <h5 class="header-title">Recursos y bitácora</h5>
                        <small class="text-muted">Los que tienen novedades nuevas aparecen primero.</small>
                    </div>
                </div>
                @can('moderar-bitacora-flota-911')
                <a href="{{ route('flota-911.bitacora.solicitudes.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-user-clock mr-1"></i> Solicitudes de cambio
                </a>
                @endcan
            </div>
            <div class="card-body">
                <form method="get" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" value="{{ $q }}"
                               placeholder="Buscar por móvil, dominio, marca o modelo...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            @if($q !== '')
                                <a href="{{ route('flota-911.informes.estado-flota') }}" class="btn btn-outline-secondary">Limpiar</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead><tr>
                            <th>Recurso</th><th>Dominio</th><th>Estado</th><th>Última entrada</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($lista as $r)
                        @php $v = $r->vehiculoActual(); $est = $r->estadoSeccion; $u = $r->ultimaBitacora; @endphp
                        <tr class="{{ $r->nuevas > 0 ? 'table-warning' : '' }}">
                            <td>
                                <strong>{{ $r->nombre }}</strong>
                                @if($r->nuevas > 0)
                                    <span class="badge badge-danger ml-1">{{ $r->nuevas }} nueva{{ $r->nuevas > 1 ? 's' : '' }}</span>
                                @endif
                            </td>
                            <td>@if($v)<span class="tei-badge">{{ $v->dominio ?? '—' }}</span>@else<span class="text-muted">—</span>@endif</td>
                            <td>
                                <span class="badge badge-{{ $est?->badgeClass ?? 'success' }}">{{ $est?->label ?? 'En servicio' }}</span>
                                @if($r->bitacoraAbiertas->isNotEmpty())
                                    <span class="badge badge-warning ml-1"><i class="fas fa-tools"></i> {{ $r->bitacoraAbiertas->count() }}</span>
                                @endif
                            </td>
                            <td>
                                @if($u)
                                    <small>
                                        <span class="badge badge-info">{{ $bitCat[$u->categoria] ?? $u->categoria }}</span>
                                        {{ $u->fecha_hora->diffForHumans() }}
                                        <span class="text-muted d-block">{{ \Illuminate\Support\Str::limit($u->descripcion, 60) }}</span>
                                    </small>
                                @else
                                    <small class="text-muted">Sin entradas</small>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('flota-911.estado-flota.bitacora', $r->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-history mr-1"></i> Bitácora
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">
                            @if($q !== '') Sin resultados para "{{ $q }}". @else No hay recursos. @endif
                        </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ─── Armar informe .docx ────────────────────────────────────── --}}
        @can('generar-estado-flota')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern" style="cursor:pointer" data-toggle="collapse" data-target="#panelInforme">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-file-word"></i></div>
                    <h5 class="header-title">Armar informe de estado de flota (.docx)</h5>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapse" id="panelInforme">
                <div class="card-body">
                    <form action="{{ route('flota-911.informes.estado-flota.generar') }}" method="POST">
                        @csrf
                        @foreach($secciones as $seccion)
                        @php
                            $recursos = $seccion->recursos;
                            if($recursos->isEmpty()) continue;
                            $preferencia = \App\Models\RecursoInformePreferencia::where('user_id', auth()->id())
                                ->where('destino_id', $seccion->id)->first();
                            $preseleccionados = $preferencia?->recurso_ids ?? $recursos->pluck('id')->toArray();
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $seccion->nombre }}</strong>
                                <span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="seleccionarTodos({{ $seccion->id }}, true)">Todos</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="seleccionarTodos({{ $seccion->id }}, false)">Ninguno</button>
                                </span>
                            </div>
                            <input type="radio" name="destino_id" value="{{ $seccion->id }}" {{ $loop->first ? 'checked' : '' }} class="d-none destino-radio-{{ $seccion->id }}">
                            <div class="row" id="seccion{{ $seccion->id }}">
                                @foreach($recursos as $recurso)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input seccion-check-{{ $seccion->id }}"
                                            name="recurso_ids[]" value="{{ $recurso->id }}" id="rec{{ $recurso->id }}"
                                            {{ in_array($recurso->id, $preseleccionados) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="rec{{ $recurso->id }}">
                                            <strong>{{ $recurso->nombre }}</strong>
                                            @php $e2 = $recurso->estadoSeccion; @endphp
                                            @if($e2 && $e2->estado !== 'en_servicio')
                                                <span class="badge badge-{{ $e2->badgeClass }} ml-1">{{ $e2->label }}</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="text-right mt-2">
                                <button type="submit" onclick="document.querySelector('.destino-radio-{{ $seccion->id }}').checked = true"
                                    class="btn btn-primary btn-sm">
                                    <i class="fas fa-file-word mr-1"></i> Generar — {{ $seccion->nombre }}
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
        @endcan

        <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</section>

@push('scripts')
<script>
function seleccionarTodos(seccionId, valor) {
    document.querySelectorAll('.seccion-check-' + seccionId).forEach(function(cb) { cb.checked = valor; });
}
</script>
@endpush
@endsection
