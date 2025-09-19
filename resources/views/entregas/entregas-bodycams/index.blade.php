{{-- resources/views/entregas/entregas-bodycams/index.blade.php --}}

@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Entregas de Bodycams<br>
                <small>Listado de entregas de cámaras corporales para diferentes acontecimientos/eventos</small>
            </h1>
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
                    <div class="card">
                        <div class="card-header">
                            <h4>Listado de Entregas</h4>
                            @can('crear-entrega-bodycams')
                                <div class="card-header-action">
                                    <a href="{{ route('entrega-bodycams.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Nueva Entrega
                                    </a>
                                </div>
                            @endcan
                        </div>
                        <div class="card-body">
                            {{-- Formulario de búsqueda --}}
                            <form method="GET" action="{{ route('entrega-bodycams.index') }}" class="mb-4">
                                <div class="row">
                                    <div class="col-md-2">
                                        <input type="text" name="numero_acta" class="form-control"
                                               placeholder="Número de Acta" value="{{ request('numero_acta') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="codigo" class="form-control"
                                               placeholder="Código" value="{{ request('codigo') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="numero_serie" class="form-control"
                                               placeholder="N° Serie" value="{{ request('numero_serie') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="fecha" class="form-control"
                                               value="{{ request('fecha') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="dependencia" class="form-control"
                                               placeholder="Dependencia" value="{{ request('dependencia') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ route('entrega-bodycams.index') }}" class="btn btn-secondary btn-block mt-1">
                                            <i class="fas fa-times"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <div class="form-group form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="toggleDevueltas" checked>
                                <label class="form-check-label" for="toggleDevueltas">
                                    Mostrar entregas devueltas
                                </label>
                            </div>

                            {{-- Tabla de entregas --}}
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>N° Acta</th>
                                            <th>Fecha</th>
                                            <th>Dependencia</th>
                                            <th>Personal Receptor</th>
                                            <th>Entregó</th>
                                            <th>Cant. Bodycams</th>
                                            <th>Estado</th>
                                            <th>Archivo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($entregas as $entrega)
                                            <tr class="{{ $entrega->estado === 'devuelto' ? 'fila-devuelta' : '' }}">
                                                <td>{{ $entrega->id }}</td>
                                                <td>{{ $entrega->fecha_entrega->format('d/m/Y') }} {{ $entrega->hora_entrega }}</td>
                                                <td>{{ $entrega->dependencia }}</td>
                                                <td>{{ $entrega->personal_receptor }}</td>
                                                <td>{{ $entrega->personal_entrega }}</td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        {{ $entrega->bodycams->count() }} bodycams
                                                    </span>
                                                </td>
                                                <td>
                                                    @switch($entrega->estado)
                                                        @case('entregada')
                                                            <span class="badge badge-warning">Entregada</span>
                                                            @break
                                                        @case('parcialmente_devuelta')
                                                            <span class="badge badge-danger">Devolución Parcial</span>
                                                            @break
                                                        @case('devuelta')
                                                            <span class="badge badge-success">Devuelta</span>
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
                                                <td>
                                                    @if($entrega->ruta_archivo)
                                                        <div class="dropdown">
                                                            <button class="btn btn-warning btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{$entrega->id}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                👁 Archivo
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$entrega->id}}">
                                                                <a class="dropdown-item" href="{{ route('entrega-bodycams.descargar', $entrega->id) }}">
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
                                                <td>
                                                    <div class="d-flex">
                                                        @can('ver-entrega-bodycams')
                                                            <a href="{{ route('entrega-bodycams.show', $entrega->id) }}" class="btn btn-warning btn-sm mr-1">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endcan

                                                        @can('editar-entrega-bodycams')
                                                            @if(in_array($entrega->estado, ['entregada', 'devolucion_parcial']))
                                                                <a href="{{ route('entrega-bodycams.edit', $entrega->id) }}" class="btn btn-info btn-sm mr-1">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                        @endcan

                                                        <a href="{{ route('entrega-bodycams.documento', $entrega->id) }}"
                                                           class="btn btn-secondary btn-sm mr-1" target="_blank">
                                                            <i class="fas fa-file-word"></i>
                                                        </a>

                                                        @can('editar-entrega-bodycams')
                                                            @php
                                                                $bodycamsPendientes = $entrega->bodycamsPendientes()->count();
                                                            @endphp
                                                            @if($bodycamsPendientes > 0)
                                                                <a href="{{ route('entrega-bodycams.devolver', $entrega->id) }}" class="btn btn-success btn-sm mr-1" title="Devolver bodycams ({{ $bodycamsPendientes }} pendientes)">
                                                                    <i class="fas fa-undo"></i>
                                                                    @if($bodycamsPendientes < $entrega->bodycams->count())
                                                                        <span class="badge badge-light" style="font-size: 10px;">{{ $bodycamsPendientes }}</span>
                                                                    @endif
                                                                </a>
                                                            @endif
                                                        @endcan

                                                        @can('borrar-entrega-bodycams')
                                                            <form action="{{ route('entrega-bodycams.destroy', $entrega->id) }}"
                                                                  method="POST" style="display: inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                        onclick="return confirm('¿Está seguro de eliminar esta entrega?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">No se encontraron entregas</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación --}}
                            <div class="d-flex justify-content-center">
                                {{ $entregas->appends(request()->query())->links() }}
                            </div>
                        </div>
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

    // Ocultar/mostrar filas de entregas devueltas
    $('#toggleDevueltas').on('change', function () {
        if ($(this).is(':checked')) {
            $('.fila-devuelta').show();
        } else {
            $('.fila-devuelta').hide();
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
