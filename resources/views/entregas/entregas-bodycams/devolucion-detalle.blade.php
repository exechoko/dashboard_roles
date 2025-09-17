@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>
            <i class="fas fa-undo-alt"></i> Detalle de Devolución #{{ $devolucion->id }}
        </h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('entrega-bodycams.show', $entrega->id) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a la entrega
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-lg-4">
                {{-- Datos generales --}}
                <div class="card">
                    <div class="card-header">
                        <h4>Información de la Devolución</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Fecha de devolución:</strong> {{ $devolucion->fecha_devolucion->format('d/m/Y') }}</p>
                        <p><strong>Hora de devolución:</strong> {{ $devolucion->hora_devolucion }}</p>
                        <p><strong>Devuelto por:</strong> {{ $devolucion->personal_devuelve ?? 'N/A' }}
                        @if($devolucion->legajo_devuelve)
                            (L.P.: {{ $devolucion->legajo_devuelve }})
                        @endif
                        </p>
                        @if($devolucion->observaciones)
                            <p><strong>Observaciones:</strong> {{ $devolucion->observaciones }}</p>
                        @endif
                        <p><strong>Registrado por:</strong> {{ $devolucion->usuario_creador }}</p>
                        <p><strong>Fecha de registro:</strong> {{ $devolucion->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                {{-- Vista de Imágenes y Archivos --}}
                @if($devolucion->rutas_imagenes)
                    @php
                        $archivos = json_decode($devolucion->rutas_imagenes, true) ?? [];
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
            </div>
        </div>

        {{-- Listado de bodycams devueltas --}}
        <div class="card">
            <div class="card-header">
                <h4>Bodycams Devueltas ({{ $devolucion->bodycams->count() }})</h4>
            </div>
            <div class="card-body">
                @if($devolucion->bodycams->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>N° Serie</th>
                                    <th>N° Tarjeta SD</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Estado Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($devolucion->bodycams as $index => $detalle)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detalle->bodycam->codigo ?? 'N/A' }}</td>
                                        <td>{{ $detalle->bodycam->numero_serie ?? 'N/A' }}</td>
                                        <td>{{ $detalle->bodycam->numero_tarjeta_sd ?? 'N/A' }}</td>
                                        <td>{{ $detalle->bodycam->marca ?? 'N/A' }}</td>
                                        <td>{{ $detalle->bodycam->modelo ?? 'N/A' }}</td>
                                        <td>
                                            @switch($detalle->bodycam->estado)
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
                                                @default
                                                    <span class="badge badge-secondary">{{ $detalle->bodycam->estado }}</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No hay bodycams en esta devolución.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
