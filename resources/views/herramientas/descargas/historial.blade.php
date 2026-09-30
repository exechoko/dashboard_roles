@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-history mr-2"></i>Mi Historial</h3>
    </div>

    <div class="section-body">
        @include('herramientas.descargas.partials._nav')

        {{-- Estadísticas --}}
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header"><h4>Total descargas</h4></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-primary">{{ $totalDescargas }}</h2>
                                    <p class="text-muted mb-0">Desde siempre</p>
                                </div>
                                <div class="text-primary"><i class="fas fa-download fa-3x"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header"><h4>Este mes</h4></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-success">{{ $descargasMes }}</h2>
                                    <p class="text-muted mb-0">Descargas de {{ now()->translatedFormat('F') }}</p>
                                </div>
                                <div class="text-success"><i class="fas fa-calendar fa-3x"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-statistic-1">
                    <div class="card-wrap">
                        <div class="card-header"><h4>Archivos únicos</h4></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h2 class="text-info">{{ $archivosUnicos }}</h2>
                                    <p class="text-muted mb-0">Distintos archivos descargados</p>
                                </div>
                                <div class="text-info"><i class="fas fa-file fa-3x"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.mi-historial') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Fecha desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Fecha hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            @if(request()->hasAny(['fecha_desde', 'fecha_hasta', 'categoria_id']))
                                <a href="{{ route('descargas.mi-historial') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Descargas <span class="badge badge-secondary ml-2">{{ $logs->total() }}</span></h5>
            </div>
            <div class="card-body p-0">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Archivo</th>
                                    <th>Categoría</th>
                                    <th>Fecha de descarga</th>
                                    <th>IP</th>
                                    <th style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>
                                            @if($log->archivo)
                                                <i class="{{ $log->archivo->icono_extension }} mr-1"></i>
                                                <a href="{{ route('descargas.show', $log->archivo) }}">
                                                    {{ Str::limit($log->archivo->nombre_original, 50) }}
                                                </a>
                                                <br>
                                                <small class="text-muted">Subido por: {{ $log->archivo->user->name ?? 'Sistema' }}</small>
                                            @else
                                                <span class="text-muted"><i class="fas fa-ban mr-1"></i>Archivo eliminado</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->archivo)
                                                <span class="badge descarga-badge-categoria" style="--categoria-color: {{ $log->archivo->categoria->color }}">
                                                    {{ $log->archivo->categoria->nombre }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $log->downloaded_at->format('d/m/Y H:i:s') }}
                                            <br>
                                            <small class="text-muted">{{ $log->downloaded_at->diffForHumans() }}</small>
                                        </td>
                                        <td><code>{{ $log->ip_address }}</code></td>
                                        <td>
                                            @if($log->archivo)
                                                <a href="{{ route('descargas.show', $log->archivo) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('descargas.download', $log->archivo) }}" class="btn btn-sm btn-success" title="Descargar nuevamente">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
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
                    <div class="descargas-empty">
                        <i class="fas fa-history"></i>
                        <p class="mb-3">No hay descargas en tu historial.</p>
                        <a href="{{ route('descargas.index') }}" class="btn btn-primary">
                            <i class="fas fa-search"></i> Explorar archivos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@include('herramientas.descargas.partials._styles')
@endsection
