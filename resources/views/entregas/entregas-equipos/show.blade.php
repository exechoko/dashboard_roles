{{-- resources/views/entregas/entregas-equipos/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle de Entrega - Acta ' . $entrega->numero_acta)

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Detalle de Entrega</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="#">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="{{ route('entrega-equipos.index') }}">Entregas de Equipos</a></div>
                <div class="breadcrumb-item active">Detalle</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                {{-- Información de la Entrega --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Acta N° {{ $entrega->numero_acta }}</h4>
                            <div class="card-header-action">
                                @switch($entrega->estado)
                                    @case('entregado')
                                        <span class="badge badge-warning badge-lg">Entregado</span>
                                        @break
                                    @case('devuelto')
                                        <span class="badge badge-success badge-lg">Devuelto</span>
                                        @break
                                    @case('perdido')
                                        <span class="badge badge-danger badge-lg">Perdido</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Fecha de Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->fecha_entrega->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Hora de Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->hora_entrega }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Dependencia:</strong></label>
                                        <p class="form-control-static">{{ $entrega->dependencia }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Personal Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->personal_receptor }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Legajo Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->legajo_receptor ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Usuario Creador:</strong></label>
                                        <p class="form-control-static">{{ $entrega->usuario_creador }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Motivo Operativo:</strong></label>
                                <p class="form-control-static">{{ $entrega->motivo_operativo }}</p>
                            </div>

                            @if($entrega->observaciones)
                                <div class="form-group">
                                    <label><strong>Observaciones:</strong></label>
                                    <p class="form-control-static">{{ $entrega->observaciones }}</p>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Fecha de Creación:</strong></label>
                                        <p class="form-control-static">{{ $entrega->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Última Actualización:</strong></label>
                                        <p class="form-control-static">{{ $entrega->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Listado de Equipos Entregados --}}
                    <div class="card">
                        <div class="card-header">
                            <h4>Equipos Entregados ({{ $entrega->equipos->count() }})</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>ID Equipo</th>
                                            <th>TEI</th>
                                            <th>ISSI</th>
                                            <th>N° Batería</th>
                                            <th>Estado Actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entrega->equipos as $index => $equipo)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $equipo->id_equipo ?? 'N/A' }}</td>
                                                <td>{{ $equipo->tei ?? 'N/A' }}</td>
                                                <td>{{ $equipo->issi ?? 'N/A' }}</td>
                                                <td>{{ $equipo->numero_bateria ?? 'N/A' }}</td>
                                                <td>
                                                    @switch($equipo->estado)
                                                        @case('disponible')
                                                            <span class="badge badge-success">Disponible</span>
                                                            @break
                                                        @case('entregado')
                                                            <span class="badge badge-warning">Entregado</span>
                                                            @break
                                                        @case('mantenimiento')
                                                            <span class="badge badge-info">Mantenimiento</span>
                                                            @break
                                                        @case('perdido')
                                                            <span class="badge badge-danger">Perdido</span>
                                                            @break
                                                        @case('baja')
                                                            <span class="badge badge-dark">Baja</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">{{ $equipo->estado }}</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Acciones</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                {{-- Generar Documento --}}
                                <a href="{{ route('entrega-equipos.documento', $entrega->id) }}"
                                   class="btn btn-info btn-block mb-2" target="_blank">
                                    <i class="fas fa-file-word"></i> Generar Documento
                                </a>

                                {{-- Editar --}}
                                @can('editar-entrega-equipos')
                                    @if($entrega->estado === 'entregado')
                                        <a href="{{ route('entrega-equipos.edit', $entrega->id) }}"
                                           class="btn btn-warning btn-block mb-2">
                                            <i class="fas fa-edit"></i> Editar Entrega
                                        </a>
                                    @endif
                                @endcan

                                {{-- Devolver Equipos --}}
                                @can('devolver-entrega-equipos')
                                    @if($entrega->estado === 'entregado')
                                        <form action="{{ route('entrega-equipos.devolver', $entrega->id) }}"
                                              method="POST" class="mb-2">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-block"
                                                    onclick="return confirm('¿Está seguro de marcar como devueltos todos los equipos?')">
                                                <i class="fas fa-undo"></i> Devolver Equipos
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                {{-- Volver --}}
                                <a href="{{ route('entrega-equipos.index') }}"
                                   class="btn btn-secondary btn-block mb-2">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>

                                {{-- Eliminar --}}
                                @can('eliminar-entrega-equipos')
                                    <hr>
                                    <form action="{{ route('entrega-equipos.destroy', $entrega->id) }}"
                                          method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-block"
                                                onclick="return confirm('¿Está seguro de eliminar esta entrega? Esta acción no se puede deshacer.')">
                                            <i class="fas fa-trash"></i> Eliminar Entrega
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>

                    {{-- Información adicional --}}
                    <div class="card">
                        <div class="card-header">
                            <h4>Resumen</h4>
                        </div>
                        <div class="card-body">
                            <div class="summary-item">
                                <div class="summary-info">
                                    <h6>Total de Equipos</h6>
                                    <h2 class="text-primary">{{ $entrega->equipos->count() }}</h2>
                                </div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-info">
                                    <h6>Estado de la Entrega</h6>
                                    <h4>
                                        @switch($entrega->estado)
                                            @case('entregado')
                                                <span class="text-warning">En Uso</span>
                                                @break
                                            @case('devuelto')
                                                <span class="text-success">Finalizada</span>
                                                @break
                                            @case('perdido')
                                                <span class="text-danger">Con Pérdidas</span>
                                                @break
                                        @endswitch
                                    </h4>
                                </div>
                            </div>

                            @if($entrega->equipos->where('estado', 'entregado')->count() > 0)
                                <div class="summary-item">
                                    <div class="summary-info">
                                        <h6>Equipos Pendientes</h6>
                                        <h4 class="text-warning">{{ $entrega->equipos->where('estado', 'entregado')->count() }}</h4>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush

@push('styles')
<style>
    .summary-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .form-control-static {
        padding: 7px 0;
        margin: 0;
        border: none;
        background: none;
    }

    .badge-lg {
        font-size: 14px;
        padding: 8px 12px;
    }
</style>
@endpush
