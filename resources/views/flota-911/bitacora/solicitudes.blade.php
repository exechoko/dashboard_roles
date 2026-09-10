@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Bitácora — Solicitudes de cambio</h3>
    </div>
    <div class="section-body">

        @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
            @if(session($key))
                <div class="alert alert-{{ $cls }} alert-dismissible fade show">
                    {{ session($key) }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon text-warning"><i class="fas fa-user-clock"></i></div>
                    <div>
                        <h5 class="header-title">Pendientes</h5>
                        <small class="text-muted">{{ $pendientes->count() }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($pendientes->isEmpty())
                    <div class="text-center py-4 text-muted">No hay solicitudes pendientes.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead><tr>
                            <th>Recurso</th><th>Tipo</th><th>Propuesta</th><th>Solicita</th><th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr></thead>
                        <tbody>
                        @foreach($pendientes as $s)
                        <tr>
                            <td>
                                <strong>{{ $s->bitacora?->recurso?->nombre ?? '—' }}</strong><br>
                                <small class="text-muted">Entrada #{{ $s->bitacora_id }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $s->tipo === 'eliminacion' ? 'danger' : 'primary' }}">
                                    {{ $s->tipo === 'eliminacion' ? 'Eliminación' : 'Edición' }}
                                </span>
                            </td>
                            <td><small>
                                @if($s->tipo === 'edicion' && $s->cambios)
                                    @foreach($s->cambios as $campo => $valor)
                                        <div><strong>{{ $campo }}:</strong> {{ \Illuminate\Support\Str::limit((string) $valor, 60) }}</div>
                                    @endforeach
                                @endif
                                @if($s->motivo)<div class="text-muted">Motivo: {{ $s->motivo }}</div>@endif
                            </small></td>
                            <td><small>{{ $s->usuario?->name }} {{ $s->usuario?->apellido }}</small></td>
                            <td><small>{{ $s->created_at->format('d/m/Y H:i') }}</small></td>
                            <td class="text-center" style="white-space:nowrap">
                                <form action="{{ route('flota-911.bitacora.solicitudes.aprobar', $s->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Aprobar y aplicar el cambio?');">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#rechazar{{ $s->id }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header-modern"><div class="card-header-left">
                <div class="header-icon"><i class="fas fa-history"></i></div>
                <h5 class="header-title">Historial</h5>
            </div></div>
            <div class="card-body p-0">
                @if($historial->isEmpty())
                    <div class="text-center py-4 text-muted">Sin historial.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead><tr><th>Recurso</th><th>Tipo</th><th>Estado</th><th>Resolvió</th><th>Fecha</th></tr></thead>
                        <tbody>
                        @foreach($historial as $s)
                        <tr>
                            <td>{{ $s->bitacora?->recurso?->nombre ?? '—' }} <small class="text-muted">#{{ $s->bitacora_id }}</small></td>
                            <td>{{ $s->tipo === 'eliminacion' ? 'Eliminación' : 'Edición' }}</td>
                            <td>
                                @if($s->estado === 'aprobada')
                                    <span class="badge badge-success">Aprobada</span>
                                @else
                                    <span class="badge badge-secondary" title="{{ $s->motivo_resolucion }}">Rechazada</span>
                                @endif
                            </td>
                            <td><small>{{ $s->resueltaPor?->name }} {{ $s->resueltaPor?->apellido }}</small></td>
                            <td><small>{{ optional($s->resuelta_en)->format('d/m/Y H:i') }}</small></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $historial->links() }}
                @endif
            </div>
        </div>
    </div>
</section>

@foreach($pendientes as $s)
<div class="modal fade" id="rechazar{{ $s->id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Rechazar solicitud</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form action="{{ route('flota-911.bitacora.solicitudes.rechazar', $s->id) }}" method="POST">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div class="form-group">
                    <label>Motivo <span class="text-danger">*</span></label>
                    <textarea name="motivo_resolucion" class="form-control" rows="2" maxlength="500" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div></div>
</div>
@endforeach
@endsection
