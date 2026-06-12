@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">CeCoCo - Mapeo de Recursos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header-modern">
                            <div class="card-header-left">
                                <div class="header-icon"><i class="fas fa-project-diagram"></i></div>
                                <div>
                                    <h5 class="header-title">Mapeo CECOCO-CAR911</h5>
                                    <small class="text-muted">
                                        <span class="badge-total">{{ $aliases->total() }}</span> registros
                                        @if($texto) &mdash; buscando <strong>"{{ $texto }}"</strong> @endif
                                    </small>
                                </div>
                            </div>
                            @can('crear-recurso-alias-cecoco')
                                <a class="btn btn-nuevo" href="{{ route('cecoco.recursos-alias.create') }}">
                                    <i class="fas fa-plus mr-1"></i> Nuevo
                                </a>
                            @endcan
                        </div>

                        <div class="card-body pt-3">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('cecoco.recursos-alias.index') }}" method="get" onsubmit="return showLoad()" class="mb-4">
                                <div class="search-wrapper">
                                    <div class="search-icon-left"><i class="fas fa-search"></i></div>
                                    <input type="text" name="texto" class="search-input" placeholder="Buscar por alias, recurso, ISSI, TEI..." value="{{ $texto }}" autocomplete="off">
                                    @if($texto || $estado !== null && $estado !== '')
                                        <a href="{{ route('cecoco.recursos-alias.index') }}" class="search-clear"><i class="fas fa-times"></i></a>
                                    @endif
                                    <select name="estado" class="form-control search-select" style="border:none;border-left:1px solid var(--border-color);border-radius:0;max-width:170px;background:transparent;color:var(--text-primary);">
                                        <option value="">Todos</option>
                                        <option value="1" {{ $estado === '1' ? 'selected' : '' }}>Activos</option>
                                        <option value="0" {{ $estado === '0' ? 'selected' : '' }}>Inactivos</option>
                                    </select>
                                    <button type="submit" class="btn-search"><i class="fas fa-search mr-1"></i> Buscar</button>
                                </div>
                            </form>

                            @foreach ($aliases as $alias)
                                @include('cecoco.recursos-alias.modal-borrar')
                            @endforeach

                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Alias CECOCO</th>
                                            <th>Destino CAR911</th>
                                            <th>ISSI asociado</th>
                                            <th>Estado</th>
                                            <th>Observaciones</th>
                                            <th class="text-center" style="width:110px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($aliases as $alias)
                                            @php
                                                $issis = collect();
                                                if ($alias->equipo) {
                                                    $issis = collect([$alias->equipo->issi])->filter();
                                                } elseif ($alias->recurso) {
                                                    $issis = $alias->recurso->flotaActiva
                                                        ->pluck('equipo')
                                                        ->filter()
                                                        ->pluck('issi')
                                                        ->filter()
                                                        ->unique()
                                                        ->values();
                                                }

                                                $cantidadIssis = $issis->count();
                                                $textoIssis = $cantidadIssis . ' ISSI activo' . ($cantidadIssis === 1 ? '' : 's') . ' asociado' . ($cantidadIssis === 1 ? '' : 's') . ' al recurso';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-primary px-3 py-2">{{ $alias->alias_cecoco }}</span>
                                                </td>
                                                <td>
                                                    @if($alias->equipo)
                                                        <strong>Equipo específico</strong>
                                                        <div class="text-muted small">
                                                            ISSI {{ $alias->equipo->issi ?? 's/d' }}@if($alias->equipo->nombre_issi) - {{ $alias->equipo->nombre_issi }}@endif
                                                        </div>
                                                        @if($alias->recurso)
                                                            <div class="text-muted small">Contexto: {{ $alias->recurso->nombre }}</div>
                                                        @endif
                                                    @elseif($alias->recurso)
                                                        <strong>{{ $alias->recurso->nombre }}</strong>
                                                        <div class="text-muted small">Recurso completo</div>
                                                    @else
                                                        <span class="text-danger">Sin destino asociado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($issis->isNotEmpty())
                                                        <div class="mb-1">
                                                            @foreach($issis as $issi)
                                                                <span class="badge badge-info mr-1 mb-1">ISSI {{ $issi }}</span>
                                                            @endforeach
                                                        </div>
                                                        @if($alias->equipo)
                                                            <small class="text-muted">
                                                                Equipo asociado
                                                                @if($alias->equipo->tei)
                                                                    - TEI {{ $alias->equipo->tei }}
                                                                @endif
                                                            </small>
                                                        @else
                                                            <small class="text-muted">
                                                                {{ $textoIssis }}
                                                            </small>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Sin ISSI asociado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($alias->activo)
                                                        <span class="badge badge-success">Activo</span>
                                                    @else
                                                        <span class="badge badge-secondary">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="obs-cell">
                                                    @if($alias->observaciones)
                                                        <span data-toggle="tooltip" data-placement="left" data-container="body" title="{{ $alias->observaciones }}">
                                                            {{ Str::limit($alias->observaciones, 45, '…') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center action-td">
                                                    @can('editar-recurso-alias-cecoco')
                                                        <a class="action-btn btn-edit" href="{{ route('cecoco.recursos-alias.edit', $alias) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('borrar-recurso-alias-cecoco')
                                                        <a class="action-btn btn-del" data-toggle="modal" data-target="#ModalDeleteAlias{{ $alias->id }}">
                                                            <i class="far fa-trash-alt"></i>
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                                                    <span class="text-muted">No se encontraron mapeos cargados</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                                <small class="text-muted">
                                    Mostrando {{ $aliases->firstItem() ?? 0 }}–{{ $aliases->lastItem() ?? 0 }} de {{ $aliases->total() }}
                                </small>
                                <div class="pagination justify-content-end mb-0">
                                    {!! $aliases->links() !!}
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
            $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });
        });
    </script>
@endsection
