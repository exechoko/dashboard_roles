@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">
            Historial — {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
            <small class="text-muted ml-2">{{ $vehiculo->dominio }}</small>
        </h3>
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
            {{-- Columna principal: historial --}}
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-history"></i></div>
                            <div>
                                <h5 class="header-title">Novedades</h5>
                                <small class="text-muted">{{ $novedades->total() }} registro(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($novedades as $novedad)
                        <div class="border rounded p-3 mb-3 {{ $novedad->resuelta ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge badge-{{ $novedad->resuelta ? 'success' : 'danger' }} mr-2">
                                        {{ $novedad->resuelta ? 'Resuelta' : 'Pendiente' }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $novedad->fecha_novedad->format('d/m/Y') }}
                                        @if($novedad->km_actuales)
                                            &mdash; {{ number_format($novedad->km_actuales) }} km
                                        @endif
                                        &mdash; registrada por {{ $novedad->usuario?->name ?? '—' }}
                                    </small>
                                </div>
                                @can('gestionar-flota-911')
                                @if(!$novedad->resuelta)
                                <form action="{{ route('flota-911.novedades.resolver', $novedad->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success" title="Marcar como resuelta">
                                        <i class="fas fa-check mr-1"></i> Resolver
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>

                            <p class="mb-2">{{ $novedad->descripcion }}</p>

                            {{-- Seguimientos --}}
                            @if($novedad->seguimientos->isNotEmpty())
                            <div class="ml-3 border-left pl-3 mt-2">
                                @foreach($novedad->seguimientos as $seg)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-reply mr-1"></i>
                                        {{ $seg->created_at->format('d/m/Y H:i') }} — {{ $seg->usuario?->name ?? '—' }}
                                    </small>
                                    <p class="mb-0 small">{{ $seg->descripcion }}</p>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Adjuntos --}}
                            @if($novedad->adjuntos->isNotEmpty())
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                @foreach($novedad->adjuntos as $adjunto)
                                <div class="d-flex align-items-center border rounded px-2 py-1 mr-2 mb-1">
                                    <i class="fas fa-{{ $adjunto->esImagen() ? 'image' : 'file' }} mr-1 text-muted"></i>
                                    <a href="{{ Storage::url($adjunto->ruta) }}" target="_blank" class="small mr-2">
                                        {{ Str::limit($adjunto->nombre_original, 25) }}
                                    </a>
                                    @can('gestionar-flota-911')
                                    <form action="{{ route('flota-911.novedades.adjuntos.destroy', $adjunto->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Eliminar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Agregar seguimiento y adjunto --}}
                            @can('gestionar-flota-911')
                            @if(!$novedad->resuelta)
                            <div class="mt-3 border-top pt-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                    data-toggle="collapse" data-target="#seguimiento{{ $novedad->id }}">
                                    <i class="fas fa-plus mr-1"></i> Agregar seguimiento
                                </button>
                                <button class="btn btn-sm btn-outline-secondary ml-1" type="button"
                                    data-toggle="collapse" data-target="#adjunto{{ $novedad->id }}">
                                    <i class="fas fa-paperclip mr-1"></i> Adjuntar archivo
                                </button>
                                <div class="collapse mt-2" id="seguimiento{{ $novedad->id }}">
                                    <form action="{{ route('flota-911.novedades.seguimientos.store', $novedad->id) }}" method="POST">
                                        @csrf
                                        <div class="form-group mb-1">
                                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Actualización del estado..." required maxlength="2000"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">Guardar seguimiento</button>
                                    </form>
                                </div>
                                <div class="collapse mt-2" id="adjunto{{ $novedad->id }}">
                                    <form action="{{ route('flota-911.novedades.adjuntos.store', $novedad->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="input-group">
                                            <input type="file" name="adjunto" class="form-control" required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">Subir</button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Máximo 10 MB.</small>
                                    </form>
                                </div>
                            </div>
                            @endif
                            @endcan
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard fa-2x text-muted mb-2 d-block"></i>
                            <span class="text-muted">Este vehículo no tiene novedades registradas.</span>
                        </div>
                        @endforelse

                        {{ $novedades->links() }}
                    </div>
                </div>
            </div>

            {{-- Columna lateral: nueva novedad + estado --}}
            <div class="col-lg-4">
                @can('gestionar-flota-911')
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-plus"></i></div>
                            <h5 class="header-title">Nueva novedad</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('flota-911.novedades.store', $vehiculo->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_novedad" class="form-control"
                                    value="{{ old('fecha_novedad', today()->toDateString()) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Kilómetros actuales</label>
                                <input type="number" name="km_actuales" class="form-control"
                                    value="{{ old('km_actuales') }}" min="0" placeholder="Opcional">
                            </div>
                            <div class="form-group">
                                <label>Descripción <span class="text-danger">*</span></label>
                                <textarea name="descripcion" class="form-control" rows="4" required
                                    maxlength="2000" placeholder="Detallá la novedad...">{{ old('descripcion') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Adjuntos (opcional)</label>
                                <input type="file" name="adjuntos[]" class="form-control-file" multiple>
                                <small class="text-muted">Podés adjuntar varias fotos o archivos.</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Registrar novedad
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header-modern">
                        <div class="card-header-left">
                            <div class="header-icon"><i class="fas fa-sliders-h"></i></div>
                            <h5 class="header-title">Estado del vehículo</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $estadoActual = $vehiculo->estadoSeccion; @endphp
                        <form action="{{ route('flota-911.estado-seccion.update', $vehiculo->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="form-group">
                                <label>Estado general</label>
                                <select name="estado" class="form-control" required>
                                    @foreach(\App\Models\VehiculoEstadoSeccion::$estados as $key => $label)
                                        <option value="{{ $key }}" {{ ($estadoActual?->estado ?? 'en_servicio') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"
                                    maxlength="500">{{ $estadoActual?->observaciones }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">
                                <i class="fas fa-save mr-1"></i> Actualizar estado
                            </button>
                        </form>
                    </div>
                </div>
                @endcan

                <div class="mt-3">
                    <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al dashboard
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
