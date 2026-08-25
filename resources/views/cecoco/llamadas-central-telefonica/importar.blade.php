@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading mb-0">CeCoCo - Importar Llamadas Central Telefónica</h3>
            <a href="{{ route('cecoco.llamadas-central-telefonica') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al reporte
            </a>
        </div>
        <div class="section-body">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-sync-alt mr-1 text-primary"></i>Sincronización automática</h5>
                    <p class="text-muted" style="font-size:.85rem;">
                        Todas las noches (06:15) se trae automáticamente el día anterior completo directamente desde el panel
                        de la central telefónica, sin necesidad de exportar ni cargar ningún CSV. Para actualizar las llamadas
                        de hoy antes de esa hora (por ejemplo, para ver el reporte al momento), usá este botón.
                    </p>
                    <form method="POST" action="{{ route('cecoco.llamadas-central-telefonica.importar-hoy') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cloud-download-alt mr-1"></i> Importar llamadas de hoy
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-file-csv mr-1 text-primary"></i>Cargar CSV de la central telefónica</h5>
                    <p class="text-muted" style="font-size:.85rem;">
                        Formato esperado: <code>Unique ID, Calldate, ANI, Dialed number, Final DNIS, Forwarded to, Duration, Bill duration</code>.
                        Podés seleccionar varios archivos a la vez (uno por mes). La importación es idempotente: si volvés a cargar
                        un CSV ya importado, actualiza los registros existentes en lugar de duplicarlos.
                    </p>

                    <form method="POST" action="{{ route('cecoco.llamadas-central-telefonica.importar.post') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="archivos">Archivos CSV</label>
                            <input type="file" class="form-control-file @error('archivos') is-invalid @enderror" id="archivos" name="archivos[]" accept=".csv,.txt" multiple required>
                            @error('archivos')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('archivos.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload mr-1"></i> Importar
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-history mr-1 text-primary"></i>Historial de importaciones</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Importados</th>
                                    <th class="text-end">Omitidos</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($importaciones as $importacion)
                                    <tr>
                                        <td>{{ $importacion->nombre_archivo }}</td>
                                        <td class="text-center">
                                            @if ($importacion->estado === 'completado')
                                                <span class="badge badge-success">Completado</span>
                                            @else
                                                <span class="badge badge-danger" title="{{ $importacion->error_mensaje }}">Error</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($importacion->registros_importados, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($importacion->registros_omitidos, 0, ',', '.') }}</td>
                                        <td>{{ $importacion->usuario->name ?? '-' }}</td>
                                        <td>{{ $importacion->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Todavía no se cargó ningún archivo desde acá.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $importaciones->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
