@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Auditoría</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Filtros de búsqueda</h4>
                            <div class="card-header-action">
                                <label class="badge badge-dark">Registros: {{ $auditorias->total() }}</label>
                            </div>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('auditoria.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="row">
                                    <!-- Búsqueda general -->
                                    <div class="col-md-6 mb-3">
                                        <label>Búsqueda general</label>
                                        <input type="text" name="texto" class="form-control"
                                               placeholder="Buscar en acción o cambios..."
                                               value="{{ $texto }}">
                                    </div>

                                    <!-- Filtro por tabla -->
                                    <div class="col-md-6 mb-3">
                                        <label>Tabla modificada</label>
                                        <select name="tabla" class="form-control">
                                            <option value="">Todas las tablas</option>
                                            @foreach($tablas as $t)
                                                <option value="{{ $t }}" {{ $tabla == $t ? 'selected' : '' }}>
                                                    {{ $t }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Filtro por usuario -->
                                    <div class="col-md-4 mb-3">
                                        <label>Usuario</label>
                                        <select name="usuario" class="form-control">
                                            <option value="">Todos los usuarios</option>
                                            @foreach($usuarios as $u)
                                                <option value="{{ $u->id }}" {{ $usuario == $u->id ? 'selected' : '' }}>
                                                    {{ $u->apellido }} {{ $u->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Fecha desde -->
                                    <div class="col-md-4 mb-3">
                                        <label>Fecha desde</label>
                                        <input type="date" name="fecha_desde" class="form-control"
                                               value="{{ $fecha_desde }}">
                                    </div>

                                    <!-- Fecha hasta -->
                                    <div class="col-md-4 mb-3">
                                        <label>Fecha hasta</label>
                                        <input type="date" name="fecha_hasta" class="form-control"
                                               value="{{ $fecha_hasta }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ route('auditoria.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Limpiar filtros
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-striped table-hover">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <tr>
                                            <th style="color:#fff; width: 5%;">ID</th>
                                            <th style="color:#fff; width: 12%;">Fecha</th>
                                            <th style="color:#fff; width: 15%;">Usuario</th>
                                            <th style="color:#fff; width: 15%;">Tabla</th>
                                            <th style="color:#fff; width: 15%;">Item</th>
                                            <th style="color:#fff; width: 10%;">Acción</th>
                                            <th style="color:#fff; width: 28%;">Cambios</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($auditorias as $auditoria)
                                            <tr>
                                                <td><span class="badge badge-light">{{ $auditoria->id }}</span></td>
                                                <td>
                                                    <small>{{ $auditoria->created_at->format('d/m/Y') }}</small><br>
                                                    <small class="text-muted">{{ $auditoria->created_at->format('H:i:s') }}</small>
                                                </td>
                                                <td>
                                                    <strong>{{ $auditoria->user->apellido }}</strong><br>
                                                    <small class="text-muted">{{ $auditoria->user->name }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ $auditoria->nombre_tabla }}</span>
                                                </td>
                                                <td>
                                                    @switch($auditoria->nombre_tabla)
                                                        @case('user')
                                                            @if (!is_null($auditoria->usuarioModificado))
                                                                {{ $auditoria->usuarioModificado->name }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @break

                                                        @case('flota_general')
                                                            @if (!is_null($auditoria->flotaModificada))
                                                                {{ $auditoria->flotaModificada->equipo->tei }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @break

                                                        @case('historico')
                                                            @if (!is_null($auditoria->historicoModificado))
                                                                {{ $auditoria->historicoModificado->equipo->tei }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @break

                                                        @case('recursos')
                                                            @if (!is_null($auditoria->recursoModificado))
                                                                {{ $auditoria->recursoModificado->nombre }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @break

                                                        @default
                                                            <span class="text-muted">N/A</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @switch($auditoria->accion)
                                                        @case('create')
                                                            <span class="badge badge-success">Crear</span>
                                                            @break
                                                        @case('update')
                                                            <span class="badge badge-warning">Editar</span>
                                                            @break
                                                        @case('delete')
                                                            <span class="badge badge-danger">Eliminar</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">{{ $auditoria->accion }}</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    <small style="word-break: break-word;">
                                                        {{ Str::limit($auditoria->cambios, 100) }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                                    No se encontraron registros con los filtros aplicados
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">
                                        Mostrando {{ $auditorias->firstItem() ?? 0 }} a {{ $auditorias->lastItem() ?? 0 }}
                                        de {{ $auditorias->total() }} registros
                                    </small>
                                </div>
                                <div>
                                    {!! $auditorias->appends(request()->query())->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
