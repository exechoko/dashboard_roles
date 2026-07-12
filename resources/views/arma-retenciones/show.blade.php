@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Detalle de Retención</h3>
            <div>
                <a href="{{ route('armas.retenciones.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('editar-arma-retencion')
                    <a href="{{ route('armas.retenciones.edit', $armaRetencion) }}" class="btn btn-primary">
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Funcionario</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%">Nombre Completo:</th>
                                    <td>{{ $armaRetencion->personal->nombre_completo }}</td>
                                </tr>
                                <tr>
                                    <th>Jerarquía:</th>
                                    <td>{{ $armaRetencion->personal->jerarquia }}</td>
                                </tr>
                                <tr>
                                    <th>Legajo Policial:</th>
                                    <td>{{ $armaRetencion->personal->lp }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Arma</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%">Numeración:</th>
                                    <td>{{ $armaRetencion->personal->numeracion_arma ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td>{{ $armaRetencion->personal->tipoArma?->nombre ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>N° Chaleco:</th>
                                    <td>{{ $armaRetencion->personal->nro_chaleco ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información de la Retención</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 25%">Tipo:</th>
                                    <td>
                                        <span class="badge badge-{{ $armaRetencion->tipo == 'RETENCIÓN' ? 'warning' : ($armaRetencion->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                            {{ $armaRetencion->tipo_label }}
                                        </span>
                                    </td>
                                    <th style="width: 25%">Motivo:</th>
                                    <td>{{ $armaRetencion->motivo->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de Posesión:</th>
                                    <td>{{ $armaRetencion->fecha_posesion->format('d/m/Y') }}</td>
                                    <th>Días para Elevación:</th>
                                    <td>
                                        @if($armaRetencion->dias_restantes !== null)
                                            <span class="badge badge-{{ $armaRetencion->dias_restantes <= 5 ? 'danger' : ($armaRetencion->dias_restantes <= 15 ? 'warning' : 'success') }}">
                                                {{ $armaRetencion->dias_restantes }} días
                                            </span>
                                        @else
                                            <span class="text-muted">No aplica</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha de Elevación:</th>
                                    <td>
                                        @if($armaRetencion->fecha_elevacion)
                                            {{ $armaRetencion->fecha_elevacion->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">Pendiente</span>
                                        @endif
                                    </td>
                                    <th>Fecha de Devolución:</th>
                                    <td>
                                        @if($armaRetencion->fecha_devolucion)
                                            {{ $armaRetencion->fecha_devolucion->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado Actual:</th>
                                    <td colspan="3">
                                        <span class="badge badge-{{ $armaRetencion->estado == 'DEVUELTA' ? 'success' : ($armaRetencion->estado == 'EN_JEF_CENTRAL' ? 'info' : 'warning') }}">
                                            {{ $armaRetencion->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Observaciones:</th>
                                    <td colspan="3">{{ $armaRetencion->observaciones ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @can('editar-arma-retencion')
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Acciones</h4>
                            </div>
                            <div class="card-body">
                                @if(!$armaRetencion->fecha_elevacion && $armaRetencion->estado == 'EN_ARMERIA')
                                    <form action="{{ route('armas.retenciones.elevar', $armaRetencion) }}" method="POST" class="d-inline">
                                        @csrf
                                        <div class="form-row align-items-end">
                                            <div class="col-auto">
                                                <label>Fecha de Elevación:</label>
                                                <input type="date" name="fecha_elevacion" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-info" onclick="return confirm('¿Confirmar elevación a Jefatura Central?')">
                                                    <i class="fas fa-arrow-up"></i> Elevar a Jef. Central
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif

                                @if($armaRetencion->fecha_elevacion && !$armaRetencion->fecha_devolucion)
                                    <form action="{{ route('armas.retenciones.devolver', $armaRetencion) }}" method="POST" class="d-inline">
                                        @csrf
                                        <div class="form-row align-items-end">
                                            <div class="col-auto">
                                                <label>Fecha de Devolución:</label>
                                                <input type="date" name="fecha_devolucion" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('¿Confirmar devolución al funcionario?')">
                                                    <i class="fas fa-undo"></i> Devolver al Funcionario
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif

                                @if($armaRetencion->estado == 'DEVUELTA')
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle"></i> El arma ha sido devuelta al funcionario.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Auditoría</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 25%">Creado por:</th>
                                    <td>{{ $armaRetencion->creadoPor->name ?? 'Sistema' }} - {{ $armaRetencion->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($armaRetencion->actualizadoPor)
                                    <tr>
                                        <th>Actualizado por:</th>
                                        <td>{{ $armaRetencion->actualizadoPor->name }} - {{ $armaRetencion->updated_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
