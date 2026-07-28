@extends('layouts.app')

@section('css')
    <style>
        .hash-drop-zone {
            border: 2px dashed #6777ef;
            border-radius: 12px;
            background: var(--bg-tertiary, #f8f9fa);
            color: var(--text-primary, #34395e);
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
        }

        .hash-drop-zone:hover,
        .hash-drop-zone.dragover {
            background: rgba(103, 119, 239, .1);
            border-color: #35199a;
            transform: translateY(-1px);
        }

        .hash-drop-zone:focus {
            outline: none;
            box-shadow: 0 0 0 .2rem rgba(103, 119, 239, .2);
        }

        .hash-result-value {
            font-family: Consolas, 'Courier New', monospace;
            font-size: .95rem;
            letter-spacing: .03em;
        }

        .hash-feature {
            border-left: 3px solid #6777ef;
            padding-left: 1rem;
        }

        [data-theme="dark"] .hash-drop-zone {
            background: var(--bg-secondary, #1e293b);
        }

        [data-theme="dark"] .hash-drop-zone:hover,
        [data-theme="dark"] .hash-drop-zone.dragover {
            background: rgba(103, 119, 239, .18);
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-fingerprint mr-2"></i>Hash de archivo</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-shield-alt mr-2"></i>Calcular SHA-256</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Seleccioná o arrastrá cualquier archivo. Se procesan sus bytes sin importar el formato y el
                                archivo no se guarda en el sistema.
                            </p>

                            <form method="POST" action="{{ route('herramientas.hash.calcular') }}"
                                enctype="multipart/form-data" id="hash-form">
                                @csrf

                                <div id="hash-drop-zone" class="hash-drop-zone p-4 text-center" role="button" tabindex="0"
                                    aria-controls="archivo" aria-label="Seleccionar archivo">
                                    <input type="file" id="archivo" name="archivo" class="d-none" required>
                                    <div id="hash-file-placeholder">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h5>Arrastrá el archivo aquí</h5>
                                        <p class="text-muted mb-3">o hacé clic para buscarlo en tu equipo</p>
                                        <span class="btn btn-primary"><i class="fas fa-folder-open mr-2"></i>Seleccionar archivo</span>
                                    </div>
                                    <div id="hash-file-selected" class="d-none">
                                        <i class="fas fa-file fa-3x text-success mb-3"></i>
                                        <h5 id="hash-file-name" class="text-break mb-1"></h5>
                                        <p id="hash-file-size" class="text-muted mb-0"></p>
                                    </div>
                                </div>

                                @error('archivo')
                                    <div class="alert alert-danger mt-3 mb-0" role="alert">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror

                                <button type="submit" id="hash-submit" class="btn btn-success btn-block mt-4" disabled>
                                    <i class="fas fa-calculator mr-2"></i>Calcular SHA-256
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    @isset($resultado)
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"><i class="fas fa-check-circle text-success mr-2"></i>Resultado</h4>
                                <span class="badge badge-primary">SHA-256</span>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Archivo</strong></p>
                                <p class="text-break mb-3">{{ $resultado['nombre'] }}</p>
                                <p class="mb-1"><strong>Tamaño</strong></p>
                                <p class="text-muted mb-4">{{ number_format($resultado['tamano'], 0, ',', '.') }} bytes</p>

                                <label for="hash-value"><strong>Hash SHA-256</strong></label>
                                <div class="input-group">
                                    <input type="text" id="hash-value" class="form-control hash-result-value"
                                        value="{{ $resultado['hash'] }}" readonly spellcheck="false" aria-label="Hash SHA-256">
                                    <div class="input-group-append">
                                        <button type="button" id="copy-hash" class="btn btn-outline-primary" title="Copiar hash">
                                            <i class="fas fa-copy"></i><span class="sr-only">Copiar hash</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="copy-status" class="small text-success mt-2" role="status" aria-live="polite"></div>
                            </div>
                        </div>
                    @else
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información</h4>
                            </div>
                            <div class="card-body">
                                <div class="hash-feature mb-4">
                                    <h6>¿Qué es SHA-256?</h6>
                                    <p class="text-muted mb-0">Es una huella digital única de 64 caracteres hexadecimales para verificar la integridad de un archivo.</p>
                                </div>
                                <div class="hash-feature mb-4">
                                    <h6>Privacidad</h6>
                                    <p class="text-muted mb-0">El archivo se utiliza de forma temporal durante el cálculo y no se almacena.</p>
                                </div>
                                <div class="hash-feature">
                                    <h6>Archivos grandes</h6>
                                    <p class="text-muted mb-0">El servidor lo lee por bloques para reducir el uso de memoria. El tamaño máximo práctico depende de PHP y del servidor web.</p>
                                </div>
                            </div>
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('hash-form');
            const input = document.getElementById('archivo');
            const dropZone = document.getElementById('hash-drop-zone');
            const placeholder = document.getElementById('hash-file-placeholder');
            const selected = document.getElementById('hash-file-selected');
            const fileName = document.getElementById('hash-file-name');
            const fileSize = document.getElementById('hash-file-size');
            const submitButton = document.getElementById('hash-submit');

            if (!form || !input || !dropZone || !placeholder || !selected || !submitButton) {
                return;
            }

            const formatBytes = function (bytes) {
                if (bytes === 0) {
                    return '0 B';
                }

                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

                return (bytes / Math.pow(1024, unitIndex)).toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
            };

            const showFile = function (file) {
                if (!file) {
                    return;
                }

                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                placeholder.classList.add('d-none');
                selected.classList.remove('d-none');
                submitButton.disabled = false;
            };

            const assignDroppedFile = function (file) {
                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                } catch (error) {
                    return;
                }

                showFile(file);
            };

            input.addEventListener('change', function () {
                showFile(input.files[0]);
            });

            dropZone.addEventListener('click', function (event) {
                if (event.target !== input) {
                    input.click();
                }
            });

            dropZone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input.click();
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropZone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropZone.classList.remove('dragover');
                });
            });

            dropZone.addEventListener('drop', function (event) {
                const file = event.dataTransfer.files[0];

                if (file) {
                    assignDroppedFile(file);
                }
            });

            form.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Calculando...';
            });

            const copyButton = document.getElementById('copy-hash');
            const hashInput = document.getElementById('hash-value');
            const copyStatus = document.getElementById('copy-status');

            if (!copyButton || !hashInput || !copyStatus) {
                return;
            }

            const copied = function () {
                copyStatus.textContent = 'Hash copiado al portapapeles.';
                window.setTimeout(function () {
                    copyStatus.textContent = '';
                }, 2500);
            };

            copyButton.addEventListener('click', function () {
                const value = hashInput.value;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(copied);
                    return;
                }

                hashInput.select();
                document.execCommand('copy');
                hashInput.setSelectionRange(0, 0);
                copied();
            });
        })();
    </script>
@endpush
