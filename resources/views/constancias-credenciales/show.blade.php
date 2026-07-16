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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Acta de Credenciales N° {{ $constancia->id }}</h4>
                            <span class="badge {{ $constancia->firmada ? 'badge-success' : 'badge-warning' }}" style="font-size: 14px;">
                                {{ $constancia->estado }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Lugar:</strong></label>
                                        <p class="form-control-static">{{ $constancia->lugar }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Fecha de Entrega:</strong></label>
                                        <p class="form-control-static">{{ $constancia->fecha_entrega_formateada }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Creado por:</strong></label>
                                        <p class="form-control-static">{{ $constancia->usuario_creador_nombre }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Nombre y Apellido:</strong></label>
                                        <p class="form-control-static">{{ $constancia->nombre_apellido }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>DNI:</strong></label>
                                        <p class="form-control-static">{{ $constancia->dni }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Email:</strong></label>
                                        <p class="form-control-static">{{ $constancia->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Usuario Vinculado:</strong></label>
                                        <p class="form-control-static">
                                            @if($constancia->usuario)
                                                {{ $constancia->usuario->name }} {{ $constancia->usuario->apellido }}
                                            @else
                                                <span class="text-muted">No vinculado</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Email de notificación:</strong></label>
                                        <p class="form-control-static">{!! $constancia->email_estado !!}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Fecha de Firma:</strong></label>
                                        <p class="form-control-static">
                                            {{ $constancia->fecha_firma ? $constancia->fecha_firma->format('d/m/Y H:i') : '—' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($constancia->observaciones)
                                <div class="form-group">
                                    <label><strong>Observaciones:</strong></label>
                                    <p class="form-control-static">{{ $constancia->observaciones }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-clock"></i> Registro de eventos</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-plus-circle text-primary"></i> Acta creada por <strong>{{ $constancia->usuario_creador_nombre }}</strong></span>
                                    <small class="text-muted">{{ $constancia->created_at->format('d/m/Y H:i') }}</small>
                                </li>
                                @if($constancia->email_enviado)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-envelope text-info"></i> Email de notificación enviado</span>
                                        <small class="text-muted">{{ $constancia->fecha_envio_email ? $constancia->fecha_envio_email->format('d/m/Y H:i') : '—' }}</small>
                                    </li>
                                @endif
                                @if($constancia->ruta_archivo)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-file-word text-secondary"></i> Documento Word generado</span>
                                        <small class="text-muted">{{ $constancia->updated_at->format('d/m/Y H:i') }}</small>
                                    </li>
                                @endif
                                @if($constancia->firmada)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-check-circle text-success"></i> Acta firmada en conformidad</span>
                                        <small class="text-muted">{{ $constancia->fecha_firma ? $constancia->fecha_firma->format('d/m/Y H:i') : '—' }}</small>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-file-signature"></i> Acta Firmada</h4>
                        </div>
                        <div class="card-body">
                            @if($constancia->ruta_archivo_firmado)
                                <p class="mb-3">
                                    <span class="badge badge-success">Acta firmada cargada</span>
                                </p>
                                <a href="{{ asset($constancia->ruta_archivo_firmado) }}" target="_blank" class="btn btn-success">
                                    <i class="fas fa-eye"></i> Ver / Descargar Acta Firmada
                                </a>
                            @else
                                <div class="alert alert-warning">Todavía no se cargó el acta firmada en conformidad.</div>
                            @endif

                            @can('editar-constancias-credenciales')
                                <form action="{{ route('constancias-credenciales.upload-acta-firmada', $constancia) }}" method="POST" enctype="multipart/form-data" class="mt-4">
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
                            @if($constancia->ruta_archivo)
                                <a href="{{ route('constancias-credenciales.descargar', $constancia) }}" class="btn btn-secondary btn-block mb-2">
                                    <i class="fas fa-file-word"></i> Descargar Documento Word
                                </a>
                            @else
                                <div class="alert alert-warning mb-2">No se ha generado documento para esta constancia.</div>
                            @endif

                            @can('crear-constancias-credenciales')
                                <form action="{{ route('constancias-credenciales.enviar-email', $constancia) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-info btn-block" onclick="return confirm('¿Está seguro de enviar el email de notificación?')">
                                        <i class="fas fa-envelope"></i>
                                        {{ $constancia->email_enviado ? 'Reenviar Email' : 'Enviar Email' }} de Notificación
                                    </button>
                                </form>
                            @endcan

                            @can('editar-constancias-credenciales')
                                <a href="{{ route('constancias-credenciales.edit', $constancia) }}" class="btn btn-info btn-block mb-2">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            @endcan

                            <a href="{{ route('constancias-credenciales.index') }}" class="btn btn-secondary btn-block mb-2">
                                <i class="fas fa-arrow-left"></i> Volver al Listado
                            </a>

                            @can('borrar-constancias-credenciales')
                                <form action="{{ route('constancias-credenciales.destroy', $constancia) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('¿Está seguro de eliminar esta constancia?')">
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
