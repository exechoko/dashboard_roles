@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema — Logs</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Filtros</h4>
                    <div class="card-header-action">
                        <label class="badge badge-dark">Entradas: {{ $entradas->total() }}</label>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('configuracion.logs') }}" method="get">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Archivo</label>
                                <select name="archivo" class="form-control" onchange="this.form.submit()">
                                    @forelse ($archivos as $a)
                                        <option value="{{ $a['nombre'] }}" {{ $archivo == $a['nombre'] ? 'selected' : '' }}>
                                            {{ $a['nombre'] }} ({{ $a['tamano_mb'] }} MB)
                                        </option>
                                    @empty
                                        <option value="">No hay archivos de log</option>
                                    @endforelse
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Nivel</label>
                                <select name="nivel" class="form-control">
                                    <option value="">Todos los niveles</option>
                                    @foreach ($niveles as $n)
                                        <option value="{{ $n }}" {{ $nivel == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label>Búsqueda de texto</label>
                                <input type="text" name="texto" class="form-control"
                                       placeholder="Buscar en mensaje o stack trace..."
                                       value="{{ $texto }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('configuracion.logs') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar filtros
                                </a>
                                @if ($archivo)
                                    <a href="{{ route('configuracion.logs.descargar', $archivo) }}" class="btn btn-outline-dark float-right">
                                        <i class="fas fa-download"></i> Descargar archivo completo
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @forelse ($entradas as $entrada)
                        @php
                            $colorNivel = match ($entrada['nivel']) {
                                'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
                                'WARNING' => 'warning',
                                'NOTICE', 'INFO' => 'info',
                                default => 'secondary',
                            };
                        @endphp
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge badge-{{ $colorNivel }}">{{ $entrada['nivel'] }}</span>
                                        <small class="text-muted ml-2">{{ $entrada['fecha'] }}</small>
                                        <span class="badge badge-light">{{ $entrada['entorno'] }}</span>
                                    </div>
                                </div>
                                <p class="mb-1 mt-2" style="word-break: break-word;">{{ $entrada['mensaje'] }}</p>
                                @if ($entrada['detalle'])
                                    <details>
                                        <summary class="text-muted small" style="cursor: pointer;">Ver detalle / stack trace</summary>
                                        <pre class="small bg-light p-2 mt-2" style="white-space: pre-wrap; word-break: break-word;">{{ $entrada['detalle'] }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3"></i><br>
                            No se encontraron entradas con los filtros aplicados
                        </div>
                    @endforelse
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Mostrando {{ $entradas->firstItem() ?? 0 }} a {{ $entradas->lastItem() ?? 0 }}
                                de {{ $entradas->total() }} entradas
                            </small>
                        </div>
                        <div>
                            {!! $entradas->appends(request()->query())->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
