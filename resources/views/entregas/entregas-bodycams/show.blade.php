{{-- resources/views/entregas/entregas-bodycams/show.blade.php --}}

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

            <div class="row">
                {{-- Información de la Entrega --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información del Acta N° {{ $entrega->id }}</h4>
                            <div class="card-header-action">
                                @switch($entrega->estado)
                                    @case('entregado')
                                        <span class="badge badge-warning badge-lg">Entregado</span>
                                        @break
                                    @case('devolucion_parcial')
                                        <span class="badge badge-info badge-lg">Devolución Parcial</span>
                                        @break
                                    @case('devuelto')
                                        <span class="badge badge-success badge-lg">Devuelto</span>
                                        @break
                                    @case('perdido')
                                        <span class="badge badge-danger badge-lg">Perdido</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Fecha de Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->fecha_entrega->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Hora de Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->hora_entrega }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Dependencia:</strong></label>
                                        <p class="form-control-static">{{ $entrega->dependencia }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Personal Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->personal_receptor ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Legajo Receptor:</strong></label>
                                        <p class="form-control-static">{{ $entrega->legajo_receptor ?: 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Entregó:</strong></label>
                                        <p class="form-control-static">{{ $entrega->personal_entrega ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Legajo Entrega:</strong></label>
                                        <p class="form-control-static">{{ $entrega->legajo_entrega ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Usuario sistema:</strong></label>
                                        <p class="form-control-static">
                                            <span class="badge badge-info badge-lg">{{ $entrega->usuario_creador }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Motivo Operativo:</strong></label>
                                <p class="form-control-static">{{ $entrega->motivo_operativo }}</p>
                            </div>

                            @if($entrega->observaciones)
                                <div class="form-group">
                                    <label><strong>Observaciones:</strong></label>
                                    <p class="form-control-static">{{ $entrega->observaciones }}</p>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Fecha de Creación:</strong></label>
                                        <p class="form-control-static">{{ $entrega->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Última Actualización:</strong></label>
                                        <p class="form-control-static">{{ $entrega->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vista de Imágenes y Archivos --}}
                    @if($entrega->rutas_imagenes)
                        @php
                            $archivos = json_decode($entrega->rutas_imagenes, true) ?? [];
                        @endphp

                        @if(count($archivos) > 0)
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-camera"></i> Imágenes y Archivos Adjuntos ({{ count($archivos) }})</h4>
                                    <div class="card-header-action">
                                        <small class="text-muted">Haz clic para ver en tamaño completo</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    {{-- Si hay solo un archivo, mostrarlo grande --}}
                                    @if(count($archivos) == 1)
                                        @php
                                            $ruta = $archivos[0];
                                            $esImagen = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $ruta);
                                            $nombreArchivo = basename($ruta);
                                            $extension = pathinfo($ruta, PATHINFO_EXTENSION);
                                        @endphp

                                        <div class="d-flex justify-content-center align-items-center" style="min-height: 300px;">
                                            @if($esImagen)
                                                {{-- Imagen única grande --}}
                                                <div class="image-container text-center">
                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                        <img src="{{ asset($ruta) }}" alt="Imagen"
                                                            style="max-height: 400px; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); cursor: pointer;">
                                                    </a>
                                                    <div class="mt-3">
                                                        <h6 class="text-muted">{{ $nombreArchivo }}</h6>
                                                        <a href="{{ asset($ruta) }}" target="_blank" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-external-link-alt"></i> Ver en tamaño completo
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                {{-- Archivo único --}}
                                                <div class="file-container text-center">
                                                    <div class="file-icon mb-3">
                                                        @if(strpos($ruta, '.pdf') !== false)
                                                            <i class="fas fa-file-pdf fa-5x text-danger"></i>
                                                        @elseif(strpos($ruta, '.doc') !== false || strpos($ruta, '.docx') !== false)
                                                            <i class="fas fa-file-word fa-5x text-primary"></i>
                                                        @elseif(strpos($ruta, '.xlsx') !== false || strpos($ruta, '.xls') !== false)
                                                            <i class="fas fa-file-excel fa-5x text-success"></i>
                                                        @elseif(strpos($ruta, '.zip') !== false || strpos($ruta, '.rar') !== false)
                                                            <i class="fas fa-file-archive fa-5x text-warning"></i>
                                                        @else
                                                            <i class="fas fa-file fa-5x text-secondary"></i>
                                                        @endif
                                                    </div>
                                                    <h5 class="mb-3">{{ $nombreArchivo }}</h5>
                                                    <a href="{{ asset($ruta) }}" target="_blank" class="btn btn-success btn-lg">
                                                        <i class="fas fa-download"></i> Descargar/Ver Archivo
                                                    </a>
                                                    <div class="mt-2">
                                                        <span class="badge badge-info">{{ strtoupper($extension) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Múltiples archivos - Grid style --}}
                                        <div class="files-grid">
                                            <div class="row">
                                                @foreach($archivos as $index => $ruta)
                                                    @php
                                                        $esImagen = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $ruta);
                                                        $nombreArchivo = basename($ruta);
                                                        $extension = pathinfo($ruta, PATHINFO_EXTENSION);
                                                    @endphp

                                                    <div class="col-md-3 col-sm-4 col-6 mb-4">
                                                        <div class="file-item text-center">
                                                            @if($esImagen)
                                                                {{-- Imagen --}}
                                                                <div class="image-thumbnail">
                                                                    <a href="{{ asset($ruta) }}" target="_blank">
                                                                        <img src="{{ asset($ruta) }}" alt="Imagen {{ $index + 1 }}"
                                                                            class="img-thumbnail"
                                                                            style="width: 100%; height: 150px; object-fit: cover; cursor: pointer;">
                                                                    </a>
                                                                </div>
                                                            @else
                                                                {{-- Archivo --}}
                                                                <div class="file-icon-container" style="height: 150px; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; border-radius: 8px; background-color: #f8f9fa;">
                                                                    <a href="{{ asset($ruta) }}" target="_blank" style="text-decoration: none;">
                                                                        @if(strpos($ruta, '.pdf') !== false)
                                                                            <i class="fas fa-file-pdf" style="font-size: 48px; color: #e74c3c;" title="PDF"></i>
                                                                        @elseif(strpos($ruta, '.doc') !== false || strpos($ruta, '.docx') !== false)
                                                                            <i class="fas fa-file-word" style="font-size: 48px; color: #007aff;" title="Word Document"></i>
                                                                        @elseif(strpos($ruta, '.xlsx') !== false || strpos($ruta, '.xls') !== false)
                                                                            <i class="fas fa-file-excel" style="font-size: 48px; color: #28a745;" title="Excel Spreadsheet"></i>
                                                                        @elseif(strpos($ruta, '.zip') !== false || strpos($ruta, '.rar') !== false)
                                                                            <i class="fas fa-file-archive" style="font-size: 48px; color: #6f42c1;" title="Compressed File"></i>
                                                                        @else
                                                                            <i class="fas fa-file" style="font-size: 48px; color: #6c757d;" title="File"></i>
                                                                        @endif
                                                                    </a>
                                                                </div>
                                                            @endif

                                                            <div class="file-info mt-2">
                                                                <small class="text-muted" title="{{ $nombreArchivo }}">
                                                                    {{ strlen($nombreArchivo) > 20 ? substr($nombreArchivo, 0, 20) . '...' : $nombreArchivo }}
                                                                </small>
                                                                <div class="mt-1">
                                                                    <span class="badge badge-info badge-sm">{{ strtoupper($extension) }}</span>
                                                                </div>
                                                                <div class="mt-2">
                                                                    <a href="{{ asset($ruta) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        @if($esImagen)
                                                                            <i class="fas fa-eye"></i> Ver
                                                                        @else
                                                                            <i class="fas fa-download"></i> Descargar
                                                                        @endif
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Listado de Bodycams Entregadas --}}
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-video"></i> Bodycams Entregadas ({{ $entrega->bodycams->count() }})</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Código</th>
                                            <th>N° Serie</th>
                                            <th>Tarjeta SD</th>
                                            <th>Marca/Modelo</th>
                                            <th>Estado Actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entrega->bodycams as $index => $bodycam)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $bodycam->codigo ?: 'N/A' }}</td>
                                                <td>{{ $bodycam->numero_serie ?: 'N/A' }}</td>
                                                <td>{{ $bodycam->numero_tarjeta_sd ?: 'N/A' }}</td>
                                                <td>{{ $bodycam->marca ?: 'N/A' }} {{ $bodycam->modelo ?: '' }}</td>
                                                <td>
                                                    @switch($bodycam->estado)
                                                        @case('disponible')
                                                            <span class="badge badge-success">Disponible</span>
                                                            @break
                                                        @case('entregada')
                                                            <span class="badge badge-warning">Entregada</span>
                                                            @break
                                                        @case('mantenimiento')
                                                            <span class="badge badge-info">Mantenimiento</span>
                                                            @break
                                                        @case('perdida')
                                                            <span class="badge badge-danger">Perdida</span>
                                                            @break
                                                        @case('baja')
                                                            <span class="badge badge-dark">Baja</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">{{ ucfirst($bodycam->estado) }}</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Historial de Devoluciones --}}
                    @if($entrega->devoluciones->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-history"></i> Historial de Devoluciones ({{ $entrega->devoluciones->count() }})</h4>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    @foreach($entrega->devoluciones->sortByDesc('fecha_devolucion') as $devolucion)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <h6 class="timeline-title">
                                                        Devolución #{{ $devolucion->id }}
                                                        <span class="badge badge-success">{{ $devolucion->bodycams->count() }} bodycams</span>
                                                    </h6>
                                                    <small class="text-muted">
                                                        {{ $devolucion->fecha_devolucion->format('d/m/Y') }} - {{ $devolucion->hora_devolucion }}
                                                    </small>
                                                </div>
                                                <div class="timeline-body">
                                                    @if($devolucion->personal_devuelve)
                                                        <p><strong>Devuelto por:</strong> {{ $devolucion->personal_devuelve }}
                                                            @if($devolucion->legajo_devuelve)
                                                                (Legajo: {{ $devolucion->legajo_devuelve }})
                                                            @endif
                                                        </p>
                                                    @endif

                                                    @if($devolucion->observaciones)
                                                        <p><strong>Observaciones:</strong> {{ $devolucion->observaciones }}</p>
                                                    @endif

                                                    {{-- Mostrar miniatura si hay imágenes cargadas --}}
                                                    @php
                                                        $archivosDevolucion = json_decode($devolucion->rutas_imagenes, true) ?? [];
                                                        $imagenes = collect($archivosDevolucion)->filter(function($ruta){
                                                            return preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $ruta);
                                                        });
                                                    @endphp

                                                    @if($imagenes->count() > 0)
                                                        <div class="mt-3">
                                                            <strong>Imágenes Adjuntas:</strong>
                                                            <div class="d-flex flex-wrap mt-2">
                                                                @foreach($imagenes as $img)
                                                                    <a href="{{ asset($img) }}" target="_blank" class="mr-2 mb-2">
                                                                        <img src="{{ asset($img) }}"
                                                                            alt="Imagen devolución"
                                                                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="row">
                                                        @foreach($devolucion->bodycams as $bodycam)
                                                            <div class="col-md-6 col-lg-4 mb-2">
                                                                <div class="small-box bg-light">
                                                                    <div class="small-box-content p-2">
                                                                        <div><strong>Código:</strong> {{ $bodycam->codigo ?: 'N/A' }}</div>
                                                                        <div><strong>Serie:</strong> {{ $bodycam->numero_serie ?: 'N/A' }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <div class="timeline-footer">
                                                        <small class="text-muted">
                                                            <i class="fas fa-user"></i> Registrado por: {{ $devolucion->usuario_creador }}
                                                            el {{ $devolucion->created_at->format('d/m/Y H:i') }}
                                                        </small>
                                                        <div class="btn-group btn-group-sm ml-2">
                                                            <a href="{{ route('entrega-bodycams.devolucion.detalle', [$entrega->id, $devolucion->id]) }}"
                                                            class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> Ver Detalle
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Estado Actual de Bodycams --}}
                    <div class="card">
                        <div class="card-header">
                            <h4>Estado Actual de Bodycams ({{ $entrega->bodycams->count() }} total)</h4>
                            <div class="card-header-action">
                                @php
                                    $bodycamsPendientes = $entrega->bodycamsPendientes()->count();
                                    $bodycamsDevueltas = $entrega->bodycamsDevueltas()->count();
                                @endphp
                                <span class="badge badge-warning">{{ $bodycamsPendientes }} pendientes</span>
                                <span class="badge badge-success">{{ $bodycamsDevueltas }} devueltas</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Código</th>
                                            <th>N° Serie</th>
                                            <th>Tarjeta SD</th>
                                            <th>Estado</th>
                                            <th>Devuelta en</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entrega->bodycams as $index => $bodycam)
                                            @php
                                                $devolucion = $entrega->devoluciones()
                                                    ->whereHas('detalleDevoluciones', function($q) use ($bodycam) {
                                                        $q->where('bodycam_id', $bodycam->id);
                                                    })->first();
                                            @endphp
                                            <tr class="{{ $devolucion ? 'table-success' : 'table-warning' }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $bodycam->codigo ?: 'N/A' }}</td>
                                                <td>{{ $bodycam->numero_serie ?: 'N/A' }}</td>
                                                <td>{{ $bodycam->numero_tarjeta_sd ?: 'N/A' }}</td>
                                                <td>
                                                    @if($devolucion)
                                                        <span class="badge badge-success">Devuelta</span>
                                                    @else
                                                        <span class="badge badge-warning">Pendiente</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($devolucion)
                                                        <small>
                                                            Devolución #{{ $devolucion->id }}<br>
                                                            {{ $devolucion->fecha_devolucion->format('d/m/Y') }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Acciones --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Acciones</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                {{-- Generar Documento --}}
                                <a href="{{ route('entrega-bodycams.documento', $entrega->id) }}"
                                class="btn btn-info btn-block mb-2" target="_blank">
                                    <i class="fas fa-file-word"></i> Generar Documento
                                </a>

                                {{-- Editar --}}
                                @can('editar-entrega-bodycams')
                                    @if(in_array($entrega->estado, ['entregado', 'devolucion_parcial']))
                                        <a href="{{ route('entrega-bodycams.edit', $entrega->id) }}"
                                        class="btn btn-warning btn-block mb-2">
                                            <i class="fas fa-edit"></i> Editar Entrega
                                        </a>
                                    @endif
                                @endcan

                                {{-- Devolución --}}
                                @can('editar-entrega-bodycams')
                                    @php
                                        $bodycamsPendientes = $entrega->bodycamsPendientes()->count();
                                    @endphp
                                    @if($bodycamsPendientes > 0)
                                        <a href="{{ route('entrega-bodycams.devolver', $entrega->id) }}"
                                        class="btn btn-success btn-block mb-2">
                                            <i class="fas fa-undo"></i> Devolver Bodycams
                                            <span class="badge badge-light ml-1">{{ $bodycamsPendientes }}</span>
                                        </a>
                                    @endif
                                @endcan

                                {{-- Volver --}}
                                <a href="{{ route('entrega-bodycams.index') }}"
                                class="btn btn-secondary btn-block mb-2">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>

                                {{-- Eliminar --}}
                                @can('borrar-entrega-bodycams')
                                    <hr>
                                    <form action="{{ route('entrega-bodycams.destroy', $entrega->id) }}"
                                        method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-block"
                                                onclick="return confirm('¿Está seguro de eliminar esta entrega? Esta acción eliminará también todas las devoluciones asociadas.')">
                                            <i class="fas fa-trash"></i> Eliminar Entrega
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>

                    {{-- Información adicional --}}
                    <div class="card">
                        <div class="card-header">
                            <h4>Resumen</h4>
                        </div>
                        <div class="card-body">
                            <div class="summary-item">
                                <div class="summary-info">
                                    <h6>Total de Bodycams</h6>
                                    <h2 class="text-primary">{{ $entrega->bodycams->count() }}</h2>
                                </div>
                            </div>

                            @php
                                $bodycamsPendientes = $entrega->bodycamsPendientes()->count();
                                $bodycamsDevueltas = $entrega->bodycamsDevueltas()->count();
                            @endphp

                            @if($bodycamsPendientes > 0)
                                <div class="summary-item">
                                    <div class="summary-info">
                                        <h6>Bodycams Pendientes</h6>
                                        <h4 class="text-warning">{{ $bodycamsPendientes }}</h4>
                                    </div>
                                </div>
                            @endif

                            @if($bodycamsDevueltas > 0)
                                <div class="summary-item">
                                    <div class="summary-info">
                                        <h6>Bodycams Devueltas</h6>
                                        <h4 class="text-success">{{ $bodycamsDevueltas }}</h4>
                                    </div>
                                </div>
                            @endif

                            <div class="summary-item">
                                <div class="summary-info">
                                    <h6>Estado de la Entrega</h6>
                                    <h4>
                                        @switch($entrega->estado)
                                            @case('entregado')
                                                <span class="text-warning">Bodycams Entregadas</span>
                                                @break
                                            @case('devolucion_parcial')
                                                <span class="text-info">Devolución Parcial</span>
                                                @break
                                            @case('devuelto')
                                                <span class="text-success">Completamente Devuelto</span>
                                                @break
                                            @case('perdido')
                                                <span class="text-danger">Con Pérdidas</span>
                                                @break
                                        @endswitch
                                    </h4>
                                </div>
                            </div>

                            @if($entrega->devoluciones->count() > 0)
                                <div class="summary-item">
                                    <div class="summary-info">
                                        <h6>Total Devoluciones</h6>
                                        <h4 class="text-info">{{ $entrega->devoluciones->count() }}</h4>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush

@push('styles')
<style>
    .summary-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .form-control-static {
        padding: 7px 0;
        margin: 0;
        border: none;
        background: none;
    }

    .badge-lg {
        font-size: 14px;
        padding: 8px 12px;
    }

    .files-grid .file-item {
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.3s ease;
        background-color: #fff;
        height: 100%;
    }

    .files-grid .file-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #007bff;
    }

    .image-thumbnail img {
        transition: transform 0.3s ease;
    }

    .image-thumbnail img:hover {
        transform: scale(1.05);
    }

    .file-icon-container {
        transition: background-color 0.3s ease;
    }

    .file-icon-container:hover {
        background-color: #e9ecef !important;
    }

    .file-info {
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Estilo para archivo único */
    .file-container {
        padding: 40px 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
        min-height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .image-container {
        max-width: 100%;
        padding: 20px;
    }

    /* Timeline styles */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 30px;
    }

    .timeline-marker {
        position: absolute;
        left: 0;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #28a745;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #28a745;
    }

    .timeline-header {
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .timeline-title {
        margin: 0;
        color: #495057;
        font-size: 16px;
        font-weight: 600;
    }

    .timeline-footer {
        border-top: 1px solid #dee2e6;
        padding-top: 10px;
        margin-top: 15px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .files-grid .col-6 {
            margin-bottom: 15px;
        }

        .file-icon-container {
            height: 120px !important;
        }

        .file-icon-container i {
            font-size: 36px !important;
        }

        .image-container {
            padding: 10px;
        }

        .image-container img {
            max-height: 250px !important;
        }

        .timeline-item {
            padding-left: 20px;
        }
    }

    /* Table row colors */
    .table-success {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .table-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
</style>
@endpush
