@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Flota 911 — Préstamos</h3>
    </div>
    <div class="section-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">

                {{-- Préstamos activos --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon text-warning"><i class="fas fa-exchange-alt"></i></div>
                            <div>
                                <h5 class="header-title">Préstamos activos</h5>
                                <small class="text-muted">{{ $prestamos->count() }} activo(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($prestamos->isEmpty())
                            <div class="text-center py-4 text-muted">No hay préstamos activos.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Desde</th>
                                        <th>Novedades salida</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prestamos as $prestamo)
                                    <tr>
                                        <td>
                                            <strong>{{ $prestamo->vehiculo->marca }} {{ $prestamo->vehiculo->modelo }}</strong>
                                            <br><span class="tei-badge">{{ $prestamo->vehiculo->dominio }}</span>
                                        </td>
                                        <td><small>{{ $prestamo->destinoOrigen->nombre }}</small></td>
                                        <td><small>{{ $prestamo->destinoDestino->nombre }}</small></td>
                                        <td><small>{{ $prestamo->fecha_salida->format('d/m/Y H:i') }}</small></td>
                                        <td><small class="text-muted">{{ Str::limit($prestamo->observaciones_salida, 40) ?? '—' }}</small></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-success" data-toggle="modal"
                                                data-target="#modalDevolver{{ $prestamo->id }}">
                                                <i class="fas fa-undo mr-1"></i> Devolver
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Historial --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-history"></i></div>
                            <h5 class="header-title">Historial de préstamos</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($historial->isEmpty())
                            <div class="text-center py-4 text-muted">Sin historial.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Destino</th>
                                        <th>Salida</th>
                                        <th>Retorno</th>
                                        <th>Novedades retorno</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historial as $p)
                                    <tr>
                                        <td>{{ $p->vehiculo->marca }} {{ $p->vehiculo->modelo }} <span class="tei-badge">{{ $p->vehiculo->dominio }}</span></td>
                                        <td><small>{{ $p->destinoDestino->nombre }}</small></td>
                                        <td><small>{{ $p->fecha_salida->format('d/m/Y') }}</small></td>
                                        <td><small>{{ $p->fecha_retorno?->format('d/m/Y H:i') ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ Str::limit($p->observaciones_retorno, 40) ?? '—' }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $historial->links() }}
                        @endif
                    </div>
                </div>

            </div>

            {{-- Formulario nuevo préstamo --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-plus"></i></div>
                            <h5 class="header-title">Registrar préstamo</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('flota-911.prestamos.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Vehículo <span class="text-danger">*</span></label>
                                <select name="vehiculo_id" class="form-control" required>
                                    <option value="">— Seleccionar —</option>
                                    @foreach($destinos->where('id', 42)->first()?->getDestinosHijosRecursivo() ?? [] as $did)
                                        @php $d = $destinos->find($did); @endphp
                                        @if($d)
                                            <optgroup label="{{ $d->nombre }}">
                                                @foreach($d->recursos ?? [] as $r)
                                                    @if($r->vehiculo_id)
                                                        <option value="{{ $r->vehiculo_id }}">{{ $r->nombre }} ({{ $r->vehiculo->dominio ?? '—' }})</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sección origen <span class="text-danger">*</span></label>
                                <select name="destino_origen_id" class="form-control" required>
                                    <option value="">— Seleccionar —</option>
                                    @foreach($destinos as $d)
                                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Destino <span class="text-danger">*</span></label>
                                <select name="destino_destino_id" class="form-control" required>
                                    <option value="">— Seleccionar —</option>
                                    @foreach($destinos as $d)
                                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fecha y hora de salida <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="fecha_salida" class="form-control"
                                    value="{{ old('fecha_salida', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Novedades de salida</label>
                                <textarea name="observaciones_salida" class="form-control" rows="3"
                                    maxlength="1000" placeholder="Condición del vehículo, motivo del préstamo..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Registrar
                            </button>
                        </form>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al dashboard
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- Modales devolución --}}
@foreach($prestamos as $prestamo)
<div class="modal fade" id="modalDevolver{{ $prestamo->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar devolución — {{ $prestamo->vehiculo->dominio }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('flota-911.prestamos.devolver', $prestamo->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Fecha y hora de retorno <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="fecha_retorno" class="form-control"
                            value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Novedades del retorno</label>
                        <textarea name="observaciones_retorno" class="form-control" rows="3"
                            maxlength="1000" placeholder="Estado al retorno, novedades..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-undo mr-1"></i> Confirmar devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
