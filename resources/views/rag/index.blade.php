@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Base de Conocimiento — Servidor IA</h3>
    </div>
    <div class="section-body">

        {{-- Estado de servicios --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Estado del servidor IA</h4>
                        <button id="btn-refresh-estado" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex gap-3" id="estado-servicios">
                            <span class="badge badge-secondary px-3 py-2">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Panel izquierdo: carga de documentos --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Cargar documento</h4>
                    </div>
                    <div class="card-body">
                        <div id="drop-doc" class="p-4 border rounded text-center mb-3"
                            style="border: 2px dashed #6777ef !important; cursor: pointer;">
                            <i class="fas fa-file-upload fa-2x mb-2 text-primary"></i>
                            <p class="mb-1">Arrastrá el archivo aquí</p>
                            <small class="text-muted">TXT, PDF, CSV, MD — máx. 20MB</small>
                            <input type="file" id="doc-file" accept=".txt,.pdf,.csv,.md" hidden>
                        </div>

                        <div id="doc-preview" class="alert alert-info py-2 d-none">
                            <i class="fas fa-file mr-1"></i>
                            <span id="doc-nombre"></span>
                        </div>

                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="chk-resumir" checked>
                                <label class="custom-control-label" for="chk-resumir">
                                    Generar resumen con IA (Ollama)
                                </label>
                            </div>
                        </div>

                        <button id="btn-cargar" class="btn btn-primary btn-block" disabled
                            style="background: linear-gradient(45deg,#6777ef,#35199a); border: none;">
                            <i class="fas fa-upload mr-2"></i>Cargar al RAG
                        </button>

                        {{-- Progreso --}}
                        <div id="carga-progress" class="mt-3 d-none">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    style="width: 100%; background-color: #6777ef;"></div>
                            </div>
                            <small class="text-muted d-block text-center mt-1" id="carga-status">Procesando...</small>
                        </div>

                        {{-- Resultado de carga --}}
                        <div id="carga-resultado" class="mt-3 d-none">
                            <div class="alert alert-success py-2 mb-2" id="carga-ok" style="display:none!important"></div>
                            <div class="alert alert-danger py-2 mb-2" id="carga-error" style="display:none!important"></div>
                            <div id="resumen-box" class="d-none">
                                <h6 class="mt-3">Resumen generado por IA:</h6>
                                <div id="resumen-texto" class="p-3 bg-light rounded"
                                    style="white-space: pre-wrap; font-size: 0.9rem; max-height: 300px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Re-indexar --}}
                <div class="card mt-2">
                    <div class="card-body py-2 d-flex align-items-center justify-content-between">
                        <small class="text-muted">Re-indexar todos los documentos del servidor</small>
                        <button id="btn-reindexar" class="btn btn-sm btn-outline-warning ml-2">
                            <i class="fas fa-database mr-1"></i>Re-indexar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Panel derecho: consulta --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h4>Consultar base de conocimiento</h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        {{-- Historial de chat --}}
                        <div id="chat-historial" class="flex-grow-1 mb-3 p-3 bg-light rounded"
                            style="min-height: 350px; max-height: 500px; overflow-y: auto;">
                            <div class="text-center text-muted mt-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Hacé una pregunta sobre los documentos cargados en el RAG.</p>
                            </div>
                        </div>

                        {{-- Input --}}
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
    </div>
</section>

<script>
$(document).ready(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ── Estado de servicios ──────────────────────────────────────────────
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

    // ── Carga de documentos ──────────────────────────────────────────────
    const dropDoc  = document.getElementById('drop-doc');
    const docFile  = document.getElementById('doc-file');
    let archivoSeleccionado = null;

    dropDoc.addEventListener('click', () => docFile.click());

    dropDoc.addEventListener('dragover', e => { e.preventDefault(); dropDoc.classList.add('bg-light'); });
    dropDoc.addEventListener('dragleave', () => dropDoc.classList.remove('bg-light'));
    dropDoc.addEventListener('drop', e => {
        e.preventDefault();
        dropDoc.classList.remove('bg-light');
        if (e.dataTransfer.files.length) seleccionarArchivo(e.dataTransfer.files[0]);
    });

    docFile.addEventListener('change', function () {
        if (this.files.length) seleccionarArchivo(this.files[0]);
    });

    function seleccionarArchivo(file) {
        const formatos = ['text/plain', 'application/pdf', 'text/csv', 'text/markdown'];
        const ext = file.name.split('.').pop().toLowerCase();
        const extOk = ['txt', 'pdf', 'csv', 'md'].includes(ext);

        if (!extOk) {
            mostrarCargaError('Formato no soportado. Usá TXT, PDF, CSV o MD.');
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            mostrarCargaError('El archivo supera los 20MB.');
            return;
        }

        archivoSeleccionado = file;
        $('#doc-nombre').text(file.name);
        $('#doc-preview').removeClass('d-none');
        $('#btn-cargar').prop('disabled', false);
        $('#carga-resultado').addClass('d-none');
        $('#resumen-box').addClass('d-none');
    }

    $('#btn-cargar').on('click', function () {
        if (!archivoSeleccionado) return;

        const formData = new FormData();
        formData.append('documento', archivoSeleccionado);
        formData.append('resumir', $('#chk-resumir').is(':checked') ? '1' : '0');
        formData.append('_token', csrfToken);

        $('#carga-progress').removeClass('d-none');
        $('#carga-status').text('Enviando archivo...');
        $('#btn-cargar').prop('disabled', true);
        $('#carga-resultado').addClass('d-none');

        if ($('#chk-resumir').is(':checked')) {
            setTimeout(() => $('#carga-status').text('Generando resumen con Ollama... (puede tardar)'), 3000);
        }

        $.ajax({
            url: '{{ route("rag.cargar") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 200000,
            success: function (data) {
                $('#carga-progress').addClass('d-none');
                $('#carga-resultado').removeClass('d-none');

                if (data.success) {
                    $('#carga-ok')
                        .html(`<i class="fas fa-check-circle mr-1"></i>
                            <strong>${data.archivo}</strong> indexado correctamente.
                            <span class="ml-2 badge badge-info">${data.documentos_total} docs en RAG</span>`)
                        .show();
                    $('#carga-error').hide();

                    if (data.resumen) {
                        $('#resumen-texto').text(data.resumen);
                        $('#resumen-box').removeClass('d-none');
                    }
                } else {
                    mostrarCargaError(data.message || 'Error desconocido');
                }

                archivoSeleccionado = null;
                docFile.value = '';
                $('#doc-preview').addClass('d-none');
                cargarEstado();
            },
            error: function (xhr) {
                $('#carga-progress').addClass('d-none');
                const msg = xhr.responseJSON?.message || 'Error al conectar con el servidor.';
                mostrarCargaError(msg);
                $('#btn-cargar').prop('disabled', false);
            }
        });
    });

    function mostrarCargaError(msg) {
        $('#carga-resultado').removeClass('d-none');
        $('#carga-error').html('<i class="fas fa-exclamation-circle mr-1"></i>' + msg).show();
        $('#carga-ok').hide();
    }

    // ── Re-indexar ───────────────────────────────────────────────────────
    $('#btn-reindexar').on('click', function () {
        if (!confirm('¿Re-indexar todos los documentos del servidor?')) return;
        const btn = $(this).prop('disabled', true).text('Indexando...');

        $.post('{{ route("rag.reindexar") }}', { _token: csrfToken }, function (data) {
            if (data.success) {
                toastr.success(`Re-indexado: ${data.documentos} documentos`);
                cargarEstado();
            } else {
                toastr.error(data.message || 'Error al re-indexar');
            }
        }).fail(() => toastr.error('No se pudo conectar al servidor IA'))
          .always(() => btn.prop('disabled', false).html('<i class="fas fa-database mr-1"></i>Re-indexar'));
    });

    // ── Chat / Consulta ──────────────────────────────────────────────────
    $('#pregunta-input').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn-preguntar').trigger('click');
    });

    $('#btn-preguntar').on('click', function () {
        const pregunta = $('#pregunta-input').val().trim();
        if (!pregunta) return;

        agregarMensaje('user', pregunta);
        $('#pregunta-input').val('').prop('disabled', true);
        $('#btn-preguntar').prop('disabled', true);

        const loadingId = 'loading-' + Date.now();
        agregarMensaje('loading', '...', loadingId);

        $.ajax({
            url: '{{ route("rag.preguntar") }}',
            method: 'POST',
            data: { pregunta, _token: csrfToken },
            timeout: 70000,
            success: function (data) {
                $('#' + loadingId).remove();
                if (data.success) {
                    agregarMensaje('assistant', data.respuesta);
                } else {
                    agregarMensaje('error', data.message || 'Error al consultar el RAG.');
                }
            },
            error: function () {
                $('#' + loadingId).remove();
                agregarMensaje('error', 'No se pudo conectar con el servidor IA.');
            },
            complete: function () {
                $('#pregunta-input').prop('disabled', false).focus();
                $('#btn-preguntar').prop('disabled', false);
            }
        });
    });

    function agregarMensaje(tipo, texto, id = null) {
        const historial = $('#chat-historial');

        // Limpiar placeholder inicial
        historial.find('.text-center').remove();

        let html = '';
        const idAttr = id ? `id="${id}"` : '';

        if (tipo === 'user') {
            html = `<div class="d-flex justify-content-end mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded text-white" style="max-width:75%; background:#6777ef; white-space:pre-wrap;">
                    ${escapeHtml(texto)}
                </div>
            </div>`;
        } else if (tipo === 'assistant') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded bg-white border" style="max-width:85%; white-space:pre-wrap; font-size:0.9rem;">
                    <small class="text-muted d-block mb-1"><i class="fas fa-brain mr-1"></i>Servidor IA</small>
                    ${escapeHtml(texto)}
                </div>
            </div>`;
        } else if (tipo === 'loading') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded bg-white border text-muted">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i>Consultando...
                </div>
            </div>`;
        } else if (tipo === 'error') {
            html = `<div class="d-flex justify-content-start mb-2" ${idAttr}>
                <div class="p-2 px-3 rounded bg-white border text-danger" style="max-width:85%;">
                    <i class="fas fa-exclamation-circle mr-1"></i>${escapeHtml(texto)}
                </div>
            </div>`;
        }

        historial.append(html);
        historial.scrollTop(historial[0].scrollHeight);
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
</script>
@endsection
