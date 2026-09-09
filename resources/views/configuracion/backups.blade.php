@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema — Backups de Base de Datos</h3>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i>
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @unless ($disponible)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No se encontró <code>mysqldump</code> en el sistema. Configurá su ubicación en
                    <a href="{{ route('configuracion.env') }}">Variables de Entorno &gt; General &gt; MYSQL_BIN_PATH</a>
                    (carpeta que contiene <code>mysqldump</code> y <code>mysql</code>).
                </div>
            @endunless

            @php $enCurso = ($estado['estado'] ?? null) === 'procesando'; @endphp
            <div id="estadoOperacion" class="alert alert-info" style="{{ $enCurso ? '' : 'display:none' }}">
                <span id="estadoOperacionTexto">
                    <i class="fas fa-spinner fa-spin"></i>
                    {{ ($estado['accion'] ?? null) === 'restaurar' ? 'Restaurando' : 'Generando' }} backup en segundo plano...
                    Esta pantalla se actualiza sola cuando termine (puede tardar varios minutos en bases grandes).
                </span>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus"></i> Generar backup</h5></div>
                <div class="card-body">
                    <form action="{{ route('configuracion.backups.crear') }}" method="POST" class="form-inline">
                        @csrf
                        <label class="mr-2" for="nota">Nota (opcional):</label>
                        <input type="text" name="nota" id="nota" class="form-control mr-2" style="min-width: 300px"
                            placeholder="Ej: antes de actualizar el sistema" maxlength="255" {{ $enCurso ? 'disabled' : '' }}>
                        <button type="submit" id="btnGenerarBackup" class="btn btn-primary" {{ $disponible && !$enCurso ? '' : 'disabled' }}>
                            <i class="fas fa-database"></i> Generar backup ahora
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-list"></i> Backups existentes ({{ $backups->count() }})</h5></div>
                <div class="card-body p-0">
                    @if ($backups->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Fecha</th>
                                        <th>Tamaño</th>
                                        <th>Nota</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($backups as $backup)
                                        <tr>
                                            <td class="text-monospace small">{{ $backup['archivo'] }}</td>
                                            <td>{{ $backup['creado_en']->format('d/m/Y H:i') }}</td>
                                            <td>{{ $backup['tamano_mb'] }} MB</td>
                                            <td>{{ $backup['nota'] ?? '—' }}</td>
                                            <td class="text-right">
                                                @can('descargar-configuracion-backup')
                                                    <a href="{{ route('configuracion.backups.descargar', $backup['archivo']) }}"
                                                        class="btn btn-xs btn-outline-primary" title="Descargar">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endcan
                                                @can('restaurar-configuracion-backup')
                                                    <button type="button" class="btn btn-xs btn-outline-warning js-abrir-restaurar"
                                                        title="Restaurar" data-archivo="{{ $backup['archivo'] }}" {{ $enCurso ? 'disabled' : '' }}>
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                @endcan
                                                @can('borrar-configuracion-backup')
                                                    <form action="{{ route('configuracion.backups.eliminar', $backup['archivo']) }}" method="POST" class="d-inline"
                                                        onsubmit="return confirm('¿Eliminar el backup {{ $backup['archivo'] }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar" {{ $enCurso ? 'disabled' : '' }}>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted p-3 mb-0">Todavía no hay backups generados.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @can('restaurar-configuracion-backup')
        <div class="modal fade" id="modalRestaurar" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="formRestaurar" method="POST">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Restaurar backup</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>
                                Vas a <strong>reemplazar la base de datos actual</strong> por el contenido de
                                <code id="modalArchivoNombre"></code>. Se generará automáticamente un backup de
                                seguridad de la base actual antes de restaurar.
                            </p>
                            <div class="form-group">
                                <label>Para confirmar, escribí el nombre exacto del archivo:</label>
                                <input type="text" name="confirmacion" class="form-control" required autocomplete="off">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-undo"></i> Restaurar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            var boton = e.target.closest('.js-abrir-restaurar');
            if (!boton) { return; }

            var archivo = boton.getAttribute('data-archivo');
            var form = document.getElementById('formRestaurar');
            form.action = '{{ url('configuracion/backups') }}/' + archivo + '/restaurar';
            document.getElementById('modalArchivoNombre').textContent = archivo;
            form.querySelector('[name="confirmacion"]').value = '';

            if (window.jQuery) { window.jQuery('#modalRestaurar').modal('show'); }
        });

        // La generación/restauración corre en un Job en segundo plano (evita el
        // timeout de Cloudflare en producción para bases grandes): esta pantalla
        // consulta el estado cada pocos segundos y se recarga sola al terminar.
        (function () {
            var enCurso = {{ $enCurso ? 'true' : 'false' }};
            if (!enCurso) { return; }

            var banner = document.getElementById('estadoOperacion');
            var texto = document.getElementById('estadoOperacionTexto');

            var intervalo = setInterval(function () {
                fetch('{{ route('configuracion.backups.estado') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.estado === 'completado') {
                            clearInterval(intervalo);
                            banner.className = 'alert alert-success';
                            texto.innerHTML = '<i class="fas fa-check-circle"></i> Listo. Actualizando...';
                            setTimeout(function () { window.location.reload(); }, 1500);
                        } else if (data.estado === 'error') {
                            clearInterval(intervalo);
                            banner.className = 'alert alert-danger';
                            texto.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Falló: ' + (data.error || 'error desconocido') + '. Actualizando...';
                            setTimeout(function () { window.location.reload(); }, 4000);
                        } else if (data.estado !== 'procesando') {
                            // 'inactivo' (o cualquier otro valor): alguien limpió el estado
                            // a mano (job borrado, caché limpiada) sin pasar por el Job.
                            clearInterval(intervalo);
                            banner.className = 'alert alert-warning';
                            texto.innerHTML = '<i class="fas fa-info-circle"></i> La operación ya no está en curso. Actualizando...';
                            setTimeout(function () { window.location.reload(); }, 1500);
                        }
                    })
                    .catch(function () { /* red caída puntual: reintenta en el próximo tick */ });
            }, 4000);
        })();
    </script>
@endpush
