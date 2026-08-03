@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Armería &mdash; Chalecos</h3>
            <div>
                @can('crear-armeria')
                    <a href="{{ route('armas.armeria.chalecos.importar') }}" class="btn btn-info">
                        <i class="fas fa-upload"></i> Importar
                    </a>
                    <a href="{{ route('armas.armeria.chalecos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Cargar Chaleco
                    </a>
                @endcan
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="armeria-stat-card bg-slate">
                        <div class="small">Total</div>
                        <div class="h3 mb-0">{{ $contadores['total'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="armeria-stat-card bg-teal">
                        <div class="small">En Armería División 911</div>
                        <div class="h3 mb-0">{{ $contadores['en_division'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="armeria-stat-card bg-indigo">
                        <div class="small">En Armería Jefatura Central</div>
                        <div class="h3 mb-0">{{ $contadores['en_jefatura'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="armeria-stat-card bg-amber">
                        <div class="small">En Reparación</div>
                        <div class="h3 mb-0">{{ $contadores['en_reparacion'] }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('armas.armeria.chalecos.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar por serie, marca, modelo o móvil..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="talle" class="form-control">
                                    <option value="">Todos los talles</option>
                                    @foreach (\App\Models\ArmeriaChaleco::TALLES as $talle)
                                        <option value="{{ $talle }}" {{ request('talle') == $talle ? 'selected' : '' }}>{{ $talle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="EN_SERVICIO" {{ request('estado') == 'EN_SERVICIO' ? 'selected' : '' }}>En Servicio</option>
                                    <option value="EN_REPARACION" {{ request('estado') == 'EN_REPARACION' ? 'selected' : '' }}>En Reparación</option>
                                    <option value="DE_BAJA" {{ request('estado') == 'DE_BAJA' ? 'selected' : '' }}>De Baja</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="ubicacion" class="form-control">
                                    <option value="">Todas las ubicaciones</option>
                                    <option value="DIVISION_911" {{ request('ubicacion') == 'DIVISION_911' ? 'selected' : '' }}>Armería División 911</option>
                                    <option value="JEFATURA_CENTRAL" {{ request('ubicacion') == 'JEFATURA_CENTRAL' ? 'selected' : '' }}>Armería Jefatura Central</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('armas.armeria.chalecos.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>N° Serie</th>
                                    <th>Marca / Modelo</th>
                                    <th>Talle</th>
                                    <th>Móvil</th>
                                    <th>Estado</th>
                                    <th>Ubicación</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($chalecos as $chaleco)
                                    <tr>
                                        <td><strong>{{ $chaleco->numero_serie }}</strong></td>
                                        <td>{{ trim(($chaleco->marca ?? '') . ' ' . ($chaleco->modelo ?? '')) ?: '-' }}</td>
                                        <td>{{ $chaleco->talle ?? '-' }}</td>
                                        <td>{{ $chaleco->movil ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-armeria-{{ strtolower(str_replace('_', '-', $chaleco->estado)) }}">
                                                {{ $chaleco->estado_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-armeria-{{ $chaleco->ubicacion === 'DIVISION_911' ? 'division' : 'jefatura' }}">
                                                {{ $chaleco->ubicacion_label }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('armas.armeria.chalecos.show', $chaleco) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-armeria')
                                                <a href="{{ route('armas.armeria.chalecos.edit', $chaleco) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay chalecos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $chalecos->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('arma-armeria._styles')
@endpush
