@extends('layouts.app')

@section('content')
@php $bitCat = \App\Models\RecursoBitacora::CATEGORIAS; @endphp
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Bitácora — {{ $recurso->nombre }}</h3>
    </div>
    <div class="section-body">

        @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
            @if(session($key))
                <div class="alert alert-{{ $cls }} alert-dismissible fade show">
                    {{ session($key) }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        @php $veh = $recurso->vehiculoActual(); $est = $recurso->estadoSeccion; @endphp
        <div class="d-flex align-items-center flex-wrap mb-3" style="gap:.5rem">
            @if($veh)<span class="tei-badge"><i class="fas fa-id-card mr-1"></i>{{ $veh->dominio }}</span>@endif
            @if($veh)<span class="text-muted small">{{ implode(' / ', array_filter([$veh->tipo_vehiculo, $veh->marca, $veh->modelo])) }}</span>@endif
            <span class="badge badge-{{ $est?->badgeClass ?? 'success' }}">{{ $est?->label ?? 'En servicio' }}</span>
            <a href="{{ route('flota-911.informes.estado-flota') }}" class="btn btn-sm btn-outline-secondary ml-auto">
                <i class="fas fa-arrow-left mr-1"></i> Estado Flota
            </a>
        </div>

        @if($recordarDevolucion)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Hay un ingreso a taller del <strong>{{ $abierta->fecha_hora->format('d/m/Y H:i') }}</strong> sin devolución registrada,
                y ya hay movimientos posteriores. Registrá la devolución para dejar constancia.
            </div>
        @endif

        <div class="row">
            {{-- Nueva entrada --}}
            <div class="col-lg-4">
                @can('gestionar-flota-911')
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-plus"></i></div>
                            <h5 class="header-title">Nueva entrada</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('flota-911.estado-flota.bitacora.store', $recurso->id) }}"
                              method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Categoría <span class="text-danger">*</span></label>
                                <select name="categoria" class="form-control select2-cat" required>
                                    @foreach($bitCat as $k => $label)
                                        <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fecha y hora <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="fecha_hora" class="form-control"
                                       value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Descripción <span class="text-danger">*</span></label>
                                <textarea name="descripcion" class="form-control" rows="3" maxlength="5000" required
                                          placeholder="Qué pasó con el recurso..."></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label>Km</label>
                                    <input type="number" name="km" class="form-control" min="0">
                                </div>
                                <div class="form-group col-6">
                                    <label>Costo</label>
                                    <input type="number" step="0.01" name="costo" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Taller / mecánico</label>
                                <input type="text" name="taller" class="form-control" maxlength="191">
                            </div>
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado" class="form-control" id="inputEstadoEntrada">
                                    <option value="">Sin estado (solo bitácora)</option>
                                    <option value="abierto">Abierta (trabajo en curso)</option>
                                    <option value="cerrado">Cerrada</option>
                                </select>
                            </div>
                            <div class="custom-control custom-checkbox mb-2" id="wrapPonerTaller" style="display:none">
                                <input type="checkbox" class="custom-control-input" name="poner_en_taller" value="1" id="ponerTaller">
                                <label class="custom-control-label" for="ponerTaller">Poner el recurso <strong>En taller</strong></label>
                            </div>
                            <div class="form-group">
                                <label>Adjuntos (imagen / PDF / DOCX)</label>
                                <input type="file" name="adjuntos[]" class="form-control-file" multiple>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Guardar entrada
                            </button>
                        </form>
                    </div>
                </div>
                @endcan
            </div>

            {{-- Timeline --}}
            <div class="col-lg-8">
                @forelse($recurso->bitacora as $e)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap:.4rem">
                            <span class="badge badge-info">{{ $bitCat[$e->categoria] ?? $e->categoria }}</span>
                            <span class="text-muted small"><i class="far fa-clock mr-1"></i>{{ $e->fecha_hora->format('d/m/Y H:i') }}</span>
                            <span class="text-muted small"><i class="far fa-user mr-1"></i>{{ $e->usuario?->name }} {{ $e->usuario?->apellido }}</span>
                            @if($e->estado === 'abierto')
                                <span class="badge badge-warning">Abierta</span>
                            @elseif($e->estado === 'cerrado')
                                <span class="badge badge-success">Cerrada {{ optional($e->cerrada_en)->format('d/m/Y') }}</span>
                            @endif
                            @if($e->solicitudPendiente)
                                <span class="badge badge-secondary" title="{{ $e->solicitudPendiente->motivo }}">
                                    Cambio solicitado — pendiente
                                </span>
                            @endif
                            <span class="dropdown ml-auto">
                                <button class="btn btn-sm btn-link text-muted" data-toggle="dropdown">⋮</button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @if(!$e->solicitudPendiente)
                                    <button class="dropdown-item" data-toggle="modal" data-target="#modalEditar{{ $e->id }}">Solicitar edición</button>
                                    <button class="dropdown-item text-danger" data-toggle="modal" data-target="#modalEliminar{{ $e->id }}">Solicitar eliminación</button>
                                    @else
                                    <span class="dropdown-item-text text-muted small">Solicitud en curso</span>
                                    @endif
                                </div>
                            </span>
                        </div>

                        <p class="mb-2" style="white-space:pre-line">{{ $e->descripcion }}</p>

                        <div class="small text-muted mb-2">
                            @if($e->km)<span class="mr-3"><i class="fas fa-road mr-1"></i>{{ number_format($e->km, 0, ',', '.') }} km</span>@endif
                            @if($e->taller)<span class="mr-3"><i class="fas fa-warehouse mr-1"></i>{{ $e->taller }}</span>@endif
                            @if($e->costo)<span><i class="fas fa-dollar-sign mr-1"></i>{{ number_format($e->costo, 2, ',', '.') }}</span>@endif
                        </div>

                        @if($e->adjuntos->isNotEmpty())
                        <div class="d-flex flex-wrap mb-2" style="gap:.4rem">
                            @foreach($e->adjuntos as $ad)
                                <a href="{{ route('flota-911.bitacora.adjuntos.show', $ad->id) }}" target="_blank"
                                   class="badge badge-light border">
                                    <i class="fas {{ $ad->esImagen() ? 'fa-image' : ($ad->esPdf() ? 'fa-file-pdf' : 'fa-file') }} mr-1"></i>
                                    {{ \Illuminate\Support\Str::limit($ad->nombre_original ?? 'archivo', 24) }}
                                </a>
                            @endforeach
                        </div>
                        @endif

                        @if($e->estado === 'abierto')
                            @can('gestionar-flota-911')
                            <button class="btn btn-sm btn-outline-success mb-2" data-toggle="modal" data-target="#modalCerrar{{ $e->id }}">
                                <i class="fas fa-undo mr-1"></i> Registrar devolución
                            </button>
                            @endcan
                        @endif

                        {{-- Seguimientos --}}
                        @if($e->seguimientos->isNotEmpty())
                        <div class="border-left pl-3 mt-2" style="border-color:#dee2e6!important">
                            @foreach($e->seguimientos as $s)
                            <div class="mb-2">
                                <div class="small text-muted">
                                    {{ $s->created_at->format('d/m/Y H:i') }} — {{ $s->usuario?->name }} {{ $s->usuario?->apellido }}
                                </div>
                                <div style="white-space:pre-line">{{ $s->descripcion }}</div>
                                @if($s->adjuntos->isNotEmpty())
                                    <div class="d-flex flex-wrap mt-1" style="gap:.3rem">
                                        @foreach($s->adjuntos as $ad)
                                            <a href="{{ route('flota-911.bitacora.adjuntos.show', $ad->id) }}" target="_blank" class="badge badge-light border">
                                                <i class="fas fa-paperclip mr-1"></i>{{ \Illuminate\Support\Str::limit($ad->nombre_original ?? 'archivo', 20) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @can('gestionar-flota-911')
                        <form action="{{ route('flota-911.bitacora.seguimientos.store', $e->id) }}" method="POST"
                              enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="text" name="descripcion" class="form-control" placeholder="Agregar seguimiento..." maxlength="5000" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit">Agregar</button>
                                </div>
                            </div>
                        </form>
                        @endcan
                    </div>
                </div>

                {{-- Modales de la entrada --}}
                @can('gestionar-flota-911')
                <div class="modal fade" id="modalCerrar{{ $e->id }}" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Registrar devolución</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                        <form action="{{ route('flota-911.bitacora.cerrar', $e->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Fecha y hora de devolución</label>
                                    <input type="datetime-local" name="fecha_cierre" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="form-group">
                                    <label>Nota</label>
                                    <textarea name="nota_cierre" class="form-control" rows="2" maxlength="2000"></textarea>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="volver_en_servicio" value="1" id="volver{{ $e->id }}" checked>
                                    <label class="custom-control-label" for="volver{{ $e->id }}">Volver el recurso a <strong>En servicio</strong></label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success">Confirmar</button>
                            </div>
                        </form>
                    </div></div>
                </div>

                <div class="modal fade" id="modalEditar{{ $e->id }}" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Solicitar edición</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                        <form action="{{ route('flota-911.bitacora.solicitudes.store', $e->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="tipo" value="edicion">
                            <div class="modal-body">
                                <p class="small text-muted">Los cambios quedan pendientes hasta que un administrador los apruebe.</p>
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select name="cambios[categoria]" class="form-control">
                                        <option value="">— sin cambio —</option>
                                        @foreach($bitCat as $k => $label)
                                            <option value="{{ $k }}" {{ $e->categoria === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="cambios[descripcion]" class="form-control" rows="3" maxlength="5000">{{ $e->descripcion }}</textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6"><label>Km</label>
                                        <input type="number" name="cambios[km]" class="form-control" value="{{ $e->km }}" min="0"></div>
                                    <div class="form-group col-6"><label>Costo</label>
                                        <input type="number" step="0.01" name="cambios[costo]" class="form-control" value="{{ $e->costo }}" min="0"></div>
                                </div>
                                <div class="form-group">
                                    <label>Motivo del pedido</label>
                                    <input type="text" name="motivo" class="form-control" maxlength="500">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                            </div>
                        </form>
                    </div></div>
                </div>

                <div class="modal fade" id="modalEliminar{{ $e->id }}" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Solicitar eliminación</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                        <form action="{{ route('flota-911.bitacora.solicitudes.store', $e->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="tipo" value="eliminacion">
                            <div class="modal-body">
                                <p>Se pedirá a un administrador que elimine esta entrada.</p>
                                <div class="form-group">
                                    <label>Motivo <span class="text-danger">*</span></label>
                                    <textarea name="motivo" class="form-control" rows="2" maxlength="500" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">Enviar solicitud</button>
                            </div>
                        </form>
                    </div></div>
                </div>
                @endcan
                @empty
                <div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">
                    <i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
                    Este recurso todavía no tiene entradas en la bitácora.
                </div></div>
                @endforelse
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(function () {
    $('.select2-cat').select2({ width: '100%' });
    var estado = document.getElementById('inputEstadoEntrada');
    var wrap = document.getElementById('wrapPonerTaller');
    if (estado) {
        estado.addEventListener('change', function () {
            wrap.style.display = this.value === 'abierto' ? 'block' : 'none';
        });
    }
});
</script>
@endpush
@endsection
