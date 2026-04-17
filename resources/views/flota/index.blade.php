@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Administración</h3>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">

                        <!-- CARD HEADER -->
                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 font-weight-bold flota-title">Equipos en Flota</h5>
                                    <small class="text-muted">
                                        <span class="badge badge-total">{{ $flota->total() }}</span>
                                        registros encontrados
                                        @if($texto)
                                            &mdash; buscando <strong>"{{ $texto }}"</strong>
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <a class="btn btn-primary btn-nuevo" href="{{ route('flota.create') }}">
                                <i class="fas fa-plus mr-1"></i> Nuevo equipo
                            </a>
                        </div>

                        <div class="card-body pt-3">

                            <!-- BUSCADOR -->
                            <form action="{{ route('flota.index') }}" method="get" onsubmit="return showLoad()" class="mb-4">
                                <div class="search-wrapper">
                                    <div class="search-icon-left">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <input type="text" name="texto" class="form-control search-input"
                                        placeholder="Buscar por TEI, ISSI, nombre de recurso o dependencia..."
                                        value="{{ $texto }}" autocomplete="off">
                                    @if($texto)
                                        <a href="{{ route('flota.index') }}" class="search-clear" title="Limpiar búsqueda">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    @endif
                                    <button type="submit" class="btn btn-search">
                                        <i class="fas fa-search mr-1"></i> Buscar
                                    </button>
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Puede buscar por: TEI, ISSI, nombre de recurso o nombre de dependencia
                                </small>
                            </form>

                            {{-- MODALES fuera de la tabla --}}
                            @forelse ($flota as $f)
                                @include('flota.modal.detalle')
                                @include('flota.modal.borrar')
                            @empty
                            @endforelse

                            <!-- MOBILE → CARDS -->
                            <div class="mobile-cards">
                                @forelse ($flota as $f)
                                    @php
                                        $iconosVehiculo = [
                                            'Auto'        => 'fas fa-car',
                                            'Camioneta'   => 'fas fa-truck-pickup',
                                            'Camión'      => 'fas fa-truck',
                                            'Moto'        => 'fas fa-motorcycle',
                                            'Helicoptero' => 'fas fa-helicopter',
                                        ];
                                        $coloresVehiculo = [
                                            'Auto'        => 'recurso-auto',
                                            'Camioneta'   => 'recurso-camioneta',
                                            'Camión'      => 'recurso-camion',
                                            'Moto'        => 'recurso-moto',
                                            'Helicoptero' => 'recurso-helicoptero',
                                        ];
                                        $vehM = $f->recurso->vehiculo ?? null;
                                        $iconoVehM  = $iconosVehiculo[$vehM->tipo_vehiculo ?? ''] ?? 'fas fa-home';
                                        $claseVehM  = $coloresVehiculo[$vehM->tipo_vehiculo ?? ''] ?? 'recurso-sin-vehiculo';
                                    @endphp
                                    <div class="flota-card-modern">
                                        <div class="fcard-top">
                                            <div class="fcard-tei">
                                                <a href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                                    {{ $f->equipo->tei }}
                                                </a>
                                            </div>
                                            <img width="45" class="fcard-img"
                                                src="{{ asset($f->equipo->tipo_terminal->imagen) }}" alt="terminal">
                                        </div>

                                        <div class="fcard-body">
                                            <div class="fcard-row">
                                                <span class="fcard-label"><i class="fas fa-microchip"></i> Tipo/Modelo</span>
                                                <span class="fcard-value">{{ $f->equipo->tipo_terminal->tipo_uso->uso }} / {{ $f->equipo->tipo_terminal->modelo }}</span>
                                            </div>
                                            <div class="fcard-row highlight-recurso">
                                                <span class="fcard-label"><i class="{{ $iconoVehM }}"></i> Recurso</span>
                                                <span class="fcard-value">
                                                    @if($f->recurso)
                                                        <span class="badge-recurso {{ $claseVehM }}"><i class="{{ $iconoVehM }} mr-1"></i>{{ $f->recurso->nombre }}</span>
                                                        @if($vehM)
                                                            <div class="recurso-veh-info mt-1">
                                                                <span class="recurso-veh-detalle">{{ $vehM->marca }} {{ $vehM->modelo }}</span>
                                                                <span class="recurso-veh-dominio"><i class="fas fa-id-card mr-1"></i>{{ $vehM->dominio }}</span>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="fcard-row">
                                                <span class="fcard-label"><i class="fas fa-building"></i> Dependencia</span>
                                                <span class="fcard-value" style="font-size:.78rem;">
                                                    {{ $f->destino->nombre }}
                                                    <br><span class="text-muted">{{ $f->destino->dependeDe() }}</span>
                                                </span>
                                            </div>
                                            <div class="fcard-row">
                                                <span class="fcard-label"><i class="far fa-calendar-alt"></i> Movimiento</span>
                                                <span class="fcard-value">
                                                    <span class="badge" style="background-color: {{ $f->color_ultimo_movimiento }}; color: #fff; border-radius: 20px; padding: .2em .65em; font-size: .78rem;">
                                                        {{ $f->ultimo_movimiento ?? '—' }}
                                                    </span>
                                                    <br><small class="text-muted">{{ $f->fecha_ultimo_mov ?? '' }}</small>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="fcard-actions">
                                            <a class="btn btn-sm btn-outline-warning" data-toggle="modal"
                                                data-target="#ModalDetalle{{ $f->id }}" title="Ver detalle">
                                                <i class="far fa-eye"></i> Ver
                                            </a>
                                            @can('editar-flota')
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('flota.edit', $f->id) }}" title="Editar">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            @endcan
                                            @can('borrar-flota')
                                                <a class="btn btn-sm btn-outline-danger" data-toggle="modal"
                                                    data-target="#ModalDelete{{ $f->id }}" title="Eliminar">
                                                    <i class="far fa-trash-alt"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">
                                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No se encontraron resultados</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- DESKTOP → TABLA -->
                            <div class="table-responsive desktop-table">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>TEI</th>
                                            <th>Tipo / Modelo</th>
                                            <th>Fecha últ. mov.</th>
                                            <th>Movimiento</th>
                                            <th class="col-recurso">
                                                <i class="fas fa-car mr-1"></i>Recurso
                                            </th>
                                            <th>Dependencia</th>
                                            <th>Observaciones</th>
                                            <th class="text-center" style="width:160px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($flota as $f)
                                            @php
                                                $iconosVehiculo = [
                                                    'Auto'        => 'fas fa-car',
                                                    'Camioneta'   => 'fas fa-truck-pickup',
                                                    'Camión'      => 'fas fa-truck',
                                                    'Moto'        => 'fas fa-motorcycle',
                                                    'Helicoptero' => 'fas fa-helicopter',
                                                ];
                                                $coloresVehiculo = [
                                                    'Auto'        => 'recurso-auto',
                                                    'Camioneta'   => 'recurso-camioneta',
                                                    'Camión'      => 'recurso-camion',
                                                    'Moto'        => 'recurso-moto',
                                                    'Helicoptero' => 'recurso-helicoptero',
                                                ];
                                                $veh = $f->recurso->vehiculo ?? null;
                                                $iconoVeh = $iconosVehiculo[$veh->tipo_vehiculo ?? ''] ?? 'fas fa-home';
                                                $claseVeh = $coloresVehiculo[$veh->tipo_vehiculo ?? ''] ?? 'recurso-sin-vehiculo';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="tei-badge" href="{{ route('verHistorico', $f->id) }}" target="_blank">
                                                        {{ $f->equipo->tei }}
                                                    </a>
                                                    @if($f->equipo->issi)
                                                        <div class="issi-sub">ISSI: {{ $f->equipo->issi }}</div>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="modelo-cell">
                                                        <img width="48" class="modelo-img"
                                                            src="{{ asset($f->equipo->tipo_terminal->imagen) }}" alt="terminal">
                                                        <span class="modelo-text">
                                                            {{ $f->equipo->tipo_terminal->tipo_uso->uso }}<br>
                                                            <small>{{ $f->equipo->tipo_terminal->modelo }}</small>
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="text-nowrap">
                                                    <small>{{ $f->fecha_ultimo_mov ?? '—' }}</small>
                                                </td>

                                                <td>
                                                    <span class="badge" style="background-color: {{ $f->color_ultimo_movimiento }}; color: #fff; border-radius: 20px; padding: .25em .75em; font-size: .8rem; font-weight: 500;">
                                                        {{ $f->ultimo_movimiento ?? '—' }}
                                                    </span>
                                                </td>

                                                <td class="col-recurso">
                                                    @if($f->recurso)
                                                        <div class="recurso-cell">
                                                            <span class="badge-recurso {{ $claseVeh }}">
                                                                <i class="{{ $iconoVeh }} mr-1"></i>{{ $f->recurso->nombre }}
                                                            </span>
                                                            @if($veh)
                                                                <div class="recurso-veh-info">
                                                                    <span class="recurso-veh-detalle">{{ $veh->marca }} {{ $veh->modelo }}</span>
                                                                    <span class="recurso-veh-dominio"><i class="fas fa-id-card mr-1"></i>{{ $veh->dominio }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>

                                                <td class="dep-cell">
                                                    <span class="dep-nombre">{{ $f->destino->nombre }}</span>
                                                    <span class="dep-padre">{{ $f->destino->dependeDe() }}</span>
                                                </td>

                                                <td class="obs-cell">
                                                    @if($f->observaciones_ultimo_mov)
                                                        <span class="obs-text"
                                                              data-toggle="tooltip"
                                                              data-placement="left"
                                                              data-container="body"
                                                              title="{{ $f->observaciones_ultimo_mov }}">
                                                            {{ Str::limit($f->observaciones_ultimo_mov, 35, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>

                                                <td class="text-center action-td">
                                                    <a class="action-btn btn-view" data-toggle="modal"
                                                        data-target="#ModalDetalle{{ $f->id }}">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                    @can('editar-flota')
                                                        <a class="action-btn btn-edit" href="{{ route('flota.edit', $f->id) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('borrar-flota')
                                                        <a class="action-btn btn-del" data-toggle="modal"
                                                            data-target="#ModalDelete{{ $f->id }}">
                                                            <i class="far fa-trash-alt"></i>
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                                                    <span class="text-muted">No se encontraron resultados</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINACIÓN -->
                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                <small class="text-muted">
                                    Mostrando {{ $flota->firstItem() ?? 0 }}–{{ $flota->lastItem() ?? 0 }} de {{ $flota->total() }} registros
                                </small>
                                <div class="pagination justify-content-end mb-0">
                                    {{ $flota->appends(['texto' => $texto])->links() }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<style>
/* ─── RESPONSIVE ─── */
.mobile-cards  { display: block !important; }
.desktop-table { display: none  !important; }
@media (min-width: 768px) {
    .mobile-cards  { display: none  !important; }
    .desktop-table { display: block !important; }
}
.col-recurso { min-width: 170px; }

/* ─── MOBILE CARDS ─── */
.flota-card-modern {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 8px var(--shadow);
}
.fcard-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .8rem 1rem;
    background: linear-gradient(135deg, #6777ef, #35199a);
}
.fcard-tei a {
    color: #fff;
    font-weight: 700;
    font-size: .95rem;
    text-decoration: none;
}
.fcard-img {
    border-radius: 6px;
    border: 2px solid rgba(255,255,255,.4);
    background: rgba(255,255,255,.1);
}
.fcard-body { padding: .8rem 1rem; background: var(--card-bg); }
.fcard-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: .35rem 0;
    border-bottom: 1px solid var(--border-color);
    gap: .5rem;
}
.fcard-row:last-child { border-bottom: none; }
.fcard-label {
    color: var(--text-secondary);
    font-size: .8rem;
    white-space: nowrap;
    min-width: 110px;
}
.fcard-label i { width: 14px; }
.fcard-value { font-size: .85rem; text-align: right; color: var(--text-primary); }
.highlight-recurso { background: var(--bg-secondary); border-radius: 4px; padding: .35rem .4rem; }
.fcard-actions {
    display: flex;
    gap: .4rem;
    padding: .7rem 1rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
    flex-wrap: wrap;
}
.fcard-actions .btn { font-size: .8rem; }

/* Empty state */
.empty-state { text-align: center; padding: 3rem 1rem; }
.font-weight-500 { font-weight: 500; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });
});
</script>
@endpush
