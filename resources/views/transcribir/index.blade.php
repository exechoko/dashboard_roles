@extends('layouts.app')

@section('content')
<style>
    /* ── Variables de color para burbujas de chat ── */
    :root {
        --operador-color:      #d4edda;
        --operador-header:     #28a745;
        --operador-border:     #28a745;
        --denunciante-color:   #cce5ff;
        --denunciante-header:  #007bff;
        --denunciante-border:  #007bff;
        --timestamp-color:     #6c757d;
        --drop-bg:             #f8f9fa;
        --drop-hover-bg:       #e9f0ff;
        --mode-card-text:      inherit;
    }
    [data-theme="dark"] {
        --operador-color:      #1e4620;
        --operador-header:     #5cb85c;
        --operador-border:     #5cb85c;
        --denunciante-color:   #1a3a52;
        --denunciante-header:  #5bc0de;
        --denunciante-border:  #5bc0de;
        --timestamp-color:     #9ca3af;
        --drop-bg:             var(--bg-tertiary);
        --drop-hover-bg:       #1e293b;
        --mode-card-text:      var(--text-primary);
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
    /* Burbuja operador - izquierda */
    .align-left .bubble {
        background: var(--operador-color);
        border-bottom-left-radius: 4px;
        border-left: 3px solid var(--operador-border);
    }
    /* Burbuja denunciante - derecha */
    .align-right .bubble {
        background: var(--denunciante-color);
        border-bottom-right-radius: 4px;
        border-right: 3px solid var(--denunciante-border);
    }
    .align-left .speaker { color: var(--operador-header); }
    .align-right .speaker { color: var(--denunciante-header); }
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

        {{-- ── TABS PRINCIPAL ── --}}
        <ul class="nav nav-pills mb-3" id="transcribir-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-transcribir" data-toggle="tab" href="#panel-transcribir" role="tab">
                    <i class="fas fa-microphone-alt mr-1"></i>Transcribir
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-historial" data-toggle="tab" href="#panel-historial" role="tab">
                    <i class="fas fa-history mr-1"></i>Historial
                </a>
            </li>
        </ul>

        <div class="tab-content" id="transcribir-tabs-content">
        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: TRANSCRIBIR --}}
        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="panel-transcribir" role="tabpanel">

        
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
                                <i class="fas fa-headset mr-2"></i><span id="action-btn-label">Analizar llamada</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── COLUMNA RESULTADOS ── --}}
            <div class="col-md-7">

                {{-- Resultado análisis completo --}}
                <div id="result-analyze">

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
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: HISTORIAL --}}
        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="panel-historial" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-search mr-2"></i>Buscar Transcripciones</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="buscar-nombre">Nombre del archivo</label>
                                <input type="text" id="buscar-nombre" class="form-control" placeholder="Ej: llamada_001.mp3">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="buscar-telefono">Teléfono</label>
                                <input type="text" id="buscar-telefono" class="form-control" placeholder="Ej: 999123456">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="btn-buscar-historial" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resultados del historial --}}
            <div id="historial-results" class="mt-3">
                <p class="text-muted text-center py-4">Ingresa criterios de búsqueda o haz clic en Buscar para ver todas las transcripciones.</p>
            </div>
        </div>
        {{-- FIN TAB HISTORIAL --}}

        </div>{{-- fin tab-content --}}
    </div>
</section>

{{-- Modal para ver detalle de transcripción --}}
<div class="modal fade" id="modal-detalle-transcripcion" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-audio mr-2"></i><span id="modal-titulo"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Teléfono:</strong> <span id="modal-telefono">-</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Fecha:</strong> <span id="modal-fecha">-</span>
                    </div>
                </div>
                <div id="modal-resumen" class="alert alert-info mb-3" style="display:none;">
                    <strong>Resumen:</strong> <span id="modal-resumen-text"></span>
                </div>
                <h6><i class="fas fa-comments mr-1"></i>Transcripción</h6>
                <div id="modal-transcripcion" class="border rounded p-3" style="max-height:350px; overflow-y:auto;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

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
    const exportAnalyzeBtn= document.getElementById('export-analyze-btn');

    let selectedFile = null;
    let currentMode  = 'analyze'; // Siempre análisis completo
    let pollInterval = null;
    let lastResult   = null;  // para exportar

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

        // Análisis completo
        renderDialogos(result);
        renderResumen(result);
        renderDatos(result);
        exportAnalyzeBtn.disabled = false;
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
            // Operador a la izquierda, denunciante a la derecha
            const isOperador = d.rol === 'OPERADOR_911' || d.rol === 'AGENTE_911';
            const alignClass = isOperador ? 'align-left' : 'align-right';
            const rolLabel   = isOperador ? 'Operador 911' : 'Denunciante';
            return `<div class="message ${alignClass}">
                        <div class="speaker">${escapeHtml(rolLabel)}</div>
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

    // ══════════════════════════════════════════════════════════════════════════
    // HISTORIAL - Búsqueda y visualización
    // ══════════════════════════════════════════════════════════════════════════
    const btnBuscar = document.getElementById('btn-buscar-historial');
    const inputNombre = document.getElementById('buscar-nombre');
    const inputTelefono = document.getElementById('buscar-telefono');
    const historialResults = document.getElementById('historial-results');

    btnBuscar.addEventListener('click', buscarHistorial);
    inputNombre.addEventListener('keypress', e => { if (e.key === 'Enter') buscarHistorial(); });
    inputTelefono.addEventListener('keypress', e => { if (e.key === 'Enter') buscarHistorial(); });

    function buscarHistorial() {
        const nombre = inputNombre.value.trim();
        const telefono = inputTelefono.value.trim();

        historialResults.innerHTML = '<p class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Buscando...</p>';

        const params = new URLSearchParams();
        if (nombre) params.append('nombre_archivo', nombre);
        if (telefono) params.append('telefono', telefono);

        fetch('{{ route("transcribe.historial") }}?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            const items = data.data || data || [];
            if (!items.length) {
                historialResults.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No se encontraron transcripciones.</div>';
                return;
            }
            renderHistorial(items);
        })
        .catch(err => {
            historialResults.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error al buscar: ' + err.message + '</div>';
        });
    }

    function renderHistorial(items) {
        let html = '<div class="row">';
        items.forEach((item, idx) => {
            const fecha = item.created_at ? new Date(item.created_at).toLocaleString('es-AR') : '-';
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-truncate" title="${escapeHtml(item.nombre_archivo || '')}">
                                <i class="fas fa-file-audio text-primary mr-1"></i>${escapeHtml(item.nombre_archivo || 'Sin nombre')}
                            </h6>
                            <p class="card-text small mb-1">
                                <i class="fas fa-phone text-muted mr-1"></i>${escapeHtml(item.telefono || '-')}
                            </p>
                            <p class="card-text small text-muted">
                                <i class="fas fa-calendar mr-1"></i>${fecha}
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <button class="btn btn-sm btn-outline-primary btn-block btn-ver-detalle" data-id="${item.id}">
                                <i class="fas fa-eye mr-1"></i>Ver detalle
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        historialResults.innerHTML = html;

        // Bind eventos
        document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
            btn.addEventListener('click', function() {
                verDetalleTranscripcion(this.dataset.id);
            });
        });
    }

    function verDetalleTranscripcion(id) {
        fetch('{{ url("transcribir/ver") }}/' + id, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('modal-titulo').textContent = data.nombre_archivo || 'Transcripción';
            document.getElementById('modal-telefono').textContent = data.telefono || '-';
            document.getElementById('modal-fecha').textContent = data.created_at ? new Date(data.created_at).toLocaleString('es-AR') : '-';

            // Resumen
            const resumenDiv = document.getElementById('modal-resumen');
            if (data.resumen) {
                document.getElementById('modal-resumen-text').textContent = data.resumen;
                resumenDiv.style.display = '';
            } else {
                resumenDiv.style.display = 'none';
            }

            // Transcripción como chat
            const transcripcionDiv = document.getElementById('modal-transcripcion');
            const json = data.transcripcion_json;

            if (json && json.dialogos && json.dialogos.length) {
                // Formato chat
                let chatHtml = '<div id="dialogo-container">';
                json.dialogos.forEach(d => {
                    const isOperador = d.rol === 'OPERADOR_911' || d.rol === 'AGENTE_911';
                    const alignClass = isOperador ? 'align-left' : 'align-right';
                    const rolLabel = isOperador ? 'Operador 911' : 'Denunciante';
                    chatHtml += `<div class="message ${alignClass}">
                        <div class="speaker">${escapeHtml(rolLabel)}</div>
                        <div class="bubble">
                            ${escapeHtml(d.texto)}
                            <span class="timestamp">[${escapeHtml(d.timestamp || '')}]</span>
                        </div>
                    </div>`;
                });
                chatHtml += '</div>';
                transcripcionDiv.innerHTML = chatHtml;
            } else if (json && json.text) {
                // Solo texto
                transcripcionDiv.innerHTML = '<pre style="white-space:pre-wrap;">' + escapeHtml(json.text) + '</pre>';
            } else if (typeof json === 'string') {
                transcripcionDiv.innerHTML = '<pre style="white-space:pre-wrap;">' + escapeHtml(json) + '</pre>';
            } else {
                transcripcionDiv.innerHTML = '<p class="text-muted">Sin transcripción disponible.</p>';
            }

            $('#modal-detalle-transcripcion').modal('show');
        })
        .catch(err => {
            alert('Error al cargar detalle: ' + err.message);
        });
    }
});
</script>
@endsection
