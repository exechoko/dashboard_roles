@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-history mr-2"></i>Historial de Descargas</h3>
        <div>
            <a href="{{ route('descargas.admin.exportar_logs', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="{{ route('descargas.admin.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.admin.logs') }}" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Fecha desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Fecha hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Usuario ID</label>
                        <input type="number" name="user_id" class="form-control" value="{{ request('user_id') }}" placeholder="ID de usuario">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="{{ route('descargas.admin.logs') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de logs --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Registros de descarga
                    <span class="badge badge-secondary ml-2">{{ $logs->total() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Archivo</th>
                                    <th>Usuario</th>
                                    <th>IP</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>{{ $log->downloaded_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            @if($log->archivo)
                                                <a href="{{ route('descargas.show', $log->archivo) }}">
                                                    {{ Str::limit($log->archivo->nombre_original, 35) }}
                                                </a>
                                            @else
                                                <span class="text-muted">Archivo eliminado</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->user)
                                                {{ $log->user->name }}
                                            @elseif($log->link_publico_id)
                                                <span class="badge badge-warning">Link público</span>
                                            @else
                                                <span class="text-muted">Anónimo</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $log->ip_address }}</code></td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($log->user_agent, 40) }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $logs->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No se encontraron registros de descarga.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
