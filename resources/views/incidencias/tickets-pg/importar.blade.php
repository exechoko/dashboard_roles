@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-file-import"></i> Importar Tickets PG</h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('incidencias.tickets-pg.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h4>Importar histórico desde Excel <small class="text-muted">CONTROL DE INCIDENCIAS 911</small></h4>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se importan los tickets de la hoja <strong>Patagonia</strong> (códigos <code>PG/aa-nnn</code>).
                            Las filas plantilla precargadas al final (sin fecha ni descripción) se omiten,
                            igual que los códigos que ya existen en el sistema, por lo que puede reimportarse
                            sin generar duplicados.
                        </div>

                        <div class="alert alert-warning small">
                            <i class="fas fa-sort-numeric-up mr-1"></i>
                            Al finalizar, la numeración se sincroniza con el último código importado de cada año:
                            el próximo ticket que se genere continúa la secuencia
                            (por ejemplo, si el último es <code>PG/26-199</code>, el siguiente será <code>PG/26-200</code>).
                        </div>

                        <form action="{{ route('incidencias.tickets-pg.importar.post') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Archivo Excel (.xlsm / .xlsx) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="archivo" accept=".xlsx,.xlsm,.xls" required>
                                        <label class="custom-file-label">Seleccionar archivo...</label>
                                    </div>
                                </div>
                                <small class="text-muted">Archivo: CONTROL DE INCIDENCIAS 911 *.xlsm</small>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('incidencias.tickets-pg.index') }}" class="btn btn-light">Cancelar</a>
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
