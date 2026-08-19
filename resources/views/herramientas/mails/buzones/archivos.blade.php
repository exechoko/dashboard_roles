@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Archivos de {{ $buzon->nombre }}</h3>
            <a href="{{ route('herramientas.mails.buzones.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <p class="text-muted">
                        Archivos <code>.mbox</code> encontrados en
                        <code>{{ config('mbox.ruta') }}\{{ $buzon->carpeta }}</code> (búsqueda recursiva).
                    </p>

                    @if (empty($hallados))
                        <div class="alert alert-warning">
                            No se encontró ningún .mbox en esa carpeta. Verificá que el Takeout se haya
                            copiado ahí.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th class="text-right">Tamaño</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hallados as $h)
                                        @php($registro = $h['registro'])
                                        <tr data-archivo-id="{{ $registro->id ?? '' }}">
                                            <td>
                                                {{ $h['nombre_archivo'] }}
                                                @if ($h['requiere_reindexar'])
                                                    <span class="badge badge-warning" title="El archivo cambió de tamaño o fecha desde la última indexación">
                                                        Modificado
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format($h['tamano_bytes'] / 1048576, 1) }} MB</td>
                                            <td class="js-estado">
                                                @if ($registro)
                                                    <span class="badge badge-{{ ['pendiente' => 'secondary', 'indexando' => 'info', 'indexado' => 'success', 'error' => 'danger'][$registro->estado] }}">
                                                        {{ ucfirst($registro->estado) }}
                                                    </span>
                                                    @if ($registro->estado === 'indexado')
                                                        <div class="small text-muted">{{ number_format($registro->mensajes_total) }} mensajes</div>
                                                    @endif
                                                    @if ($registro->estado === 'error')
                                                        <div class="small text-danger">{{ $registro->error_message }}</div>
                                                    @endif
                                                @else
                                                    <span class="badge badge-light">Sin registrar</span>
                                                @endif
                                            </td>
                                            <td style="min-width:140px">
                                                @if ($registro && $registro->estado === 'indexando')
                                                    <div class="progress">
                                                        <div class="progress-bar js-progreso" role="progressbar"
                                                             style="width: {{ $registro->porcentaje_avance }}%">
                                                            {{ $registro->porcentaje_avance }}%
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-right text-nowrap">
                                                @if (!$registro)
                                                    <form action="{{ route('herramientas.mails.buzones.archivos.registrar', $buzon) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="ruta_absoluta" value="{{ $h['ruta_absoluta'] }}">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-play"></i> Registrar e indexar
                                                        </button>
                                                    </form>
                                                @elseif (in_array($registro->estado, ['pendiente', 'indexado', 'error']))
                                                    <form action="{{ route('herramientas.mails.buzones.archivos.indexar', $registro) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-sync"></i> {{ $h['requiere_reindexar'] ? 'Reindexar' : 'Indexar' }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('herramientas.mails.buzones.archivos.destroy', $registro) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Borrar el índice de este archivo? El .mbox del disco no se toca.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted">Indexando...</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            function actualizarFilas() {
                document.querySelectorAll('tr[data-archivo-id]').forEach(function (fila) {
                    var id = fila.getAttribute('data-archivo-id');
                    if (!id) return;

                    var estadoCelda = fila.querySelector('.js-estado');
                    if (!estadoCelda) return;

                    var badge = estadoCelda.querySelector('.badge');
                    if (!badge || badge.textContent.trim() !== 'Indexando') return;

                    fetch('{{ url('herramientas/mails/buzones/archivos') }}/' + id + '/estado')
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var progreso = fila.querySelector('.js-progreso');
                            if (progreso) {
                                progreso.style.width = data.porcentaje + '%';
                                progreso.textContent = data.porcentaje + '%';
                            }
                            if (data.estado !== 'indexando') {
                                location.reload();
                            }
                        });
                });
            }

            if (document.querySelector('tr[data-archivo-id] .js-progreso')) {
                setInterval(actualizarFilas, 4000);
            }
        })();
    </script>
@endpush
