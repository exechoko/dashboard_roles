@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Control de Armas</h3>
            <div>
                @can('crear-arma-retencion')
                    <a href="{{ route('armas.retenciones.importar') }}" class="btn btn-info">
                        <i class="fas fa-upload"></i> Importar
                    </a>
                    <a href="{{ route('armas.retenciones.exportar', request()->query()) }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Exportar
                    </a>
                    <a href="{{ route('armas.retenciones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Retención
                    </a>
                @endcan
            </div>
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

            @if ($alertas_vencimiento->isNotEmpty())
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <h6><i class="fas fa-exclamation-triangle"></i> Retenciones próximas a vencer</h6>
                    <ul class="mb-0 pl-3">
                        @foreach ($alertas_vencimiento as $alerta)
                            <li>
                                <strong>{{ $alerta->personal->apellido }}, {{ $alerta->personal->nombre }}</strong>
                                - Arma: {{ $alerta->numeracion_arma }}
                                - <span class="badge badge-{{ $alerta->dias_restantes <= 5 ? 'danger' : 'warning' }}">{{ $alerta->dias_restantes }} días</span>
                                <a href="{{ route('armas.retenciones.show', $alerta) }}" class="ml-1">Ver detalle</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('armas.retenciones.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar funcionario o arma..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="EN_ARMERIA" {{ request('estado') == 'EN_ARMERIA' ? 'selected' : '' }}>En Armería</option>
                                    <option value="EN_JEF_CENTRAL" {{ request('estado') == 'EN_JEF_CENTRAL' ? 'selected' : '' }}>En Jef. Central</option>
                                    <option value="DEVUELTA" {{ request('estado') == 'DEVUELTA' ? 'selected' : '' }}>Devuelta</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="tipo" class="form-control">
                                    <option value="">Todos los tipos</option>
                                    <option value="RETENCIÓN" {{ request('tipo') == 'RETENCIÓN' ? 'selected' : '' }}>Retención</option>
                                    <option value="REGULACIÓN" {{ request('tipo') == 'REGULACIÓN' ? 'selected' : '' }}>Regulación</option>
                                    <option value="RESGUARDO" {{ request('tipo') == 'RESGUARDO' ? 'selected' : '' }}>Resguardo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('armas.retenciones.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Arma</th>
                                    <th>Tipo</th>
                                    <th>Motivo</th>
                                    <th>Fecha Posesión</th>
                                    <th>Días Restantes</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($retenciones as $retencion)
                                    <tr>
                                        <td>
                                            <strong>{{ $retencion->personal->apellido }}, {{ $retencion->personal->nombre }}</strong>
                                            <br><small class="text-muted">{{ $retencion->personal->jerarquia }} - LP: {{ $retencion->personal->lp }}</small>
                                        </td>
                                        <td>
                                            {{ $retencion->numeracion_arma }}
                                            @if($retencion->nro_chaleco)
                                                <br><small class="text-muted">Chaleco: {{ $retencion->nro_chaleco }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $retencion->tipo == 'RETENCIÓN' ? 'warning' : ($retencion->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                                {{ $retencion->tipo_label }}
                                            </span>
                                        </td>
                                        <td>{{ $retencion->motivo->nombre }}</td>
                                        <td>{{ $retencion->fecha_posesion->format('d/m/Y') }}</td>
                                        <td>
                                            @if($retencion->dias_restantes !== null)
                                                <span class="badge badge-{{ $retencion->dias_restantes <= 5 ? 'danger' : ($retencion->dias_restantes <= 15 ? 'warning' : 'success') }}">
                                                    {{ $retencion->dias_restantes }} días
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $retencion->estado == 'DEVUELTA' ? 'success' : ($retencion->estado == 'EN_JEF_CENTRAL' ? 'info' : 'warning') }}">
                                                {{ $retencion->estado_label }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('armas.retenciones.show', $retencion) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-arma-retencion')
                                                <a href="{{ route('armas.retenciones.edit', $retencion) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('borrar-arma-retencion')
                                                <form action="{{ route('armas.retenciones.destroy', $retencion) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Está seguro de eliminar esta retención?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No hay retenciones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $retenciones->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
