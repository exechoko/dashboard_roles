@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Importar archivo Excel CECOCO</h2>
    <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>

@if(session('errores'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Algunos archivos tuvieron errores</h5>
        <hr>
        <ul class="mb-0">
            @foreach(session('errores') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Importación masiva de archivos</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('cecoco.importar.post') }}" enctype="multipart/form-data" id="formImportar">
                    @csrf
                    <div class="mb-3">
                        <label for="archivos" class="form-label">Seleccionar archivos (múltiples)</label>
                        <input type="file" class="form-control @error('archivos.*') is-invalid @enderror" 
                               id="archivos" name="archivos[]" accept=".xls,.xlsx,.xml" multiple required>
                        @error('archivos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Puedes seleccionar múltiples archivos a la vez (máx. 100MB cada uno). Los archivos se procesarán en segundo plano mediante un sistema de colas.
                        </div>
                    </div>

                    <div id="archivosSeleccionados" class="mb-3" style="display: none;">
                        <div class="alert alert-light border">
                            <h6 class="mb-2"><i class="bi bi-files"></i> Archivos seleccionados: <span id="totalArchivos">0</span></h6>
                            <div id="listaArchivos" class="small" style="max-height: 150px; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="mb-2">Columnas que se extraen:</h6>
                        <ul class="mb-0" style="list-style: none; padding-left: 0;">
                            <li><i class="bi bi-check2 text-success"></i> Nº Expediente</li>
                            <li><i class="bi bi-check2 text-success"></i> Fecha/Hora</li>
                            <li><i class="bi bi-check2 text-success"></i> Operador</li>
                            <li><i class="bi bi-check2 text-success"></i> Descripción</li>
                            <li><i class="bi bi-check2 text-success"></i> Dirección</li>
                            <li><i class="bi bi-check2 text-success"></i> Teléfono</li>
                            <li><i class="bi bi-check2 text-success"></i> Fecha Cierre</li>
                            <li><i class="bi bi-check2 text-success"></i> Tipo Servicio</li>
                        </ul>
                    </div>

                    <div id="loadingIndicator" class="alert alert-info" style="display: none;">
                        <div class="d-flex align-items-center">      
                            <div>
                                <strong>Agregando archivos a la cola...</strong><br>
                                <small>Los archivos se procesarán en segundo plano. Puedes cerrar esta ventana y revisar el progreso más tarde.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="btnImportar" disabled>
                        <i class="bi bi-cloud-upload"></i> Agregar a cola de procesamiento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de importaciones</h5>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="text-center p-2 rounded" style="background-color: var(--bs-secondary-bg);">
                            <small class="text-muted d-block">Archivos importados</small>
                            <strong class="fs-5">{{ $totalArchivosImportados }}</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 rounded" style="background-color: var(--bs-secondary-bg);">
                            <small class="text-muted d-block">Registros en BD</small>
                            <strong class="fs-5">{{ number_format($totalRegistrosEnBd) }}</strong>
                        </div>
                    </div>
                </div>

                @if($aniosCounts->count() > 0)
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Archivos por año:</h6>
                        @foreach($aniosCounts as $anio => $count)
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ $anio }}</span>
                                <span class="badge bg-secondary">{{ $count }} archivo(s)</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <hr>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Archivo</th>
                                <th>Período</th>
                                <th>Nuevos</th>
                                <th>Duplicados</th>
                                <th>Omitidos</th>
                                <th>Tiempo</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($importaciones as $imp)
                                <tr>
                                    <td>
                                        <small title="{{ $imp->nombre_archivo }}">
                                            {{ Str::limit($imp->nombre_archivo_corto, 20) }}
                                        </small>
                                    </td>
                                    <td><small>{{ $imp->periodo }}</small></td>
                                    <td><span class="badge bg-success">{{ $imp->registros_importados }}</span></td>
                                    <td>
                                        @if($imp->registros_duplicados > 0)
                                            <span class="badge bg-warning text-dark">{{ $imp->registros_duplicados }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($imp->registros_omitidos > 0)
                                            <span class="badge bg-danger">{{ $imp->registros_omitidos }}</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $imp->tiempo_procesamiento }}s</small></td>
                                    <td>
                                        @php
                                            $estadoBadge = [
                                                'completado' => 'success',
                                                'procesando' => 'warning',
                                                'error' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $estadoBadge[$imp->estado] ?? 'secondary' }}">
                                            {{ ucfirst($imp->estado) }}
                                        </span>
                                    </td>
                                    <td><small>{{ $imp->created_at->format('d/m/Y H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <em>No hay importaciones registradas</em>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($importaciones->hasPages())
                    <div class="mt-3">
                        {{ $importaciones->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formImportar');
    const btnImportar = document.getElementById('btnImportar');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const archivosInput = document.getElementById('archivos');
    const archivosSeleccionados = document.getElementById('archivosSeleccionados');
    const listaArchivos = document.getElementById('listaArchivos');
    const totalArchivos = document.getElementById('totalArchivos');

    archivosInput.addEventListener('change', function(e) {
        const files = e.target.files;
        
        if (files.length === 0) {
            archivosSeleccionados.style.display = 'none';
            btnImportar.disabled = true;
            return;
        }

        let totalSize = 0;
        const maxSizePerFile = 100 * 1024 * 1024;
        const maxTotalSize = 400 * 1024 * 1024;
        let hasError = false;
        
        archivosSeleccionados.style.display = 'block';
        totalArchivos.textContent = files.length;
        
        listaArchivos.innerHTML = '';
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            totalSize += file.size;
            
            const div = document.createElement('div');
            div.className = 'border-bottom py-1';
            
            let icon = '<i class="bi bi-file-earmark-excel text-success"></i>';
            let sizeClass = 'text-muted';
            
            if (file.size > maxSizePerFile) {
                icon = '<i class="bi bi-exclamation-triangle text-danger"></i>';
                sizeClass = 'text-danger';
                hasError = true;
            }
            
            div.innerHTML = `${icon} ${file.name} <span class="${sizeClass}">(${sizeInMB} MB)</span>`;
            listaArchivos.appendChild(div);
        }
        
        const totalSizeInMB = (totalSize / (1024 * 1024)).toFixed(2);
        const totalDiv = document.createElement('div');
        totalDiv.className = 'border-top pt-2 mt-2 fw-bold';
        
        if (totalSize > maxTotalSize) {
            totalDiv.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Tamaño total: <span class="text-danger">${totalSizeInMB} MB (excede límite de 400 MB)</span>`;
            hasError = true;
        } else {
            totalDiv.innerHTML = `<i class="bi bi-info-circle text-info"></i> Tamaño total: <span class="text-info">${totalSizeInMB} MB</span>`;
        }
        
        listaArchivos.appendChild(totalDiv);
        
        if (hasError) {
            btnImportar.disabled = true;
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-2 mb-0 small';
            errorDiv.innerHTML = '<i class="bi bi-x-circle"></i> Algunos archivos exceden el límite permitido. Por favor, reduce la cantidad o tamaño de archivos.';
            listaArchivos.appendChild(errorDiv);
        } else {
            btnImportar.disabled = false;
        }
    });

    form.addEventListener('submit', function(e) {
        if (!archivosInput.files || archivosInput.files.length === 0) {
            return;
        }

        loadingIndicator.style.display = 'block';
        btnImportar.disabled = true;
        btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Encolando archivos...';
    });
});
</script>
@endpush
