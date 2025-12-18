@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-tasks"></i> Tareas</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card card-mobile-optimized">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h4 class="card-title mb-2 mb-md-0">Listado</h4>
                                @can('crear-tarea')
                                    <a href="{{ route('tareas.create') }}" class="btn btn-primary btn-lg-mobile">
                                        <i class="fas fa-plus"></i> Nueva Tarea
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="search-container mb-4">
                                <button class="btn btn-outline-info btn-block d-md-none mb-2" type="button" data-toggle="collapse" data-target="#searchForm">
                                    <i class="fas fa-search"></i> Mostrar/Ocultar Búsqueda
                                </button>

                                <div class="collapse d-md-block" id="searchForm">
                                    <form method="GET" action="{{ route('tareas.index') }}">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="vista" class="form-control">
                                                    <option value="proximas" {{ (request('vista','proximas') === 'proximas') ? 'selected' : '' }}>Próximas</option>
                                                    <option value="todas" {{ (request('vista') === 'todas') ? 'selected' : '' }}>Todas</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <input type="text" name="nombre" class="form-control" placeholder="Nombre de tarea" value="{{ request('nombre') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <select name="estado" class="form-control select2">
                                                    <option value="">Todos los estados</option>
                                                    @foreach($estados as $key => $label)
                                                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if(request('vista','proximas') !== 'proximas')
                                                <div class="col-12 col-md-6 col-lg-3">
                                                    <select name="realizado_por" class="form-control select2">
                                                        <option value="">Realizado por (todos)</option>
                                                        @foreach($usuarios as $usuario)
                                                            <option value="{{ $usuario->id }}" {{ (string) request('realizado_por') === (string) $usuario->id ? 'selected' : '' }}>
                                                                {{ $usuario->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                            <div class="col-12 col-md-6 col-lg-4">
                                                <input type="text" name="observaciones" class="form-control" placeholder="Observaciones" value="{{ request('observaciones') }}">
                                            </div>
                                        </div>

                                        <div class="row g-2 mt-2">
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <label class="mb-1">Fecha programada (desde)</label>
                                                <input type="date" name="fecha_programada_desde" class="form-control" value="{{ request('fecha_programada_desde') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <label class="mb-1">Fecha programada (hasta)</label>
                                                <input type="date" name="fecha_programada_hasta" class="form-control" value="{{ request('fecha_programada_hasta') }}">
                                            </div>
                                            @if(request('vista','proximas') !== 'proximas')
                                                <div class="col-12 col-md-6 col-lg-3">
                                                    <label class="mb-1">Fecha realizada (desde)</label>
                                                    <input type="date" name="fecha_realizada_desde" class="form-control" value="{{ request('fecha_realizada_desde') }}">
                                                </div>
                                                <div class="col-12 col-md-6 col-lg-3">
                                                    <label class="mb-1">Fecha realizada (hasta)</label>
                                                    <input type="date" name="fecha_realizada_hasta" class="form-control" value="{{ request('fecha_realizada_hasta') }}">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-12 text-right">
                                                <button type="submit" class="btn btn-info">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                                <a href="{{ route('tareas.index') }}" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Limpiar
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped mobile-table">
                                    <thead>
                                        <tr>
                                            <th>Tarea</th>
                                            <th>Fecha programada</th>
                                            <th>Estado</th>
                                            <th>Realizado por</th>
                                            <th>Fecha realizada</th>
                                            <th>Observaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $item)
                                            <tr class="mobile-table-row">
                                                <td data-label="Tarea">
                                                    <div class="font-weight-bold">{{ $item->tarea->nombre ?? '-' }}</div>
                                                    <small class="text-muted">ID tarea: {{ $item->tarea_id }}</small>
                                                </td>
                                                <td data-label="Fecha programada">
                                                    {{ optional($item->fecha_programada)->format('d/m/Y') }}
                                                </td>
                                                <td data-label="Estado">
                                                    @php
                                                        $badge = 'secondary';
                                                        if ($item->estado === \App\Models\TareaItem::ESTADO_PENDIENTE) $badge = 'warning';
                                                        if ($item->estado === \App\Models\TareaItem::ESTADO_EN_PROCESO) $badge = 'info';
                                                        if ($item->estado === \App\Models\TareaItem::ESTADO_REALIZADA) $badge = 'success';
                                                    @endphp
                                                    <span class="badge badge-{{ $badge }}">{{ $estados[$item->estado] ?? $item->estado }}</span>
                                                </td>
                                                <td data-label="Realizado por" class="d-none d-md-table-cell">
                                                    {{ $item->realizadoPor->name ?? '-' }}
                                                </td>
                                                <td data-label="Fecha realizada" class="d-none d-md-table-cell">
                                                    {{ $item->fecha_realizada ? $item->fecha_realizada->format('d/m/Y H:i') : '-' }}
                                                </td>
                                                <td data-label="Observaciones" class="text-truncate" style="max-width: 260px;" title="{{ $item->observaciones }}">
                                                    {{ $item->observaciones ? \Illuminate\Support\Str::limit($item->observaciones, 60) : '-' }}
                                                </td>
                                                <td data-label="Acciones" style="min-width: 260px;">
                                                    @can('editar-tarea')
                                                        <form action="{{ route('tareas.items.update', $item->id) }}" method="POST" class="mb-2">
                                                            @csrf
                                                            @method('PATCH')

                                                            <div class="d-flex flex-row flex-nowrap align-items-center mb-2" style="gap: 8px;">
                                                                <div style="width: 140px;">
                                                                    <select name="estado" class="form-control">
                                                                        @foreach($estados as $key => $label)
                                                                            <option value="{{ $key }}" {{ $item->estado === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <input type="text" name="observaciones" class="form-control" placeholder="Observaciones" value="{{ $item->observaciones }}">
                                                                </div>
                                                            </div>

                                                            <div class="d-flex flex-row flex-nowrap align-items-center" style="gap: 6px;">
                                                                <button type="submit" class="btn btn-primary btn-sm btn-mobile-action" title="Guardar">
                                                                    <i class="fas fa-save"></i>
                                                                </button>

                                                                <a href="{{ route('tareas.edit', $item->tarea_id) }}" class="btn btn-warning btn-sm btn-mobile-action" title="Editar tarea">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>

                                                                @can('borrar-tarea')
                                                                    <button type="button" class="btn btn-danger btn-sm btn-mobile-action" title="Eliminar tarea"
                                                                            onclick="if(confirm('¿Eliminar la tarea y todas sus instancias?')) document.getElementById('delete-tarea-{{ $item->tarea_id }}').submit();">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                @endcan
                                                            </div>
                                                        </form>

                                                        @can('borrar-tarea')
                                                            <form id="delete-tarea-{{ $item->tarea_id }}" action="{{ route('tareas.destroy', $item->tarea_id) }}" method="POST" class="d-none">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        @endcan
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">Sin resultados</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center">
                                {{ $items->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .card-mobile-optimized{border-radius:10px;overflow:hidden;}
    .btn-lg-mobile{padding:12px 20px;font-size:16px;}
    .btn-mobile-action{min-width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;margin:2px;}
    .action-buttons{display:flex;flex-wrap:wrap;gap:4px;justify-content:center;}
    @media (max-width: 767.98px){
        .mobile-table{border:0;}
        .mobile-table thead{display:none;}
        .mobile-table-row{display:block;margin-bottom:1rem;border:1px solid #dee2e6;border-radius:0.375rem;padding:0.75rem;}
        .mobile-table-row td{display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid #f8f9fa;}
        .mobile-table-row td:last-child{border-bottom:none;}
        .mobile-table-row td::before{content:attr(data-label);font-weight:bold;margin-right:1rem;flex:0 0 40%;}
        .mobile-table-row td:last-child::before{display:none;}
    }
</style>
@endpush
