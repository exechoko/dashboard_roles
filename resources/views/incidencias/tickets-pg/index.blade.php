@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-ticket-alt"></i> Tickets PG</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Borradores y enviados</h4>
                @can('crear-ticket-pg')
                    <div>
                        <a href="{{ route('incidencias.tickets-pg.importar') }}" class="btn btn-outline-info">
                            <i class="fas fa-file-import"></i> Importar Excel
                        </a>
                        <a href="{{ route('incidencias.tickets-pg.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Ticket PG
                        </a>
                    </div>
                @endcan
            </div>
            <div class="card-body border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: .5rem;">
                    <form method="GET" action="{{ route('incidencias.tickets-pg.index') }}" class="flex-grow-1" style="max-width: 480px;">
                        @if($estadoFiltro !== '')
                            <input type="hidden" name="estado" value="{{ $estadoFiltro }}">
                        @endif
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" value="{{ $busqueda }}"
                                   placeholder="Buscar por texto, código PG, nro. o ID de ref. de la ticketera...">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if($busqueda !== '')
                                    <a href="{{ route('incidencias.tickets-pg.index', array_filter(['estado' => $estadoFiltro])) }}" class="btn btn-light" title="Limpiar búsqueda">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                    @php
                        $filtrosEstado = ['' => 'Todos', 'nuevos' => 'Nuevos', 'en_progreso' => 'En progreso', 'resueltos' => 'Resueltos'];
                    @endphp
                    <div class="btn-group">
                        @foreach($filtrosEstado as $claveFiltro => $etiquetaFiltro)
                            <a href="{{ route('incidencias.tickets-pg.index', array_filter(['q' => $busqueda, 'estado' => $claveFiltro])) }}"
                               class="btn btn-sm {{ $estadoFiltro === $claveFiltro ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ $etiquetaFiltro }}
                                <span class="badge badge-{{ $estadoFiltro === $claveFiltro ? 'light' : 'primary' }} ml-1">{{ $conteosPorEstado[$claveFiltro] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código interno</th>
                                <th>Ticketera</th>
                                <th>Asunto</th>
                                <th>Estado ticket</th>
                                <th>Envío</th>
                                <th>Creado</th>
                                <th class="text-center" style="width:130px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td><strong>{{ $ticket->codigo_interno }}</strong></td>
                                    <td>
                                        {{ $ticket->codigo_ticketera ?: '-' }}
                                        @if($ticket->referencia_ticketera)
                                            <small class="text-muted d-block">{{ $ticket->referencia_ticketera }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $ticket->asunto }}</td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->colorEstadoTicketera() }}">
                                            {{ $ticket->estadoTicketeraLegible() }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $colorEnvio = [
                                                'enviado'   => 'success',
                                                'error'     => 'danger',
                                                'importado' => 'info',
                                            ][$ticket->estado_envio] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $colorEnvio }}">
                                            {{ strtoupper($ticket->estado_envio) }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('incidencias.tickets-pg.show', $ticket) }}" class="btn btn-sm btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('editar-ticket-pg')
                                        @if(!$ticket->estaEnviado())
                                            <a href="{{ route('incidencias.tickets-pg.edit', $ticket) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        {{ $busqueda !== '' ? 'No se encontraron tickets para "' . $busqueda . '".' : 'No hay tickets PG cargados.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($tickets->hasPages())
                <div class="card-footer">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
