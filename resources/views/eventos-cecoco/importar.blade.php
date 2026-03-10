@extends('layouts.app')

@section('content')
<h2 class="mb-4">Importar archivo Excel CECOCO</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Formulario de importación</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('cecoco.importar.post') }}" enctype="multipart/form-data" id="formImportar">
                    @csrf
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Seleccionar archivo</label>
                        <input type="file" class="form-control @error('archivo') is-invalid @enderror" 
                               id="archivo" name="archivo" accept=".xls,.xlsx,.xml" required>
                        @error('archivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Archivos mensuales del sistema de despacho (máx. 100MB). Se detectan duplicados automáticamente por número de expediente.
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

                    <div id="loadingIndicator" class="alert alert-warning" style="display: none;">
                        <div class="d-flex align-items-center">            
                            <div>
                                <strong>Procesando archivo...</strong><br>
                                <small>Esto puede tardar varios minutos dependiendo del tamaño del archivo. Por favor, no cierre esta ventana.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="btnImportar">
                        <i class="bi bi-upload"></i> Procesar e importar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de importaciones</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center p-2 bg-light rounded">
                            <small class="text-muted d-block">Archivos importados</small>
                            <strong class="fs-5">{{ $totalArchivosImportados }}</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 bg-light rounded">
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
    const archivoInput = document.getElementById('archivo');

    form.addEventListener('submit', function(e) {
        if (!archivoInput.files || archivoInput.files.length === 0) {
            return;
        }

        loadingIndicator.style.display = 'block';
        btnImportar.disabled = true;
        btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    });
});
</script>
@endpush
