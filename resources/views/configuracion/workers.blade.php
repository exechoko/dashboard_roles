@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema — Workers y Colas</h3>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-light border alert-dismissible fade show">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revisá los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <div class="small">{{ $error }}</div>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-heartbeat"></i> Estado en vivo</h5></div>
                <div class="card-body" id="estadoWorkers">
                    <p class="text-muted mb-0"><i class="fas fa-spinner fa-spin"></i> Consultando…</p>
                </div>
            </div>

            <form action="{{ route('configuracion.workers.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-cogs"></i> Parámetros de colas</h5></div>
                    <div class="card-body">
                        @foreach ($meta as $clave => $campoMeta)
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label" for="w_{{ $clave }}">
                                    {{ $campoMeta['label'] }}
                                    <br><small class="text-muted">{{ $clave }}</small>
                                </label>
                                <div class="col-sm-8">
                                    @include('configuracion._campo', ['clave' => $clave, 'meta' => $campoMeta, 'valor' => $valores[$clave] ?? '', 'idPrefijo' => 'w_', 'deshabilitado' => false])
                                    @isset($campoMeta['ayuda'])
                                        <small class="form-text text-muted">{{ $campoMeta['ayuda'] }}</small>
                                    @endisset
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Jobs fallidos ({{ $fallidos->count() }})</h5>
                    @if ($fallidos->count() > 0)
                        <div>
                            <form action="{{ route('configuracion.workers.jobs.reintentar') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-redo"></i> Reintentar todos
                                </button>
                            </form>
                            <form action="{{ route('configuracion.workers.jobs.purgar') }}" method="POST" class="d-inline"
                                onsubmit="return confirm('¿Eliminar el historial de jobs fallidos? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Purgar historial
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($fallidos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cola</th>
                                        <th>Falló el</th>
                                        <th>Error</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($fallidos as $job)
                                        <tr>
                                            <td class="text-monospace small">{{ \Illuminate\Support\Str::limit($job->uuid, 12, '') }}</td>
                                            <td>{{ $job->queue }}</td>
                                            <td>{{ $job->failed_at }}</td>
                                            <td class="small">{{ \Illuminate\Support\Str::limit($job->exception, 120) }}</td>
                                            <td>
                                                <form action="{{ route('configuracion.workers.jobs.reintentar', $job->uuid) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-success" title="Reintentar">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted p-3 mb-0">No hay jobs fallidos.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            function render(d) {
                if (d.error) {
                    document.getElementById('estadoWorkers').innerHTML =
                        '<p class="text-warning mb-0"><i class="fas fa-exclamation-triangle"></i> ' + (d.mensaje || d.error) + '</p>';
                    return;
                }
                var html = '<div class="row text-center">' +
                    '<div class="col"><h4>' + (d.worker_activo ? '<span class="text-success">Activo</span>' : '<span class="text-danger">Inactivo</span>') + '</h4><small>Worker principal</small></div>' +
                    '<div class="col"><h4>' + d.pendientes + '</h4><small>Pendientes</small></div>' +
                    '<div class="col"><h4>' + d.procesando + '</h4><small>Procesando</small></div>' +
                    '<div class="col"><h4 class="' + (d.fallidos > 0 ? 'text-danger' : '') + '">' + d.fallidos + '</h4><small>Fallidos</small></div>' +
                    '<div class="col"><h4>' + (d.mbox_worker_activo ? '<span class="text-success">Activo</span>' : '<span class="text-danger">Inactivo</span>') + '</h4><small>Worker mbox</small></div>' +
                    '<div class="col"><h4>' + (d.backups_worker_activo ? '<span class="text-success">Activo</span>' : '<span class="text-danger">Inactivo</span>') + '</h4><small>Worker backups</small></div>' +
                    '<div class="col"><h4>' + (d.descargas_worker_activo ? '<span class="text-success">Activo</span>' : '<span class="text-danger">Inactivo</span>') + '</h4><small>Worker descargas</small></div>' +
                    '</div>';
                document.getElementById('estadoWorkers').innerHTML = html;
            }

            fetch('{{ route('api.infraestructura.workers-status') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function () {
                    document.getElementById('estadoWorkers').innerHTML = '<p class="text-danger mb-0">No se pudo consultar el estado.</p>';
                });
        })();
    </script>
@endpush
