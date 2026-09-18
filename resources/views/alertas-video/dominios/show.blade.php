@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Dominio {{ $dominioAlerta->dominio }}</h3>
            <div>
                <a href="{{ route('alertas-video.dominios.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('editar-alerta-dominio')
                    <a href="{{ route('alertas-video.dominios.edit', $dominioAlerta) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
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

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Dominio</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 20%">Dominio:</th>
                                    <td style="width: 30%">
                                        <strong>{{ $dominioAlerta->dominio }}</strong>
                                        @if ($dominioAlerta->parcial)
                                            <span class="badge badge-alerta-no" title="Patente incompleta">Parcial</span>
                                        @endif
                                    </td>
                                    <th style="width: 20%">Marca / Modelo:</th>
                                    <td>{{ trim(($dominioAlerta->marca ?? '') . ' ' . ($dominioAlerta->modelo ?? '')) ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Color:</th>
                                    <td>{{ $dominioAlerta->color ?? '-' }}</td>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge badge-alerta-{{ $dominioAlerta->activo ? 'activo' : 'inactivo' }}">
                                            {{ $dominioAlerta->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Solicitado por:</th>
                                    <td>{{ $dominioAlerta->solicitado_por ?? '-' }}</td>
                                    <th>Funcionario que carga:</th>
                                    <td>{{ $dominioAlerta->funcionario_carga ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha del hecho:</th>
                                    <td>{{ $dominioAlerta->fecha_hecho?->format('d/m/Y') ?? '-' }}</td>
                                    <th>Cámara / Detecciones LPR:</th>
                                    <td style="white-space: pre-line;">{{ $dominioAlerta->camara_texto ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Hecho relacionado:</th>
                                    <td colspan="3">{{ $dominioAlerta->motivo ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Procedimiento / Observaciones:</th>
                                    <td colspan="3">{{ $dominioAlerta->observaciones ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Cargado por:</th>
                                    <td colspan="3">
                                        {{ $dominioAlerta->creadoPor ? trim($dominioAlerta->creadoPor->apellido . ' ' . $dominioAlerta->creadoPor->name) : 'Sistema' }}
                                        el {{ $dominioAlerta->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @can('editar-alerta-dominio')
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Cambiar estado</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('alertas-video.dominios.activo', $dominioAlerta) }}" method="POST" class="form-row align-items-end">
                                    @csrf
                                    <input type="hidden" name="activo" value="{{ $dominioAlerta->activo ? 0 : 1 }}">
                                    <div class="col-md-6">
                                        <label>Comentario (opcional):</label>
                                        <input type="text" name="comentario" class="form-control" maxlength="500"
                                               placeholder="Motivo del cambio de estado...">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn {{ $dominioAlerta->activo ? 'btn-secondary' : 'btn-success' }}">
                                            <i class="fas fa-toggle-on"></i>
                                            {{ $dominioAlerta->activo ? 'Marcar como Inactivo' : 'Marcar como Activo' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row mt-3">
                <div class="col-md-12">
                    @include('alertas-video._movimientos', ['item' => $dominioAlerta, 'routeBase' => 'alertas-video.dominios', 'permisoEditar' => 'editar-alerta-dominio'])
                </div>
            </div>

            @can('borrar-alerta-dominio')
                <div class="row mt-3">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#eliminarModal">
                            <i class="fas fa-trash"></i> Eliminar registro
                        </button>
                    </div>
                </div>
            @endcan
        </div>
    </section>

    @can('borrar-alerta-dominio')
        <div class="modal fade" id="eliminarModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar eliminación</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('alertas-video.dominios.destroy', $dominioAlerta) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <strong>Esta acción es irreversible.</strong> Solo debe usarse en caso de error operativo.
                            </div>
                            <div class="form-group">
                                <label for="motivo_eliminacion">Motivo de la eliminación <span class="text-danger">*</span></label>
                                <textarea name="motivo_eliminacion" id="motivo_eliminacion" class="form-control" rows="3"
                                          minlength="10" maxlength="500" required></textarea>
                                <small class="form-text text-muted">Mínimo 10 caracteres. Queda registrado en la auditoría.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('styles')
    @include('alertas-video._styles')
@endpush
