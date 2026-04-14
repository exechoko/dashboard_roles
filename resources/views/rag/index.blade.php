@extends('layouts.app')

@section('content')
<style>
    /* ── Chat dark-mode aware ─────────────────────────────────────────── */
    .chat-historial-bg {
        background: rgba(0,0,0,.04);
    }
    [data-theme="dark"] .chat-historial-bg {
        background: rgba(255,255,255,.05);
    }
    .chat-bubble-ia {
        background: #fff;
        color: #212529;
        border: 1px solid rgba(0,0,0,.125);
    }
    [data-theme="dark"] .chat-bubble-ia {
        background: #3a3f51;
        border-color: rgba(255,255,255,.1);
        color: #e0e0e0;
    }
    [data-theme="dark"] .chat-bubble-ia .text-muted {
        color: #9a9eb5 !important;
    }
    .chat-bubble-loading {
        background: #fff;
        border: 1px solid rgba(0,0,0,.125);
        color: #6c757d;
    }
    [data-theme="dark"] .chat-bubble-loading {
        background: #3a3f51;
        border-color: rgba(255,255,255,.1);
        color: #adb5bd;
    }
    .chat-bubble-error {
        background: #fff;
        border: 1px solid rgba(0,0,0,.125);
        color: #dc3545;
    }
    [data-theme="dark"] .chat-bubble-error {
        background: #3a3f51;
        border-color: rgba(255,255,255,.1);
        color: #ff7f7f;
    }
    .resumen-box-bg {
        background: rgba(0,0,0,.05);
    }
    [data-theme="dark"] .resumen-box-bg {
        background: rgba(255,255,255,.08);
        color: #e0e0e0;
    }
    .tematica-form-bg {
        background: rgba(0,0,0,.03);
    }
    [data-theme="dark"] .tematica-form-bg {
        background: rgba(255,255,255,.06);
    }
</style>
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Base de Conocimiento — Servidor IA</h3>
    </div>
    <div class="section-body">

        {{-- Estado de servicios --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-2 d-flex align-items-center justify-content-between">
                        <div id="estado-servicios">
                            <span class="badge badge-secondary px-3 py-2">Verificando servicios...</span>
                        </div>
                        <button id="btn-refresh-estado" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            {{-- Columna izquierda: Temáticas --}}
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Temáticas</h4>
                        <button class="btn btn-sm btn-primary" id="btn-nueva-tematica"
                            style="background: linear-gradient(45deg,#6777ef,#35199a); border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        {{-- Formulario nueva temática (oculto por defecto) --}}
                        <div id="form-nueva-tematica" class="p-3 border-bottom d-none tematica-form-bg">
                            <div class="form-group mb-2">
                                <input type="text" id="nueva-nombre" class="form-control form-control-sm"
                                    placeholder="Nombre (ej: Manual de Usuario)" maxlength="80">
                            </div>
                            <div class="form-group mb-2">
                                <input type="text" id="nueva-descripcion" class="form-control form-control-sm"
                                    placeholder="Descripción (opcional)" maxlength="255">
                            </div>
                            <div class="d-flex gap-2">
                                <button id="btn-guardar-tematica" class="btn btn-sm btn-success flex-fill">
                                    <i class="fas fa-check mr-1"></i>Crear
                                </button>
                                <button id="btn-cancelar-tematica" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Lista de temáticas --}}
                        <div id="lista-tematicas">
                            @forelse($tematicas as $t)
                            <div class="tematica-item border-bottom px-3 py-2 d-flex align-items-center justify-content-between"
                                data-coleccion="{{ $t->coleccion }}"
                                data-nombre="{{ $t->nombre }}"
                                style="cursor: pointer;">
                                <div>
                                    <div class="font-weight-bold" style="font-size: 0.9rem;">{{ $t->nombre }}</div>
                                    @if($t->descripcion)
                                        <small class="text-muted">{{ $t->descripcion }}</small>
                                    @endif
                                    <small class="d-block text-muted docs-count" style="font-size: 0.75rem;">
                                        <i class="fas fa-file-alt mr-1"></i><span>—</span> chunks
                                    </small>
                                </div>
                                <button class="btn btn-sm btn-link text-danger p-0 btn-eliminar-tematica"
                                    data-coleccion="{{ $t->coleccion }}" title="Eliminar temática">
                                    <i class="fas fa-trash-alt fa-sm"></i>
                                </button>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="no-tematicas-msg">
                                <i class="fas fa-folder-open fa-2x mb-2"></i>
                                <p class="mb-0" style="font-size: 0.85rem;">No hay temáticas.<br>Creá la primera.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna central: Cargar documento --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="mb-0">
                            Cargar documento
                            <span id="tematica-activa-badge" class="badge badge-primary ml-2 d-none" style="font-size: 0.75rem;"></span>
                        </h4>
                    </div>
                    <div class="card-body">

                        <div id="seleccionar-tematica-msg" class="text-center text-muted py-4">
                            <i class="fas fa-hand-point-left fa-2x mb-2"></i>
                            <p>Seleccioná una temática para cargar documentos.</p>
                        </div>

                        <div id="panel-carga" class="d-none">
                            {{-- Drop zone --}}
                            <div id="drop-doc" class="p-3 border rounded text-center mb-2"
                                style="border: 2px dashed #6777ef !important; cursor: pointer; min-height: 100px;">
                                <i class="fas fa-file-upload fa-2x mb-1 text-primary"></i>
                                <p class="mb-0" style="font-size: 0.9rem;">Arrastrá archivos aquí o hacé click</p>
                                <small class="text-muted">TXT, PDF, CSV, MD — hasta 5 archivos — máx. 50MB c/u</small>
                                <input type="file" id="doc-file" accept=".txt,.pdf,.csv,.md" multiple hidden>
                            </div>

                            {{-- Lista de archivos seleccionados --}}
                            <div id="archivos-seleccionados" class="mb-2 d-none">
                                <div id="archivos-lista" class="border rounded" style="max-height: 160px; overflow-y: auto;"></div>
                                <small class="text-muted"><span id="archivos-count">0</span>/5 archivos seleccionados</small>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                <label class="mb-0 d-flex align-items-center" style="cursor:pointer; font-size:.85rem; gap:.5rem;">
                                    <span class="text-muted">Generar resumen con IA</span>
                                    <div class="custom-control custom-switch ml-1 mb-0">
                                        <input type="checkbox" class="custom-control-input" id="toggle-resumir" checked>
                                        <label class="custom-control-label" for="toggle-resumir"></label>
                                    </div>
                                </label>
                                <small id="resumir-hint" class="text-muted" style="font-size:.75rem;">
                                    <i class="fas fa-info-circle mr-1"></i>Procesamiento async
                                </small>
                            </div>

                            <button id="btn-cargar" class="btn btn-primary btn-block" disabled
                                style="background: linear-gradient(45deg,#6777ef,#35199a); border: none;">
                                <i class="fas fa-upload mr-2"></i>Cargar al RAG
                            </button>

                            {{-- Estado por archivo --}}
                            <div id="archivos-estado" class="mt-3 d-none">
                                <h6 style="font-size: 0.85rem;" class="text-muted mb-1">Estado de carga:</h6>
                                <div id="archivos-estado-lista"></div>
                            </div>

                            <div class="mt-2 d-flex justify-content-end">
                                <button id="btn-reindexar" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-database mr-1"></i>Re-indexar temática
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna derecha: Chat --}}
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="mb-0">
                            Consultar
                            <span id="chat-tematica-badge" class="badge badge-primary ml-2 d-none" style="font-size: 0.75rem;"></span>
                        </h4>
                    </div>
                    <div class="card-body d-flex flex-column">

                        <div id="chat-placeholder" class="text-center text-muted flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Seleccioná una temática para comenzar a consultar.</p>
                        </div>

                        <div id="chat-panel" class="d-none flex-column h-100">
                            <div id="chat-historial" class="flex-grow-1 mb-3 p-3 chat-historial-bg rounded"
                                style="min-height: 340px; max-height: 440px; overflow-y: auto;"></div>

                            <div class="input-group">
                                <input type="text" id="pregunta-input" class="form-control"
                                    placeholder="Ej: ¿Qué dice el reglamento sobre licencias?"
                                    maxlength="500">
                                <div class="input-group-append">
                                    <button id="btn-preguntar" class="btn btn-primary"
                                        style="background: linear-gradient(45deg,#6777ef,#35199a); border: none;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-1">Enter para enviar</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /row --}}
    </div>
</section>

<script>
$(document).ready(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let coleccionActiva = null;
    let nombreActivo    = null;

    // ── Toggle resumen ───────────────────────────────────────────────────────
    $('#toggle-resumir').on('change', function () {
        const activo = $(this).is(':checked');
        $('#resumir-hint').html(activo
            ? '<i class="fas fa-info-circle mr-1"></i>Procesamiento async'
            : '<i class="fas fa-bolt mr-1"></i>Procesamiento inmediato');
    });

    // ── Estado de servicios ──────────────────────────────────────────────────
    function cargarEstado() {
        $.get('{{ route("rag.estado") }}', function (data) {
            const servicios = [
                { key: 'whisper', label: 'Whisper', icon: 'fa-microphone-alt' },
                { key: 'rag',     label: 'RAG',     icon: 'fa-database' },
                { key: 'ollama',  label: 'Ollama',  icon: 'fa-brain' },
            ];
            let html = '';
            servicios.forEach(s => {
                const ok = data[s.key];
                html += `<span class="badge badge-${ok ? 'success' : 'danger'} px-3 py-2 mr-2">
                    <i class="fas ${s.icon} mr-1"></i>${s.label}: ${ok ? 'Online' : 'Offline'}
                </span>`;
            });
            $('#estado-servicios').html(html);
        });
    }
    cargarEstado();
    $('#btn-refresh-estado').on('click', cargarEstado);

    // ── Cargar contadores de chunks por temática ─────────────────────────────
    function cargarContadores() {
        $.get('{{ route("rag.colecciones") }}', function (data) {
            (data.tematicas || []).forEach(t => {
                $(`.tematica-item[data-coleccion="${t.coleccion}"] .docs-count span`)
                    .text(t.documentos);
            });
        });
    }
    cargarContadores();

    // ── Seleccionar temática ─────────────────────────────────────────────────
    $(document).on('click', '.tematica-item', function () {
        $('.tematica-item').removeClass('bg-primary-light').css('background', '');
        $(this).css('background', '#eef0fd');

        coleccionActiva = $(this).data('coleccion');
        nombreActivo    = $(this).data('nombre');

        // Badges
        $('#tematica-activa-badge, #chat-tematica-badge')
            .text(nombreActivo).removeClass('d-none');

        // Paneles
        $('#seleccionar-tematica-msg').addClass('d-none');
        $('#panel-carga').removeClass('d-none');
        $('#chat-placeholder').addClass('d-none');
        $('#chat-panel').removeClass('d-none').addClass('d-flex');

        // Limpiar resultado anterior
        $('#carga-resultado').addClass('d-none');
        $('#resumen-box').addClass('d-none');
        resetChat();
        cargarHistorial(coleccionActiva);
    });

    // ── Nueva temática ───────────────────────────────────────────────────────
    $('#btn-nueva-tematica').on('click', function () {
        $('#form-nueva-tematica').toggleClass('d-none');
        $('#nueva-nombre').focus();
    });

    $('#btn-cancelar-tematica').on('click', function () {
        $('#form-nueva-tematica').addClass('d-none');
        $('#nueva-nombre, #nueva-descripcion').val('');
    });

    $('#btn-guardar-tematica').on('click', function () {
        const nombre = $('#nueva-nombre').val().trim();
        if (!nombre) { toastr.warning('Ingresá un nombre para la temática.'); return; }

        $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("rag.tematicas.crear") }}',
            method: 'POST',
            data: {
                _token: csrf,
                nombre: nombre,
                descripcion: $('#nueva-descripcion').val().trim(),
            },
            success: function (data) {
                if (data.success) {
                    const t = data.tematica;
                    const html = `
                        <div class="tematica-item border-bottom px-3 py-2 d-flex align-items-center justify-content-between"
                            data-coleccion="${t.coleccion}" data-nombre="${t.nombre}" style="cursor: pointer;">
                            <div>
                                <div class="font-weight-bold" style="font-size:.9rem;">${escapeHtml(t.nombre)}</div>
                                ${t.descripcion ? `<small class="text-muted">${escapeHtml(t.descripcion)}</small>` : ''}
                                <small class="d-block text-muted docs-count" style="font-size:.75rem;">
                                    <i class="fas fa-file-alt mr-1"></i><span>0</span> chunks
                                </small>
                            </div>
                            <button class="btn btn-sm btn-link text-danger p-0 btn-eliminar-tematica"
                                data-coleccion="${t.coleccion}" title="Eliminar temática">
                                <i class="fas fa-trash-alt fa-sm"></i>
                            </button>
                        </div>`;
                    $('#no-tematicas-msg').remove();
                    $('#lista-tematicas').append(html);
                    $('#form-nueva-tematica').addClass('d-none');
                    $('#nueva-nombre, #nueva-descripcion').val('');
                    toastr.success(`Temática "${t.nombre}" creada.`);
                } else {
                    toastr.error(data.message || 'Error al crear la temática.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al crear la temática.');
            },
            complete: function () {
                $('#btn-guardar-tematica').prop('disabled', false);
            }
        });
    });

    // ── Eliminar temática ────────────────────────────────────────────────────
    $(document).on('click', '.btn-eliminar-tematica', function (e) {
        e.stopPropagation();
        const col   = $(this).data('coleccion');
        const $item = $(this).closest('.tematica-item');
        const nombre = $item.data('nombre');

        if (!confirm(`¿Eliminar la temática "${nombre}"?\n\nLos documentos en el servidor IA NO se borran.`)) return;

        $.ajax({
            url: `/rag/tematicas/${col}`,
            method: 'DELETE',
            data: { _token: csrf },
            success: function (data) {
                if (data.success) {
                    $item.remove();
                    if (coleccionActiva === col) {
                        coleccionActiva = null;
                        $('#panel-carga').addClass('d-none');
                        $('#seleccionar-tematica-msg').removeClass('d-none');
                        $('#chat-panel').addClass('d-none').removeClass('d-flex');
                        $('#chat-placeholder').removeClass('d-none');
                        $('#tematica-activa-badge, #chat-tematica-badge').addClass('d-none');
                    }
                    if ($('#lista-tematicas .tematica-item').length === 0) {
                        $('#lista-tematicas').html(`
                            <div class="text-center text-muted py-4" id="no-tematicas-msg">
                                <i class="fas fa-folder-open fa-2x mb-2"></i>
                                <p class="mb-0" style="font-size:.85rem;">No hay temáticas.<br>Creá la primera.</p>
                            </div>`);
                    }
                    toastr.success(`Temática "${nombre}" eliminada.`);
                }
            },
            error: function () { toastr.error('No se pudo eliminar la temática.'); }
        });
    });

    // ── Carga de documentos (hasta 5 archivos) ───────────────────────────────
    const dropDoc = document.getElementById('drop-doc');
    const docFile = document.getElementById('doc-file');
    const MAX_ARCHIVOS = 5;
    const MAX_SIZE     = 50 * 1024 * 1024;
    const EXTS_OK      = ['txt', 'pdf', 'csv', 'md'];
    let archivosSeleccionados = []; // array de File

    dropDoc.addEventListener('click', () => docFile.click());
    dropDoc.addEventListener('dragover', e => { e.preventDefault(); dropDoc.classList.add('bg-light'); });
    dropDoc.addEventListener('dragleave', () => dropDoc.classList.remove('bg-light'));
    dropDoc.addEventListener('drop', e => {
        e.preventDefault();
        dropDoc.classList.remove('bg-light');
        agregarArchivos(Array.from(e.dataTransfer.files));
    });
    docFile.addEventListener('change', function () {
        agregarArchivos(Array.from(this.files));
        this.value = ''; // reset para poder volver a seleccionar los mismos
    });

    function agregarArchivos(nuevos) {
        let rechazados = [];
        nuevos.forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!EXTS_OK.includes(ext)) {
                rechazados.push(`${file.name}: formato no soportado`);
                return;
            }
            if (file.size > MAX_SIZE) {
                rechazados.push(`${file.name}: supera 20MB`);
                return;
            }
            if (archivosSeleccionados.length >= MAX_ARCHIVOS) {
                rechazados.push(`${file.name}: límite de ${MAX_ARCHIVOS} archivos alcanzado`);
                return;
            }
            // Evitar duplicados por nombre
            if (!archivosSeleccionados.find(f => f.name === file.name)) {
                archivosSeleccionados.push(file);
            }
        });

        if (rechazados.length) {
            toastr.warning(rechazados.join('<br>'), 'Archivos omitidos', { escapeHtml: false });
        }
        renderizarListaArchivos();
    }

    function renderizarListaArchivos() {
        const lista = $('#archivos-lista');
        lista.empty();

        archivosSeleccionados.forEach((file, idx) => {
            const kb = (file.size / 1024).toFixed(1);
            lista.append(`
                <div class="d-flex align-items-center justify-content-between px-2 py-1
                    ${idx < archivosSeleccionados.length - 1 ? 'border-bottom' : ''}"
                    style="font-size:.85rem;">
                    <span><i class="fas fa-file-alt text-primary mr-1"></i>${escapeHtml(file.name)}
                        <small class="text-muted ml-1">${kb} KB</small>
                    </span>
                    <button class="btn btn-sm btn-link text-danger p-0 btn-quitar-archivo"
                        data-idx="${idx}" style="font-size:.8rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`);
        });

        const hay = archivosSeleccionados.length > 0;
        $('#archivos-seleccionados').toggleClass('d-none', !hay);
        $('#archivos-count').text(archivosSeleccionados.length);
        $('#btn-cargar').prop('disabled', !hay);
        $('#archivos-estado').addClass('d-none');
    }

    $(document).on('click', '.btn-quitar-archivo', function () {
        const idx = parseInt($(this).data('idx'));
        archivosSeleccionados.splice(idx, 1);
        renderizarListaArchivos();
    });

    $('#btn-cargar').on('click', function () {
        if (!archivosSeleccionados.length || !coleccionActiva) return;

        const formData = new FormData();
        archivosSeleccionados.forEach(f => formData.append('documentos[]', f));
        formData.append('coleccion', coleccionActiva);
        const resumir = $('#toggle-resumir').is(':checked') ? '1' : '0';
        formData.append('resumir', resumir);
        formData.append('_token', csrf);

        $('#btn-cargar').prop('disabled', true);

        // Renderizar filas de estado vacías para cada archivo
        const $estadoLista = $('#archivos-estado-lista').empty();
        archivosSeleccionados.forEach((f, idx) => {
            $estadoLista.append(`
                <div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom archivo-estado-row"
                    id="estado-row-${idx}" style="font-size:.85rem;">
                    <span class="text-truncate mr-2" style="max-width:60%;">
                        <i class="fas fa-file-alt text-primary mr-1"></i>${escapeHtml(f.name)}
                    </span>
                    <span id="estado-badge-${idx}">
                        <span class="badge badge-secondary">Enviando...</span>
                    </span>
                </div>`);
        });
        $('#archivos-estado').removeClass('d-none');

        $.ajax({
            url: '{{ route("rag.cargar") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000,
            success: function (data) {
                if (!data.success) {
                    archivosSeleccionados.forEach((f, idx) =>
                        setBadge(idx, 'danger', 'Error: ' + escapeHtml(data.message || 'Desconocido'))
                    );
                    $('#btn-cargar').prop('disabled', false);
                    return;
                }

                const archivos = data.archivos || [];
                archivos.forEach((item, idx) => {
                    if (!item.async) {
                        // Sin resumen: ya terminó
                        if (item.status === 'completed') {
                            setBadge(idx, 'success', `<i class="fas fa-check mr-1"></i>Indexado (${item.documentos_total ?? '—'} chunks)`);
                        } else {
                            setBadge(idx, 'danger', escapeHtml(item.error || 'Error'));
                        }
                    } else {
                        // Con resumen: polling
                        setBadge(idx, 'warning', '<i class="fas fa-circle-notch fa-spin mr-1"></i>Procesando...');
                        iniciarPolling(idx, item.job_id, item.archivo);
                    }
                });

                archivosSeleccionados = [];
                renderizarListaArchivos();
                $('#archivos-estado').removeClass('d-none');
                $('#btn-cargar').prop('disabled', true);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al conectar con el servidor.';
                archivosSeleccionados.forEach((f, idx) => setBadge(idx, 'danger', escapeHtml(msg)));
                $('#btn-cargar').prop('disabled', false);
            }
        });
    });

    function setBadge(idx, tipo, html) {
        $(`#estado-badge-${idx}`).html(`<span class="badge badge-${tipo}">${html}</span>`);
    }

    function iniciarPolling(idx, jobId, nombreArchivo) {
        let intentos = 0;
        const MAX_POLL = 200; // 10 min

        const timer = setInterval(function () {
            intentos++;
            if (intentos > MAX_POLL) {
                clearInterval(timer);
                setBadge(idx, 'warning', 'Timeout — sigue procesando en el servidor');
                return;
            }

            $.get(`/rag/carga-estado/${jobId}`, function (estado) {
                if (estado.status === 'completed') {
                    clearInterval(timer);
                    setBadge(idx, 'success',
                        `<i class="fas fa-check mr-1"></i>Indexado (${estado.documentos_total ?? '—'} chunks)`);
                    if (estado.resumen) {
                        $(`#estado-row-${idx}`).after(`
                            <div class="px-2 pb-2" style="font-size:.8rem;">
                                <a class="text-primary" style="cursor:pointer;"
                                    onclick="$(this).next().toggleClass('d-none')">
                                    <i class="fas fa-eye mr-1"></i>Ver resumen
                                </a>
                                <div class="d-none mt-1 p-2 resumen-box-bg rounded"
                                    style="white-space:pre-wrap;max-height:120px;overflow-y:auto;">
                                    ${escapeHtml(estado.resumen)}
                                </div>
                            </div>`);
                    }
                    cargarContadores();
                } else if (estado.status === 'failed') {
                    clearInterval(timer);
                    setBadge(idx, 'danger',
                        `<i class="fas fa-times mr-1"></i>${escapeHtml(estado.error || 'Error')}
                         <button class="btn btn-xs btn-warning ml-1 btn-reintentar-job"
                             data-job-id="${jobId}" data-idx="${idx}" data-archivo="${escapeHtml(nombreArchivo)}"
                             style="font-size:.7rem;padding:1px 6px;line-height:1.4;">
                             <i class="fas fa-redo-alt mr-1"></i>Reintentar
                         </button>`);
                }
                // pending/processing → seguir esperando
            });
        }, 3000);
    }

    $('#btn-reindexar').on('click', function () {
        if (!coleccionActiva) return;
        if (!confirm(`¿Re-indexar todos los documentos de "${nombreActivo}" desde el servidor?`)) return;
        const btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Indexando...');
        $.post('{{ route("rag.reindexar") }}', { _token: csrf, coleccion: coleccionActiva }, function (data) {
            if (data.success) {
                toastr.success(`Re-indexado: ${data.documentos} archivo(s)`);
                cargarContadores();
            } else {
                toastr.error(data.message || 'Error al re-indexar');
            }
        }).fail(() => toastr.error('No se pudo conectar al servidor IA'))
          .always(() => btn.prop('disabled', false).html('<i class="fas fa-database mr-1"></i>Re-indexar temática'));
    });

    // ── Chat ─────────────────────────────────────────────────────────────────
    function resetChat() {
        $('#chat-historial').html(`
            <div class="text-center text-muted mt-4" id="chat-inicio">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <p style="font-size:.9rem;">Hacé una pregunta sobre los documentos de <strong>${escapeHtml(nombreActivo)}</strong>.</p>
            </div>`);
    }

    function cargarHistorial(coleccion) {
        $.get('{{ route("rag.historial") }}', { coleccion }, function (data) {
            const mensajes = data.mensajes || [];
            if (!mensajes.length) return;

            $('#chat-inicio').remove();
            mensajes.forEach(m => agregarMensaje(m.role === 'user' ? 'user' : 'assistant', m.contenido));
        });
    }

    $('#pregunta-input').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn-preguntar').trigger('click');
    });

    $('#btn-preguntar').on('click', function () {
        const pregunta = $('#pregunta-input').val().trim();
        if (!pregunta || !coleccionActiva) return;

        $('#chat-inicio').remove();
        agregarMensaje('user', pregunta);
        $('#pregunta-input').val('').prop('disabled', true);
        $(this).prop('disabled', true);

        const loadingId = 'loading-' + Date.now();
        agregarMensaje('loading', '', loadingId);

        $.ajax({
            url: '{{ route("rag.preguntar") }}',
            method: 'POST',
            data: { pregunta, coleccion: coleccionActiva, _token: csrf },
            timeout: 10000,
            success: function (data) {
                if (!data.success) {
                    $(`#${loadingId}`).remove();
                    agregarMensaje('error', data.message || 'Error al consultar el RAG.');
                    $('#pregunta-input').prop('disabled', false).focus();
                    $('#btn-preguntar').prop('disabled', false);
                    return;
                }
                // Async: hacer polling hasta que el job complete
                iniciarPollingConsulta(loadingId, data.job_id);
            },
            error: function (xhr) {
                $(`#${loadingId}`).remove();
                const msg = xhr.responseJSON?.message || 'Error al conectar con el servidor IA.';
                agregarMensaje('error', msg);
                $('#pregunta-input').prop('disabled', false).focus();
                $('#btn-preguntar').prop('disabled', false);
            }
        });
    });

    function iniciarPollingConsulta(loadingId, jobId) {
        let intentos = 0;
        const MAX_POLL = 400; // ~20 min a 3s por intento

        const timer = setInterval(function () {
            intentos++;
            if (intentos > MAX_POLL) {
                clearInterval(timer);
                $(`#${loadingId}`).remove();
                agregarMensaje('error', 'El servidor IA tardó demasiado en responder.');
                $('#pregunta-input').prop('disabled', false).focus();
                $('#btn-preguntar').prop('disabled', false);
                return;
            }

            $.get(`/rag/consulta-estado/${jobId}`, function (data) {
                if (data.status === 'completed') {
                    clearInterval(timer);
                    $(`#${loadingId}`).remove();
                    agregarMensaje('assistant', data.respuesta || '(sin respuesta)');
                    $('#pregunta-input').prop('disabled', false).focus();
                    $('#btn-preguntar').prop('disabled', false);
                } else if (data.status === 'failed') {
                    clearInterval(timer);
                    $(`#${loadingId}`).remove();
                    agregarMensaje('error', data.error || 'El servidor IA no pudo responder.');
                    $('#pregunta-input').prop('disabled', false).focus();
                    $('#btn-preguntar').prop('disabled', false);
                }
                // pending/processing → seguir esperando
            }).fail(function () {
                clearInterval(timer);
                $(`#${loadingId}`).remove();
                agregarMensaje('error', 'Error al verificar el estado de la consulta.');
                $('#pregunta-input').prop('disabled', false).focus();
                $('#btn-preguntar').prop('disabled', false);
            });
        }, 3000);
    }

    function agregarMensaje(tipo, texto, id = null) {
        const historial = document.getElementById('chat-historial');
        const idAttr = id ? `id="${id}"` : '';
        let html = '';
        if (tipo === 'user') {
            html = `<div class="d-flex justify-content-end mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded text-white" style="max-width:75%;background:#6777ef;white-space:pre-wrap;">
                    ${escapeHtml(texto)}</div></div>`;
        } else if (tipo === 'assistant') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded chat-bubble-ia" style="max-width:90%;white-space:pre-wrap;font-size:.9rem;">
                    <small class="text-muted d-block mb-1"><i class="fas fa-brain mr-1"></i>${escapeHtml(nombreActivo)}</small>
                    ${escapeHtml(texto)}</div></div>`;
        } else if (tipo === 'loading') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded chat-bubble-loading">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i>Consultando...</div></div>`;
        } else if (tipo === 'error') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded chat-bubble-error" style="max-width:90%;">
                    <i class="fas fa-exclamation-circle mr-1"></i>${escapeHtml(texto)}</div></div>`;
        }
        historial.insertAdjacentHTML('beforeend', html);
        historial.scrollTop = historial.scrollHeight;
    }

    // ── Reintentar job fallido ───────────────────────────────────────────────
    $(document).on('click', '.btn-reintentar-job', function () {
        const btn       = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        const jobId     = $(this).data('job-id');
        const idx       = $(this).data('idx');
        const archivo   = $(this).data('archivo');

        $.post(`/rag/jobs/${jobId}/reintentar`, { _token: csrf })
            .done(function (data) {
                if (data.success) {
                    setBadge(idx, 'warning', '<i class="fas fa-circle-notch fa-spin mr-1"></i>Reintentando...');
                    iniciarPolling(idx, jobId, archivo);
                } else {
                    toastr.error(data.message || 'No se pudo reintentar.');
                    btn.prop('disabled', false).html('<i class="fas fa-redo-alt mr-1"></i>Reintentar');
                }
            })
            .fail(function () {
                toastr.error('Error al conectar con el servidor.');
                btn.prop('disabled', false).html('<i class="fas fa-redo-alt mr-1"></i>Reintentar');
            });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
@endsection
