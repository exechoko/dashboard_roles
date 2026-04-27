@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-file-import"></i> Importar Incidencias — Período {{ $periodo->label }}</h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h4>Importar desde Excel <small class="text-muted">CONTROL DE INCIDENCIAS 911</small></h4>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se importarán las filas cuya columna <strong>"Per. Fact."</strong> sea
                            <strong>{{ $periodo->label }}</strong>.
                            El sistema detecta automáticamente el sistema (TETRA/CCTV) desde la columna "Subsist."
                        </div>

                        <form action="{{ route('incidencias.periodos.importar.post', $periodo->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Archivo Excel (.xlsm / .xlsx) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="archivo" accept=".xlsx,.xlsm,.xls" required>
                                        <label class="custom-file-label">Seleccionar archivo...</label>
                                    </div>
                                </div>
                                <small class="text-muted">Archivos: CONTROL DE INCIDENCIAS 911 *.xlsm</small>
                            </div>

                            <div class="form-group">
                                <label>Hoja a importar <span class="text-danger">*</span></label>
                                <select name="hoja" class="form-control">
                                    <option value="patagonia">Patagonia (recomendado)</option>
                                    <option value="preventivos">Preventivos</option>
                                    <option value="telecom">Telecom</option>
                                    <option value="ute">U.T.E.</option>
                                </select>
                                <small class="text-muted">La hoja Patagonia contiene todas las incidencias facturables del período.</small>
                            </div>

                            <div class="alert alert-info small">
                                <i class="fas fa-magic mr-1"></i>
                                El tipo se detecta automáticamente por fila:
                                <strong>Transitoria</strong> si tiene fecha de solución,
                                <strong>Persistente</strong> si no fue resuelta al cierre del período
                                (se calculan los minutos hasta el {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}).
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="limpiar" name="limpiar" value="1">
                                    <label class="custom-control-label text-danger" for="limpiar">
                                        <strong>Eliminar incidencias existentes</strong> antes de importar
                                        <small class="d-block text-muted font-weight-normal">Usar cuando se reimporta para corregir datos.</small>
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-warning small">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Sin marcar la opción anterior, las incidencias existentes <strong>no se eliminan</strong>
                                y podrían generarse duplicados.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light">Cancelar</a>
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-upload"></i> Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = e.target.files[0] ? e.target.files[0].name : 'Seleccionar archivo...';
    e.target.nextElementSibling.textContent = fileName;
});
</script>
@endpush
