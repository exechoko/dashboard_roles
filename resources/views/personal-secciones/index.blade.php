@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-sitemap"></i></div>
                        <div>
                            <h5 class="header-title">Personal por Sección</h5>
                            <small class="text-muted">
                                <span class="badge-total">{{ $registros->total() }}</span> resultados
                                @if($busqueda) &mdash; buscando <strong>"{{ $busqueda }}"</strong> @endif
                            </small>
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="{{ route('personal-secciones.export', request()->query()) }}" class="btn btn-nuevo">
                            <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                        </a>
                        @can('sincronizar-personal-secciones')
                            <form action="{{ route('personal-secciones.sincronizar') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Esto trae los datos actuales de Personal 911 (personal, funciones, armas, chalecos y licencias). ¿Continuar?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary" {{ $minutosParaProximaSync > 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-sync-alt"></i>
                                    {{ $minutosParaProximaSync > 0 ? "Disponible en {$minutosParaProximaSync} min" : 'Actualizar desde Personal 911' }}
                                </button>
                            </form>
                            <div class="small text-muted mt-1">
                                Última actualización:
                                {{ $ultimaSincronizacion ? $ultimaSincronizacion->format('d/m/Y H:i') : 'nunca (se sincroniza automáticamente todos los días a las 05:30)' }}
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="card-body pt-3">
                    <form method="GET" action="{{ route('personal-secciones.index') }}" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="busqueda">Buscar funcionario</label>
                                <div class="search-wrapper">
                                    <div class="search-icon-left"><i class="fas fa-search"></i></div>
                                    <input type="text" name="busqueda" id="busqueda" class="search-input"
                                           placeholder="Apellido, nombre, LP o DNI..." value="{{ $busqueda }}" autocomplete="off">
                                    @if($busqueda)
                                        <a href="{{ route('personal-secciones.index') }}" class="search-clear"><i class="fas fa-times"></i></a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="estado">Estado</label>
                                <select name="estado" id="estado" class="form-control">
                                    <option value="todos" {{ $estado === 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="activos" {{ $estado === 'activos' ? 'selected' : '' }}>Activos</option>
                                    <option value="en_licencia" {{ $estado === 'en_licencia' ? 'selected' : '' }}>En licencia</option>
                                    <option value="bajas" {{ $estado === 'bajas' ? 'selected' : '' }}>Dejaron su sección</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="orden">Ordenar por</label>
                                <select name="orden" id="orden" class="form-control">
                                    <option value="jerarquia" {{ $orden === 'jerarquia' ? 'selected' : '' }}>Jerarquía (y antigüedad)</option>
                                    <option value="novedades" {{ $orden === 'novedades' ? 'selected' : '' }}>Novedades más recientes</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-search btn-block mb-1">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="d-block">Secciones</label>
                                <div class="d-flex flex-wrap" style="gap: .25rem 1rem">
                                    @foreach ($todasLasSecciones as $nombreSeccion)
                                        <div class="form-check form-check-inline mr-0">
                                            <input class="form-check-input" type="checkbox" name="secciones[]"
                                                   id="seccion-{{ $loop->index }}" value="{{ $nombreSeccion }}"
                                                   {{ in_array($nombreSeccion, $seccionesSeleccionadas, true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="seccion-{{ $loop->index }}">{{ $nombreSeccion }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-filter"></i> Filtrar por secciones tildadas
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Sección</th>
                                    <th>Jerarquía</th>
                                    <th>Apellido y Nombre</th>
                                    <th>L.P.</th>
                                    <th>Función actual</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paletaSecciones = ['primary', 'info', 'success', 'warning', 'danger', 'secondary', 'dark'];
                                @endphp
                                @forelse ($registros as $r)
                                    @php
                                        $p = $r->personal;
                                        $textoCopiar = trim(($p->getNombreCompletoAttribute() ?? '') . ' - ' . ($r->funcion_actual ?? $r->seccion));
                                        $badgeClase = !$r->activo
                                            ? ($r->motivo_baja === \App\Models\PersonalSeccion::MOTIVO_BAJA_POLICIAL ? 'badge-dark' : 'badge-danger')
                                            : ($r->en_licencia ? 'badge-warning' : 'badge-success');
                                        $colorSeccion = $paletaSecciones[crc32((string) $r->seccion) % count($paletaSecciones)];
                                        $esOficial = \App\Models\Personal::pesoJerarquia($p->jerarquia) < 10;
                                    @endphp
                                    <tr class="{{ !$r->activo ? 'table-light text-muted' : '' }}">
                                        <td><span class="badge badge-{{ $colorSeccion }}">{{ $r->seccion }}</span></td>
                                        <td>
                                            <i class="fas {{ $esOficial ? 'fa-star text-warning' : 'fa-shield-alt text-secondary' }} mr-1" title="{{ $esOficial ? 'Oficial' : 'Suboficial / Tropa' }}"></i>
                                            {{ $p->jerarquia }}
                                        </td>
                                        <td>
                                            <a href="{{ route('personal-secciones.show', $p->id) }}">
                                                <strong>{{ $p->apellido }}</strong>, {{ $p->nombre }}
                                            </a>
                                            @if($p->trashed())
                                                <span class="badge badge-dark">Baja policial</span>
                                            @endif
                                        </td>
                                        <td>{{ $p->lp }}</td>
                                        <td>{{ $r->funcion_actual ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $badgeClase }}">{{ $r->estadoLabel() }}</span>
                                            @if(!$r->activo && $r->fecha_baja)
                                                <div class="small text-muted mt-1">desde {{ $r->fecha_baja->format('d/m/Y') }}</div>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copiar"
                                                    data-copy="{{ $textoCopiar }}" title="Copiar datos">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal"
                                                    data-target="#modalNotas{{ $p->id }}" title="Anotaciones (las tuyas y las que te compartieron)">
                                                <i class="fas fa-sticky-note"></i>
                                                @if($p->notasSeccion->count() > 0)
                                                    <span class="badge badge-light">{{ $p->notasSeccion->count() }}</span>
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No se encontraron funcionarios con esos filtros.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $registros->links() }}
                </div>
            </div>
        </div>
    </section>

    @foreach ($registros as $r)
        @php
            $p = $r->personal;
            $usuarioActual = auth()->user();
            $puedoAnotar = $usuarioActual->can('crear-personal-seccion-nota');
            $tengoNotasPropias = $p->notasSeccion->contains(fn ($n) => $n->esAutor($usuarioActual));
        @endphp
            <div class="modal fade" id="modalNotas{{ $p->id }}" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Anotaciones — {{ $p->apellido }}, {{ $p->nombre }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            @forelse ($p->notasSeccion as $nota)
                                @php($esAutor = $nota->esAutor($usuarioActual))
                                <div class="border-left pl-3 mb-3" style="border-color:#dee2e6 !important">
                                    <div class="text-muted small d-flex flex-wrap align-items-center" style="gap:.4rem">
                                        <span><i class="far fa-clock mr-1"></i>{{ $nota->created_at->format('d/m/Y H:i') }}</span>
                                        <span><i class="far fa-user ml-1 mr-1"></i>{{ $nota->autor?->name }} {{ $nota->autor?->apellido }}</span>
                                        @if($esAutor)
                                            @if($nota->esPrivada())
                                                <span class="badge badge-secondary"><i class="fas fa-lock"></i> Privada</span>
                                            @else
                                                <span class="badge badge-info" title="{{ $nota->compartidas->map(fn($c) => $c->usuario?->name.' '.$c->usuario?->apellido)->implode(', ') }}">
                                                    <i class="fas fa-share-alt"></i> Compartida
                                                </span>
                                            @endif
                                        @elseif(!$esAutor)
                                            <span class="badge badge-light">Compartida contigo</span>
                                        @endif
                                    </div>
                                    <p class="mb-1" style="white-space: pre-line">{{ $nota->texto }}</p>
                                    @if($esAutor && $puedoAnotar)
                                        <button type="button" class="btn btn-link btn-sm p-0" data-toggle="collapse" data-target="#compartirNota{{ $nota->id }}">
                                            <i class="fas fa-share-alt"></i> Compartir esta anotación
                                        </button>
                                        <div class="collapse mt-2" id="compartirNota{{ $nota->id }}">
                                            <form action="{{ route('personal-secciones.notas.compartir', $nota->id) }}" method="POST" class="form-inline">
                                                @csrf
                                                <select name="usuarios[]" multiple class="form-control form-control-sm mr-2 mb-1" style="min-width:220px" required>
                                                    @foreach ($usuariosParaCompartir as $usuario)
                                                        <option value="{{ $usuario->id }}">{{ $usuario->name }} {{ $usuario->apellido }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-outline-primary btn-sm mb-1">Compartir</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted mb-0">Todavía no hay anotaciones para este funcionario, o las que hay no fueron compartidas con vos.</p>
                            @endforelse
                        </div>
                        @if($puedoAnotar)
                            <div class="modal-footer d-block">
                                <form action="{{ route('personal-secciones.notas.store', $p->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    <div class="form-group">
                                        <textarea name="texto" class="form-control" rows="2" minlength="3" maxlength="1000"
                                                  placeholder="Agregar una anotación (queda privada, solo vos la ves)..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Agregar anotación
                                    </button>
                                </form>

                                @if($tengoNotasPropias)
                                    <hr class="my-2">
                                    <form action="{{ route('personal-secciones.notas.compartir-todas', $p->id) }}" method="POST" class="form-inline">
                                        @csrf
                                        <label class="mr-2 mb-1 small text-muted">Compartir TODAS mis anotaciones de este funcionario con:</label>
                                        <select name="usuarios[]" multiple class="form-control form-control-sm mr-2 mb-1" style="min-width:220px" required>
                                            @foreach ($usuariosParaCompartir as $usuario)
                                                <option value="{{ $usuario->id }}">{{ $usuario->name }} {{ $usuario->apellido }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-share-alt"></i> Compartir todas
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-copiar').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var texto = boton.dataset.copy || '';
                var confirmar = function () {
                    if (window.iziToast) {
                        iziToast.success({ title: 'Copiado', message: 'Datos copiados al portapapeles', position: 'topRight' });
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(texto).then(confirmar);
                    return;
                }

                var textarea = document.createElement('textarea');
                textarea.value = texto;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    confirmar();
                } catch (e) {
                    // sin soporte de copiado en este navegador
                }
                textarea.remove();
            });
        });
    </script>
@endpush
