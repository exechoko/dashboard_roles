@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-download mr-2"></i>Plataforma de Descargas</h3>
    </div>

    <div class="section-body">
        @include('herramientas.descargas.partials._nav')

        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Nombre o descripción..."
                                   value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Extensión</label>
                            <select name="extension" class="form-control">
                                <option value="">Todas</option>
                                @foreach($extensiones as $ext)
                                    <option value="{{ $ext }}" {{ request('extension') == $ext ? 'selected' : '' }}>
                                        .{{ strtoupper($ext) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Ordenar</label>
                            <select name="orden" class="form-control">
                                <option value="recientes" {{ request('orden') == 'recientes' ? 'selected' : '' }}>Más recientes</option>
                                <option value="antiguos" {{ request('orden') == 'antiguos' ? 'selected' : '' }}>Más antiguos</option>
                                <option value="nombre" {{ request('orden') == 'nombre' ? 'selected' : '' }}>Nombre A-Z</option>
                                <option value="descargas" {{ request('orden') == 'descargas' ? 'selected' : '' }}>Más descargados</option>
                                <option value="tamano" {{ request('orden') == 'tamano' ? 'selected' : '' }}>Tamaño</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('descargas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="#" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#filtrosAvanzados">
                                <i class="fas fa-sliders-h"></i> Filtros avanzados
                            </a>
                        </div>
                    </div>

                    <div class="collapse mt-3" id="filtrosAvanzados">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Subido por</label>
                                <select name="user_id" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach($usuarios as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" name="fecha_subida_desde" class="form-control"
                                       value="{{ request('fecha_subida_desde') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" name="fecha_subida_hasta" class="form-control"
                                       value="{{ request('fecha_subida_hasta') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Tamaño (KB)</label>
                                <div class="input-group">
                                    <input type="number" name="tamano_min" class="form-control" placeholder="Mín"
                                           value="{{ request('tamano_min') }}">
                                    <div class="input-group-prepend input-group-append">
                                        <span class="input-group-text">-</span>
                                    </div>
                                    <input type="number" name="tamano_max" class="form-control" placeholder="Máx"
                                           value="{{ request('tamano_max') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(is_null($archivos))
            {{-- Landing: solo categorías. El listado de archivos se carga recién
                 al entrar a una categoría o al buscar/filtrar. --}}
            @if($categorias->count() > 0)
                <div class="row mb-4">
                    @foreach($categorias as $categoria)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('descargas.index', ['categoria_id' => $categoria->id]) }}" class="text-decoration-none">
                                <div class="card h-100" style="border-left: 4px solid {{ $categoria->color }} !important;">
                                    <div class="card-body text-center">
                                        <i class="{{ $categoria->icono }} fa-2x mb-2" style="color: {{ $categoria->color }}"></i>
                                        <h6 class="card-title mb-1">{{ $categoria->nombre }}</h6>
                                        <small class="text-muted">{{ $categoria->archivos_activos_count }} archivos</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="descargas-empty card">
                <div class="card-body">
                    <i class="fas fa-folder-open"></i>
                    <p class="mb-0">Elegí una categoría de arriba, o usá el buscador, para ver los archivos disponibles.</p>
                </div>
            </div>
        @else
            {{-- Archivos --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: .5rem;">
                <h5 class="mb-0">
                    Archivos disponibles
                    <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
                </h5>
                <div class="d-flex align-items-center flex-wrap" style="gap: .75rem;">
                    @if($archivos->count() > 0)
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="selectAllArchivos">
                            <label class="custom-control-label" for="selectAllArchivos">Seleccionar todo</label>
                        </div>
                        @if($archivos->total() > $archivos->count())
                            <button type="button" class="btn btn-link btn-sm p-0" id="btnSeleccionarTodosResultados"
                                    data-url="{{ route('descargas.ids-filtrados', request()->except('page')) }}">
                                Seleccionar los {{ $archivos->total() }} resultados
                            </button>
                        @endif
                    @endif
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnDescargarSeparado" disabled
                            title="Descarga cada archivo por separado, sin armar un ZIP (más cómodo desde el celular)">
                        <i class="fas fa-download"></i> Descargar sin ZIP
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="btnDescargarZip" disabled>
                        <i class="fas fa-file-archive"></i> Descargar como ZIP
                        <span class="badge badge-light ml-2" id="contadorSeleccionados">0</span>
                    </button>
                </div>
            </div>

            <div class="alert alert-info py-2 px-3 mb-3 d-none align-items-center flex-wrap" id="avisoSeleccionCompleta" style="gap: .5rem;">
                <i class="fas fa-check-circle mr-1"></i>
                <span>Vas a descargar los <strong id="avisoSeleccionCompletaTotal"></strong> archivos de este filtro, no solo los de esta página.</span>
                <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" id="btnCancelarSeleccionCompleta">
                    Usar solo esta página
                </button>
            </div>

            @if($archivos->count() > 0)
                <div class="descargas-grid mb-3">
                    @foreach($archivos as $archivo)
                        <x-descarga-archivo-card :archivo="$archivo" mostrar-checkbox mostrar-favorito
                            :favorito="in_array($archivo->id, $favoritosIds)" />
                    @endforeach
                </div>
                <div class="d-flex justify-content-center">
                    {{ $archivos->links() }}
                </div>
            @else
                <div class="descargas-empty card">
                    <div class="card-body">
                        <i class="fas fa-inbox"></i>
                        <p class="mb-0">No se encontraron archivos disponibles con estos filtros.</p>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>

@include('herramientas.descargas.partials._styles')

@push('scripts')
@include('herramientas.descargas.partials._scripts')
<script>
$(document).ready(function() {
    const maxTamanoBytes = {{ config('descargas.zip_tamano_maximo_gb', 10) * 1024 * 1024 * 1024 }};
    const maxTamanoGb = {{ config('descargas.zip_tamano_maximo_gb', 10) }};

    // Cuando está activa, la selección real no son los checkboxes tildados
    // en esta página sino seleccionCompleta.ids (todos los resultados del
    // filtro actual, traídos por /descargas/ids-filtrados).
    let seleccionCompleta = null;

    function activarSeleccionCompleta(data) {
        seleccionCompleta = data;
        $('.archivo-checkbox').prop('checked', true);
        $('#selectAllArchivos').prop('checked', true);
        $('#contadorSeleccionados').text(data.total);
        $('#avisoSeleccionCompletaTotal').text(data.total);
        $('#avisoSeleccionCompleta').removeClass('d-none').addClass('d-flex');

        const superaLimite = data.tamano_bytes > maxTamanoBytes;
        $('#btnDescargarZip').prop('disabled', superaLimite);
        if (superaLimite) {
            descargasToast(`El tamaño total (${data.total} archivos) supera el límite de ${maxTamanoGb} GB para armar un ZIP`, 'warning');
        }

        // Descargar sin ZIP dispara una descarga por archivo: con una
        // selección de este tamaño el navegador bloquea la mayoría por
        // "sitio pidiendo descargar muchos archivos". Mejor forzar el ZIP.
        $('#btnDescargarSeparado').prop('disabled', true)
            .attr('title', 'No disponible para selecciones tan grandes: usá "Descargar como ZIP"');
    }

    function cancelarSeleccionCompleta() {
        if (!seleccionCompleta) {
            return;
        }
        seleccionCompleta = null;
        $('#avisoSeleccionCompleta').removeClass('d-flex').addClass('d-none');
        $('#btnDescargarSeparado').attr('title', 'Descarga cada archivo por separado, sin armar un ZIP (más cómodo desde el celular)');
    }

    $('#btnSeleccionarTodosResultados').click(function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Cargando...');

        $.ajax({
            url: btn.data('url'),
            method: 'GET',
            success: function(response) {
                activarSeleccionCompleta(response);
            },
            error: function(xhr) {
                descargasErrorAjax(xhr, 'Error al seleccionar todos los resultados');
            },
            complete: function() {
                btn.prop('disabled', false).text(originalText);
            }
        });
    });

    $('#btnCancelarSeleccionCompleta').click(function() {
        cancelarSeleccionCompleta();
        $('.archivo-checkbox').prop('checked', false);
        $('#selectAllArchivos').prop('checked', false);
        actualizarContador();
    });

    $(document).on('change', '.archivo-checkbox', function() {
        cancelarSeleccionCompleta();
        const total = $('.archivo-checkbox').length;
        const marcados = $('.archivo-checkbox:checked').length;
        $('#selectAllArchivos').prop('checked', total > 0 && marcados === total);
        actualizarContador();
    });

    $('#selectAllArchivos').change(function() {
        cancelarSeleccionCompleta();
        $('.archivo-checkbox').prop('checked', this.checked);
        actualizarContador();
    });

    function actualizarContador() {
        if (seleccionCompleta) {
            return;
        }

        const seleccionados = $('.archivo-checkbox:checked').length;
        $('#contadorSeleccionados').text(seleccionados);
        $('#btnDescargarZip').prop('disabled', seleccionados === 0);
        $('#btnDescargarSeparado').prop('disabled', seleccionados === 0);

        let tamanoTotal = 0;
        $('.archivo-checkbox:checked').each(function() {
            tamanoTotal += parseInt($(this).data('tamano')) || 0;
        });

        if (tamanoTotal > maxTamanoBytes) {
            $('#btnDescargarZip').prop('disabled', true);
            descargasToast('El tamaño total seleccionado supera el límite de {{ config('descargas.zip_tamano_maximo_gb', 10) }} GB', 'warning');
        }
    }

    // Favoritos (mismo botón usado en varias vistas del módulo)
    $(document).on('click', '.btn-toggle-favorito', function() {
        const button = $(this);
        const archivoId = button.data('archivo-id');

        $.ajax({
            url: `/descargas/${archivoId}/favorito`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    if (response.es_favorito) {
                        button.removeClass('btn-outline-warning').addClass('btn-warning');
                        button.attr('title', 'Quitar de favoritos');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-outline-warning');
                        button.attr('title', 'Agregar a favoritos');
                    }
                    descargasToast(response.message || 'Favoritos actualizados');
                }
            },
            error: function(xhr) {
                descargasErrorAjax(xhr, 'Error al actualizar favorito');
            }
        });
    });

    $('#btnDescargarSeparado').click(function() {
        const archivosIds = $('.archivo-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (archivosIds.length === 0) {
            return;
        }

        descargasConfirmar({
            titulo: `¿Descargar ${archivosIds.length} archivo(s) por separado?`,
            texto: 'El navegador va a pedir permiso para varias descargas seguidas (aparece un aviso arriba, tipo "este sitio quiere descargar varios archivos"): hay que tocar "Permitir" para que bajen todos. Es más práctico que un ZIP desde el celular.',
            confirmText: 'Sí, descargar',
            icon: 'question',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            // Sin demora entre clicks: los navegadores solo cuentan una
            // descarga como "iniciada por el usuario" si ocurre dentro del
            // mismo gesto de clic, sin pasar por un setTimeout de por medio.
            // Aun así, a partir de la 2da/3ra descarga el navegador puede
            // mostrar un aviso pidiendo permitir "descargas múltiples".
            archivosIds.forEach(function(id) {
                const link = document.createElement('a');
                link.href = `/descargas/${id}/download`;
                link.rel = 'noopener';
                document.body.appendChild(link);
                link.click();
                link.remove();
            });

            descargasToast(`Descargando ${archivosIds.length} archivo(s)...`);
        });
    });

    $('#btnDescargarZip').click(function() {
        const archivosIds = seleccionCompleta
            ? seleccionCompleta.ids
            : $('.archivo-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (archivosIds.length === 0) {
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando ZIP...');

        $.ajax({
            url: '{{ route("descargas.solicitar-zip") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', archivos: archivosIds },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: response.message,
                        html: `<strong>${response.archivos} archivos</strong> - ${response.tamano}`,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-download"></i> Descargar ZIP',
                        cancelButtonText: 'Cerrar',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(response.download_url, '_blank');
                        }
                    });

                    $('.archivo-checkbox').prop('checked', false);
                    cancelarSeleccionCompleta();
                    actualizarContador();
                } else {
                    descargasToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                descargasErrorAjax(xhr, 'Error al crear el ZIP');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
                actualizarContador();
            }
        });
    });
});
</script>
@endpush
@endsection
