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
                    <a href="{{ route('incidencias.tickets-pg.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Ticket PG
                    </a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código interno</th>
                                <th>Ticketera</th>
                                <th>Asunto</th>
                                <th>Estado</th>
                                <th>Creado</th>
                                <th class="text-center" style="width:130px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td><strong>{{ $ticket->codigo_interno }}</strong></td>
                                    <td>{{ $ticket->codigo_ticketera ?: '-' }}</td>
                                    <td>{{ $ticket->asunto }}</td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->estado_envio === 'enviado' ? 'success' : ($ticket->estado_envio === 'error' ? 'danger' : 'secondary') }}">
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
                                    <td colspan="6" class="text-center text-muted py-4">No hay tickets PG cargados.</td>
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
