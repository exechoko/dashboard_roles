@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Solicitudes para Compartir Archivos</h4>
                    <div class="card-actions">
                        <a href="{{ route('descargas.admin.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('descargas.admin.solicitudes') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Buscar por nombre de archivo</label>
                                    <input type="text" name="buscar" class="form-control" 
                                           value="{{ request('buscar') }}" 
                                           placeholder="Nombre del archivo...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="aprobado" {{ request('estado') == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                                        <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-filter"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <a href="{{ route('descargas.admin.solicitudes') }}" class="btn btn-secondary btn-block">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Tabla de solicitudes --}}
                    @if($solicitudes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Solicitado por</th>
                                        <th>Compartir con</th>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solicitudes as $solicitud)
                                        <tr>
                                            <td>
                                                <a href="{{ route('descargas.show', $solicitud->archivo) }}" target="_blank">
                                                    {{ Str::limit($solicitud->archivo->nombre_original, 30) }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $solicitud->archivo->categoria->nombre }}</small>
                                            </td>
                                            <td>
                                                {{ $solicitud->usuarioSolicita->name }}
                                                <br>
                                                <small class="text-muted">{{ $solicitud->usuarioSolicita->email }}</small>
                                            </td>
                                            <td>
                                                {{ $solicitud->usuarioDestino->name }}
                                                <br>
                                                <small class="text-muted">{{ $solicitud->usuarioDestino->email }}</small>
                                            </td>
                                            <td>
                                                @if($solicitud->motivo)
                                                    {{ Str::limit($solicitud->motivo, 50) }}
                                                @else
                                                    <span class="text-muted">Sin motivo</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($solicitud->estado == 'pendiente')
                                                    <span class="badge badge-warning">Pendiente</span>
                                                @elseif($solicitud->estado == 'aprobado')
                                                    <span class="badge badge-success">Aprobado</span>
                                                @else
                                                    <span class="badge badge-danger">Rechazado</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $solicitud->created_at->format('d/m/Y H:i') }}
                                                @if($solicitud->respondido_at)
                                                    <br>
                                                    <small class="text-muted">
                                                        Respondido: {{ $solicitud->respondido_at->format('d/m/Y H:i') }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($solicitud->estado == 'pendiente')
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            data-toggle="modal" 
                                                            data-target="#modalAprobar{{ $solicitud->id }}">
                                                        <i class="fas fa-check"></i> Aprobar
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            data-toggle="modal" 
                                                            data-target="#modalRechazar{{ $solicitud->id }}">
                                                        <i class="fas fa-times"></i> Rechazar
                                                    </button>
                                                @else
                                                    <small class="text-muted">
                                                        Por: {{ $solicitud->aprobadoPor->name ?? 'N/A' }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Modal Aprobar --}}
                                        <div class="modal fade" id="modalAprobar{{ $solicitud->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('descargas.admin.solicitudes.aprobar', $solicitud) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Aprobar Solicitud</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de aprobar esta solicitud?</p>
                                                            <p><strong>Archivo:</strong> {{ $solicitud->archivo->nombre_original }}</p>
                                                            <p><strong>Compartir con:</strong> {{ $solicitud->usuarioDestino->name }}</p>
                                                            <div class="alert alert-info">
                                                                <i class="fas fa-info-circle"></i>
                                                                El usuario {{ $solicitud->usuarioDestino->name }} recibirá acceso directo al archivo y será notificado por email.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-check"></i> Aprobar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Modal Rechazar --}}
                                        <div class="modal fade" id="modalRechazar{{ $solicitud->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('descargas.admin.solicitudes.rechazar', $solicitud) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Rechazar Solicitud</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de rechazar esta solicitud?</p>
                                                            <p><strong>Archivo:</strong> {{ $solicitud->archivo->nombre_original }}</p>
                                                            <p><strong>Solicitado por:</strong> {{ $solicitud->usuarioSolicita->name }}</p>
                                                            <div class="form-group">
                                                                <label>Motivo del rechazo (opcional)</label>
                                                                <textarea name="motivo_respuesta" class="form-control" rows="3" 
                                                                          placeholder="Explica por qué se rechaza la solicitud..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times"></i> Rechazar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        <div class="d-flex justify-content-center">
                            {{ $solicitudes->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay solicitudes para mostrar</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
