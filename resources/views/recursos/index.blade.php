@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Recursos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">

                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon"><i class="fas fa-car"></i></div>
                                <div>
                                    <h5 class="header-title">Recursos</h5>
                                    <small class="text-muted">
                                        <span class="badge-total">{{ $recursos->total() }}</span> registros
                                        @if($texto) &mdash; buscando <strong>"{{ $texto }}"</strong> @endif
                                    </small>
                                </div>
                            </div>
                            @can('crear-recurso')
                                <a class="btn btn-nuevo" href="{{ route('recursos.create') }}">
                                    <i class="fas fa-plus mr-1"></i> Nuevo
                                </a>
                            @endcan
                        </div>

                        <div class="card-body pt-3">
                            <form action="{{ route('recursos.index') }}" method="get" onsubmit="return showLoad()" class="mb-4">
                                <div class="search-wrapper">
                                    <div class="search-icon-left"><i class="fas fa-search"></i></div>
                                    <input type="text" name="texto" class="search-input"
                                        placeholder="Buscar por nombre de recurso..." value="{{ $texto }}" autocomplete="off">
                                    @if($texto)
                                        <a href="{{ route('recursos.index') }}" class="search-clear"><i class="fas fa-times"></i></a>
                                    @endif
                                    <select name="dependencia_id" class="form-control search-select select2"
                                        style="border:none;border-left:1px solid var(--border-color);border-radius:0;max-width:240px;background:transparent;color:var(--text-primary);">
                                        <option value="">Todas las dependencias</option>
                                        @foreach ($dependencias as $dependencia)
                                            <option value="{{ $dependencia->id }}" {{ $dependencia_seleccionada == $dependencia->id ? 'selected' : '' }}>
                                                {{ $dependencia->nombre . ' - ' . $dependencia->dependeDe() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-search"><i class="fas fa-search mr-1"></i> Buscar</button>
                                </div>
                            </form>

                            @foreach ($recursos as $recurso)
                                @include('recursos.modal.detalle')
                                @include('recursos.modal.borrar')
                            @endforeach

                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th style="min-width:180px;">Vehículo</th>
                                            <th>Dependencia</th>
                                            <th>Observaciones</th>
                                            <th class="text-center" style="width:120px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recursos as $recurso)
                                            @php
                                                $iconosR = ['Auto'=>'fas fa-car','Camioneta'=>'fas fa-truck-pickup','Camión'=>'fas fa-truck','Moto'=>'fas fa-motorcycle','Helicoptero'=>'fas fa-helicopter'];
                                                $clsR    = ['Auto'=>'recurso-auto','Camioneta'=>'recurso-camioneta','Camión'=>'recurso-camion','Moto'=>'recurso-moto','Helicoptero'=>'recurso-helicoptero'];
                                                $vehR    = $recurso->vehiculo ?? null;
                                                $iconoR  = $iconosR[$vehR->tipo_vehiculo ?? ''] ?? 'fas fa-home';
                                                $claseR  = $clsR[$vehR->tipo_vehiculo ?? '']   ?? 'recurso-sin-vehiculo';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge-recurso {{ $claseR }}">
                                                        <i class="{{ $iconoR }} mr-1"></i>{{ $recurso->nombre }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($vehR)
                                                        <div class="recurso-veh-info">
                                                            <span class="recurso-veh-detalle">{{ $vehR->marca }} {{ $vehR->modelo }}</span>
                                                            <span class="recurso-veh-dominio"><i class="fas fa-id-card mr-1"></i>{{ $vehR->dominio }}</span>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="dep-cell">
                                                    <span class="dep-nombre">{{ $recurso->destino->nombre }}</span>
                                                    <span class="dep-padre">{{ $recurso->destino->dependeDe() }}</span>
                                                </td>
                                                <td class="obs-cell">
                                                    @if($recurso->observaciones)
                                                        <span class="obs-text" data-toggle="tooltip" data-placement="left"
                                                              data-container="body" title="{{ $recurso->observaciones }}">
                                                            {{ Str::limit($recurso->observaciones, 35, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center action-td">
                                                    <a class="action-btn btn-view" data-toggle="modal"
                                                        data-target="#ModalDetalle{{ $recurso->id }}">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                    @can('editar-recurso')
                                                        <a class="action-btn btn-edit" href="{{ route('recursos.edit', $recurso->id) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('borrar-recurso')
                                                        <a class="action-btn btn-del" data-toggle="modal"
                                                            data-target="#ModalDelete{{ $recurso->id }}">
                                                            <i class="far fa-trash-alt"></i>
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                                                    <span class="text-muted">No se encontraron resultados</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                                <small class="text-muted">
                                    Mostrando {{ $recursos->firstItem() ?? 0 }}–{{ $recursos->lastItem() ?? 0 }} de {{ $recursos->total() }}
                                </small>
                                <div class="pagination justify-content-end mb-0">
                                    {!! $recursos->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $('.select2').select2({ width: '100%' });
            $(document).on('select2:open', () => {
                let f = document.querySelector('.select2-search__field');
                if (f) f.focus();
            });
            $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });
        });
    </script>
@endsection
