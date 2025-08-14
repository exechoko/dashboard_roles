@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>
            <i class="fas fa-undo-alt"></i> Detalle de Devolución #{{ $devolucion->id }}
        </h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a la entrega
            </a>
        </div>
    </div>

    <div class="section-body">
        {{-- Datos generales --}}
        <div class="card">
            <div class="card-header">
                <h4>Información de la Devolución</h4>
            </div>
            <div class="card-body">
                <p><strong>Fecha de devolución:</strong> {{ $devolucion->fecha_devolucion->format('d/m/Y') }}</p>
                <p><strong>Hora de devolución:</strong> {{ $devolucion->hora_devolucion }}</p>
                <p><strong>Devuelto por:</strong> {{ $devolucion->personal_devuelve ?? 'N/A' }}
                    @if($devolucion->legajo_devuelve)
                        (Legajo: {{ $devolucion->legajo_devuelve }})
                    @endif
                </p>
                @if($devolucion->observaciones)
                    <p><strong>Observaciones:</strong> {{ $devolucion->observaciones }}</p>
                @endif
                <p><strong>Registrado por:</strong> {{ $devolucion->usuario_creador }}</p>
                <p><strong>Fecha de registro:</strong> {{ $devolucion->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        {{-- Listado de equipos devueltos --}}
        <div class="card">
            <div class="card-header">
                <h4>Equipos Devueltos ({{ $devolucion->equipos->count() }})</h4>
            </div>
            <div class="card-body">
                @if($devolucion->equipos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>TEI</th>
                                    <th>ISSI</th>
                                    <th>N° Batería</th>
                                    <th>Estado Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($devolucion->equipos as $index => $detalle)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detalle->equipo->tei ?? 'N/A' }}</td>
                                        <td>{{ $detalle->equipo->issi ?? 'N/A' }}</td>
                                        <td>{{ $detalle->equipo->numero_bateria ?? 'N/A' }}</td>
                                        <td>
                                            @switch($detalle->equipo->estado->nombre)
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
                                                @default
                                                    <span class="badge badge-secondary">{{ $detalle->equipo->estado->nombre }}</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No hay equipos en esta devolución.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
