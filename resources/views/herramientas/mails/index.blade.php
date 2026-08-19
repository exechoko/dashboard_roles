@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Visor de Correos</h3>
            @can('administrar-visor-mails')
                <a href="{{ route('herramientas.mails.buzones.index') }}" class="btn btn-secondary">
                    <i class="fas fa-inbox"></i> Administrar Buzones
                </a>
            @endcan
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('herramientas.mails.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="mb-1">Buzón</label>
                                <select name="buzon_id" class="form-control select2" onchange="this.form.submit()">
                                    @foreach ($buzones as $b)
                                        <option value="{{ $b->id }}" {{ $buzon->id === $b->id ? 'selected' : '' }}>{{ $b->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="mb-1">Buscar en asunto, cuerpo y adjuntos</label>
                                <input type="text" name="texto" class="form-control" placeholder="Palabras a buscar dentro del mensaje..."
                                       value="{{ request('texto') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">De</label>
                                <input type="text" name="de" class="form-control" placeholder="Nombre o correo" value="{{ request('de') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">Para / CC</label>
                                <input type="text" name="para" class="form-control" placeholder="Nombre o correo" value="{{ request('para') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">Asunto contiene</label>
                                <input type="text" name="asunto" class="form-control" value="{{ request('asunto') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">Nombre de adjunto</label>
                                <input type="text" name="adjunto_nombre" class="form-control" value="{{ request('adjunto_nombre') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Fecha desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Fecha hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Carpeta</label>
                                <select name="carpeta" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach ($carpetas as $valor => $etiqueta)
                                        <option value="{{ $valor }}" {{ request('carpeta') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Adjuntos</label>
                                <select name="adjuntos" class="form-control">
                                    <option value="">Con o sin</option>
                                    <option value="con" {{ request('adjuntos') === 'con' ? 'selected' : '' }}>Con adjuntos</option>
                                    <option value="sin" {{ request('adjuntos') === 'sin' ? 'selected' : '' }}>Sin adjuntos</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Etiqueta Gmail</label>
                                <input type="text" name="etiqueta" class="form-control" value="{{ request('etiqueta') }}">
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-1">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('herramientas.mails.index', ['buzon_id' => $buzon->id]) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>

                        <input type="hidden" name="orden" value="{{ request('orden', 'fecha') }}">
                        <input type="hidden" name="direccion" value="{{ request('direccion', 'desc') }}">
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">{{ $mensajes->total() }} mensajes encontrados</span>
                        <a href="{{ route('herramientas.mails.exportar', request()->query()) }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel"></i> Exportar
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>De</th>
                                    <th>Asunto</th>
                                    <th class="text-center">📎</th>
                                    <th class="text-right">Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mensajes as $mensaje)
                                    <tr>
                                        <td class="text-nowrap">{{ $mensaje->fecha?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>{{ $mensaje->de_nombre ?: $mensaje->de_email ?: '(desconocido)' }}</td>
                                        <td>
                                            <a href="{{ route('herramientas.mails.show', $mensaje) }}">
                                                {{ $mensaje->asunto ?: '(sin asunto)' }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if ($mensaje->tiene_adjuntos)
                                                <i class="fas fa-paperclip" title="{{ $mensaje->cantidad_adjuntos }} adjunto(s)"></i>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($mensaje->tamano_bytes / 1024, 1) }} KB</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No se encontraron mensajes con esos filtros.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $mensajes->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('.select2').select2({ width: '100%' });
        });
    </script>
@endpush
