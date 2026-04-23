@extends('layouts.app')

@section('title', 'Manuales CeCoCo')

@section('content')
<div class="section-header">
    <h1><i class="fas fa-folder-open mr-2"></i>Manuales CeCoCo</h1>
</div>

<div class="section-body">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        {{-- Formulario de carga --}}
        @can('cargar-manuales-cecoco')
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-header"><h4><i class="fas fa-upload mr-2"></i>Cargar Documentos</h4></div>
                <div class="card-body">
                    <form action="{{ route('manuales.upload') }}" method="POST" enctype="multipart/form-data" id="form-upload-cecoco">
                        @csrf
                        <input type="hidden" name="tipo" value="cecoco">
                        <div class="form-group">
                            <label>Seleccionar archivo(s)</label>
                            <div class="drop-zone" id="drop-zone-cecoco">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-1">Arrastrá archivos aquí o hacé clic para seleccionar</p>
                                <small class="text-muted">PDF, DOCX, MD, HTML — máx. 50 MB por archivo</small>
                                <input type="file" name="archivos[]" id="archivos-cecoco" multiple
                                    accept=".pdf,.docx,.md,.html" class="drop-zone-input">
                            </div>
                            <div id="preview-cecoco" class="mt-2"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-upload mr-1"></i>Subir
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endcan

        {{-- Listado de documentos --}}
        <div class="col-12 @can('cargar-manuales-cecoco') col-md-8 @else col-md-12 @endcan">
            <div class="card">
                <div class="card-header"><h4><i class="fas fa-file-alt mr-2"></i>Documentos</h4></div>
                <div class="card-body p-0">
                    @if($documentos->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>No hay documentos cargados aún.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Tamaño</th>
                                        <th>Subido por</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documentos as $doc)
                                        <tr>
                                            <td>
                                                <i class="{{ \App\Helpers\IconHelper::forExtension($doc->extension) }} mr-1"></i>
                                                {{ $doc->nombre_original }}
                                            </td>
                                            <td><span class="badge badge-secondary text-uppercase">{{ $doc->extension }}</span></td>
                                            <td>{{ $doc->tamano_formateado }}</td>
                                            <td>{{ $doc->uploader->name ?? '—' }}</td>
                                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-center text-nowrap">
                                                <button class="btn btn-sm btn-info btn-ver"
                                                    data-id="{{ $doc->id }}"
                                                    data-nombre="{{ $doc->nombre_original }}"
                                                    data-ext="{{ $doc->extension }}"
                                                    title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @can('descargar-manuales-cecoco')
                                                    <a href="{{ route('manuales.download', $doc->id) }}"
                                                        class="btn btn-sm btn-success" title="Descargar">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endcan
                                                @can('borrar-manuales-cecoco')
                                                    <form action="{{ route('manuales.destroy', $doc->id) }}" method="POST" class="d-inline form-eliminar">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('manuales._visor_modal')
@endsection

@push('scripts')
@include('manuales._scripts')
@endpush

@push('styles')
@include('manuales._styles')
@endpush
