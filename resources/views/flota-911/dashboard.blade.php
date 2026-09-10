@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Flota 911 — Control de Vehículos</h3>
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

        @can('confirmar-transferencia-recurso')
        @if($transferenciasPendientes > 0)
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Hay <strong>{{ $transferenciasPendientes }}</strong> transferencia(s) reportada(s) pendiente(s) de confirmar.
                </span>
                <a href="{{ route('flota-911.transferencias.index') }}" class="btn btn-sm btn-warning">
                    Revisar
                </a>
            </div>
        @endif
        @endcan

        {{-- Contadores globales --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-primary"><i class="fas fa-truck-pickup fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalRecursos }}</div>
                            <small class="text-muted">Recursos en la división</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-danger"><i class="fas fa-tools fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalEnTaller }}</div>
                            <small class="text-muted">Con bitácora abierta (en taller)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="mr-3 text-warning"><i class="fas fa-exchange-alt fa-2x"></i></div>
                        <div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalPrestados }}</div>
                            <small class="text-muted">Préstamos activos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div class="row mb-4">
            @can('generar-parte-diario')
            <div class="col-auto">
                <a href="{{ route('flota-911.informes.parte-diario') }}" class="btn btn-primary">
                    <i class="fas fa-file-alt mr-1"></i> Parte Diario
                </a>
            </div>
            @endcan
            @can('generar-estado-flota')
            <div class="col-auto">
                <a href="{{ route('flota-911.informes.estado-flota') }}" class="btn btn-secondary">
                    <i class="fas fa-clipboard-list mr-1"></i> Estado Flota
                </a>
            </div>
            @endcan
            @can('gestionar-flota-911')
            <div class="col-auto">
                <a href="{{ route('flota-911.prestamos.index') }}" class="btn btn-warning">
                    <i class="fas fa-exchange-alt mr-1"></i> Préstamos
                </a>
            </div>
            @endcan
            @can('confirmar-transferencia-recurso')
            <div class="col-auto">
                <a href="{{ route('flota-911.transferencias.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-truck-moving mr-1"></i> Transferencias
                    @if($transferenciasPendientes > 0)
                        <span class="badge badge-danger ml-1">{{ $transferenciasPendientes }}</span>
                    @endif
                </a>
            </div>
            @endcan
        </div>

        {{-- Recursos por sección --}}
        @foreach($secciones as $seccion)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <h5 class="header-title">{{ $seccion->nombre }}</h5>
                        <small class="text-muted">{{ $seccion->recursos->count() }} recurso(s)</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Móvil</th>
                                <th>Dominio</th>
                                <th>Tipo / Marca / Modelo</th>
                                <th>Estado</th>
                                <th>Bitácora</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seccion->recursos as $recurso)
                            @php
                                $vehiculo = $recurso->vehiculoActual();
                                $estado = $recurso->estadoSeccion;
                                $abiertas = $recurso->bitacoraAbiertas->count();
                                $prestamo = $recurso->prestamoActivo;
                            @endphp
                            <tr>
                                <td><strong>{{ $recurso->nombre }}</strong></td>
                                <td>
                                    @if($vehiculo)
                                        <span class="tei-badge">
                                            <i class="fas fa-id-card mr-1"></i>{{ $vehiculo->dominio ?? '—' }}
                                        </span>
                                    @else
                                        <span class="badge badge-light text-muted">Sin ficha</span>
                                    @endif
                                </td>
                                <td>
                                    @if($vehiculo)
                                        <small>{{ implode(' / ', array_filter([$vehiculo->tipo_vehiculo, $vehiculo->marca, $vehiculo->modelo])) }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    @if($prestamo)
                                        <span class="badge badge-info">
                                            <i class="fas fa-exchange-alt mr-1"></i>Prestado a {{ $prestamo->destinoDestino->nombre }}
                                        </span>
                                    @elseif($estado)
                                        <span class="badge badge-{{ $estado->badgeClass }}">{{ $estado->label }}</span>
                                    @else
                                        <span class="badge badge-success">En servicio</span>
                                    @endif
                                </td>
                                <td>
                                    @if($abiertas > 0)
                                        <span class="badge badge-warning"><i class="fas fa-tools mr-1"></i>{{ $abiertas }} abierta(s)</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center action-td">
                                    <a href="{{ route('flota-911.informes.estado-flota') }}?q={{ urlencode($recurso->nombre) }}"
                                       class="action-btn btn-view" title="Bitácora del recurso">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    @can('gestionar-flota-911')
                                    <button class="action-btn btn-edit" title="Cambiar estado"
                                        data-toggle="modal" data-target="#modalEstado{{ $recurso->id }}">
                                        <i class="fas fa-sliders-h"></i>
                                    </button>
                                    @if($recurso->transferenciaPendiente)
                                        <span class="action-btn text-warning" title="Transferencia reportada, pendiente de confirmar">
                                            <i class="fas fa-truck-moving"></i>
                                        </span>
                                    @else
                                        <button class="action-btn btn-del" title="Reportar transferencia del vehículo"
                                            data-toggle="modal" data-target="#modalTransferir{{ $recurso->id }}">
                                            <i class="fas fa-truck-moving"></i>
                                        </button>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modales de estado por recurso --}}
        @can('gestionar-flota-911')
        @foreach($seccion->recursos as $recurso)
        @php $estadoActual = $recurso->estadoSeccion; @endphp
        <div class="modal fade" id="modalEstado{{ $recurso->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Estado de {{ $recurso->nombre }}
                            @php $v = $recurso->vehiculoActual(); @endphp
                            @if($v) <small class="text-muted">({{ $v->dominio }})</small> @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('flota-911.estado-seccion.update', $recurso->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Estado general</label>
                                <select name="estado" class="form-control" required>
                                    @foreach(\App\Models\RecursoEstadoSeccion::$estados as $key => $label)
                                        <option value="{{ $key }}" {{ ($estadoActual?->estado ?? 'en_servicio') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2" maxlength="500">{{ $estadoActual?->observaciones }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @unless($recurso->transferenciaPendiente)
        <div class="modal fade" id="modalTransferir{{ $recurso->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Reportar transferencia — {{ $recurso->nombre }}
                            @php $vt = $recurso->vehiculoActual(); @endphp
                            @if($vt) <small class="text-muted">({{ $vt->dominio }})</small> @endif
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('flota-911.transferencias.store', $recurso->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="text-muted small">
                                Se avisa a los administradores para que efectivicen el movimiento. El recurso sigue
                                activo hasta que un administrador confirme la transferencia.
                            </p>
                            <div class="form-group">
                                <label>Fecha de la transferencia <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_transferencia" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Repartición destino</label>
                                <select name="destino_transferencia_id" class="form-control select2-destino">
                                    <option value="">— Desconocida —</option>
                                    @foreach($destinos as $d)
                                        <option value="{{ $d->id }}">{{ $d->label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Dejar en "Desconocida" si no se sabe adónde fue.</small>
                            </div>
                            <div class="form-group">
                                <label>Repartición destino (texto libre)</label>
                                <input type="text" name="reparticion_texto" class="form-control" maxlength="255"
                                    placeholder="Solo si la repartición no está en la lista">
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3" maxlength="2000"
                                    placeholder="Motivo, vehículo de reemplazo, etc."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-truck-moving mr-1"></i> Reportar transferencia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endunless
        @endforeach
        @endcan
        @endforeach

        {{-- Préstamos activos --}}
        @if($prestamosActivos->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon text-warning"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <h5 class="header-title">Préstamos activos</h5>
                        <small class="text-muted">Recursos prestados a otras dependencias</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Dominio</th>
                                <th>Destino</th>
                                <th>Desde</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prestamosActivos as $prestamo)
                            <tr>
                                <td>{{ $prestamo->recurso->nombre }}</td>
                                <td><span class="tei-badge">{{ $prestamo->vehiculoSnapshot?->dominio ?? '—' }}</span></td>
                                <td>{{ $prestamo->destinoDestino->nombre }}</td>
                                <td>{{ $prestamo->fecha_salida->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</section>
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
