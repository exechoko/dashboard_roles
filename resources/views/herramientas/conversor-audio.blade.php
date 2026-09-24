@extends('layouts.app')

@section('css')
    <style>
        .audio-drop-zone {
            border: 2px dashed #6777ef;
            border-radius: 12px;
            background: var(--bg-tertiary, #f8f9fa);
            color: var(--text-primary, #34395e);
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
        }

        .audio-drop-zone:hover,
        .audio-drop-zone.dragover {
            background: rgba(103, 119, 239, .1);
            border-color: #35199a;
            transform: translateY(-1px);
        }

        .audio-drop-zone:focus {
            outline: none;
            box-shadow: 0 0 0 .2rem rgba(103, 119, 239, .2);
        }

        .audio-feature {
            border-left: 3px solid #6777ef;
            padding-left: 1rem;
        }

        .audio-file-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .audio-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem .75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: .5rem;
        }

        .audio-file-item .audio-file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: .75rem;
        }

        .audio-file-status {
            flex: 0 0 auto;
            width: 22px;
            text-align: center;
        }

        [data-theme="dark"] .audio-drop-zone {
            background: var(--bg-secondary, #1e293b);
        }

        [data-theme="dark"] .audio-drop-zone:hover,
        [data-theme="dark"] .audio-drop-zone.dragover {
            background: rgba(103, 119, 239, .18);
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-file-audio mr-2"></i>Conversor de Audio a MP3</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-exchange-alt mr-2"></i>Convertir a MP3</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Seleccioná o arrastrá hasta {{ $maxArchivos }} archivos GSM, OGG o WAV. Se convierten
                                con FFmpeg de a uno; si es uno solo se descarga el MP3, si son varios se descarga un ZIP.
                            </p>

                            <form id="audio-form">
                                <div id="audio-drop-zone" class="audio-drop-zone p-4 text-center" role="button" tabindex="0"
                                    aria-controls="archivos" aria-label="Seleccionar archivos de audio">
                                    <input type="file" id="archivos" class="d-none" multiple accept=".gsm,.ogg,.wav">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5>Arrastrá los archivos aquí</h5>
                                    <p class="text-muted mb-3">o hacé clic para buscarlos en tu equipo</p>
                                    <span class="btn btn-primary"><i class="fas fa-folder-open mr-2"></i>Seleccionar archivos</span>
                                </div>

                                <div id="audio-file-list" class="audio-file-list mt-3"></div>

                                <div id="audio-progress" class="d-none mt-4">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span id="audio-progress-status">Convirtiendo...</span>
                                        <span id="audio-progress-count">0 / 0</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div id="audio-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                            role="progressbar" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <div id="audio-error" class="alert alert-danger d-none mt-3 mb-0" role="alert"></div>
                                <div id="audio-warning" class="alert alert-warning d-none mt-3 mb-0" role="alert"></div>
                                <div id="audio-success" class="alert alert-success d-none mt-3 mb-0" role="alert"></div>

                                <button type="submit" id="audio-submit" class="btn btn-success btn-block mt-4" disabled>
                                    <i class="fas fa-exchange-alt mr-2"></i>Convertir a MP3
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información</h4>
                        </div>
                        <div class="card-body">
                            <div class="audio-feature mb-4">
                                <h6>Formatos aceptados</h6>
                                <p class="text-muted mb-0">GSM, OGG y WAV. La conversión se hace con FFmpeg en el servidor, el mismo que se usa para las modulaciones del grabador TETRA.</p>
                            </div>
                            <div class="audio-feature mb-4">
                                <h6>Hasta {{ $maxArchivos }} archivos por tanda</h6>
                                <p class="text-muted mb-0">Cada archivo se sube y convierte por separado para poder mostrar el progreso real. Si convertís más de uno, se descarga un único ZIP.</p>
                            </div>
                            <div class="audio-feature">
                                <h6>Calidad</h6>
                                <p class="text-muted mb-0">Se codifica con libmp3lame a 128 kbps.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-history mr-2"></i>Historial de conversiones</h4>
                    <span id="audio-historial-count" class="badge badge-secondary">{{ $historial->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha/hora</th>
                                    <th>Nombre de archivo</th>
                                    <th>Formato</th>
                                    <th>Resultado</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="audio-historial-body">
                                @forelse($historial as $registro)
                                    <tr>
                                        <td>{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td class="text-break">{{ $registro->nombre_archivo }}</td>
                                        <td><span class="badge badge-primary">{{ strtoupper($registro->extension_original) }}</span></td>
                                        <td>
                                            @if($registro->exito)
                                                <span class="badge badge-success">Convertido</span>
                                            @else
                                                <span class="badge badge-danger" title="{{ $registro->mensaje_error }}">Falló</span>
                                            @endif
                                        </td>
                                        <td>{{ trim(($registro->user?->name ?? '') . ' ' . ($registro->user?->apellido ?? '')) ?: 'Usuario eliminado' }}</td>
                                    </tr>
                                @empty
                                    <tr id="audio-historial-empty">
                                        <td colspan="5" class="text-center text-muted py-4">Todavía no hay conversiones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($historial->hasPages())
                        <div class="p-3">
                            {!! $historial->withQueryString()->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('audio-form');
            const input = document.getElementById('archivos');
            const dropZone = document.getElementById('audio-drop-zone');
            const fileList = document.getElementById('audio-file-list');
            const submitButton = document.getElementById('audio-submit');
            const progress = document.getElementById('audio-progress');
            const progressBar = document.getElementById('audio-progress-bar');
            const progressCount = document.getElementById('audio-progress-count');
            const errorPanel = document.getElementById('audio-error');
            const warningPanel = document.getElementById('audio-warning');
            const successPanel = document.getElementById('audio-success');
            const historialBody = document.getElementById('audio-historial-body');
            const historialCount = document.getElementById('audio-historial-count');

            if (!form || !input || !dropZone || !fileList || !submitButton || !progress
                || !progressBar || !progressCount || !errorPanel || !warningPanel || !successPanel
                || !historialBody || !historialCount) {
                return;
            }

            const maxArchivos = {{ (int) $maxArchivos }};
            const allowedExtensions = ['gsm', 'ogg', 'wav'];

            // URLs con un token de reemplazo: cada tanda genera su propio token
            // (agrupa los archivos convertidos del lote hasta pedir el ZIP final).
            const subirUrlTemplate = '{{ route('herramientas.conversor-audio.lote.archivo', ['token' => 'TOKEN']) }}';
            const descargarUrlTemplate = '{{ route('herramientas.conversor-audio.lote.descargar', ['token' => 'TOKEN']) }}';

            let selectedFiles = [];
            let enviando = false;

            const generarToken = function () {
                if (window.crypto && window.crypto.randomUUID) {
                    return window.crypto.randomUUID().replace(/-/g, '');
                }

                let token = '';
                for (let i = 0; i < 32; i++) {
                    token += Math.floor(Math.random() * 16).toString(16);
                }

                return token;
            };

            const formatBytes = function (bytes) {
                if (bytes === 0) {
                    return '0 B';
                }

                const units = ['B', 'KB', 'MB', 'GB'];
                const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

                return (bytes / Math.pow(1024, unitIndex)).toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
            };

            const hideMessages = function () {
                errorPanel.classList.add('d-none');
                warningPanel.classList.add('d-none');
                successPanel.classList.add('d-none');
            };

            const showError = function (message) {
                hideMessages();
                errorPanel.textContent = message;
                errorPanel.classList.remove('d-none');
            };

            const renderFileList = function () {
                fileList.innerHTML = '';

                selectedFiles.forEach(function (file, index) {
                    const item = document.createElement('div');
                    item.className = 'audio-file-item';
                    item.dataset.index = index;

                    const name = document.createElement('span');
                    name.className = 'audio-file-name';
                    name.textContent = file.name + ' (' + formatBytes(file.size) + ')';

                    const status = document.createElement('span');
                    status.className = 'audio-file-status';

                    if (enviando) {
                        status.innerHTML = '<i class="fas fa-clock text-muted"></i>';
                    } else {
                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'btn btn-sm btn-outline-danger';
                        removeButton.innerHTML = '<i class="fas fa-times"></i>';
                        removeButton.addEventListener('click', function () {
                            selectedFiles.splice(index, 1);
                            renderFileList();
                        });
                        status.appendChild(removeButton);
                    }

                    item.appendChild(name);
                    item.appendChild(status);
                    fileList.appendChild(item);
                });

                submitButton.disabled = selectedFiles.length === 0 || enviando;
            };

            const setItemStatus = function (index, iconHtml) {
                const item = fileList.querySelector('[data-index="' + index + '"] .audio-file-status');
                if (item) {
                    item.innerHTML = iconHtml;
                }
            };

            const appendHistorialItem = function (item) {
                if (!item) {
                    return;
                }

                const emptyRow = document.getElementById('audio-historial-empty');
                if (emptyRow) {
                    emptyRow.remove();
                }

                const row = document.createElement('tr');

                const celdaFecha = document.createElement('td');
                celdaFecha.textContent = item.fecha_hora || '';

                const celdaNombre = document.createElement('td');
                celdaNombre.className = 'text-break';
                celdaNombre.textContent = item.nombre_archivo || '';

                const celdaFormato = document.createElement('td');
                const badgeFormato = document.createElement('span');
                badgeFormato.className = 'badge badge-primary';
                badgeFormato.textContent = item.extension_original || '';
                celdaFormato.appendChild(badgeFormato);

                const celdaResultado = document.createElement('td');
                const badgeResultado = document.createElement('span');
                if (item.exito) {
                    badgeResultado.className = 'badge badge-success';
                    badgeResultado.textContent = 'Convertido';
                } else {
                    badgeResultado.className = 'badge badge-danger';
                    badgeResultado.textContent = 'Falló';
                    if (item.mensaje_error) {
                        badgeResultado.title = item.mensaje_error;
                    }
                }
                celdaResultado.appendChild(badgeResultado);

                const celdaUsuario = document.createElement('td');
                celdaUsuario.textContent = item.usuario || '';

                row.appendChild(celdaFecha);
                row.appendChild(celdaNombre);
                row.appendChild(celdaFormato);
                row.appendChild(celdaResultado);
                row.appendChild(celdaUsuario);

                historialBody.prepend(row);
                historialCount.textContent = String(Number(historialCount.textContent || 0) + 1);
            };

            const addFiles = function (files) {
                const rechazados = [];

                Array.from(files).forEach(function (file) {
                    const extension = file.name.split('.').pop().toLowerCase();

                    if (!allowedExtensions.includes(extension)) {
                        rechazados.push(file.name + ' (extensión no permitida)');
                        return;
                    }

                    if (selectedFiles.length >= maxArchivos) {
                        rechazados.push(file.name + ' (se superó el máximo de ' + maxArchivos + ' archivos)');
                        return;
                    }

                    selectedFiles.push(file);
                });

                if (rechazados.length) {
                    showError('No se agregaron estos archivos: ' + rechazados.join(', ') + '.');
                } else {
                    hideMessages();
                }

                renderFileList();
            };

            input.addEventListener('change', function () {
                addFiles(input.files);
                input.value = '';
            });

            dropZone.addEventListener('click', function (event) {
                if (event.target !== input && !enviando) {
                    input.click();
                }
            });

            dropZone.addEventListener('keydown', function (event) {
                if ((event.key === 'Enter' || event.key === ' ') && !enviando) {
                    event.preventDefault();
                    input.click();
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    if (!enviando) {
                        dropZone.classList.add('dragover');
                    }
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropZone.classList.remove('dragover');
                });
            });

            dropZone.addEventListener('drop', function (event) {
                if (!enviando && event.dataTransfer.files.length) {
                    addFiles(event.dataTransfer.files);
                }
            });

            const parseFilenameFromDisposition = function (disposition) {
                if (!disposition) {
                    return null;
                }

                const match = disposition.match(/filename="?([^"]+)"?/);

                return match ? match[1] : null;
            };

            const csrfToken = function () {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            };

            const subirYConvertir = async function (token, file) {
                const formData = new FormData();
                formData.append('archivo', file);

                const response = await fetch(subirUrlTemplate.replace('TOKEN', token), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: formData
                });

                const data = await response.json().catch(() => ({ success: false }));

                return { ok: response.ok, data };
            };

            const descargarLote = async function (token) {
                const response = await fetch(descargarUrlTemplate.replace('TOKEN', token), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('No se pudo descargar el resultado.');
                }

                const disposition = response.headers.get('Content-Disposition');
                const filename = parseFilenameFromDisposition(disposition) || 'audio-convertido.mp3';
                const blob = await response.blob();
                const blobUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(blobUrl);
            };

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (enviando || selectedFiles.length === 0) {
                    return;
                }

                hideMessages();
                enviando = true;
                renderFileList();

                const token = generarToken();
                const total = selectedFiles.length;
                let completados = 0;
                let exitosos = 0;
                const fallidos = [];

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Convirtiendo...';
                progress.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressCount.textContent = '0 / ' + total;

                for (let i = 0; i < selectedFiles.length; i++) {
                    setItemStatus(i, '<i class="fas fa-spinner fa-spin text-primary"></i>');

                    try {
                        const resultado = await subirYConvertir(token, selectedFiles[i]);
                        appendHistorialItem(resultado.data.item);

                        if (resultado.ok && resultado.data.success) {
                            setItemStatus(i, '<i class="fas fa-check text-success"></i>');
                            exitosos++;
                        } else {
                            setItemStatus(i, '<i class="fas fa-times text-danger"></i>');
                            fallidos.push(selectedFiles[i].name);
                        }
                    } catch (error) {
                        setItemStatus(i, '<i class="fas fa-times text-danger"></i>');
                        fallidos.push(selectedFiles[i].name);
                    }

                    completados++;
                    const porcentaje = Math.round((completados / total) * 100);
                    progressBar.style.width = porcentaje + '%';
                    progressCount.textContent = completados + ' / ' + total;
                }

                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add(exitosos === 0 ? 'bg-danger' : 'bg-success');

                if (exitosos === 0) {
                    showError('No se pudo convertir ningún archivo. Verificá que FFmpeg esté disponible en el servidor.');
                } else {
                    try {
                        await descargarLote(token);

                        if (fallidos.length) {
                            warningPanel.textContent = 'Se descargó la conversión, pero no se pudieron convertir: ' + fallidos.join(', ') + '.';
                            warningPanel.classList.remove('d-none');
                        } else {
                            successPanel.textContent = 'Conversión completada: ' + exitosos + ' de ' + total + ' archivo(s).';
                            successPanel.classList.remove('d-none');
                        }
                    } catch (error) {
                        showError('Los archivos se convirtieron, pero no se pudo descargar el resultado. Volvé a intentarlo.');
                    }

                    selectedFiles = [];
                }

                enviando = false;
                progress.classList.add('d-none');
                progressBar.classList.add('progress-bar-animated');
                progressBar.classList.remove('bg-danger');
                progressBar.classList.add('bg-success');
                submitButton.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Convertir a MP3';
                renderFileList();
            });
        })();
    </script>
@endpush
