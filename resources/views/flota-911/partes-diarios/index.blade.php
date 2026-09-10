@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Historial de partes diarios — División 911</h3>
    </div>
    <div class="section-body">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header-modern">
                <div class="card-header-left">
                    <div class="header-icon"><i class="fas fa-folder-open"></i></div>
                    <div>
                        <h5 class="header-title">Partes guardados</h5>
                        <small class="text-muted">Registro de qué recursos circularon y quiénes los tripularon.</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="form-row align-items-end mb-3">
                    <div class="col-md-2">
                        <label class="small font-weight-bold mb-1">Desde</label>
                        <input type="date" name="desde" class="form-control form-control-sm"
                               value="{{ $desde?->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold mb-1">Hasta</label>
                        <input type="date" name="hasta" class="form-control form-control-sm"
                               value="{{ $hasta?->toDateString() }}">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold mb-1">Tipo</label>
                        <select name="tipo" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="moviles" {{ $tipo === 'moviles' ? 'selected' : '' }}>Móviles</option>
                            <option value="motos" {{ $tipo === 'motos' ? 'selected' : '' }}>Motopatrullas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold mb-1">Guardia</label>
                        <select name="guardia" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            @foreach($guardias as $k => $label)
                                <option value="{{ $k }}" {{ $guardia === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search mr-1"></i> Filtrar</button>
                        @if($desde || $hasta || $tipo || $guardia)
                            <a href="{{ route('flota-911.partes-diarios.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead><tr>
                            <th>Fecha</th><th>Guardia</th><th>Horario</th>
                            <th>Tipo</th><th class="text-center">Tripulantes</th><th>Generó</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($partes as $parte)
                        <tr>
                            <td><strong>{{ $parte->fecha->format('d/m/Y') }}</strong></td>
                            <td>{{ $parte->guardiaLabel() }}</td>
                            <td>{{ $parte->horarioLabel() }}</td>
                            <td>
                                <span class="badge badge-{{ $parte->esMotos() ? 'warning' : 'primary' }}">
                                    {{ $parte->esMotos() ? 'Motopatrullas' : 'Móviles' }}
                                </span>
                            </td>
                            <td class="text-center">{{ $parte->dotaciones_count }}</td>
                            <td>
                                <small>
                                    {{ $parte->usuario?->name }} {{ $parte->usuario?->apellido }}
                                    <span class="text-muted d-block">{{ $parte->created_at?->format('d/m/Y H:i') }}</span>
                                </small>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('flota-911.partes-diarios.show', $parte->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye mr-1"></i> Ver
                                </a>
                                <a href="{{ route('flota-911.partes-diarios.docx', $parte->id) }}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-word"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay partes diarios que coincidan con el filtro.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $partes->links() }}</div>
            </div>
        </div>

        <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</section>
@endsection
