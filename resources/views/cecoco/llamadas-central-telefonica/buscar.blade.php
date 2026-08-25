@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading mb-0">CeCoCo - Buscar Número (Central Telefónica)</h3>
            <a href="{{ route('cecoco.llamadas-central-telefonica') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al reporte
            </a>
        </div>
        <div class="section-body">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('cecoco.llamadas-central-telefonica.buscar') }}" class="row align-items-end g-2">
                        <div class="col-md-4">
                            <label class="form-label mb-1" for="numero">Número de teléfono</label>
                            <input type="text" id="numero" name="numero" class="form-control @error('numero') is-invalid @enderror"
                                   placeholder="Ej: 3435123456" value="{{ $numero }}" autofocus>
                            @error('numero')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1" for="desde">Desde</label>
                            <input type="date" id="desde" name="desde" class="form-control" value="{{ $desde }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1" for="hasta">Hasta</label>
                            <input type="date" id="hasta" name="hasta" class="form-control" value="{{ $hasta }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1" for="tipo">Tipo</label>
                            <select id="tipo" name="tipo" class="form-control">
                                <option value="">Todas</option>
                                <option value="recibida" {{ $tipo === 'recibida' ? 'selected' : '' }}>Recibida</option>
                                <option value="saliente" {{ $tipo === 'saliente' ? 'selected' : '' }}>Saliente</option>
                                <option value="interna" {{ $tipo === 'interna' ? 'selected' : '' }}>Interna</option>
                                <option value="otra" {{ $tipo === 'otra' ? 'selected' : '' }}>Otra</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search mr-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($numero)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="fas fa-list mr-1 text-primary"></i>
                            Resultados para "<strong>{{ $numero }}</strong>"
                            <span class="text-muted" style="font-size:.85rem;">({{ $resultados->total() }} llamadas)</span>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th>
                                        <th>ANI (origen)</th>
                                        <th>Final DNIS (destino)</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Atendida</th>
                                        <th class="text-end">Duración</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($resultados as $llamada)
                                        <tr>
                                            <td>{{ $llamada->calldate->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $llamada->ani ?? '-' }}</td>
                                            <td>{{ $llamada->final_dnis ?? '-' }}</td>
                                            <td class="text-center">
                                                @php
                                                    $badges = [
                                                        'recibida' => 'badge-primary',
                                                        'saliente' => 'badge-secondary',
                                                        'interna' => 'badge-info',
                                                        'otra' => 'badge-light',
                                                    ];
                                                @endphp
                                                <span class="badge {{ $badges[$llamada->tipo_llamada] ?? 'badge-light' }}">{{ ucfirst($llamada->tipo_llamada) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($llamada->atendida)
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-times text-danger"></i>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ intdiv($llamada->bill_duration, 60) }}:{{ str_pad($llamada->bill_duration % 60, 2, '0', STR_PAD_LEFT) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No se encontraron llamadas para ese número.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $resultados->links() }}
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
