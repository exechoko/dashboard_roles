@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Detalles de {{ ucfirst($dependencia->tipo) }}</h3>
            <div class="alert alert-info ml-3">
                {{ $dependencia->nombre }}
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <!-- Información básica -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información General</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Tipo:</strong></label>
                                        <p>
                                            <span class="badge badge-{{ $dependencia->getBadgeClass() }}">
                                                {{ ucfirst($dependencia->tipo) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Nombre:</strong></label>
                                        <p>{{ $dependencia->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Teléfono:</strong></label>
                                        <p>
                                            {{ $dependencia->telefono ?: 'No especificado' }}
                                            @if($dependencia->getWhatsappUrl())
                                                <a href="{{ $dependencia->getWhatsappUrl() }}" target="_blank"
                                                   title="Enviar mensaje por WhatsApp" class="ml-2">
                                                    <i class="fab fa-whatsapp text-success"></i>
                                                </a>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Ubicación:</strong></label>
                                        <p>{{ $dependencia->ubicacion ?: 'No especificada' }}</p>
                                    </div>
                                </div>
                                @if($dependencia->observaciones)
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><strong>Observaciones:</strong></label>
                                        <p>{{ $dependencia->observaciones }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jerarquía -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Jerarquía</h4>
                        </div>
                        <div class="card-body">
                            @if($dependencia->padre)
                                <div class="form-group">
                                    <label><strong>Depende de:</strong></label>
                                    <p>
                                        <a href="{{ route('dependencias.show', $dependencia->padre->id) }}"
                                           class="text-decoration-none">
                                            {{ $dependencia->padre->nombre }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ ucfirst($dependencia->padre->tipo) }}</small>
                                    </p>
                                </div>
                            @else
                                <p class="text-muted">Esta es una dependencia de nivel superior</p>
                            @endif

                            <!-- Breadcrumb de jerarquía -->
                            @if($dependencia->padre)
                                <div class="form-group">
                                    <label><strong>Ruta jerárquica:</strong></label>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb bg-light">
                                            @foreach($dependencia->getRutaJerarquica() as $nivel)
                                                <li class="breadcrumb-item">
                                                    <a href="{{ route('dependencias.show', $nivel->id) }}">
                                                        {{ $nivel->nombre }}
                                                    </a>
                                                </li>
                                            @endforeach
                                            <li class="breadcrumb-item active">{{ $dependencia->nombre }}</li>
                                        </ol>
                                    </nav>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Acciones</h4>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical w-100" role="group">
                                @can('editar-dependencia')
                                    <a href="{{ route('dependencias.edit', $dependencia->id) }}"
                                       class="btn btn-success mb-2">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                @endcan

                                <a href="{{ route('dependencias.index') }}"
                                   class="btn btn-secondary mb-2">
                                    <i class="fas fa-list"></i> Volver al Listado
                                </a>

                                @if($dependencia->puedeSerEliminada())
                                    @can('borrar-dependencia')
                                        <form action="{{ route('dependencias.destroy', $dependencia->id) }}"
                                              method="POST" class="w-100">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('¿Está seguro de eliminar esta dependencia?')"
                                                    class="btn btn-danger w-100">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dependencias subordinadas -->
            @if($dependencia->hijos->count() > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>
                                    Dependencias Subordinadas
                                    <span class="badge badge-info">{{ $dependencia->hijos->count() }}</span>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <th>Tipo</th>
                                            <th>Nombre</th>
                                            <th>Teléfono</th>
                                            <th>Ubicación</th>
                                            <th>Acciones</th>
                                        </thead>
                                        <tbody>
                                            @foreach($dependencia->hijos->sortBy('nombre') as $hijo)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-{{ $hijo->getBadgeClass() }}">
                                                            {{ ucfirst($hijo->tipo) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $hijo->nombre }}</td>
                                                    <td>{{ $hijo->telefono }}</td>
                                                    <td>{{ $hijo->ubicacion }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('dependencias.show', $hijo->id) }}"
                                                               class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @can('editar-dependencia')
                                                                <a href="{{ route('dependencias.edit', $hijo->id) }}"
                                                                   class="btn btn-sm btn-success">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
