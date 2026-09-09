@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-history"></i> Mi Historial de Descargas
                    </h4>
                    <div class="card-actions">
                        <a href="{{ route('descargas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Todos los Archivos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Estadísticas --}}
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-left-primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Descargas
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ $totalDescargas }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-download fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Descargas este Mes
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ $descargasMes }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-info">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Archivos Únicos
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                {{ $archivosUnicos }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('descargas.mi-historial') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha desde</label>
                                    <input type="date" name="fecha_desde" class="form-control" 
                                           value="{{ request('fecha_desde') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha hasta</label>
                                    <input type="date" name="fecha_hasta" class="form-control" 
                                           value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select name="categoria_id" class="form-control">
                                        <option value="">Todas</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" 
                                                    {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
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
                        </div>
                        @if(request()->hasAny(['fecha_desde', 'fecha_hasta', 'categoria_id']))
                            <div class="row">
                                <div class="col-12">
                                    <a href="{{ route('descargas.mi-historial') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-times"></i> Limpiar filtros
                                    </a>
                                </div>
                            </div>
                        @endif
                    </form>

                    {{-- Lista de descargas --}}
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Categoría</th>
                                        <th>Fecha de descarga</th>
                                        <th>IP</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>
                                                @if($log->archivo)
                                                    <i class="{{ $log->archivo->icono_extension }}"></i>
                                                    <a href="{{ route('descargas.show', $log->archivo) }}">
                                                        {{ Str::limit($log->archivo->nombre_original, 50) }}
                                                    </a>
                                                    <br>
                                                    <small class="text-muted">
                                                        Subido por: {{ $log->archivo->user->name ?? 'Sistema' }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-file-slash"></i>
                                                        Archivo eliminado
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log->archivo)
                                                    <span class="badge" style="background-color: {{ $log->archivo->categoria->color }}">
                                                        {{ $log->archivo->categoria->nombre }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $log->downloaded_at->format('d/m/Y H:i:s') }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $log->downloaded_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <code>{{ $log->ip_address }}</code>
                                            </td>
                                            <td>
                                                @if($log->archivo)
                                                    <a href="{{ route('descargas.show', $log->archivo) }}" 
                                                       class="btn btn-sm btn-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('descargas.download', $log->archivo) }}" 
                                                       class="btn btn-sm btn-success" title="Descargar nuevamente">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        <div class="d-flex justify-content-center">
                            {{ $logs->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay descargas en tu historial</p>
                            <a href="{{ route('descargas.index') }}" class="btn btn-primary">
                                <i class="fas fa-search"></i> Explorar archivos
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
