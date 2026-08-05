@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading"><i class="fas fa-tower-broadcast"></i> Activaciones Tótem</h3>
            @can('editar-activacion-totem')
                <form action="{{ route('activaciones-totem.escanear') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-sync"></i> Escanear ahora
                    </button>
                </form>
            @endcan
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('activaciones-totem.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estados as $key => $label)
                                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Fecha del evento (desde)</label>
                                <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Fecha del evento (hasta)</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="vencidas" name="vencidas" value="1" {{ request()->boolean('vencidas') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="vencidas">
                                        <span class="text-danger">Vencidas</span> (+6 meses)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('activaciones-totem.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Fecha evento</th>
                                    <th>N° Expediente</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Tótem</th>
                                    <th>Descarga / Eliminación</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activaciones as $activacion)
                                    @php
                                        $vencida = $activacion->esVencida();
                                    @endphp
                                    <tr class="{{ $vencida ? 'table-danger' : '' }}">
                                        <td>{{ $activacion->fecha_evento->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('cecoco.expediente', $activacion->evento_cecoco_id) }}" target="_blank">
                                                {{ $activacion->nro_expediente }}
                                            </a>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($activacion->evento->descripcion, 90) }}</td>
                                        <td>
                                            @php
                                                $badge = match ($activacion->estado) {
                                                    \App\Models\ActivacionTotem::ESTADO_DESCARGADO => 'success',
                                                    \App\Models\ActivacionTotem::ESTADO_DESCARTADO => 'secondary',
                                                    \App\Models\ActivacionTotem::ESTADO_ELIMINADO => 'dark',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badge }}">{{ $estados[$activacion->estado] ?? $activacion->estado }}</span>
                                            @if ($vencida)
                                                <span class="badge badge-danger" title="Superó el plazo legal de retención de {{ \App\Models\ActivacionTotem::MESES_RETENCION_LEGAL }} meses">
                                                    <i class="fas fa-exclamation-triangle"></i> Vencido
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $activacion->camara->nombre ?? '-' }}</td>
                                        <td>
                                            @if ($activacion->descargadoPor)
                                                <div>{{ $activacion->descargadoPor->name }}</div>
                                                <small class="text-muted d-block">Descargado: {{ $activacion->fecha_descarga->format('d/m/Y H:i') }}</small>
                                            @endif
                                            @if ($activacion->eliminadoPor)
                                                <div class="text-danger">{{ $activacion->eliminadoPor->name }}</div>
                                                <small class="text-muted d-block">Eliminado: {{ $activacion->fecha_eliminado->format('d/m/Y H:i') }}</small>
                                            @endif
                                            @if (!$activacion->descargadoPor && !$activacion->eliminadoPor)
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @can('editar-activacion-totem')
                                                @if ($activacion->estado === \App\Models\ActivacionTotem::ESTADO_PENDIENTE)
                                                    <button type="button" class="btn btn-sm btn-success" title="Registrar descarga"
                                                            data-toggle="modal" data-target="#descargarModal{{ $activacion->id }}">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary" title="Descartar"
                                                            onclick="if(confirm('¿Descartar esta activación? Se usa cuando no corresponde a un tótem real.')) document.getElementById('descartar-{{ $activacion->id }}').submit();">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    <form id="descartar-{{ $activacion->id }}" action="{{ route('activaciones-totem.descartar', $activacion) }}" method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                                @if ($activacion->estado === \App\Models\ActivacionTotem::ESTADO_DESCARGADO && $vencida)
                                                    <button type="button" class="btn btn-sm btn-danger" title="Marcar como eliminado (ya se borró el video)"
                                                            onclick="if(confirm('¿Confirmás que ya borraste el video descargado? Esta acción queda registrada.')) document.getElementById('eliminar-{{ $activacion->id }}').submit();">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <form id="eliminar-{{ $activacion->id }}" action="{{ route('activaciones-totem.eliminar', $activacion) }}" method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay activaciones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $activaciones->links() }}
                </div>
            </div>
        </div>
    </section>

    @can('editar-activacion-totem')
        @foreach ($activaciones as $activacion)
            @if ($activacion->estado === \App\Models\ActivacionTotem::ESTADO_PENDIENTE)
                <div class="modal fade" id="descargarModal{{ $activacion->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-download"></i> Registrar descarga — Exp. {{ $activacion->nro_expediente }}</h5>
                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <form action="{{ route('activaciones-totem.update', $activacion) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="camara_id-{{ $activacion->id }}">Tótem involucrado</label>
                                        <select name="camara_id" id="camara_id-{{ $activacion->id }}" class="form-control">
                                            <option value="">Sin especificar</option>
                                            @foreach ($totems as $totem)
                                                <option value="{{ $totem->id }}">{{ $totem->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="observaciones-{{ $activacion->id }}">Observaciones</label>
                                        <textarea name="observaciones" id="observaciones-{{ $activacion->id }}" class="form-control" rows="3" maxlength="1000"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-download"></i> Registrar descarga</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endcan
@endsection
