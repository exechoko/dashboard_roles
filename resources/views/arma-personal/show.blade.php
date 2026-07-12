@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Detalle de Funcionario</h3>
            <div>
                <a href="{{ route('armas.personal.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @can('editar-personal')
                    <a href="{{ route('armas.personal.edit', $personal) }}" class="btn btn-primary">
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
                                    <th style="width: 35%">Apellido:</th>
                                    <td><strong>{{ $personal->apellido }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $personal->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Legajo Policial (LP):</th>
                                    <td>{{ $personal->lp }}</td>
                                </tr>
                                <tr>
                                    <th>Jerarquía:</th>
                                    <td>{{ $personal->jerarquia }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-gun"></i> Arma Asignada</h4>
                        </div>
                        <div class="card-body">
                            @if($personal->tieneArmaAsignada())
                                <table class="table table-borderless">
                                    <tr>
                                        <th style="width: 35%">Numeración:</th>
                                        <td><strong>{{ $personal->numeracion_arma }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Tipo de Arma:</th>
                                        <td>{{ $personal->tipoArma?->nombre ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>N° Chaleco:</th>
                                        <td>{{ $personal->nro_chaleco ?? 'No asignado' }}</td>
                                    </tr>
                                </table>
                            @else
                                <div class="text-muted py-2">
                                    <i class="fas fa-exclamation-circle"></i> El funcionario no tiene un arma asignada.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Historial de Retenciones</h4>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('armas.personal.show', ['personal' => $personal]) }}"
                                   class="btn {{ !$estadoFiltro ? 'btn-primary' : 'btn-outline-primary' }}">
                                    Todas
                                </a>
                                <a href="{{ route('armas.personal.show', ['personal' => $personal, 'estado' => 'EN_ARMERIA']) }}"
                                   class="btn {{ $estadoFiltro === 'EN_ARMERIA' ? 'btn-warning' : 'btn-outline-warning' }}">
                                    En Armería
                                </a>
                                <a href="{{ route('armas.personal.show', ['personal' => $personal, 'estado' => 'EN_JEF_CENTRAL']) }}"
                                   class="btn {{ $estadoFiltro === 'EN_JEF_CENTRAL' ? 'btn-info' : 'btn-outline-info' }}">
                                    En Jef. Central
                                </a>
                                <a href="{{ route('armas.personal.show', ['personal' => $personal, 'estado' => 'DEVUELTA']) }}"
                                   class="btn {{ $estadoFiltro === 'DEVUELTA' ? 'btn-success' : 'btn-outline-success' }}">
                                    Devueltas
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($personal->retenciones->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>Arma</th>
                                                <th>Tipo</th>
                                                <th>Motivo</th>
                                                <th>Fecha Posesión</th>
                                                <th>Estado</th>
                                                <th>Días Restantes</th>
                                                <th class="text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($personal->retenciones as $retencion)
                                                <tr>
                                                    <td>{{ $personal->numeracion_arma }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $retencion->tipo == 'RETENCIÓN' ? 'warning' : ($retencion->tipo == 'REGULACIÓN' ? 'info' : 'secondary') }}">
                                                            {{ $retencion->tipo_label }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $retencion->motivo->nombre }}</td>
                                                    <td>{{ $retencion->fecha_posesion->format('d/m/Y') }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $retencion->estado == 'DEVUELTA' ? 'success' : ($retencion->estado == 'EN_JEF_CENTRAL' ? 'info' : 'warning') }}">
                                                            {{ $retencion->estado_label }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($retencion->dias_restantes !== null)
                                                            <span class="badge badge-{{ $retencion->dias_restantes <= 5 ? 'danger' : ($retencion->dias_restantes <= 15 ? 'warning' : 'success') }}">
                                                                {{ $retencion->dias_restantes }} días
                                                            </span>
                                                        @else
                                                            <span class="text-muted">No aplica</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        <a href="{{ route('armas.retenciones.show', $retencion) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle"></i> El funcionario no posee retenciones registradas.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($personal->armasAnteriores->isNotEmpty())
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-history"></i> Historial de Armas</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>Numeración</th>
                                                <th>Tipo</th>
                                                <th>Chaleco</th>
                                                <th>Fecha Cambio</th>
                                                <th>Motivo</th>
                                                <th>Registrado por</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($personal->armasAnteriores as $armaAnterior)
                                                <tr>
                                                    <td><strong>{{ $armaAnterior->numeracion_arma }}</strong></td>
                                                    <td>{{ $armaAnterior->armaTipo->nombre }}</td>
                                                    <td>{{ $armaAnterior->nro_chaleco ?? '-' }}</td>
                                                    <td>{{ $armaAnterior->fecha_cambio->format('d/m/Y') }}</td>
                                                    <td>{{ $armaAnterior->motivo_cambio ?? '-' }}</td>
                                                    <td>{{ $armaAnterior->creadoPor?->name ?? 'Sistema' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

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
                                    <td>{{ $personal->creadoPor->name ?? 'Sistema' }} - {{ $personal->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                @if($personal->actualizadoPor)
                                    <tr>
                                        <th>Actualizado por:</th>
                                        <td>{{ $personal->actualizadoPor->name }} - {{ $personal->updated_at->format('d/m/Y H:i') }}</td>
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
