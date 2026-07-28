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

        .hash-history-table {
            min-width: 900px;
        }

        .hash-history-value {
            font-family: Consolas, 'Courier New', monospace;
            word-break: break-all;
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
                                    <input type="file" id="archivo" name="archivo" class="d-none">
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

                                <div id="hash-progress" class="d-none mt-4">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span id="hash-progress-status">Preparando...</span>
                                        <span id="hash-progress-percent">0%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div id="hash-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <div id="hash-error" class="alert alert-danger d-none mt-3 mb-0" role="alert"></div>

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
                    <div id="hash-result-panel" class="card h-100 {{ isset($resultado) ? '' : 'd-none' }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-check-circle text-success mr-2"></i>Resultado</h4>
                            <span class="badge badge-primary">SHA-256</span>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Archivo</strong></p>
                            <p id="hash-result-name" class="text-break mb-3">{{ $resultado['nombre'] ?? '' }}</p>
                            <p class="mb-1"><strong>Tamaño</strong></p>
                            <p id="hash-result-size" class="text-muted mb-4">
                                @isset($resultado)
                                    {{ number_format($resultado['tamano'], 0, ',', '.') }} bytes
                                @endisset
                            </p>

                            <label for="hash-value"><strong>Hash SHA-256</strong></label>
                            <div class="input-group">
                                <input type="text" id="hash-value" class="form-control hash-result-value"
                                    value="{{ $resultado['hash'] ?? '' }}" readonly spellcheck="false" aria-label="Hash SHA-256">
                                <div class="input-group-append">
                                    <button type="button" id="copy-hash" class="btn btn-outline-primary" title="Copiar hash">
                                        <i class="fas fa-copy"></i><span class="sr-only">Copiar hash</span>
                                    </button>
                                </div>
                            </div>
                            <div id="copy-status" class="small text-success mt-2" role="status" aria-live="polite"></div>
                            <div id="hash-history-save-status" class="small mt-2" role="status" aria-live="polite"></div>
                        </div>
                    </div>

                    <div id="hash-info-panel" class="card h-100 {{ isset($resultado) ? 'd-none' : '' }}">
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
                                <p class="text-muted mb-0">El archivo se procesa localmente en tu navegador y no se envía al servidor.</p>
                            </div>
                            <div class="hash-feature">
                                <h6>Archivos grandes</h6>
                                <p class="text-muted mb-0">Se leen por bloques de 1 MiB para reducir el uso de memoria. Cloudflare y los límites de carga de PHP no intervienen.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-history mr-2"></i>Historial de hashes</h4>
                    <span id="hash-history-count" class="badge badge-secondary">{{ $historial->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 hash-history-table">
                            <thead>
                                <tr>
                                    <th>Fecha/hora</th>
                                    <th>Nombre de archivo</th>
                                    <th>Cifrado aplicado</th>
                                    <th>Hash</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="hash-history-body">
                                @forelse($historial as $registro)
                                    <tr>
                                        <td>{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td class="text-break">{{ $registro->nombre_archivo }}</td>
                                        <td><span class="badge badge-primary">{{ $registro->cifrado_aplicado }}</span></td>
                                        <td><code class="hash-history-value">{{ $registro->hash }}</code></td>
                                        <td>{{ trim(($registro->user?->name ?? '') . ' ' . ($registro->user?->apellido ?? '')) ?: 'Usuario eliminado' }}</td>
                                    </tr>
                                @empty
                                    <tr id="hash-history-empty">
                                        <td colspan="5" class="text-center text-muted py-4">Todavía no hay cálculos registrados.</td>
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
            const progress = document.getElementById('hash-progress');
            const progressBar = document.getElementById('hash-progress-bar');
            const progressStatus = document.getElementById('hash-progress-status');
            const progressPercent = document.getElementById('hash-progress-percent');
            const errorPanel = document.getElementById('hash-error');
            const resultPanel = document.getElementById('hash-result-panel');
            const infoPanel = document.getElementById('hash-info-panel');
            const resultName = document.getElementById('hash-result-name');
            const resultSize = document.getElementById('hash-result-size');
            const hashInput = document.getElementById('hash-value');
            const historyBody = document.getElementById('hash-history-body');
            const historyCount = document.getElementById('hash-history-count');
            const historySaveStatus = document.getElementById('hash-history-save-status');
            const historyEndpoint = '{{ route('herramientas.hash.historial.registrar') }}';

            if (!form || !input || !dropZone || !placeholder || !selected || !submitButton || !progress
                || !progressBar || !progressStatus || !progressPercent || !errorPanel || !resultPanel
                || !infoPanel || !resultName || !resultSize || !hashInput || !historyBody || !historyCount
                || !historySaveStatus) {
                return;
            }

            const SHA256_K = new Uint32Array([
                0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
                0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
                0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
                0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
                0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
                0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
                0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
                0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
            ]);

            const SHA256_INITIAL_STATE = new Uint32Array([
                0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a,
                0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19
            ]);

            const rotateRight = function (value, amount) {
                return (value >>> amount) | (value << (32 - amount));
            };

            class IncrementalSha256 {
                constructor() {
                    this.state = new Uint32Array(SHA256_INITIAL_STATE);
                    this.buffer = new Uint8Array(64);
                    this.bufferLength = 0;
                    this.bytesHashed = 0;
                    this.finished = false;
                }

                update(data) {
                    if (this.finished) {
                        throw new Error('El hash ya fue finalizado.');
                    }

                    this.bytesHashed += data.length;
                    let offset = 0;

                    if (this.bufferLength > 0) {
                        const needed = 64 - this.bufferLength;

                        if (data.length < needed) {
                            this.buffer.set(data, this.bufferLength);
                            this.bufferLength += data.length;
                            return this;
                        }

                        this.buffer.set(data.subarray(0, needed), this.bufferLength);
                        this.compress(this.buffer, 0);
                        this.bufferLength = 0;
                        offset = needed;
                    }

                    while (offset + 64 <= data.length) {
                        this.compress(data, offset);
                        offset += 64;
                    }

                    if (offset < data.length) {
                        this.buffer.set(data.subarray(offset));
                        this.bufferLength = data.length - offset;
                    }

                    return this;
                }

                digest() {
                    if (this.finished) {
                        return this.hash;
                    }

                    const blockLength = this.bufferLength < 56 ? 64 : 128;
                    const finalBlock = new Uint8Array(blockLength);
                    finalBlock.set(this.buffer.subarray(0, this.bufferLength));
                    finalBlock[this.bufferLength] = 0x80;

                    const high = Math.floor(this.bytesHashed / 0x20000000) >>> 0;
                    const low = (this.bytesHashed * 8) >>> 0;
                    const lengthOffset = blockLength - 8;

                    finalBlock[lengthOffset] = high >>> 24;
                    finalBlock[lengthOffset + 1] = high >>> 16;
                    finalBlock[lengthOffset + 2] = high >>> 8;
                    finalBlock[lengthOffset + 3] = high;
                    finalBlock[lengthOffset + 4] = low >>> 24;
                    finalBlock[lengthOffset + 5] = low >>> 16;
                    finalBlock[lengthOffset + 6] = low >>> 8;
                    finalBlock[lengthOffset + 7] = low;

                    for (let offset = 0; offset < blockLength; offset += 64) {
                        this.compress(finalBlock, offset);
                    }

                    this.finished = true;
                    this.hash = Array.from(this.state)
                        .map(function (value) {
                            return value.toString(16).padStart(8, '0');
                        })
                        .join('');

                    return this.hash;
                }

                compress(data, offset) {
                    const words = new Uint32Array(64);

                    for (let index = 0; index < 16; index++) {
                        const position = offset + (index * 4);
                        words[index] = (
                            (data[position] << 24)
                            | (data[position + 1] << 16)
                            | (data[position + 2] << 8)
                            | data[position + 3]
                        ) >>> 0;
                    }

                    for (let index = 16; index < 64; index++) {
                        const word15 = words[index - 15];
                        const word2 = words[index - 2];
                        const sigma0 = (rotateRight(word15, 7) ^ rotateRight(word15, 18) ^ (word15 >>> 3)) >>> 0;
                        const sigma1 = (rotateRight(word2, 17) ^ rotateRight(word2, 19) ^ (word2 >>> 10)) >>> 0;
                        words[index] = (sigma1 + words[index - 7] + sigma0 + words[index - 16]) >>> 0;
                    }

                    let a = this.state[0];
                    let b = this.state[1];
                    let c = this.state[2];
                    let d = this.state[3];
                    let e = this.state[4];
                    let f = this.state[5];
                    let g = this.state[6];
                    let h = this.state[7];

                    for (let index = 0; index < 64; index++) {
                        const sigma1 = (rotateRight(e, 6) ^ rotateRight(e, 11) ^ rotateRight(e, 25)) >>> 0;
                        const choice = ((e & f) ^ (~e & g)) >>> 0;
                        const temp1 = (h + sigma1 + choice + SHA256_K[index] + words[index]) >>> 0;
                        const sigma0 = (rotateRight(a, 2) ^ rotateRight(a, 13) ^ rotateRight(a, 22)) >>> 0;
                        const majority = ((a & b) ^ (a & c) ^ (b & c)) >>> 0;
                        const temp2 = (sigma0 + majority) >>> 0;

                        h = g;
                        g = f;
                        f = e;
                        e = (d + temp1) >>> 0;
                        d = c;
                        c = b;
                        b = a;
                        a = (temp1 + temp2) >>> 0;
                    }

                    this.state[0] = (this.state[0] + a) >>> 0;
                    this.state[1] = (this.state[1] + b) >>> 0;
                    this.state[2] = (this.state[2] + c) >>> 0;
                    this.state[3] = (this.state[3] + d) >>> 0;
                    this.state[4] = (this.state[4] + e) >>> 0;
                    this.state[5] = (this.state[5] + f) >>> 0;
                    this.state[6] = (this.state[6] + g) >>> 0;
                    this.state[7] = (this.state[7] + h) >>> 0;
                }
            }

            const formatBytes = function (bytes) {
                if (bytes === 0) {
                    return '0 B';
                }

                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

                return (bytes / Math.pow(1024, unitIndex)).toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
            };

            let selectedFile = null;

            const setFile = function (file) {
                if (!file) {
                    return;
                }

                selectedFile = file;
                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                placeholder.classList.add('d-none');
                selected.classList.remove('d-none');
                resultPanel.classList.add('d-none');
                infoPanel.classList.remove('d-none');
                progress.classList.add('d-none');
                errorPanel.classList.add('d-none');
                historySaveStatus.textContent = '';
                historySaveStatus.className = 'small mt-2';
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-calculator mr-2"></i>Calcular SHA-256';
            };

            const updateProgress = function (percent, status) {
                progress.classList.remove('d-none');
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                progressStatus.textContent = status;
            };

            const showError = function (message) {
                errorPanel.textContent = message;
                errorPanel.classList.remove('d-none');
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('bg-danger');
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-calculator mr-2"></i>Reintentar';
            };

            const appendHistoryItem = function (item) {
                const emptyRow = document.getElementById('hash-history-empty');

                if (emptyRow) {
                    emptyRow.remove();
                }

                const row = document.createElement('tr');
                const values = [
                    item.fecha_hora,
                    item.nombre_archivo,
                    item.cifrado_aplicado,
                    item.hash,
                    item.usuario
                ];

                values.forEach(function (value, index) {
                    const cell = document.createElement('td');
                    cell.textContent = value || '';

                    if (index === 1) {
                        cell.classList.add('text-break');
                    }

                    if (index === 2) {
                        const badge = document.createElement('span');
                        badge.className = 'badge badge-primary';
                        badge.textContent = value || '';
                        cell.textContent = '';
                        cell.appendChild(badge);
                    }

                    if (index === 3) {
                        const hash = document.createElement('code');
                        hash.className = 'hash-history-value';
                        hash.textContent = value || '';
                        cell.textContent = '';
                        cell.appendChild(hash);
                    }

                    row.appendChild(cell);
                });

                historyBody.prepend(row);
                historyCount.textContent = String(Number(historyCount.textContent || 0) + 1);
            };

            const saveHistory = async function (file, hash) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(historyEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        nombre_archivo: file.name,
                        cifrado_aplicado: 'SHA-256',
                        hash: hash
                    })
                });

                if (!response.ok) {
                    throw new Error('No se pudo guardar el historial.');
                }

                return response.json();
            };

            const calculateLocally = async function (file) {
                const hasher = new IncrementalSha256();
                const chunkSize = 1024 * 1024;
                let offset = 0;

                updateProgress(0, 'Calculando en este dispositivo...');

                while (offset < file.size) {
                    const end = Math.min(offset + chunkSize, file.size);
                    const bytes = new Uint8Array(await file.slice(offset, end).arrayBuffer());

                    hasher.update(bytes);
                    offset = end;

                    const percent = Math.round((offset / file.size) * 100);
                    updateProgress(percent, 'Calculando en este dispositivo...');

                    await new Promise(function (resolve) {
                        window.setTimeout(resolve, 0);
                    });
                }

                if (file.size === 0) {
                    updateProgress(100, 'Calculando en este dispositivo...');
                }

                return hasher.digest();
            };

            input.addEventListener('change', function () {
                setFile(input.files[0]);
            });

            dropZone.addEventListener('drop', function (event) {
                const file = event.dataTransfer.files[0];

                if (file) {
                    setFile(file);
                }
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const file = selectedFile || input.files[0];

                if (!file) {
                    showError('Seleccioná un archivo para calcular su hash.');
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Calculando...';
                errorPanel.classList.add('d-none');
                progressBar.classList.remove('bg-danger');
                progressBar.classList.add('bg-success');

                try {
                    const hash = await calculateLocally(file);

                    resultName.textContent = file.name;
                    resultSize.textContent = formatBytes(file.size) + ' (' + file.size.toLocaleString('es-AR') + ' bytes)';
                    hashInput.value = hash;
                    resultPanel.classList.remove('d-none');
                    infoPanel.classList.add('d-none');
                    updateProgress(100, 'Hash calculado localmente.');

                    try {
                        const historyResponse = await saveHistory(file, hash);
                        appendHistoryItem(historyResponse.item);
                        historySaveStatus.className = 'small mt-2 text-success';
                        historySaveStatus.textContent = 'Registro guardado en el historial.';
                    } catch (error) {
                        historySaveStatus.className = 'small mt-2 text-warning';
                        historySaveStatus.textContent = 'Hash calculado, pero no se pudo guardar el historial.';
                    }

                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-check mr-2"></i>Calcular otro archivo';
                } catch (error) {
                    showError('No se pudo leer el archivo en el navegador.');
                }
            });
        })();
    </script>
@endpush
