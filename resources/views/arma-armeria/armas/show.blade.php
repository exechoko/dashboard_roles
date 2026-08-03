@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Arma Secundaria N° {{ $armeriaArma->numero_serie }}</h3>
            <div>
                <a href="{{ route('armas.armeria.armas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('editar-armeria')
                    <a href="{{ route('armas.armeria.armas.edit', $armeriaArma) }}" class="btn btn-primary">
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
                            <h4>Información del Arma</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 20%">Tipo:</th>
                                    <td style="width: 30%">{{ $armeriaArma->tipo }}</td>
                                    <th style="width: 20%">Marca / Modelo:</th>
                                    <td>{{ trim(($armeriaArma->marca ?? '') . ' ' . ($armeriaArma->modelo ?? '')) ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>N° de Serie:</th>
                                    <td>{{ $armeriaArma->numero_serie }}</td>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge badge-armeria-{{ strtolower(str_replace('_', '-', $armeriaArma->estado)) }}">
                                            {{ $armeriaArma->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ubicación actual:</th>
                                    <td colspan="3">
                                        <span class="badge badge-armeria-{{ $armeriaArma->ubicacion === 'DIVISION_911' ? 'division' : 'jefatura' }}">
                                            {{ $armeriaArma->ubicacion_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Observaciones:</th>
                                    <td colspan="3">{{ $armeriaArma->observaciones ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Cargada por:</th>
                                    <td colspan="3">
                                        {{ $armeriaArma->creadoPor->name ?? 'Sistema' }} el {{ $armeriaArma->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @can('editar-armeria')
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Acciones</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-row align-items-end mb-3">
                                    <div class="col-auto">
                                        <label>Cambiar estado:</label>
                                        <form action="{{ route('armas.armeria.armas.estado', $armeriaArma) }}" method="POST" class="form-inline">
                                            @csrf
                                            <select name="estado" class="form-control mr-2">
                                                @foreach (\App\Models\ArmeriaArma::ESTADOS as $estado)
                                                    <option value="{{ $estado }}" {{ $armeriaArma->estado === $estado ? 'selected' : '' }}>
                                                        {{ ucfirst(strtolower(str_replace('_', ' ', $estado))) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label class="mr-2 mb-0 text-muted small">Fecha y hora:</label>
                                            <input type="datetime-local" name="fecha" class="form-control mr-2" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                                            <button type="submit" class="btn btn-armeria-amber">
                                                <i class="fas fa-sliders-h"></i> Aplicar
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                @if ($armeriaArma->ubicacion === 'DIVISION_911')
                                    <form action="{{ route('armas.armeria.armas.enviar-jefatura', $armeriaArma) }}" method="POST" class="form-row align-items-end">
                                        @csrf
                                        <div class="col-md-4">
                                            <label>Comentario (opcional):</label>
                                            <input type="text" name="comentario" class="form-control" placeholder="Motivo del envío..." maxlength="500">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Fecha y hora:</label>
                                            <input type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-armeria-indigo"
                                                    onclick="return confirm('¿Confirmar envío a Armería Jefatura Central?')">
                                                <i class="fas fa-arrow-right"></i> Enviar a Jefatura Central
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <form action="{{ route('armas.armeria.armas.retornar-division', $armeriaArma) }}" method="POST" class="form-row align-items-end">
                                        @csrf
                                        <div class="col-md-4">
                                            <label>Comentario (opcional):</label>
                                            <input type="text" name="comentario" class="form-control" placeholder="Motivo del retorno..." maxlength="500">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Fecha y hora:</label>
                                            <input type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-armeria-green"
                                                    onclick="return confirm('¿Confirmar retorno a Armería División 911?')">
                                                <i class="fas fa-arrow-left"></i> Retornar a División 911
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row mt-3">
                <div class="col-md-12">
                    @include('arma-armeria._adjuntos', ['item' => $armeriaArma, 'routeBase' => 'armas.armeria.armas'])
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    @include('arma-armeria._movimientos', ['item' => $armeriaArma, 'routeBase' => 'armas.armeria.armas'])
                </div>
            </div>

            @can('borrar-armeria')
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

    @can('borrar-armeria')
        <div class="modal fade" id="eliminarModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar eliminación</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('armas.armeria.armas.destroy', $armeriaArma) }}" method="POST">
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
    @include('arma-armeria._styles')
@endpush
