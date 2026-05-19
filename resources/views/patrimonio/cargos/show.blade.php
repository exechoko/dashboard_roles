@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-file-signature mr-2"></i>Cargo #{{ $cargo->id }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('patrimonio.cargos.index') }}">Cargos</a></div>
                <div class="breadcrumb-item active">Detalle</div>
            </div>
        </div>

        <div class="section-body patrimonio-cargo-show">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    {{ session('error') }}
                </div>
            @endif

            <div class="row align-items-stretch">
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información del cargo</h4>
                            <span class="badge badge-{{ $cargo->badge_class }} patrimonio-status-badge mt-2 mt-md-0">
                                <i class="{{ $cargo->badge_icon }} mr-1"></i>{{ $cargo->estado_formateado }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0 detail-table">
                                    <tbody>
                                        <tr>
                                            <th>Dependencia</th>
                                            <td>
                                                <strong>{{ $cargo->destino->nombre ?? '-' }}</strong>
                                                @if ($cargo->destino && $cargo->destino->padre)
                                                    <div class="text-muted small">Dep. de: {{ $cargo->destino->padre->nombre }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Creado por</th>
                                            <td>{{ $cargo->usuario_creador ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha creación</th>
                                            <td>{{ $cargo->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        @if ($cargo->historico)
                                            <tr>
                                                <th>Movimiento</th>
                                                <td>
                                                    {{ $cargo->historico->tipoMovimiento->nombre ?? '-' }}
                                                    <div class="text-muted small">
                                                        {{ \Carbon\Carbon::parse($cargo->historico->fecha_asignacion)->format('d/m/Y H:i') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th>Observaciones</th>
                                            <td class="text-break">{{ $cargo->observaciones ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('patrimonio.cargos.generar-acta', $cargo->id) }}" class="btn btn-primary">
                                    <i class="fas fa-file-word mr-1"></i>Generar Acta Patrimonial
                                </a>
                                @if(!$cargo->firmante_nombre)
                                    <small class="text-muted d-block mt-2">Si el cargo aún no tiene firmante registrado, el acta se genera con espacios para completar.</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 mt-4 mt-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4><i class="fas fa-broadcast-tower mr-2"></i>Equipos incluidos</h4>
                        </div>
                        <div class="card-body">
                            @if ($cargo->flotas->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>TEI</th>
                                                <th>ISSI</th>
                                                <th>Tipo / Modelo</th>
                                                <th>Estado</th>
                                                @if($cargo->estaPendiente())
                                                    <th class="text-center">Acción</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cargo->flotas as $flota)
                                                @if($flota->equipo)
                                                <tr>
                                                    <td><span class="badge badge-primary patrimonio-code-badge">{{ $flota->equipo->tei }}</span></td>
                                                    <td><span class="badge badge-info patrimonio-code-badge">{{ $flota->equipo->issi ?? '-' }}</span></td>
                                                    <td>{{ $flota->equipo->tipo_terminal ? $flota->equipo->tipo_terminal->marca . ' ' . $flota->equipo->tipo_terminal->modelo : '-' }}</td>
                                                    <td>{{ $flota->equipo->estado->nombre ?? '-' }}</td>
                                                    @if($cargo->estaPendiente())
                                                        <td class="text-center">
                                                            <form method="POST" action="{{ route('patrimonio.cargos.equipos.quitar', [$cargo->id, $flota->id]) }}" class="d-inline js-quitar-equipo">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar del cargo">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    @endif
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif ($cargo->equipo)
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0 detail-table">
                                        <tbody>
                                            <tr><th>TEI</th><td><span class="badge badge-primary patrimonio-code-badge">{{ $cargo->equipo->tei }}</span></td></tr>
                                            <tr><th>ISSI</th><td><span class="badge badge-info patrimonio-code-badge">{{ $cargo->equipo->issi ?? '-' }}</span></td></tr>
                                            @if ($cargo->equipo->tipo_terminal)
                                                <tr><th>Marca / Modelo</th><td>{{ $cargo->equipo->tipo_terminal->marca }} {{ $cargo->equipo->tipo_terminal->modelo }}</td></tr>
                                            @endif
                                            @if ($cargo->equipo->estado)
                                                <tr><th>Estado</th><td>{{ $cargo->equipo->estado->nombre }}</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No hay equipos asociados</p>
                            @endif

                            @if($cargo->estaPendiente())
                                <hr>
                                <form method="POST" action="{{ route('patrimonio.cargos.equipos.agregar', $cargo->id) }}">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label>Agregar equipo al cargo</label>
                                        <select name="flota_id" class="form-control select2 @error('flota_id') is-invalid @enderror">
                                            <option value="">Seleccione un equipo disponible...</option>
                                            @foreach($equiposDisponibles as $flotaDisponible)
                                                @if($flotaDisponible->equipo)
                                                    <option value="{{ $flotaDisponible->id }}">
                                                        TEI: {{ $flotaDisponible->equipo->tei }} - ISSI: {{ $flotaDisponible->equipo->issi ?? '-' }}
                                                        @if($flotaDisponible->cargo_id)
                                                            (en otro cargo pendiente)
                                                        @endif
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('flota_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary" {{ $equiposDisponibles->isEmpty() ? 'disabled' : '' }}>
                                        <i class="fas fa-plus mr-1"></i>Agregar equipo
                                    </button>
                                    @if($equiposDisponibles->isEmpty())
                                        <small class="text-muted ml-2">No hay equipos disponibles para esta dependencia.</small>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($cargo->estaFirmado())
                <div class="card mt-4 border-left-success">
                    <div class="card-header">
                        <h4><i class="fas fa-check-circle mr-2 text-success"></i>Firma registrada</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-6 col-xl-3 mb-3 mb-xl-0">
                                <span class="detail-label">Firmante</span>
                                <strong class="d-block text-break">{{ $cargo->firmante_nombre }}</strong>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3 mb-3 mb-xl-0">
                                <span class="detail-label">Cargo</span>
                                <strong class="d-block text-break">{{ $cargo->firmante_cargo ?? '-' }}</strong>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3 mb-3 mb-md-0">
                                <span class="detail-label">Legajo</span>
                                <strong class="d-block">{{ $cargo->firmante_legajo ?? '-' }}</strong>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <span class="detail-label">Fecha</span>
                                <strong class="d-block">{{ $cargo->fecha_firma->format('d/m/Y H:i') }}</strong>
                            </div>
                            <div class="col-12 mt-3">
                                <span class="detail-label">Dependencia del firmante</span>
                                <strong class="d-block text-break">{{ $cargo->firmanteDestino->nombre ?? '-' }}</strong>
                            </div>
                            @if($cargo->ruta_documento)
                                <div class="col-12 mt-3">
                                    <span class="detail-label">Acta firmada</span>
                                    <a href="{{ route('patrimonio.cargos.acta', $cargo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-signature mr-1"></i>{{ $cargo->acta_nombre_original ?? 'Ver acta firmada' }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($cargo->estaPendiente())
                <div class="row mt-4">
                    <div class="col-12 col-lg-7">
                        <div class="card card-action card-action-warning">
                            <div class="card-header">
                                <h4><i class="fas fa-signature mr-2"></i>Firmar cargo</h4>
                            </div>
                            <form method="POST" action="{{ route('patrimonio.cargos.firmar', $cargo->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label>Nombre del firmante <span class="text-danger">*</span></label>
                                                <input type="text" name="firmante_nombre" class="form-control @error('firmante_nombre') is-invalid @enderror" value="{{ old('firmante_nombre') }}" required>
                                                @error('firmante_nombre')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label>Cargo / Rango</label>
                                                <input type="text" name="firmante_cargo" class="form-control" value="{{ old('firmante_cargo') }}" placeholder="Ej: Comisario...">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label>Legajo</label>
                                                <input type="text" name="firmante_legajo" class="form-control" value="{{ old('firmante_legajo') }}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label>Dependencia del firmante <span class="text-danger">*</span></label>
                                                <select name="firmante_destino_id" class="form-control select2 @error('firmante_destino_id') is-invalid @enderror" required>
                                                    <option value="">Seleccione dependencia...</option>
                                                    @foreach ($destinos as $dest)
                                                        <option value="{{ $dest->id }}" {{ (old('firmante_destino_id') ?? $cargo->destino_id) == $dest->id ? 'selected' : '' }}>
                                                            {{ $dest->nombre }} @if ($dest->padre) (Dep: {{ $dest->padre->nombre }}) @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('firmante_destino_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Observaciones</label>
                                                <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label>Acta firmada</label>
                                                <input type="file" name="acta_firmada" class="form-control-file @error('acta_firmada') is-invalid @enderror" accept="application/pdf,image/jpeg,image/png,image/webp">
                                                <small class="form-text text-muted">PDF o imagen JPG, PNG, WEBP. Máximo 10 MB.</small>
                                                @error('acta_firmada')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check mr-1"></i>Confirmar firma
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5 mt-4 mt-lg-0">
                        <div class="card card-action card-action-danger">
                            <div class="card-header">
                                <h4><i class="fas fa-times mr-2"></i>Rechazar cargo</h4>
                            </div>
                            <form method="POST" action="{{ route('patrimonio.cargos.rechazar', $cargo->id) }}" class="js-rechazar-cargo">
                                @csrf
                                <div class="card-body">
                                    <div class="alert alert-light mb-3">
                                        Indique el motivo del rechazo para dejar trazabilidad del cargo patrimonial.
                                    </div>
                                    <div class="form-group mb-0">
                                        <label>Motivo <span class="text-danger">*</span></label>
                                        <textarea name="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="6" required placeholder="Motivo del rechazo...">{{ old('observaciones') }}</textarea>
                                        @error('observaciones')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times mr-1"></i>Rechazar cargo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-2">
                <a href="{{ route('patrimonio.cargos.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $('.select2').select2({ width: '100%' });

        $('.js-rechazar-cargo').on('submit', function () {
            return confirm('¿Rechazar este cargo patrimonial?');
        });

        $('.js-quitar-equipo').on('submit', function () {
            return confirm('¿Quitar este equipo del cargo patrimonial?');
        });

        setTimeout(function () {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
    </script>
@endpush

@push('styles')
    <style>
        .patrimonio-cargo-show .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
            overflow: visible;
        }

        .patrimonio-cargo-show .card-header {
            min-height: auto;
            padding: 1rem 1.25rem;
        }

        .patrimonio-cargo-show .card-header h4 {
            line-height: 1.4;
        }

        .patrimonio-status-badge,
        .patrimonio-code-badge {
            font-size: .9rem;
            padding: .45rem .75rem;
            white-space: normal;
        }

        .detail-table th {
            color: #6c757d;
            font-weight: 700;
            width: 38%;
            min-width: 130px;
            vertical-align: top;
        }

        .detail-table td {
            vertical-align: top;
        }

        .detail-label {
            color: #6c757d;
            display: block;
            font-size: .78rem;
            font-weight: 700;
            margin-bottom: .25rem;
            text-transform: uppercase;
        }

        .card-action {
            border-left: 4px solid transparent;
        }

        .card-action-warning {
            border-left-color: #fdd14e;
        }

        .card-action-danger {
            border-left-color: #fc544b;
        }

        @media (max-width: 575.98px) {
            .detail-table th,
            .detail-table td {
                display: block;
                width: 100%;
            }

            .detail-table tr + tr th {
                padding-top: .85rem;
            }
        }
    </style>
@endpush
