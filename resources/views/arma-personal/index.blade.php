@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Personal</h3>
            @can('crear-personal')
                <a href="{{ route('armas.personal.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Funcionario
                </a>
            @endcan
        </div>

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

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('armas.personal.index') }}" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="busqueda">Buscar funcionario o licencia</label>
                                <input type="text" name="busqueda" id="busqueda" class="form-control" placeholder="Apellido, nombre, LP, tipo de licencia..."
                                       value="{{ $busqueda }}">
                            </div>
                            <div class="col-md-3">
                                <label for="estado_licencia">Estado de licencia</label>
                                <select name="estado_licencia" id="estado_licencia" class="form-control">
                                    <option value="todos" {{ $estadoLicencia === 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="vigentes" {{ $estadoLicencia === 'vigentes' ? 'selected' : '' }}>Solo de licencia</option>
                                    <option value="sin_licencia" {{ $estadoLicencia === 'sin_licencia' ? 'selected' : '' }}>Sin licencia vigente</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_licencia">Tipo de licencia vigente</label>
                                <select name="tipo_licencia" id="tipo_licencia" class="form-control">
                                    <option value="">Todos los tipos</option>
                                    @foreach ($tiposLicencia as $tipo)
                                        <option value="{{ $tipo->tipo_licencia_id }}" {{ (string) $tipoLicencia === (string) $tipo->tipo_licencia_id ? 'selected' : '' }}>
                                            {{ $tipo->tipo_licencia }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary mb-1">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('armas.personal.index') }}" class="btn btn-secondary mb-1">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="btn-group" role="group" aria-label="Estado del funcionario">
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'activos'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'activos' ? 'btn-primary' : 'btn-outline-primary' }}">
                                        <i class="fas fa-user-check"></i> Activos
                                    </a>
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'eliminados'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'eliminados' ? 'btn-danger' : 'btn-outline-danger' }}">
                                        <i class="fas fa-user-slash"></i> Eliminados
                                    </a>
                                    <a href="{{ route('armas.personal.index', array_merge(request()->except(['ver_eliminados', 'page']), ['ver_eliminados' => 'todos'])) }}"
                                       class="btn btn-sm {{ $ver_eliminados === 'todos' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-users"></i> Todos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>LP</th>
                                    <th>Jerarquía</th>
                                    <th>Situación / función</th>
                                    <th>Arma</th>
                                    <th>Licencia</th>
                                    <th>Período y días</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($personales as $personal)
                                    @php($resumenLicencia = $personal->resumen_licencia_actual)
                                    <tr class="{{ $personal->trashed() ? 'table-danger' : '' }}">
                                        <td>
                                            <strong>{{ $personal->apellido }}</strong>
                                            @if($personal->trashed())
                                                <span class="badge badge-danger">Eliminado</span>
                                            @endif
                                        </td>
                                        <td>{{ $personal->nombre }}</td>
                                        <td>{{ $personal->lp }}</td>
                                        <td>{{ $personal->jerarquia }}</td>
                                        <td>
                                            @if($personal->situacion_personal911)
                                                <span class="badge badge-{{ $personal->situacion_personal911 === 'Baja' ? 'danger' : ($personal->situacion_personal911 === 'Activo Efectivo' ? 'success' : 'secondary') }}">
                                                    {{ $personal->situacion_personal911 }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin informar</span>
                                            @endif
                                            @if($personal->funcion_personal911)
                                                <div class="small mt-1">{{ $personal->funcion_personal911 }}</div>
                                            @endif
                                            @if($personal->observaciones_personal911)
                                                <div class="small text-muted" title="{{ $personal->observaciones_personal911 }}">
                                                    <i class="fas fa-comment-alt"></i> Observaciones disponibles
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($personal->numeracion_arma)
                                                <span class="badge badge-secondary">{{ $personal->numeracion_arma }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($resumenLicencia)
                                                <span class="badge badge-warning">De licencia</span>
                                                @foreach($resumenLicencia['licencias'] as $licenciaActual)
                                                    <div class="small mt-1">
                                                        {{ $licenciaActual->tipo_licencia ?? 'Sin tipo informado' }}:
                                                        {{ $licenciaActual->cantidad_dias ?? '-' }} días
                                                    </div>
                                                @endforeach
                                            @else
                                                @if($personal->indicaLicenciaEnFuncion())
                                                    <span class="badge badge-secondary">Licencia indicada en función</span>
                                                    <div class="small text-muted mt-1">Revisar observaciones de Personal 911</div>
                                                @else
                                                    <span class="text-muted">Sin licencia vigente</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($resumenLicencia)
                                                <div class="small">
                                                    {{ $resumenLicencia['fecha_inicio']->format('d/m/Y') }} al {{ $resumenLicencia['fecha_fin']->format('d/m/Y') }}
                                                </div>
                                                <span class="badge badge-info">{{ $resumenLicencia['dias_transcurridos'] }} días transcurridos</span>
                                                <div class="small text-muted">{{ $resumenLicencia['dias_otorgados'] }} días otorgados acumulados</div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($personal->trashed())
                                                @can('restaurar-personal')
                                                    <form action="{{ route('armas.personal.restore', $personal->id) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Restaurar este funcionario?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Restaurar">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('ver-personal')
                                                    <a href="{{ route('armas.personal.show', $personal) }}" class="btn btn-sm btn-info" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('editar-personal')
                                                    <a href="{{ route('armas.personal.edit', $personal) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('borrar-personal')
                                                    <form action="{{ route('armas.personal.destroy', $personal) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Está seguro de eliminar este funcionario?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            @if($ver_eliminados === 'eliminados')
                                                No hay funcionarios eliminados.
                                            @else
                                                No hay funcionarios registrados.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $personales->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
