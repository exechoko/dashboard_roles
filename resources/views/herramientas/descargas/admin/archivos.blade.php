@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-file-alt mr-2"></i>Gestión de Archivos</h3>
    </div>

    <div class="section-body">
        @include('herramientas.descargas.admin.partials._nav')

        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.admin.archivos') }}" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre del archivo..."
                               value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-3 mb-2">
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
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="activos" {{ request('estado') == 'activos' ? 'selected' : '' }}>Activos</option>
                            <option value="expirados" {{ request('estado') == 'expirados' ? 'selected' : '' }}>Expirados</option>
                            <option value="inactivos" {{ request('estado') == 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Lista de archivos --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Archivos
                    <span class="badge badge-secondary ml-2">{{ $archivos->total() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($archivos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Tamaño</th>
                                    <th>Roles</th>
                                    <th>Descargas</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th style="width: 180px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($archivos as $archivo)
                                    <tr class="{{ !$archivo->activo ? 'table-secondary' : '' }}">
                                        <td class="text-center">
                                            @if($archivo->destacado)
                                                <i class="fas fa-star text-warning"></i>
                                            @else
                                                <i class="{{ $archivo->icono_extension }}"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($archivo->nombre_original, 40) }}</strong>
                                            @if($archivo->descripcion)
                                                <br><small class="text-muted">{{ Str::limit($archivo->descripcion, 40) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge descarga-badge-categoria" style="--categoria-color: {{ $archivo->categoria->color }}">
                                                {{ $archivo->categoria->nombre }}
                                            </span>
                                        </td>
                                        <td>{{ $archivo->tamano_humano }}</td>
                                        <td>
                                            @foreach($archivo->roles->take(3) as $rol)
                                                <span class="badge badge-light">{{ Str::limit($rol->name, 10) }}</span>
                                            @endforeach
                                            @if($archivo->roles->count() > 3)
                                                <span class="badge badge-secondary">+{{ $archivo->roles->count() - 3 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $archivo->descargas_count }}</span>
                                        </td>
                                        <td>
                                            @if($archivo->estado_proceso === 'procesando')
                                                <span class="badge badge-info" title="Se está moviendo/transcodificando en segundo plano">
                                                    <i class="fas fa-spinner fa-spin"></i> Procesando
                                                </span>
                                            @elseif($archivo->estado_proceso === 'error')
                                                <span class="badge badge-danger" title="{{ $archivo->error_proceso }}">
                                                    <i class="fas fa-exclamation-triangle"></i> Error de proceso
                                                </span>
                                            @elseif(!$archivo->activo)
                                                <span class="badge badge-danger">Inactivo</span>
                                            @elseif($archivo->esta_expirado)
                                                <span class="badge badge-warning">Expirado</span>
                                            @elseif($archivo->expira_at)
                                                <span class="badge badge-info" title="{{ $archivo->expira_at->format('d/m/Y') }}">
                                                    {{ $archivo->dias_para_expirar }} días
                                                </span>
                                            @else
                                                <span class="badge badge-success">Activo</span>
                                            @endif
                                        </td>
                                        <td>{{ $archivo->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <a href="{{ route('descargas.show', $archivo) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('descargas.admin.edit', $archivo) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(!$archivo->activo)
                                                <form action="{{ route('descargas.admin.reactivar', $archivo) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('descargas.admin.destroy', $archivo) }}" method="POST" class="d-inline form-eliminar-archivo" data-archivo="{{ $archivo->nombre_original }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
                        <p class="text-muted">No se encontraron archivos.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@include('herramientas.descargas.partials._styles')

@push('scripts')
@include('herramientas.descargas.partials._scripts')
<script>
$(document).ready(function() {
    $('.form-eliminar-archivo').submit(function(e) {
        e.preventDefault();
        const form = $(this);

        descargasConfirmar({
            titulo: '¿Eliminar este archivo?',
            texto: `"${form.data('archivo')}" se marcará como inactivo. Esta acción no se puede deshacer desde la interfaz.`,
            confirmText: 'Sí, eliminar',
        }).then((result) => {
            if (result.isConfirmed) {
                form.off('submit').submit();
            }
        });
    });
});
</script>
@endpush
@endsection
