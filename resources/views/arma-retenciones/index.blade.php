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
                                - Arma: {{ $alerta->arma_numero ?? $alerta->arma?->numero ?? '-' }}
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
                                            {{ $retencion->arma_numero ?? $retencion->arma?->numero ?? '-' }}
                                            @if($retencion->chaleco_numero || $retencion->chaleco)
                                                <br><small class="text-muted">Chaleco: {{ $retencion->chaleco_numero ?? $retencion->chaleco?->numero_serie }}</small>
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
                                            @can('crear-arma-retencion')
                                                <a href="{{ route('armas.retenciones.documento', $retencion) }}" class="btn btn-sm btn-secondary" title="Generar Acta Word">
                                                    <i class="fas fa-file-word"></i>
                                                </a>
                                            @endcan
                                            @can('editar-arma-retencion')
                                                <a href="{{ route('armas.retenciones.edit', $retencion) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('borrar-arma-retencion')
                                                <button type="button" class="btn btn-sm btn-danger" title="Eliminar"
                                                        data-toggle="modal" data-target="#eliminarModal"
                                                        data-id="{{ $retencion->id }}"
                                                        data-funcionario="{{ $retencion->personal->apellido }}, {{ $retencion->personal->nombre }}"
                                                        data-arma="{{ $retencion->arma_numero ?? $retencion->arma?->numero ?? 'N/A' }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

                    @if ($ultimasDevoluciones->isNotEmpty())
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary btn-block" type="button" data-toggle="collapse" data-target="#devolucionesCollapse" aria-expanded="false" aria-controls="devolucionesCollapse">
                                <i class="fas fa-archive"></i> Últimas devoluciones ({{ $ultimasDevoluciones->count() }})
                                <i class="fas fa-chevron-down ml-2 toggle-icon"></i>
                            </button>
                            <div class="collapse" id="devolucionesCollapse">
                                <div class="card card-body mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-muted"><i class="fas fa-check-circle text-success"></i> Armas devueltas a funcionarios</h6>
                                        <a href="{{ route('armas.retenciones.historial') }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-history"></i> Ver historial completo
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Funcionario</th>
                                                    <th>Arma</th>
                                                    <th>Tipo</th>
                                                    <th>Fec. Devolución</th>
                                                    <th>Estado</th>
                                                    <th class="text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($ultimasDevoluciones as $devuelta)
                                                    <tr>
                                                        <td>
                                                            {{ $devuelta->personal->apellido }}, {{ $devuelta->personal->nombre }}
                                                        </td>
                                                        <td>{{ $devuelta->arma_numero ?? $devuelta->arma?->numero ?? '-' }}</td>
                                                        <td>
                                                            <span class="badge badge-{{ $devuelta->tipo == 'RETENCIÓN' ? 'warning' : ($devuelta->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                                                {{ $devuelta->tipo_label }}
                                                            </span>
                                                        </td>
                                                        <td>{{ optional($devuelta->fecha_devolucion)->format('d/m/Y') ?? '-' }}</td>
                                                        <td>
                                                            <span class="badge badge-success">{{ $devuelta->estado_label }}</span>
                                                        </td>
                                                        <td class="text-right">
                                                            <a href="{{ route('armas.retenciones.show', $devuelta) }}" class="btn btn-sm btn-outline-info" title="Ver detalle">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @can('borrar-arma-retencion')
        <div class="modal fade" id="eliminarModal" tabindex="-1" role="dialog" aria-labelledby="eliminarModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="eliminarModalLabel">
                            <i class="fas fa-exclamation-triangle"></i> Confirmar eliminación
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="eliminarForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <strong>Esta acción es irreversible.</strong> Solo debe usarse en caso de registro por error operativo.
                            </div>
                            <p>Está por eliminar la retención de: <strong id="eliminarFuncionario"></strong></p>
                            <p>Arma: <strong id="eliminarArma"></strong></p>
                            <div class="form-group">
                                <label for="motivo_eliminacion">Motivo de la eliminación <span class="text-danger">*</span></label>
                                <textarea name="motivo_eliminacion" id="motivo_eliminacion" class="form-control" rows="3"
                                          placeholder="Describa el motivo por el cual se elimina este registro..."
                                          minlength="10" maxlength="500" required></textarea>
                                <small class="form-text text-muted">Mínimo 10 caracteres. Este motivo queda registrado en la auditoría.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Eliminar registro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#eliminarModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var retencionId = button.data('id');
                var funcionario = button.data('funcionario');
                var arma = button.data('arma');
                var action = "{{ route('armas.retenciones.destroy', '__ID__') }}".replace('__ID__', retencionId);
                var modal = $(this);
                modal.find('#eliminarForm').attr('action', action);
                modal.find('#eliminarFuncionario').text(funcionario);
                modal.find('#eliminarArma').text(arma);
                modal.find('#motivo_eliminacion').val('');
            });
        });
    </script>
@endpush
