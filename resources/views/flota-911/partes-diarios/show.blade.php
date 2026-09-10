@extends('layouts.app')

@php
    $rubros = $novedades
        ? $novedades->rubrosCompletos()
        : collect(\App\Models\ParteDiarioNovedades::RUBROS)
            ->map(fn ($et) => ['etiqueta' => $et, 'valor' => \App\Models\ParteDiarioNovedades::SIN_NOVEDAD])
            ->all();
@endphp

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Parte diario — {{ $parte->seccion?->nombre }}</h3>
    </div>
    <div class="section-body">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><small class="text-muted d-block">Fecha</small><strong>{{ $parte->fecha->format('d/m/Y') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Guardia</small><strong>{{ $parte->guardiaLabel() }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Horario</small><strong>{{ $parte->horarioLabel() }}</strong></div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Tipo</small>
                        <span class="badge badge-{{ $parte->esMotos() ? 'warning' : 'primary' }}">{{ $parte->esMotos() ? 'Motos' : 'Móviles' }}</span>
                    </div>
                </div>
                <hr>
                <small class="text-muted">
                    Generado por {{ $parte->usuario?->name }} {{ $parte->usuario?->apellido }}
                    el {{ $parte->created_at?->format('d/m/Y H:i') }}
                    @if($parte->updated_at && $parte->updated_at->ne($parte->created_at))
                        · última modificación {{ $parte->updated_at->format('d/m/Y H:i') }}
                    @endif
                </small>
                <div class="mt-2">
                    <a href="{{ route('flota-911.partes-diarios.docx', $parte->id) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-word mr-1"></i> Descargar .docx
                    </a>
                    <a href="{{ route('flota-911.partes-diarios.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al histórico
                    </a>
                </div>
            </div>
        </div>

        {{-- Recursos que circularon --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-users"></i></div>
                    <h5 class="header-title">Recursos y tripulación</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead><tr>
                            <th>Recurso</th><th>Dominio</th><th>Estado</th><th>Zona</th><th>HT</th>
                            <th>Motivo</th><th>Tripulación</th>
                        </tr></thead>
                        <tbody>
                        @forelse($recursos as $item)
                        @php $estado = $item['estado']; $recurso = $item['recurso']; @endphp
                        <tr>
                            <td><strong>{{ $recurso?->nombre ?? 'Recurso #'.$estado->recurso_id }}</strong></td>
                            <td>
                                @if($recurso?->vehiculo)
                                    <span class="tei-badge">{{ $recurso->vehiculo->dominio ?? '—' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $estado->badge_class }}">{{ $estado->label }}</span></td>
                            <td>{{ $estado->zona ?: '—' }}</td>
                            <td>{{ $estado->ht ?: '—' }}</td>
                            <td>{{ $estado->motivo ?: '—' }}</td>
                            <td>
                                @forelse($item['tripulacion'] as $d)
                                    <div class="small">
                                        {{ $d->personal?->nombre_completo ?? 'Personal #'.$d->personal_id }}
                                        @if($d->es_chofer)<span class="badge badge-info ml-1">chofer</span>@endif
                                    </div>
                                @empty
                                    <span class="text-muted small">Sin dotación cargada</span>
                                @endforelse
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Este parte no tiene recursos registrados.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block font-weight-bold">Guardia (suboficiales de guardia interna)</small>
                        {{ $parte->guardia_interna ?: '—' }}
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block font-weight-bold">Personal de Licencia Ordinaria</small>
                        {{ $parte->licencia_ordinaria ?: '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Asignación de servicios (motos) --}}
        @if($parte->asignaciones->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-list-ol"></i></div>
                    <h5 class="header-title">Asignación de servicios</h5>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th style="width:180px">Grupo</th><th style="width:220px">Consigna</th><th>Asignación</th></tr></thead>
                    <tbody>
                    @foreach($parte->asignaciones as $a)
                        <tr>
                            <td><small class="text-muted">{{ $a->grupo ?: '—' }}</small></td>
                            <td>{{ $a->nombre }}</td>
                            <td>{{ $a->asignacion_texto ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if($parte->novedades_pie)
                <div class="mt-3">
                    <small class="text-muted d-block font-weight-bold">NOVEDADES (pie del parte de motos)</small>
                    {{ $parte->novedades_pie }}
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Hoja NOVEDADES de la División --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <h5 class="header-title">NOVEDADES — División 911</h5>
                        <small class="text-muted">{{ $novedades ? 'Registradas para esta fecha/guardia.' : 'No se registró la hoja de novedades para esta fecha/guardia.' }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($rubros as $rubro)
                    <div class="col-md-6 mb-2">
                        <small class="font-weight-bold d-block">{{ $rubro['etiqueta'] }}</small>
                        <span class="{{ $rubro['valor'] === \App\Models\ParteDiarioNovedades::SIN_NOVEDAD ? 'text-muted' : '' }}">{{ $rubro['valor'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
