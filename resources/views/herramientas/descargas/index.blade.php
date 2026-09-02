@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-download mr-2"></i>Plataforma de Descargas</h3>
        <div>
            <a href="{{ route('descargas.galeria') }}" class="btn btn-outline-secondary">
                <i class="fas fa-images"></i> Ver Galería
            </a>
            @can('administrar-plataforma-descargas')
                <a href="{{ route('descargas.admin.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-cogs"></i> Administrar
                </a>
            @endcan
        </div>
    </div>

    <div class="section-body">
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
                    
                    {{-- Filtros avanzados --}}
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

        {{-- Categorías como cards --}}
        @if($categorias->count() > 0 && !request()->hasAny(['buscar', 'categoria_id', 'extension']))
            <div class="row mb-4">
                @foreach($categorias as $categoria)
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="{{ route('descargas.index', ['categoria_id' => $categoria->id]) }}" class="text-decoration-none">
                            <div class="card h-100 border-left-4" style="border-left: 4px solid {{ $categoria->color }} !important;">
                                <div class="card-body text-center">
                                    <i class="{{ $categoria->icono }} fa-2x mb-2" style="color: {{ $categoria->color }}"></i>
                                    <h6 class="card-title mb-1">{{ $categoria->nombre }}</h6>
                                    <small class="text-muted">{{ $categoria->archivos_activos_count ?? $categoria->archivos()->activos()->count() }} archivos</small>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Lista de archivos --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-open mr-2"></i>
                        Archivos disponibles
                        <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
                    </h5>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" id="btnDescargarZip" disabled>
                            <i class="fas fa-file-archive"></i> Descargar seleccionados como ZIP
                            <span class="badge badge-light ml-2" id="contadorSeleccionados">0</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($archivos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="selectAll">
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th style="width: 50px;"></th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Tamaño</th>
                                    <th>Subido por</th>
                                    <th>Fecha</th>
                                    <th>Descargas</th>
                                    <th style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($archivos as $archivo)
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input archivo-checkbox" 
                                                       id="archivo_{{ $archivo->id }}" value="{{ $archivo->id }}"
                                                       data-tamano="{{ $archivo->tamano_bytes }}">
                                                <label class="custom-control-label" for="archivo_{{ $archivo->id }}"></label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($archivo->destacado)
                                                <i class="fas fa-star text-warning" title="Destacado"></i>
                                            @else
                                                <i class="{{ $archivo->icono_extension }}"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('descargas.show', $archivo) }}" class="font-weight-bold">
                                                {{ $archivo->nombre_original }}
                                            </a>
                                            @if($archivo->expira_at)
                                                @if($archivo->esta_expirado)
                                                    <span class="badge badge-danger ml-2">Expirado</span>
                                                @else
                                                    <span class="badge badge-warning ml-2">
                                                        Expira en {{ $archivo->dias_para_expirar }} días
                                                    </span>
                                                @endif
                                            @endif
                                            @if($archivo->descripcion)
                                                <br><small class="text-muted">{{ Str::limit($archivo->descripcion, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $archivo->categoria->color }}">
                                                {{ $archivo->categoria->nombre }}
                                            </span>
                                        </td>
                                        <td>{{ $archivo->tamano_humano }}</td>
                                        <td>{{ $archivo->user->name ?? 'Sistema' }}</td>
                                        <td>{{ $archivo->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $archivo->descargas_count }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('descargas.download', $archivo) }}" class="btn btn-sm btn-success" title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($archivo->es_previeweable)
                                                <a href="{{ route('descargas.preview', $archivo) }}" class="btn btn-sm btn-info" title="Vista previa" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $archivos->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No se encontraron archivos disponibles.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    const maxTamanoBytes = {{ config('descargas.zip_tamano_maximo_gb', 10) * 1024 * 1024 * 1024 }};
    
    // Seleccionar/deseleccionar todos
    $('#selectAll').change(function() {
        $('.archivo-checkbox').prop('checked', this.checked);
        actualizarContador();
    });
    
    // Actualizar contador al cambiar selección
    $('.archivo-checkbox').change(function() {
        actualizarContador();
    });
    
    function actualizarContador() {
        const seleccionados = $('.archivo-checkbox:checked').length;
        $('#contadorSeleccionados').text(seleccionados);
        $('#btnDescargarZip').prop('disabled', seleccionados === 0);
        
        // Calcular tamaño total
        let tamanoTotal = 0;
        $('.archivo-checkbox:checked').each(function() {
            tamanoTotal += parseInt($(this).data('tamano')) || 0;
        });
        
        if (tamanoTotal > maxTamanoBytes) {
            $('#btnDescargarZip').prop('disabled', true);
            alert('El tamaño total de los archivos seleccionados supera el límite de {{ config('descargas.zip_tamano_maximo_gb', 10) }} GB');
        }
    }
    
    // Descargar ZIP
    $('#btnDescargarZip').click(function() {
        const archivosIds = [];
        $('.archivo-checkbox:checked').each(function() {
            archivosIds.push($(this).val());
        });
        
        if (archivosIds.length === 0) {
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando ZIP...');
        
        $.ajax({
            url: '{{ route("descargas.solicitar-zip") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                archivos: archivosIds
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                
                if (response.success) {
                    // Mostrar mensaje de éxito
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                            <h5><i class="fas fa-check-circle"></i> ${response.message}</h5>
                            <p><strong>${response.archivos} archivos</strong> - ${response.tamano}</p>
                            <a href="${response.download_url}" class="btn btn-success btn-lg" target="_blank">
                                <i class="fas fa-download"></i> Descargar ZIP
                            </a>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                    
                    // Insertar el alert al principio de section-body
                    $('.section-body').prepend(alertHtml);
                    
                    // Scroll suave hacia el alert
                    $('html, body').animate({
                        scrollTop: $('.alert-success').offset().top - 100
                    }, 500);
                    
                    // Resetear selección
                    $('.archivo-checkbox').prop('checked', false);
                    $('#selectAll').prop('checked', false);
                    actualizarContador();
                    
                    // No auto-ocultar para que el usuario pueda hacer clic en el botón
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en AJAX:', status, error);
                console.error('Respuesta:', xhr.responseText);
                console.error('Status:', xhr.status);
                
                const response = xhr.responseJSON;
                alert(response?.message || 'Error al crear el ZIP (Status: ' + xhr.status + ')');
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
