@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Flota 911 — Transferencias de vehículos</h3>
    </div>
    <div class="section-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- Pendientes de confirmar --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon text-warning"><i class="fas fa-truck-moving"></i></div>
                    <div>
                        <h5 class="header-title">Pendientes de confirmar</h5>
                        <small class="text-muted">{{ $pendientes->count() }} reportada(s)</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($pendientes->isEmpty())
                    <div class="text-center py-4 text-muted">No hay transferencias pendientes.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Dominio</th>
                                <th>Repartición destino</th>
                                <th>Fecha</th>
                                <th>Reportó</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendientes as $t)
                            <tr>
                                <td><strong>{{ $t->recurso->nombre }}</strong></td>
                                <td><span class="tei-badge">{{ $t->vehiculo?->dominio ?? '—' }}</span></td>
                                <td><small>{{ $t->reparticionDestinoNombre() }}</small></td>
                                <td><small>{{ $t->fecha_transferencia->format('d/m/Y') }}</small></td>
                                <td><small>{{ $t->usuarioReporte?->name }} {{ $t->usuarioReporte?->apellido }}</small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success" data-toggle="modal"
                                        data-target="#modalConfirmar{{ $t->id }}">
                                        <i class="fas fa-check mr-1"></i> Confirmar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-toggle="modal"
                                        data-target="#modalRechazar{{ $t->id }}">
                                        <i class="fas fa-times mr-1"></i> Rechazar
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

        {{-- Historial --}}
        <div class="card shadow-sm border-0">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-history"></i></div>
                    <h5 class="header-title">Historial</h5>
                </div>
            </div>
            <div class="card-body p-0">
                @if($historial->isEmpty())
                    <div class="text-center py-4 text-muted">Sin historial.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Dominio</th>
                                <th>Repartición destino</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Resolvió</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historial as $t)
                            <tr>
                                <td>{{ $t->recurso->nombre }}</td>
                                <td><span class="tei-badge">{{ $t->vehiculo?->dominio ?? '—' }}</span></td>
                                <td><small>{{ $t->reparticionDestinoNombre() }}</small></td>
                                <td><small>{{ $t->fecha_transferencia->format('d/m/Y') }}</small></td>
                                <td>
                                    @switch($t->estado)
                                        @case(\App\Models\RecursoTransferencia::ESTADO_CONFIRMADA)
                                            <span class="badge badge-success">Confirmada</span>
                                            @break
                                        @case(\App\Models\RecursoTransferencia::ESTADO_REACTIVADA)
                                            <span class="badge badge-info" data-toggle="tooltip"
                                                title="{{ $t->observaciones }}">Reactivada</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary" data-toggle="tooltip"
                                                title="{{ $t->motivo_rechazo }}">Rechazada</span>
                                    @endswitch
                                </td>
                                <td><small>{{ $t->usuarioResolucion?->name }} {{ $t->usuarioResolucion?->apellido }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $historial->links() }}
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al dashboard
            </a>
        </div>

    </div>
</section>

{{-- Modales confirmar / rechazar --}}
@foreach($pendientes as $t)
<div class="modal fade" id="modalConfirmar{{ $t->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar transferencia — {{ $t->recurso->nombre }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('flota-911.transferencias.confirmar', $t->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="text-muted small">
                        Confirmá solo después de haber hecho los movimientos de asignación en Recursos.
                        Al confirmar, el recurso pasa al listado de transferidos (no se libera la ficha del vehículo).
                    </p>
                    <div class="form-group">
                        <label>Fecha de la transferencia <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_transferencia" class="form-control"
                            value="{{ $t->fecha_transferencia->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Repartición destino</label>
                        <select name="destino_transferencia_id" class="form-control select2-destino">
                            <option value="">— Desconocida —</option>
                            @foreach($destinos as $d)
                                <option value="{{ $d->id }}" {{ $t->destino_transferencia_id == $d->id ? 'selected' : '' }}>
                                    {{ $d->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Repartición destino (texto libre)</label>
                        <input type="text" name="reparticion_texto" class="form-control" maxlength="255"
                            value="{{ $t->reparticion_texto }}">
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3" maxlength="2000">{{ $t->observaciones }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRechazar{{ $t->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar transferencia — {{ $t->recurso->nombre }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('flota-911.transferencias.rechazar', $t->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="motivo_rechazo" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $(document).on('shown.bs.modal', '.modal', function () {
            $(this).find('.select2-destino').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) { return; }
                $(this).select2({
                    width: '100%',
                    placeholder: '— Desconocida —',
                    allowClear: true,
                    dropdownParent: $(this).closest('.modal'),
                });
            });
        });
        $(document).on('select2:open', function () {
            setTimeout(function () {
                var campo = document.querySelector('.select2-container--open .select2-search__field');
                if (campo) { campo.focus(); }
            }, 0);
        });
    });
</script>
@endpush
