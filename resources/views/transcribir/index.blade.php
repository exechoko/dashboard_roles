@extends('layouts.app')

@section('content')
<style>
    /* ── Variables de color para burbujas de chat ── */
    :root {
        --agente-color:      #e6e6e6;
        --denunciante-color: #97cdff;
        --timestamp-color:   #6c757d;
        --drop-bg:           #f8f9fa;
        --drop-hover-bg:     #e9f0ff;
        --mode-card-text:    inherit;
    }
    [data-theme="dark"] {
        --agente-color:      #2d3748;
        --denunciante-color: #1a3a52;
        --timestamp-color:   #9ca3af;
        --drop-bg:           var(--bg-tertiary);
        --drop-hover-bg:     #1e293b;
        --mode-card-text:    var(--text-primary);
    }

    /* ── Selector de modo ── */
    .mode-card {
        border: 2px solid transparent;
        border-radius: 12px;
        cursor: pointer;
        transition: border-color .2s, box-shadow .2s, transform .15s;
        user-select: none;
        color: var(--mode-card-text);
    }
    .mode-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,.12);
    }
    .mode-card.active {
        border-color: #6777ef;
        box-shadow: 0 4px 16px rgba(103,119,239,.25);
    }
    .mode-card .mode-icon {
        font-size: 2.2rem;
        margin-bottom: .5rem;
    }

    /* ── Drop area ── */
    #drop-area {
        border: 2px dashed #6777ef;
        cursor: pointer;
        transition: background .2s;
        background: var(--drop-bg);
    }
    #drop-area.dragover {
        background: var(--drop-hover-bg);
    }

    /* ── Chat de diálogos (mismo patrón que transcription/index.blade.php) ── */
    #dialogo-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 12px;
    }
    .message {
        display: flex;
        flex-direction: column;
        max-width: 85%;
    }
    .align-left {
        align-items: flex-start;
        text-align: left;
        margin-right: auto;
    }
    .align-right {
        align-items: flex-end;
        text-align: right;
        margin-left: auto;
    }
    .bubble {
        padding: 8px 12px;
        border-radius: 14px;
        position: relative;
        word-wrap: break-word;
        line-height: 1.4;
    }
    .align-left  .bubble { background: var(--agente-color);      border-bottom-left-radius:  4px; }
    .align-right .bubble { background: var(--denunciante-color); border-bottom-right-radius: 4px; }
    .speaker {
        font-weight: bold;
        font-size: 0.85em;
        margin-bottom: 3px;
        color: var(--mode-card-text);
    }
    .timestamp {
        display: block;
        font-size: 0.65em;
        color: var(--timestamp-color);
        margin-top: 3px;
        opacity: 0.85;
    }
    /* Texto dentro de la burbuja en dark mode */
    [data-theme="dark"] .bubble {
        color: var(--text-primary);
    }
</style>

<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Transcripción / Análisis de Audio</h3>
    </div>
    <div class="section-body">

        {{-- ── SELECTOR DE MODO ── --}}
        <div class="row mb-3">
            <div class="col-md-4 col-sm-6 mb-2">
                <div class="mode-card card text-center p-3 active" id="mode-transcribe" onclick="setMode('transcribe')">
                    <div class="mode-icon text-primary"><i class="fas fa-microphone-alt"></i></div>
                    <h6 class="mb-1">Solo Transcribir</h6>
                    <small class="text-muted">Convierte el audio a texto<br>(Whisper local)</small>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-2">
                <div class="mode-card card text-center p-3" id="mode-analyze" onclick="setMode('analyze')">
                    <div class="mode-icon text-success"><i class="fas fa-headset"></i></div>
                    <h6 class="mb-1">Análisis Completo 911</h6>
                    <small class="text-muted">Transcripción + diarización + extracción de datos<br>(Servidor 8082)</small>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- ── COLUMNA UPLOAD ── --}}
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-upload mr-2"></i>Subir Archivo de Audio</h4>
                    </div>
                    <div class="card-body">

                        <div id="drop-area" class="p-4 rounded text-center" onclick="document.getElementById('audio-file').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                            <h5>Arrastra y suelta tu archivo aquí</h5>
                            <p class="text-muted">o hacé clic para seleccionar</p>
                            <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); document.getElementById('audio-file').click()">
                                <i class="fas fa-folder-open mr-2"></i>Seleccionar archivo
                            </button>
                            <input type="file" id="audio-file" accept=".mp3,.wav,.m4a,.ogg" hidden>
                            <div class="mt-3">
                                <small class="text-muted">Formatos: MP3, WAV, M4A, OGG (máx. 100 MB)</small>
                            </div>
                        </div>

                        {{-- Preview --}}
                        <div id="audio-preview" class="mt-3 d-none">
                            <p class="mb-1"><strong>Archivo:</strong> <span id="file-name"></span></p>
                            <audio id="audio-player" controls style="width:100%;"></audio>
                            <div class="mt-2 d-flex" style="gap:.5rem;">
                                <button id="play-btn" class="btn btn-sm btn-success">
                                    <i class="fas fa-play"></i> Reproducir
                                </button>
                                <button id="stop-btn" class="btn btn-sm btn-danger">
                                    <i class="fas fa-stop"></i> Detener
                                </button>
                            </div>
                        </div>

                        {{-- Progreso --}}
                        <div id="progress-container" class="mt-4 d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <span id="status-message">Procesando...</span>
                                <span id="progress-percent">0%</span>
                            </div>
                            <div class="progress">
                                <div id="progress-bar"
                                    class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width:0%; background:#6777ef;"></div>
                            </div>
                        </div>

                        {{-- Botón acción --}}
                        <div class="mt-4">
                            <button id="action-btn" class="btn btn-success btn-block" disabled
                                style="background:linear-gradient(45deg,#6777ef,#35199a); border:none;">
                                <i class="fas fa-microphone-alt mr-2"></i><span id="action-btn-label">Transcribir audio</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── COLUMNA RESULTADOS ── --}}
            <div class="col-md-7">

                {{-- Resultado transcripción simple --}}
                <div id="result-transcribe" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-align-left mr-2"></i>Transcripción</h4>
                        <button id="export-transcribe-btn" class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fas fa-download mr-1"></i>Exportar TXT
                        </button>
                    </div>
                    <div class="card-body">
                        <textarea id="transcription-result" class="form-control" rows="14"
                            placeholder="El texto transcrito aparecerá aquí..."></textarea>
                        <button id="copy-btn" class="btn btn-outline-primary btn-block mt-3" disabled>
                            <i class="fas fa-copy mr-2"></i>Copiar texto
                        </button>
                    </div>
                </div>

                {{-- Resultado análisis completo (oculto por defecto) --}}
                <div id="result-analyze" class="d-none">

                    {{-- Diálogo --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-comments mr-2"></i>Transcripción con Hablantes</h4>
                            <button id="export-analyze-btn" class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="fas fa-download mr-1"></i>Exportar TXT
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div id="dialogo-container" style="max-height:340px; overflow-y:auto;">
                                <p class="text-muted text-center py-3">Los diálogos aparecerán aquí.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen --}}
                    <div class="card mb-3" id="resumen-card" style="display:none;">
                        <div class="card-header" style="background:#0ea5e9; color:#fff;">
                            <i class="fas fa-file-alt mr-2"></i>Resumen
                        </div>
                        <div class="card-body">
                            <p id="resumen-text" class="lead mb-0"></p>
                        </div>
                    </div>

                    {{-- Datos extraídos --}}
                    <div class="card" id="datos-card" style="display:none;">
                        <div class="card-header" style="background:#6777ef; color:#fff;">
                            <i class="fas fa-info-circle mr-2"></i>Datos Extraídos
                        </div>
                        <div class="card-body">
                            <div id="datos-container"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script>
$(document).ready(function () {
    const dropArea        = document.getElementById('drop-area');
    const fileInput       = document.getElementById('audio-file');
    const audioPreview    = document.getElementById('audio-preview');
    const fileNameLabel   = document.getElementById('file-name');
    const audioPlayer     = document.getElementById('audio-player');
    const playBtn         = document.getElementById('play-btn');
    const stopBtn         = document.getElementById('stop-btn');
    const progressCont    = document.getElementById('progress-container');
    const progressBar     = document.getElementById('progress-bar');
    const progressPct     = document.getElementById('progress-percent');
    const statusMsg       = document.getElementById('status-message');
    const actionBtn       = document.getElementById('action-btn');
    const actionBtnLabel  = document.getElementById('action-btn-label');
    const copyBtn         = document.getElementById('copy-btn');
    const transcriptionTA = document.getElementById('transcription-result');
    const exportTranscBtn = document.getElementById('export-transcribe-btn');
    const exportAnalyzeBtn= document.getElementById('export-analyze-btn');

    let selectedFile = null;
    let currentMode  = 'transcribe';
    let pollInterval = null;
    let lastResult   = null;  // para exportar

    // ── Modo ──────────────────────────────────────────────────────────────
    window.setMode = function(mode) {
        currentMode = mode;
        document.getElementById('mode-transcribe').classList.toggle('active', mode === 'transcribe');
        document.getElementById('mode-analyze').classList.toggle('active', mode === 'analyze');

        if (mode === 'transcribe') {
            actionBtnLabel.textContent = 'Transcribir audio';
            document.getElementById('result-transcribe').classList.remove('d-none');
            document.getElementById('result-analyze').classList.add('d-none');
        } else {
            actionBtnLabel.textContent = 'Analizar llamada';
            document.getElementById('result-transcribe').classList.add('d-none');
            document.getElementById('result-analyze').classList.remove('d-none');
        }
    };

    // ── Validación de archivo ─────────────────────────────────────────────
    function validateFile(file) {
        const allowed = ['audio/mpeg','audio/wav','audio/x-wav','audio/mp4','audio/x-m4a','audio/ogg'];
        if (!allowed.includes(file.type)) {
            setStatus('Formato no soportado. Usá MP3, WAV, M4A u OGG.', 'danger');
            return false;
        }
        if (file.size > 100 * 1024 * 1024) {
            setStatus('El archivo supera los 100 MB permitidos.', 'danger');
            return false;
        }
        return true;
    }

    function setStatus(msg, type) {
        statusMsg.textContent = msg;
        statusMsg.className   = 'text-' + type;
    }

    function resetUI() {
        progressBar.style.width = '0%';
        progressPct.textContent  = '0%';
        statusMsg.textContent    = '';
        progressCont.classList.add('d-none');
    }

    function setProgress(pct, msg) {
        progressCont.classList.remove('d-none');
        progressBar.style.width = pct + '%';
        progressPct.textContent  = pct + '%';
        if (msg) setStatus(msg, 'muted');
    }

    // ── Selección de archivo ──────────────────────────────────────────────
    fileInput.addEventListener('change', function () {
        if (!this.files.length) return;
        loadFile(this.files[0]);
    });

    dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('dragover'); });
    dropArea.addEventListener('dragleave', ()  => dropArea.classList.remove('dragover'));
    dropArea.addEventListener('drop', e => {
        e.preventDefault();
        dropArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) loadFile(e.dataTransfer.files[0]);
    });

    function loadFile(file) {
        resetUI();
        if (!validateFile(file)) { selectedFile = null; return; }
        selectedFile = file;
        fileNameLabel.textContent = file.name;
        audioPlayer.src = URL.createObjectURL(file);
        audioPreview.classList.remove('d-none');
        actionBtn.disabled = false;
        setStatus('Archivo listo: ' + file.name, 'success');
        progressCont.classList.remove('d-none');
    }

    playBtn.addEventListener('click', () => audioPlayer.play());
    stopBtn.addEventListener('click', () => { audioPlayer.pause(); audioPlayer.currentTime = 0; });

    // ── Envío ─────────────────────────────────────────────────────────────
    actionBtn.addEventListener('click', function () {
        if (!selectedFile) return;

        actionBtn.disabled = true;
        setProgress(10, 'Subiendo archivo...');

        const formData = new FormData();
        formData.append('audio', selectedFile);
        formData.append('mode', currentMode);

        fetch('{{ route("callanalysis.submit") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.job_id) throw new Error(data.message || 'Error al iniciar el proceso.');
            setProgress(20, 'En cola — esperando que el servidor procese...');
            startPolling(data.job_id);
        })
        .catch(err => {
            setProgress(0, '');
            setStatus('Error: ' + err.message, 'danger');
            actionBtn.disabled = false;
        });
    });

    // ── Polling cada 5 segundos ───────────────────────────────────────────
    function startPolling(jobId) {
        let visualPct   = 20;
        let attempts    = 0;
        const MAX       = 240;  // 240 × 5s = 20 min máximo

        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(() => {
            attempts++;

            if (attempts > MAX) {
                clearInterval(pollInterval);
                setStatus('Tiempo de espera agotado. El servidor puede seguir procesando; recargá más tarde.', 'warning');
                actionBtn.disabled = false;
                return;
            }

            // Avanzar la barra visualmente
            if (visualPct < 90) {
                visualPct += 0.4;
                setProgress(Math.round(visualPct), null);
            }

            fetch('{{ url("call-analysis/estado") }}/' + jobId, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(r => r.json())
            .then(estado => {
                if (estado.status === 'completed') {
                    clearInterval(pollInterval);
                    setProgress(100, '¡Proceso completado!');
                    progressBar.classList.remove('bg-danger');
                    actionBtn.disabled = false;
                    lastResult = estado;
                    renderResult(estado);
                } else if (estado.status === 'failed') {
                    clearInterval(pollInterval);
                    setProgress(100, '');
                    progressBar.classList.add('bg-danger');
                    setStatus('Error: ' + (estado.error || 'Error desconocido'), 'danger');
                    actionBtn.disabled = false;
                } else if (estado.status === 'processing') {
                    setStatus('Procesando en el servidor IA...', 'muted');
                }
                // pending → seguir esperando
            })
            .catch(() => { /* error de red — seguir intentando */ });

        }, 5000);
    }

    // ── Render resultados ─────────────────────────────────────────────────
    function renderResult(estado) {
        const result = estado.result;
        if (!result) return;

        if (estado.mode === 'transcribe') {
            // Solo texto
            transcriptionTA.value = result.text || '';
            copyBtn.disabled       = false;
            exportTranscBtn.disabled = false;
        } else {
            // Análisis completo
            renderDialogos(result);
            renderResumen(result);
            renderDatos(result);
            exportAnalyzeBtn.disabled = false;
        }
    }

    function renderDialogos(result) {
        const container = document.getElementById('dialogo-container');
        container.innerHTML = '';

        const dialogos = result?.transcription?.dialogos || [];
        if (!dialogos.length) {
            container.innerHTML = '<p class="text-muted text-center py-3">Sin diálogos disponibles.</p>';
            return;
        }

        const html = dialogos.map(d => {
            const isAgente  = d.rol === 'AGENTE_911';
            const alignClass = isAgente ? 'align-left' : 'align-right';
            return `<div class="message ${alignClass}">
                        <div class="speaker">${escapeHtml(d.rol)}</div>
                        <div class="bubble">
                            ${escapeHtml(d.texto)}
                            <span class="timestamp">[${escapeHtml(d.timestamp || '')}]</span>
                        </div>
                    </div>`;
        }).join('');

        container.innerHTML = html;
    }

    function renderResumen(result) {
        const card = document.getElementById('resumen-card');
        const el   = document.getElementById('resumen-text');
        if (result.resumen) {
            el.textContent = result.resumen;
            card.style.display = '';
        }
    }

    function renderDatos(result) {
        const card = document.getElementById('datos-card');
        const cont = document.getElementById('datos-container');
        const de   = result?.datos_extraidos;
        if (!de) return;

        const categorias = [
            { key: 'nombres',    label: 'Nombres',    icon: 'fas fa-user' },
            { key: 'telefonos',  label: 'Teléfonos',  icon: 'fas fa-phone' },
            { key: 'direcciones',label: 'Direcciones',icon: 'fas fa-map-marker-alt' },
            { key: 'documentos', label: 'Documentos', icon: 'fas fa-id-card' },
            { key: 'otros',      label: 'Otros datos',icon: 'fas fa-info-circle' },
        ];

        let html = '<div class="row">';
        categorias.forEach(cat => {
            const items = de[cat.key] || [];
            if (!items.length) return;
            html += `<div class="col-md-6 mb-3">
                        <h6><i class="${cat.icon} mr-1"></i>${cat.label}</h6>
                        <ul class="list-group list-group-flush">
                            ${items.map(v => `<li class="list-group-item py-1">${escapeHtml(String(v))}</li>`).join('')}
                        </ul>
                    </div>`;
        });
        html += '</div>';
        cont.innerHTML = html;
        card.style.display = '';
    }

    // ── Copiar ────────────────────────────────────────────────────────────
    copyBtn.addEventListener('click', () => {
        transcriptionTA.select();
        document.execCommand('copy');
        copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Copiado';
        setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy mr-2"></i>Copiar texto', 2000);
    });

    // ── Exportar TXT (transcripción simple) ───────────────────────────────
    exportTranscBtn.addEventListener('click', () => {
        const text = transcriptionTA.value;
        if (!text) return;
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        downloadBlob(blob, 'transcripcion_' + timestamp() + '.txt');
    });

    // ── Exportar TXT (análisis completo) ──────────────────────────────────
    exportAnalyzeBtn.addEventListener('click', () => {
        if (!lastResult || !lastResult.result) return;
        const txt = buildAnalysisTxt(lastResult.result);
        const blob = new Blob([txt], { type: 'text/plain;charset=utf-8' });
        downloadBlob(blob, 'analisis_911_' + timestamp() + '.txt');
    });

    function buildAnalysisTxt(result) {
        const lines = [];
        lines.push('ANÁLISIS DE LLAMADA 911');
        lines.push('Archivo: ' + (result.nombre_archivo || ''));
        lines.push('Fecha:   ' + new Date().toLocaleString('es-AR'));
        lines.push('');

        // Resumen
        if (result.resumen) {
            lines.push('── RESUMEN ──────────────────────────────────────────────────────');
            lines.push(result.resumen);
            lines.push('');
        }

        // Diálogos
        const dialogos = result?.transcription?.dialogos || [];
        if (dialogos.length) {
            lines.push('── TRANSCRIPCIÓN CON HABLANTES ──────────────────────────────────');
            dialogos.forEach(d => {
                lines.push(`[${d.timestamp || '??:??'}] ${d.rol}: ${d.texto}`);
            });
            lines.push('');
        }

        // Datos extraídos
        const de = result?.datos_extraidos;
        if (de) {
            lines.push('── DATOS EXTRAÍDOS ──────────────────────────────────────────────');
            ['nombres','telefonos','direcciones','documentos','otros'].forEach(k => {
                const items = de[k] || [];
                if (items.length) lines.push(`${k.toUpperCase()}: ${items.join(', ')}`);
            });
            lines.push('');
        }

        return lines.join('\n');
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    function escapeHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function timestamp() {
        return new Date().toISOString().slice(0,19).replace(/[T:]/g,'-');
    }

    function downloadBlob(blob, filename) {
        const a   = document.createElement('a');
        a.href    = URL.createObjectURL(blob);
        a.download = filename;
        a.click();
        URL.revokeObjectURL(a.href);
    }
});
</script>
@endsection
