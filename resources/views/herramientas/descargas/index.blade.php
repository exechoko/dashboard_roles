@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-download mr-2"></i>Plataforma de Descargas</h3>
        @can('administrar-plataforma-descargas')
            <a href="{{ route('descargas.admin.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-cogs"></i> Administrar
            </a>
        @endcan
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.index') }}" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre o extensión..."
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
                <h5 class="mb-0">
                    <i class="fas fa-folder-open mr-2"></i>
                    Archivos disponibles
                    <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($archivos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
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
@endsection
