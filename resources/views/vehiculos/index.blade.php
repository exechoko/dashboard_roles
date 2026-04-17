@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Vehículos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">

                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon"><i class="fas fa-truck"></i></div>
                                <div>
                                    <h5 class="header-title">Vehículos</h5>
                                    <small class="text-muted">
                                        <span class="badge-total">{{ $vehiculos->total() }}</span> registros
                                        @if($texto) &mdash; buscando <strong>"{{ $texto }}"</strong> @endif
                                    </small>
                                </div>
                            </div>
                            @can('crear-vehiculo')
                                <a class="btn btn-nuevo" href="{{ route('vehiculos.create') }}">
                                    <i class="fas fa-plus mr-1"></i> Nuevo
                                </a>
                            @endcan
                        </div>

                        <div class="card-body pt-3">
                            <form action="{{ route('vehiculos.index') }}" method="get" onsubmit="return showLoad()" class="mb-4">
                                <div class="search-wrapper">
                                    <div class="search-icon-left"><i class="fas fa-search"></i></div>
                                    <input type="text" name="texto" class="search-input"
                                        placeholder="Buscar por marca, modelo o dominio..." value="{{ $texto }}" autocomplete="off">
                                    @if($texto)
                                        <a href="{{ route('vehiculos.index') }}" class="search-clear"><i class="fas fa-times"></i></a>
                                    @endif
                                    <button type="submit" class="btn-search"><i class="fas fa-search mr-1"></i> Buscar</button>
                                </div>
                            </form>

                            @foreach ($vehiculos as $vehiculo)
                                @include('vehiculos.modal.detalle')
                                @include('vehiculos.modal.borrar')
                            @endforeach

                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Marca</th>
                                            <th>Modelo</th>
                                            <th>Dominio</th>
                                            <th>Observaciones</th>
                                            <th class="text-center" style="width:120px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $iconosVeh = ['Auto'=>'fas fa-car','Camioneta'=>'fas fa-truck-pickup','Camión'=>'fas fa-truck','Moto'=>'fas fa-motorcycle','Helicoptero'=>'fas fa-helicopter'];
                                            $clsVeh    = ['Auto'=>'recurso-auto','Camioneta'=>'recurso-camioneta','Camión'=>'recurso-camion','Moto'=>'recurso-moto','Helicoptero'=>'recurso-helicoptero'];
                                        @endphp
                                        @forelse ($vehiculos as $vehiculo)
                                            @php
                                                $iconoV = $iconosVeh[$vehiculo->tipo_vehiculo ?? ''] ?? 'fas fa-car';
                                                $claseV = $clsVeh[$vehiculo->tipo_vehiculo ?? '']   ?? 'recurso-sin-vehiculo';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge-recurso {{ $claseV }}">
                                                        <i class="{{ $iconoV }} mr-1"></i>{{ $vehiculo->tipo_vehiculo ?? '—' }}
                                                    </span>
                                                </td>
                                                <td><span class="font-weight-500">{{ $vehiculo->marca }}</span></td>
                                                <td><small class="text-muted">{{ $vehiculo->modelo }}</small></td>
                                                <td>
                                                    <span class="tei-badge">
                                                        <i class="fas fa-id-card mr-1"></i>{{ $vehiculo->dominio }}
                                                    </span>
                                                </td>
                                                <td class="obs-cell">
                                                    @if($vehiculo->observaciones)
                                                        <span class="obs-text" data-toggle="tooltip" data-placement="left"
                                                              data-container="body" title="{{ $vehiculo->observaciones }}">
                                                            {{ Str::limit($vehiculo->observaciones, 35, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center action-td">
                                                    <a class="action-btn btn-view" data-toggle="modal"
                                                        data-target="#ModalDetalle{{ $vehiculo->id }}">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                    @can('editar-vehiculo')
                                                        <a class="action-btn btn-edit" href="{{ route('vehiculos.edit', $vehiculo->id) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('borrar-vehiculo')
                                                        <a class="action-btn btn-del" data-toggle="modal"
                                                            data-target="#ModalDelete{{ $vehiculo->id }}">
                                                            <i class="far fa-trash-alt"></i>
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
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
                                    Mostrando {{ $vehiculos->firstItem() ?? 0 }}–{{ $vehiculos->lastItem() ?? 0 }} de {{ $vehiculos->total() }}
                                </small>
                                <div class="pagination justify-content-end mb-0">
                                    {!! $vehiculos->appends(['texto' => $texto])->links() !!}
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });
});
</script>
@endpush
