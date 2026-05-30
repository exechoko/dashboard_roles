@extends('layouts.app')

@section('content')
    <section class="section">
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

            @error('acta_firmada')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Entrega de Combustible N° {{ $entrega->id }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Fecha:</strong></label>
                                        <p class="form-control-static">{{ $entrega->fecha_entrega->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Hora:</strong></label>
                                        <p class="form-control-static">{{ substr($entrega->hora_entrega, 0, 5) }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Ticket:</strong></label>
                                        <p class="form-control-static">{{ $entrega->ticket }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Remito:</strong></label>
                                        <p class="form-control-static">{{ $entrega->remito ?: 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Empresa:</strong></label>
                                        <p class="form-control-static">{{ $entrega->empresa_soporte }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->personal_receptor }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>L.P. Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->legajo_receptor ?: 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Entregó:</strong></label>
                                        <p class="form-control-static">{{ $entrega->personal_entrega }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>L.P. Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->legajo_entrega ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Usuario sistema:</strong></label>
                                        <p class="form-control-static"><span class="badge badge-info">{{ $entrega->usuario_creador }}</span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Litros:</strong></label>
                                        <p class="form-control-static">{{ $entrega->cantidad_litros }} litros</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Bidones:</strong></label>
                                        <p class="form-control-static">{{ $entrega->cantidad_bidones }} bidones x {{ $entrega->litros_por_bidon }} L</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Combustible:</strong></label>
                                        <p class="form-control-static">{{ $entrega->combustible }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($entrega->observaciones)
                                <div class="form-group">
                                    <label><strong>Observaciones:</strong></label>
                                    <p class="form-control-static">{{ $entrega->observaciones }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-file-signature"></i> Acta Firmada</h4>
                        </div>
                        <div class="card-body">
                            @if($entrega->ruta_acta_firmada)
                                <p class="mb-3">
                                    <span class="badge badge-success">Acta firmada cargada</span>
                                </p>
                                <a href="{{ asset($entrega->ruta_acta_firmada) }}" target="_blank" class="btn btn-success">
                                    <i class="fas fa-eye"></i> Ver/Descargar Acta Firmada
                                </a>
                            @else
                                <div class="alert alert-warning">Todavía no se cargó el acta de entrega firmada.</div>
                            @endif

                            @can('editar-entrega-combustible')
                                <form action="{{ route('entrega-combustible.acta-firmada', $entrega) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                                    @csrf
                                    <div class="form-group">
                                        <label for="acta_firmada">Cargar o reemplazar acta firmada</label>
                                        <input type="file" name="acta_firmada" id="acta_firmada" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                                        <small class="text-muted">Formatos permitidos: JPG, PNG, PDF, DOC, DOCX. Máximo 8 MB.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Subir Acta Firmada
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Acciones</h4>
                        </div>
                        <div class="card-body">
                            @can('crear-entrega-combustible')
                                <a href="{{ route('entrega-combustible.documento', $entrega) }}" class="btn btn-secondary btn-block mb-2">
                                    <i class="fas fa-file-word"></i> Generar Acta Word
                                </a>
                            @endcan
                            @if($entrega->ruta_archivo)
                                <a href="{{ route('entrega-combustible.descargar', $entrega) }}" class="btn btn-primary btn-block mb-2">
                                    <i class="fas fa-download"></i> Descargar Acta Generada
                                </a>
                            @endif
                            @can('editar-entrega-combustible')
                                <a href="{{ route('entrega-combustible.edit', $entrega) }}" class="btn btn-info btn-block mb-2">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            @endcan
                            <a href="{{ route('entrega-combustible.index') }}" class="btn btn-secondary btn-block mb-2">
                                <i class="fas fa-arrow-left"></i> Volver al Listado
                            </a>
                            @can('borrar-entrega-combustible')
                                <form action="{{ route('entrega-combustible.destroy', $entrega) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('¿Está seguro de eliminar esta entrega?')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
