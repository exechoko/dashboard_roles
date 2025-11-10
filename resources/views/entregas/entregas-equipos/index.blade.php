{{-- resources/views/entregas-equipos/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1 class="section-title">Entregas de Equipos</h1>
            <p class="section-subtitle">Listado de entregas de equipos de mano (HT) para diferentes acontecimientos/eventos</p>
        </div>

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
                <div class="col-12">
                    <div class="card card-mobile-optimized">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center flex-column flex-md-row">
                                <h4 class="card-title mb-2 mb-md-0">Listado de Entregas</h4>
                                @can('crear-entrega-equipos')
                                    <a href="{{ route('entrega-equipos.create') }}" class="btn btn-primary btn-lg-mobile">
                                        <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Nueva Entrega</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Formulario de búsqueda optimizado para mobile --}}
                            <div class="search-container mb-4">
                                <button class="btn btn-outline-info btn-block d-md-none mb-2" type="button" data-toggle="collapse" data-target="#searchForm">
                                    <i class="fas fa-search"></i> Mostrar/Ocultar Búsqueda
                                </button>

                                <div class="collapse d-md-block" id="searchForm">
                                    <form method="GET" action="{{ route('entrega-equipos.index') }}">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="text" name="numero_acta" class="form-control form-control-mobile"
                                                       placeholder="Número de Acta" value="{{ request('numero_acta') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="text" name="tei" class="form-control form-control-mobile"
                                                       placeholder="TEI" value="{{ request('tei') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="text" name="issi" class="form-control form-control-mobile"
                                                       placeholder="ISSI" value="{{ request('issi') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="date" name="fecha" class="form-control form-control-mobile"
                                                       value="{{ request('fecha') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <input type="text" name="dependencia" class="form-control form-control-mobile"
                                                       placeholder="Dependencia" value="{{ request('dependencia') }}">
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-2">
                                                <div class="d-grid gap-2 d-md-block">
                                                    <button type="submit" class="btn btn-info btn-block btn-mobile">
                                                        <i class="fas fa-search"></i> <span class="d-none d-md-inline">Buscar</span>
                                                    </button>
                                                    <a href="{{ route('entrega-equipos.index') }}" class="btn btn-secondary btn-block btn-mobile mt-1">
                                                        <i class="fas fa-times"></i> <span class="d-none d-md-inline">Limpiar</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="toggleDevueltas" checked>
                                <label class="form-check-label" for="toggleDevueltas">
                                    Mostrar entregas devueltas
                                </label>
                            </div>

                            {{-- Tabla optimizada para mobile --}}
                            <div class="table-responsive">
                                <table class="table table-striped mobile-table">
                                    <thead class="d-none d-md-table-header-group">
                                        <tr>
                                            <th>N° Acta</th>
                                            <th>Fecha</th>
                                            <th>Dependencia</th>
                                            <th>Personal Receptor</th>
                                            <th>Entregó</th>
                                            <th>Cant. Equipos</th>
                                            <th>Estado</th>
                                            <th>Archivo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($entregas as $entrega)
                                            <tr class="{{ $entrega->estado === 'devuelto' ? 'fila-devuelta' : '' }} mobile-table-row">
                                                <td data-label="N° Acta" class="fw-bold">{{ $entrega->id }}</td>
                                                <td data-label="Fecha">{{ $entrega->fecha_entrega->format('d/m/Y') }}<br>
                                                    <small class="text-muted">{{ $entrega->hora_entrega }}</small>
                                                </td>
                                                <td data-label="Dependencia" class="text-truncate" style="max-width: 150px;" title="{{ $entrega->dependencia }}">
                                                    {{ $entrega->dependencia }}
                                                </td>
                                                <td data-label="Personal Receptor" class="d-none d-md-table-cell">{{ $entrega->personal_receptor }}</td>
                                                <td data-label="Entregó" class="d-none d-md-table-cell">{{ $entrega->personal_entrega }}</td>
                                                <td data-label="Cant. Equipos">
                                                    <span class="badge badge-info">
                                                        {{ $entrega->equipos->count() }} equipos
                                                    </span>
                                                    {{-- Mostrar accesorios --}}
                                                    @if($entrega->accesorios->count() > 0)
                                                        <br><small class="text-muted">Con accesorios</small>
                                                    @endif
                                                </td>
                                                <td data-label="Estado">
                                                    @switch($entrega->estado)
                                                        @case('entregado')
                                                            <span class="badge badge-warning">Entregado</span>
                                                            @break
                                                        @case('devolucion_parcial')
                                                            <span class="badge badge-danger">Devolución Parcial</span>
                                                            @break
                                                        @case('devuelto')
                                                            <span class="badge badge-success">Devuelto</span>
                                                            @break
                                                        @case('perdido')
                                                            <span class="badge badge-danger">Perdido</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">{{ ucfirst($entrega->estado) }}</span>
                                                    @endswitch

                                                    {{-- Mostrar contador de devoluciones si existen --}}
                                                    @if($entrega->devoluciones->count() > 0)
                                                        <br><small class="text-muted">{{ $entrega->devoluciones->count() }} devolución(es)</small>
                                                    @endif
                                                </td>
                                                <td data-label="Archivo">
                                                    @if($entrega->ruta_archivo)
                                                        <div class="dropdown">
                                                            <button class="btn btn-warning btn-sm dropdown-toggle btn-mobile-action" type="button" id="dropdownMenuButton{{$entrega->id}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-file"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$entrega->id}}">
                                                                <a class="dropdown-item" href="{{ route('entrega-equipos.descargar', $entrega->id) }}">
                                                                    <i class="fas fa-download"></i> Descargar copia
                                                                </a>
                                                                <a class="dropdown-item" href="#" onclick="copyFilePath('{{ str_replace('\\', '\\\\', $entrega->ruta_archivo) }}')">
                                                                    <i class="fas fa-copy"></i> Copiar ruta original
                                                                </a>
                                                                <a class="dropdown-item" href="#" onclick="showFileAccessInstructions('{{ str_replace('\\', '\\\\', $entrega->ruta_archivo) }}')">
                                                                    <i class="fas fa-folder-open"></i> Abrir en Explorer
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No generado</span>
                                                    @endif
                                                </td>
                                                <td data-label="Acciones">
                                                    <div class="action-buttons">
                                                        @can('ver-entrega-equipos')
                                                            <a href="{{ route('entrega-equipos.show', $entrega->id) }}" class="btn btn-warning btn-sm btn-mobile-action" title="Ver">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endcan

                                                        @can('editar-entrega-equipos')
                                                            @if(in_array($entrega->estado, ['entregado', 'devolucion_parcial']))
                                                                <a href="{{ route('entrega-equipos.edit', $entrega->id) }}" class="btn btn-info btn-sm btn-mobile-action" title="Editar">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                        @endcan

                                                        <a href="{{ route('entrega-equipos.documento', $entrega->id) }}"
                                                           class="btn btn-secondary btn-sm btn-mobile-action" target="_blank" title="Documento Word">
                                                            <i class="fas fa-file-word"></i>
                                                        </a>

                                                        @can('editar-entrega-equipos')
                                                            @php
                                                                $equiposPendientes = $entrega->equiposPendientes()->count();
                                                            @endphp
                                                            @if($equiposPendientes > 0)
                                                                <a href="{{ route('entrega-equipos.devolver', $entrega->id) }}" class="btn btn-success btn-sm btn-mobile-action" title="Devolver equipos ({{ $equiposPendientes }} pendientes)">
                                                                    <i class="fas fa-undo"></i>
                                                                    @if($equiposPendientes < $entrega->equipos->count())
                                                                        <span class="badge badge-light" style="font-size: 10px;">{{ $equiposPendientes }}</span>
                                                                    @endif
                                                                </a>
                                                            @endif
                                                        @endcan

                                                        @can('borrar-entrega-equipos')
                                                            <form action="{{ route('entrega-equipos.destroy', $entrega->id) }}"
                                                                  method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm btn-mobile-action"
                                                                        onclick="return confirm('¿Está seguro de eliminar esta entrega?')" title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4">No se encontraron entregas</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="d-flex justify-content-center mt-4">
                                {{ $entregas->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    /* Estilos optimizados para mobile */
    .card-mobile-optimized {
        border-radius: 10px;
        overflow: hidden;
    }

    .btn-mobile {
        padding: 10px 15px;
        font-size: 14px;
    }

    .btn-lg-mobile {
        padding: 12px 20px;
        font-size: 16px;
    }

    .btn-mobile-action {
        min-width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 2px;
    }

    .form-control-mobile {
        height: 45px;
        font-size: 16px; /* Previene zoom en iOS */
    }

    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }

    /* Estilos para tabla responsive en móviles */
    @media (max-width: 767.98px) {
        .mobile-table {
            border: 0;
        }

        .mobile-table thead {
            display: none;
        }

        .mobile-table-row {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
        }

        .mobile-table-row td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .mobile-table-row td:last-child {
            border-bottom: none;
        }

        .mobile-table-row td::before {
            content: attr(data-label);
            font-weight: bold;
            margin-right: 1rem;
            flex: 0 0 40%;
        }

        .mobile-table-row td:last-child::before {
            display: none;
        }

        .section-title {
            font-size: 1.5rem;
        }

        .section-subtitle {
            font-size: 0.9rem;
        }

        .card-title {
            font-size: 1.25rem;
        }

        .search-container .collapse:not(.show) {
            display: none;
        }
    }

    /* Mejoras para tablets */
    @media (min-width: 768px) and (max-width: 1024px) {
        .btn-mobile-action {
            min-width: 35px;
            height: 35px;
            font-size: 0.8rem;
        }

        .table td, .table th {
            padding: 0.5rem;
        }
    }

    /* Estilo para filas devueltas */
    .fila-devuelta {
        background-color: rgba(40, 167, 69, 0.1);
    }

    /* Asegurar scroll horizontal en tema oscuro */
    [data-theme="dark"] .table-responsive {
        background-color: var(--card-bg) !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }

    [data-theme="dark"] .table {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        margin-bottom: 0 !important;
        min-width: 800px;
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Ocultar/mostrar filas de entregas devueltas
    $('#toggleDevueltas').on('change', function () {
        if ($(this).is(':checked')) {
            $('.fila-devuelta').show();
        } else {
            $('.fila-devuelta').hide();
        }
    });

    // Auto expand search form on page load if there are search parameters
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const hasSearchParams = Array.from(urlParams.keys()).some(key =>
            key !== 'page' && urlParams.get(key) !== ''
        );

        if (hasSearchParams && window.innerWidth < 768) {
            $('#searchForm').collapse('show');
        }
    });

    /**
     * Copies the given text to the clipboard.
     * @param {string} text The text to copy.
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Ruta copiada al portapapeles ✅');
            }).catch(err => {
                console.error('Error al copiar al portapapeles:', err);
                copyTextFallback(text);
            });
        } else {
            copyTextFallback(text);
        }
    }

    /**
     * Fallback function to copy text to clipboard for older browsers.
     * @param {string} text The text to copy.
     */
    function copyTextFallback(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            showToast('Ruta copiada al portapapeles (fallback) ✅');
        } catch (err) {
            console.error('Error al copiar al portapapeles:', err);
            alert('Por favor, copia la siguiente ruta manualmente: \n' + text);
        }
        document.body.removeChild(textArea);
    }

    /**
     * Shows a temporary toast message.
     * @param {string} message The message to display.
     */
    function showToast(message) {
        const toastHtml = `
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                <div class="toast-header">
                    <strong class="mr-auto">Notificación</strong>
                    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        $('body').append(toastHtml);
        $('.toast').toast({ delay: 3000 }).toast('show');
        $('.toast').on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }

    /**
     * Handles copying the file path to clipboard.
     * @param {string} filePath The full path of the file.
     */
    function copyFilePath(filePath) {
        copyToClipboard(filePath);
    }

    /**
     * Displays a modal with instructions to access the file/folder in Windows Explorer.
     * @param {string} filePath The full path of the file.
     */
    function showFileAccessInstructions(filePath) {
        const fileName = filePath.substring(filePath.lastIndexOf('\\') + 1);
        const folderPath = filePath.substring(0, filePath.lastIndexOf('\\'));

        const modalHtml = `
            <div class="modal fade" id="fileAccessModal" tabindex="-1" role="dialog" aria-labelledby="fileAccessModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="fileAccessModalLabel">Acceder al Archivo en Explorer</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Para abrir el archivo o la carpeta en el Explorador de Windows, sigue estas instrucciones:</p>
                            <h6>Ruta del archivo:</h6>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="filePathInput" value="${filePath}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(document.getElementById('filePathInput').value)">Copiar Ruta</button>
                                </div>
                            </div>
                            <h6>Ruta de la carpeta:</h6>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="folderPathInput" value="${folderPath}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(document.getElementById('folderPathInput').value)">Copiar Ruta</button>
                                </div>
                            </div>
                            <div class="alert alert-info" role="alert">
                                <strong>Opción 1: Abrir la Carpeta</strong>
                                <ol>
                                    <li>Copia la <strong>Ruta de la carpeta</strong>.</li>
                                    <li>Presiona la combinación de teclas <kbd>Win + R</kbd> (ejecutar).</li>
                                    <li>Pega la ruta en el cuadro de diálogo y presiona <kbd>Enter</kbd>.</li>
                                    <li>Busca el archivo "${fileName}" en la carpeta.</li>
                                </ol>
                                <strong>Opción 2: Abrir el Archivo Directamente (si tu navegador lo permite)</strong>
                                <ol>
                                    <li>Copia la <strong>Ruta del archivo</strong>.</li>
                                    <li>Presiona la combinación de teclas <kbd>Win + R</kbd> (ejecutar).</li>
                                    <li>Pega la ruta en el cuadro de diálogo y presiona <kbd>Enter</kbd>.</li>
                                </ol>
                                <p class="mt-2"><small>Ten en cuenta que el acceso directo a rutas locales o de red desde el navegador puede estar restringido por razones de seguridad.</small></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal to prevent duplicates if opened multiple times
        $('#fileAccessModal').remove();
        $('body').append(modalHtml);
        $('#fileAccessModal').modal('show');
    }
</script>
@endpush
