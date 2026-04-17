@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Equipamientos - Terminales</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">

                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon"><i class="fas fa-microchip"></i></div>
                                <div>
                                    <h5 class="header-title">Terminales</h5>
                                    <small class="text-muted">
                                        <span class="badge-total">{{ $equipos->total() }}</span> registros
                                        @if($texto) &mdash; buscando <strong>"{{ $texto }}"</strong> @endif
                                    </small>
                                </div>
                            </div>
                            @can('crear-equipo')
                                <a class="btn btn-nuevo" href="{{ route('equipos.create') }}">
                                    <i class="fas fa-plus mr-1"></i> Nuevo
                                </a>
                            @endcan
                        </div>

                        <div class="card-body pt-3">
                            <form action="{{ route('equipos.index') }}" method="get" onsubmit="return showLoad()" class="mb-4">
                                <div class="search-wrapper">
                                    <div class="search-icon-left"><i class="fas fa-search"></i></div>
                                    <input type="text" name="texto" class="search-input"
                                        placeholder="Buscar por TEI o ISSI..." value="{{ $texto }}" autocomplete="off">
                                    @if($texto)
                                        <a href="{{ route('equipos.index') }}" class="search-clear"><i class="fas fa-times"></i></a>
                                    @endif
                                    <button type="submit" class="btn-search"><i class="fas fa-search mr-1"></i> Buscar</button>
                                </div>
                            </form>

                            @foreach ($equipos as $equipo)
                                @include('equipos.modal.detalle')
                                @include('equipos.modal.borrar')
                            @endforeach

                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>TEI</th>
                                            <th>Tipo / Marca / Modelo</th>
                                            <th>ISSI</th>
                                            <th>Estado</th>
                                            <th>Observaciones</th>
                                            <th class="text-center" style="width:120px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($equipos as $equipo)
                                            <tr>
                                                <td>
                                                    <a class="tei-badge" href="{{ route('verHistoricoDesdeEquipo', $equipo->id) }}" target="_blank">
                                                        {{ $equipo->tei }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="modelo-cell">
                                                        <img width="50" class="modelo-img"
                                                            src="{{ asset($equipo->tipo_terminal->imagen) }}" alt="">
                                                        <span class="modelo-text">
                                                            {{ $equipo->tipo_terminal->tipo_uso->uso }}<br>
                                                            <small>{{ $equipo->tipo_terminal->marca }} / {{ $equipo->tipo_terminal->modelo }}</small>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td><small class="text-muted">{{ $equipo->issi ?? '—' }}</small></td>
                                                <td>
                                                    <span class="estado-badge">{{ $equipo->estado->nombre }}</span>
                                                </td>
                                                <td class="obs-cell">
                                                    @if($equipo->observaciones)
                                                        <span class="obs-text" data-toggle="tooltip" data-placement="left"
                                                              data-container="body" title="{{ $equipo->observaciones }}">
                                                            {{ Str::limit($equipo->observaciones, 35, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center action-td">
                                                    <a class="action-btn btn-view" data-toggle="modal"
                                                        data-target="#ModalDetalle{{ $equipo->id }}">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                    @can('editar-equipo')
                                                        <a class="action-btn btn-edit" href="{{ route('equipos.edit', $equipo->id) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('borrar-equipo')
                                                        <a class="action-btn btn-del" data-toggle="modal"
                                                            data-target="#ModalDelete{{ $equipo->id }}">
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
                                    Mostrando {{ $equipos->firstItem() ?? 0 }}–{{ $equipos->lastItem() ?? 0 }} de {{ $equipos->total() }}
                                </small>
                                <div class="pagination justify-content-end mb-0">
                                    {{ $equipos->appends(['texto' => $texto])->links() }}
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
