{{-- resources/views/entregas-equipos/index.blade.php --}}

@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Entregas de Equipos<br>
                <small>Listado de entregas de equipos de mano (HT) para diferentes acontecimientos/eventos</small>
            </h1>

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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Listado de Entregas</h4>
                            @can('crear-entrega-equipos')
                                <div class="card-header-action">
                                    <a href="{{ route('entrega-equipos.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Nueva Entrega
                                    </a>
                                </div>
                            @endcan
                        </div>
                        <div class="card-body">
                            {{-- Formulario de búsqueda --}}
                            <form method="GET" action="{{ route('entrega-equipos.index') }}" class="mb-4">
                                <div class="row">
                                    <div class="col-md-2">
                                        <input type="text" name="numero_acta" class="form-control"
                                               placeholder="Número de Acta" value="{{ request('numero_acta') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="tei" class="form-control"
                                               placeholder="TEI" value="{{ request('tei') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="issi" class="form-control"
                                               placeholder="ISSI" value="{{ request('issi') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="fecha" class="form-control"
                                               value="{{ request('fecha') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="dependencia" class="form-control"
                                               placeholder="Dependencia" value="{{ request('dependencia') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary btn-block mt-1">
                                            <i class="fas fa-times"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
                            </form>

                            {{-- Tabla de entregas --}}
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>N° Acta</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Dependencia</th>
                                            <th>Personal Receptor</th>
                                            <th>Entregó</th>
                                            <th>Cant. Equipos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($entregas as $entrega)
                                            <tr>
                                                <td>{{ $entrega->id }}</td>
                                                <td>{{ $entrega->fecha_entrega->format('d/m/Y') }}</td>
                                                <td>{{ $entrega->hora_entrega }}</td>
                                                <td>{{ $entrega->dependencia }}</td>
                                                <td>{{ $entrega->personal_receptor }}</td>
                                                <td>{{ $entrega->personal_entrega }}</td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        {{ $entrega->equipos->count() }} equipos
                                                    </span>
                                                </td>
                                                <td>
                                                    @switch($entrega->estado)
                                                        @case('entregado')
                                                            <span class="badge badge-warning">Entregado</span>
                                                            @break
                                                        @case('devolucion_parcial')
                                                            <span class="badge badge-danger">Devolución Parcial</span>
                                                            @break
                                                        @case('devuelto')
                                                            <span class="badge badge-success">Devuelto</span>
                                                            @break
                                                        @case('perdido')
                                                            <span class="badge badge-danger">Perdido</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">{{ ucfirst($entrega->estado) }}</span>
                                                    @endswitch

                                                    {{-- Mostrar contador de devoluciones si existen --}}
                                                    @if($entrega->devoluciones->count() > 0)
                                                        <br><small class="text-muted">{{ $entrega->devoluciones->count() }} devolución(es)</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        @can('ver-entrega-equipos')
                                                            <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-warning btn-sm mr-1">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endcan

                                                        @can('editar-entrega-equipos')
                                                            @if(in_array($entrega->estado, ['entregado', 'devolucion_parcial']))
                                                                <a href="{{ route('entrega-equipos.edit', $entrega->id) }}" class="btn btn-info btn-sm mr-1">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                        @endcan

                                                            <a href="{{ route('entrega-equipos.documento', $entrega->id) }}"
                                                               class="btn btn-secondary" target="_blank">
                                                                <i class="fas fa-file-word"></i></a>

                                                            @can('devolver-entrega-equipos')
                                                                @php
                                                                    $equiposPendientes = $entrega->equiposPendientes()->count();
                                                                @endphp
                                                                @if($equiposPendientes > 0)
                                                                    <a href="{{ route('entrega-equipos.devolver', $entrega->id) }}" class="btn btn-success btn-sm mr-1" title="Devolver equipos ({{ $equiposPendientes }} pendientes)">
                                                                        <i class="fas fa-undo"></i>
                                                                        @if($equiposPendientes < $entrega->equipos->count())
                                                                            <span class="badge badge-light" style="font-size: 10px;">{{ $equiposPendientes }}</span>
                                                                        @endif
                                                                    </a>
                                                                @endif
                                                            @endcan

                                                            @can('eliminar-entrega-equipos')
                                                                <div class="dropdown-divider"></div>
                                                                <form action="{{ route('entrega-equipos.destroy', $entrega->id) }}"
                                                                      method="POST" style="display: inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger"
                                                                            onclick="return confirm('¿Está seguro de eliminar esta entrega?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No se encontraron entregas</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="d-flex justify-content-center">
                                {{ $entregas->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush
