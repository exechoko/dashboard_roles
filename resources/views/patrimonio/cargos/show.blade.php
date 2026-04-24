@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-file-signature"></i> Cargo #{{ $cargo->id }}</h1>
    </div>
    <div class="section-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> Información</h4>
                        <div class="card-header-action">
                            <span class="badge badge-{{ $cargo->badge_class }}" style="font-size:0.9rem;padding:8px 16px">
                                <i class="{{ $cargo->badge_icon }}"></i> {{ $cargo->estado_formateado }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th width="40%">Dependencia</th><td><strong>{{ $cargo->destino->nombre ?? '-' }}</strong>
                                @if($cargo->destino && $cargo->destino->padre)<br><small class="text-muted">Dep. de: {{ $cargo->destino->padre->nombre }}</small>@endif</td></tr>
                            <tr><th>Creado por</th><td>{{ $cargo->usuario_creador ?? '-' }}</td></tr>
                            <tr><th>Fecha creación</th><td>{{ $cargo->created_at->format('d/m/Y H:i') }}</td></tr>
                            @if($cargo->historico)
                            <tr><th>Movimiento</th><td>{{ $cargo->historico->tipoMovimiento->nombre ?? '-' }}
                                <br><small>{{ \Carbon\Carbon::parse($cargo->historico->fecha_asignacion)->format('d/m/Y H:i') }}</small></td></tr>
                            @endif
                            <tr><th>Observaciones</th><td>{{ $cargo->observaciones ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-broadcast-tower"></i> Equipo</h4></div>
                    <div class="card-body">
                        @if($cargo->equipo)
                        <table class="table table-sm">
                            <tr><th width="40%">TEI</th><td><span class="badge badge-primary" style="font-size:0.9rem">{{ $cargo->equipo->tei }}</span></td></tr>
                            <tr><th>ISSI</th><td><span class="badge badge-info" style="font-size:0.9rem">{{ $cargo->equipo->issi ?? '-' }}</span></td></tr>
                            @if($cargo->equipo->tipo_terminal)
                            <tr><th>Marca/Modelo</th><td>{{ $cargo->equipo->tipo_terminal->marca }} {{ $cargo->equipo->tipo_terminal->modelo }}</td></tr>
                            @endif
                            @if($cargo->equipo->estado)
                            <tr><th>Estado</th><td>{{ $cargo->equipo->estado->nombre }}</td></tr>
                            @endif
                        </table>
                        @else <p class="text-muted">Equipo no encontrado</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($cargo->estaFirmado())
        <div class="row"><div class="col-12">
            <div class="card border-left-success">
                <div class="card-header bg-success text-white"><h4 class="text-white"><i class="fas fa-check-circle"></i> Firma Registrada</h4></div>
                <div class="card-body"><div class="row">
                    <div class="col-md-3"><strong>Firmante:</strong><br>{{ $cargo->firmante_nombre }}</div>
                    <div class="col-md-3"><strong>Cargo:</strong><br>{{ $cargo->firmante_cargo ?? '-' }}</div>
                    <div class="col-md-3"><strong>Legajo:</strong><br>{{ $cargo->firmante_legajo ?? '-' }}</div>
                    <div class="col-md-3"><strong>Fecha:</strong><br>{{ $cargo->fecha_firma->format('d/m/Y H:i') }}</div>
                </div></div>
            </div>
        </div></div>
        @endif

        @if($cargo->estaPendiente())
        <div class="row">
            <div class="col-md-6">
                <div class="card" style="border-left:4px solid #ffc107">
                    <div class="card-header bg-warning"><h4><i class="fas fa-signature"></i> Firmar Cargo</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('patrimonio.cargos.firmar', $cargo->id) }}">@csrf
                            <div class="form-group"><label>Nombre del Firmante <span class="text-danger">*</span></label>
                                <input type="text" name="firmante_nombre" class="form-control @error('firmante_nombre') is-invalid @enderror" value="{{ old('firmante_nombre') }}" required>
                                @error('firmante_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="form-group"><label>Cargo / Rango</label>
                                <input type="text" name="firmante_cargo" class="form-control" value="{{ old('firmante_cargo') }}" placeholder="Ej: Comisario..."></div>
                            <div class="form-group"><label>Legajo</label>
                                <input type="text" name="firmante_legajo" class="form-control" value="{{ old('firmante_legajo') }}"></div>
                            <div class="form-group"><label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea></div>
                            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check"></i> Confirmar Firma</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card" style="border-left:4px solid #dc3545">
                    <div class="card-header bg-danger text-white"><h4 class="text-white"><i class="fas fa-times"></i> Rechazar</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('patrimonio.cargos.rechazar', $cargo->id) }}">@csrf
                            <div class="form-group"><label>Motivo <span class="text-danger">*</span></label>
                                <textarea name="observaciones" class="form-control" rows="4" required placeholder="Motivo del rechazo...">{{ old('observaciones') }}</textarea>
                                @error('observaciones')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('¿Rechazar? El equipo perderá su estado patrimonial.')">
                                <i class="fas fa-times"></i> Rechazar Cargo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <a href="{{ route('patrimonio.cargos.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</section>
@endsection
@push('scripts')<script>setTimeout(function(){$('.alert').fadeOut('slow')},5000);</script>@endpush
@push('styles')<style>.card{border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08)}</style>@endpush
