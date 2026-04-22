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

                            <div class="task-cards-wrapper">
                                <div class="row task-cards-row">
                                    @forelse($items as $item)
                                        @php
                                            $statusClasses = [
                                                \App\Models\TareaItem::ESTADO_PENDIENTE => 'task-card--pending',
                                                \App\Models\TareaItem::ESTADO_EN_PROCESO => 'task-card--progress',
                                                \App\Models\TareaItem::ESTADO_REALIZADA => 'task-card--done',
                                            ];
                                            $badge = 'secondary';
                                            if ($item->estado === \App\Models\TareaItem::ESTADO_PENDIENTE) $badge = 'warning';
                                            if ($item->estado === \App\Models\TareaItem::ESTADO_EN_PROCESO) $badge = 'info';
                                            if ($item->estado === \App\Models\TareaItem::ESTADO_REALIZADA) $badge = 'success';
                                        @endphp
                                        <div class="col-md-6 col-lg-4 task-card-col">
                                            <div class="card task-card {{ $statusClasses[$item->estado] ?? '' }}">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h5 class="mb-1">{{ $item->tarea->nombre ?? 'Tarea sin nombre' }}</h5>
                                                            <small class="text-muted">ID tarea: {{ $item->tarea_id }}</small>
                                                        </div>
                                                        <span class="badge badge-{{ $badge }}">{{ $estados[$item->estado] ?? $item->estado }}</span>
                                                    </div>

                                                    <ul class="list-unstyled task-card-details mb-3">
                                                        <li>
                                                            <i class="fas fa-calendar-day text-primary mr-2"></i>
                                                            <strong>Programada:</strong>
                                                            {{ optional($item->fecha_programada)->format('d/m/Y') ?? 'Sin fecha' }}
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-user text-info mr-2"></i>
                                                            <strong>Responsable:</strong>
                                                            {{ $item->realizadoPor->name ?? '-' }}
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check-circle text-success mr-2"></i>
                                                            <strong>Realizada:</strong>
                                                            {{ $item->fecha_realizada ? $item->fecha_realizada->format('d/m/Y H:i') : '-' }}
                                                        </li>
                                                    </ul>

                                                    <div class="task-card-observaciones mb-3">
                                                        <small class="text-muted d-block mb-1">Observaciones</small>
                                                        <p class="mb-0 text-break">{{ $item->observaciones ? \Illuminate\Support\Str::limit($item->observaciones, 140) : 'Sin observaciones' }}</p>
                                                    </div>

                                                    @can('editar-tarea')
                                                        <form action="{{ route('tareas.items.update', $item->id) }}" method="POST" class="task-card-actions">
                                                            @csrf
                                                            @method('PATCH')

                                                            <div class="form-row align-items-center">
                                                                <div class="col-sm-5 mb-2">
                                                                    <label class="sr-only" for="estado-{{ $item->id }}">Estado</label>
                                                                    <select id="estado-{{ $item->id }}" name="estado" class="form-control">
                                                                        @foreach($estados as $key => $label)
                                                                            <option value="{{ $key }}" {{ $item->estado === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-sm-7 mb-2">
                                                                    <label class="sr-only" for="observaciones-{{ $item->id }}">Observaciones</label>
                                                                    <input id="observaciones-{{ $item->id }}" type="text" name="observaciones" class="form-control" placeholder="Observaciones" value="{{ $item->observaciones }}">
                                                                </div>
                                                            </div>

                                                            <div class="d-flex align-items-center flex-wrap task-card-buttons">
                                                                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2" title="Guardar">
                                                                    <i class="fas fa-save mr-1"></i> Guardar
                                                                </button>

                                                                <a href="{{ route('tareas.edit', $item->tarea_id) }}" class="btn btn-warning btn-sm mr-2 mb-2" title="Editar tarea">
                                                                    <i class="fas fa-edit mr-1"></i> Editar
                                                                </a>

                                                                @can('borrar-tarea')
                                                                    <button type="button" class="btn btn-danger btn-sm mb-2" title="Eliminar tarea"
                                                                            onclick="if(confirm('¿Eliminar la tarea y todas sus instancias?')) document.getElementById('delete-tarea-{{ $item->tarea_id }}').submit();">
                                                                        <i class="fas fa-trash mr-1"></i> Eliminar
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
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-light text-center text-muted mb-0">
                                                <i class="fas fa-info-circle mr-1"></i> Sin resultados
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
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

    .task-cards-row{display:flex;flex-wrap:wrap;}
    .task-card-col{display:flex;margin-bottom:1.5rem;}
    .task-card{width:100%;display:flex;flex-direction:column;transition:all .2s;border-left:4px solid transparent;}
    .task-card:hover{transform:translateY(-4px);box-shadow:0 8px 20px rgba(0,0,0,.08);}
    .task-card--pending{border-color:#ffc10733;}
    .task-card--progress{border-color:#17a2b833;}
    .task-card--done{border-color:#28a74533;}
    .task-card-details li{margin-bottom:.35rem;font-size:.9rem;}
    .task-card-observaciones{background:#f8f9fa;border-radius:8px;padding:.75rem;}
    .task-card-actions{border-top:1px solid #f0f0f0;padding-top:1rem;margin-top:1.5rem;}
    .task-card-buttons button,
    .task-card-buttons a{min-width:95px;}

    @media (max-width: 575.98px){
        .task-card-buttons button,
        .task-card-buttons a{width:100%;}
        .task-card-buttons{width:100%;}
    }
</style>
@endpush
