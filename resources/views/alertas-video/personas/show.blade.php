@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">{{ $personaAlerta->apellido_nombre }}</h3>
            <div>
                <a href="{{ route('alertas-video.personas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('editar-alerta-persona')
                    <a href="{{ route('alertas-video.personas.edit', $personaAlerta) }}" class="btn btn-primary">
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
                            <h4>Información de la Persona</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 text-center mb-3">
                                    @if ($personaAlerta->foto_url)
                                        <img src="{{ $personaAlerta->foto_url }}" alt="Foto" class="alerta-foto-preview">
                                    @else
                                        <i class="fas fa-user-circle fa-5x text-muted"></i>
                                    @endif
                                </div>
                                <div class="col-md-10">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <th style="width: 20%">D.N.I.:</th>
                                            <td style="width: 30%">{{ $personaAlerta->dni ?? '-' }}</td>
                                            <th style="width: 20%">Estado:</th>
                                            <td>
                                                <span class="badge badge-alerta-{{ $personaAlerta->activo ? 'activo' : 'inactivo' }}">
                                                    {{ $personaAlerta->estado_label }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Dirección:</th>
                                            <td colspan="3">{{ $personaAlerta->direccion ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Identificado:</th>
                                            <td>
                                                <span class="badge badge-alerta-{{ $personaAlerta->identificado ? 'si' : 'no' }}">
                                                    {{ $personaAlerta->identificado ? 'Sí' : 'No' }}
                                                </span>
                                            </td>
                                            <th>Finalizado:</th>
                                            <td>
                                                <span class="badge badge-alerta-{{ $personaAlerta->finalizado ? 'si' : 'no' }}">
                                                    {{ $personaAlerta->finalizado ? 'Sí' : 'No' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Solicitado por:</th>
                                            <td>{{ $personaAlerta->solicitado_por ?? '-' }}</td>
                                            <th>Funcionario que carga:</th>
                                            <td>{{ $personaAlerta->funcionario_carga ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha del hecho:</th>
                                            <td colspan="3">{{ $personaAlerta->fecha_hecho?->format('d/m/Y') ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Hecho relacionado:</th>
                                            <td colspan="3">{{ $personaAlerta->motivo ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Observaciones:</th>
                                            <td colspan="3">{{ $personaAlerta->observaciones ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Cargada por:</th>
                                            <td colspan="3">
                                                {{ $personaAlerta->creadoPor ? trim($personaAlerta->creadoPor->apellido . ' ' . $personaAlerta->creadoPor->name) : 'Sistema' }}
                                                el {{ $personaAlerta->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @can('editar-alerta-persona')
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Cambiar estado</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('alertas-video.personas.activo', $personaAlerta) }}" method="POST" class="form-row align-items-end">
                                    @csrf
                                    <input type="hidden" name="activo" value="{{ $personaAlerta->activo ? 0 : 1 }}">
                                    <div class="col-md-6">
                                        <label>Comentario (opcional):</label>
                                        <input type="text" name="comentario" class="form-control" maxlength="500"
                                               placeholder="Motivo del cambio de estado...">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn {{ $personaAlerta->activo ? 'btn-secondary' : 'btn-success' }}">
                                            <i class="fas fa-toggle-on"></i>
                                            {{ $personaAlerta->activo ? 'Marcar como Inactivo' : 'Marcar como Activo' }}
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
                    @include('alertas-video._movimientos', ['item' => $personaAlerta, 'routeBase' => 'alertas-video.personas', 'permisoEditar' => 'editar-alerta-persona'])
                </div>
            </div>

            @can('borrar-alerta-persona')
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

    @can('borrar-alerta-persona')
        <div class="modal fade" id="eliminarModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar eliminación</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('alertas-video.personas.destroy', $personaAlerta) }}" method="POST">
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
