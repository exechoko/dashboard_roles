@extends('layouts.app')

@section('content')
<div class="section">
    <div class="section-header">
        <h1>Chat</h1>
    </div>

    <div class="section-body">
        <div class="card chat-app">
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h5 class="mb-0">Conversaciones</h5>
                    <button type="button" id="chat-nuevo-btn" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
                <div id="chat-lista-conversaciones" class="chat-lista-conversaciones">
                    <div class="chat-lista-vacia text-muted">Todavía no tenés conversaciones.</div>
                </div>
            </div>

            <div class="chat-panel">
                <div id="chat-panel-vacio" class="chat-panel-vacio">
                    <i class="fas fa-comments"></i>
                    <p>Elegí una conversación o iniciá una nueva.</p>
                </div>

                <div id="chat-panel-activo" class="chat-panel-activo" style="display:none;">
                    <div class="chat-panel-header">
                        <strong id="chat-panel-titulo"></strong>
                        <small id="chat-panel-escribiendo" class="text-muted"></small>
                    </div>

                    <div id="chat-mensajes" class="chat-mensajes"></div>

                    <form id="chat-form-envio" class="chat-composer">
                        <div id="chat-adjuntos-preview" class="chat-adjuntos-preview"></div>
                        <div class="chat-composer-row">
                            <label for="chat-input-adjuntos" class="chat-adjuntar-btn" title="Adjuntar archivo">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="chat-input-adjuntos" multiple hidden
                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <textarea id="chat-input-cuerpo" class="form-control" rows="1" maxlength="4000"
                                placeholder="Escribí un mensaje..."></textarea>
                            <button type="submit" id="chat-enviar-btn" class="chat-enviar-btn" aria-label="Enviar">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chat-modal-nuevo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva conversación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group" id="chat-nombre-grupo-wrapper" style="display:none;">
                    <label>Nombre del grupo</label>
                    <input type="text" id="chat-input-nombre-grupo" class="form-control" maxlength="120">
                </div>
                <div class="form-group mb-0">
                    <label>Destinatarios</label>
                    <div id="chat-lista-contactos" class="chat-lista-contactos"></div>
                    <small class="text-muted">Elegí un usuario para un chat privado, o dos o más para un grupo.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="chat-iniciar-btn" class="btn btn-primary">Iniciar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .chat-app {
        display: flex;
        height: calc(100vh - 220px);
        min-height: 480px;
        overflow: hidden;
    }

    .chat-sidebar {
        display: flex;
        flex-direction: column;
        width: 320px;
        flex-shrink: 0;
        border-right: 1px solid var(--border-color, #dce3e8);
    }

    .chat-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color, #dce3e8);
    }

    .chat-lista-conversaciones {
        flex: 1;
        overflow-y: auto;
    }

    .chat-lista-vacia {
        padding: 20px 16px;
        font-size: .85rem;
    }

    .chat-conversacion-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-color, #eef1f4);
    }

    .chat-conversacion-item:hover {
        background: var(--body-bg, #f5f7fa);
    }

    .chat-conversacion-item.activa {
        background: var(--body-bg, #f5f7fa);
        box-shadow: inset 3px 0 0 #176b87;
    }

    .chat-conversacion-nombre {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        color: var(--text-primary, #263238);
    }

    .chat-conversacion-preview {
        font-size: .8rem;
        color: var(--text-secondary, #6c757d);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .chat-punto-online {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin-right: 6px;
        border-radius: 50%;
        background: #28a745;
        box-shadow: 0 0 0 2px var(--card-bg, #fff);
    }

    #chat-panel-escribiendo {
        color: #28a745;
    }

    .chat-conversacion-badge {
        min-width: 20px;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: .7rem;
        text-align: center;
        color: #fff;
        background: #dc3545;
    }

    .chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .chat-panel-vacio {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary, #6c757d);
    }

    .chat-panel-vacio i {
        font-size: 40px;
        margin-bottom: 10px;
        color: #176b87;
    }

    .chat-panel-activo {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .chat-panel-header {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color, #dce3e8);
    }

    .chat-mensajes {
        flex: 1;
        overflow-y: auto;
        padding: 18px;
        background: var(--body-bg, #f5f7fa);
    }

    .chat-mensaje {
        display: flex;
        margin-bottom: 12px;
    }

    .chat-mensaje.propio {
        justify-content: flex-end;
    }

    .chat-mensaje-bloque {
        max-width: 70%;
    }

    .chat-mensaje-autor {
        margin-bottom: 2px;
        font-size: .72rem;
        color: var(--text-secondary, #6c757d);
    }

    .chat-mensaje-bubble {
        padding: 10px 13px;
        border-radius: 14px;
        font-size: .9rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .chat-mensaje.propio .chat-mensaje-bubble {
        border-bottom-right-radius: 4px;
        color: #fff;
        background: #176b87;
    }

    .chat-mensaje:not(.propio) .chat-mensaje-bubble {
        border: 1px solid var(--border-color, #dce3e8);
        border-bottom-left-radius: 4px;
        color: var(--text-primary, #263238);
        background: var(--card-bg, #fff);
    }

    .chat-mensaje-adjunto {
        display: block;
        margin-top: 6px;
        font-size: .82rem;
        text-decoration: underline;
    }

    .chat-mensaje.propio .chat-mensaje-adjunto {
        color: #eaf6fb;
    }

    .chat-mensaje-estado {
        margin-top: 3px;
        text-align: right;
        font-size: .7rem;
        color: var(--text-secondary, #6c757d);
    }

    .chat-composer {
        border-top: 1px solid var(--border-color, #dce3e8);
        background: var(--card-bg, #fff);
        padding: 10px 14px;
    }

    .chat-composer-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    .chat-adjuntar-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin: 0;
        border-radius: 10px;
        color: var(--text-secondary, #6c757d);
        cursor: pointer;
    }

    .chat-adjuntar-btn:hover {
        background: var(--body-bg, #f5f7fa);
    }

    .chat-composer textarea {
        min-height: 40px;
        max-height: 105px;
        resize: none;
        border-radius: 12px;
    }

    .chat-enviar-btn {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: #176b87;
    }

    .chat-adjuntos-preview:not(:empty) {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .chat-adjuntos-preview .badge {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .chat-lista-contactos {
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid var(--border-color, #dce3e8);
        border-radius: 8px;
        padding: 8px 12px;
    }

    @media (max-width: 767.98px) {
        .chat-app {
            flex-direction: column;
            height: auto;
        }

        .chat-sidebar {
            width: 100%;
            max-height: 260px;
            border-right: 0;
            border-bottom: 1px solid var(--border-color, #dce3e8);
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const urls = {
        sync: @json(route('chat.sync')),
        contactos: @json(route('chat.contactos')),
        iniciar: @json(route('chat.conversaciones.store')),
        conversacion: @json(route('chat.conversaciones.show', ['conversacion' => '__ID__'])),
        enviar: @json(route('chat.mensajes.store', ['conversacion' => '__ID__'])),
        leido: @json(route('chat.conversaciones.leido', ['conversacion' => '__ID__'])),
        escribiendo: @json(route('chat.conversaciones.escribiendo', ['conversacion' => '__ID__'])),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const usuarioActualId = {{ (int) auth()->id() }};

    const lista = $('#chat-lista-conversaciones');
    const panelVacio = $('#chat-panel-vacio');
    const panelActivo = $('#chat-panel-activo');
    const panelTitulo = $('#chat-panel-titulo');
    const panelEscribiendo = $('#chat-panel-escribiendo');
    const mensajesEl = $('#chat-mensajes');
    const inputCuerpo = $('#chat-input-cuerpo');
    const inputAdjuntos = $('#chat-input-adjuntos');
    const adjuntosPreview = $('#chat-adjuntos-preview');
    const badgeNavbar = $('#chat-nav-badge');

    let conversaciones = [];
    let conversacionActivaId = null;
    let ultimoMensajeId = 0;
    let lecturas = {};
    let ultimoAviso = 0;

    /**
     * Costura de transporte: todo el polling vive acá. El día que se pueda migrar a
     * WebSocket (Reverb, tras subir a PHP 8.2), sólo hay que reescribir este objeto —
     * el resto de la interfaz sólo consume el callback de `iniciar`.
     */
    const ChatTransport = (function () {
        let timer = null;
        let callback = null;

        function intervaloMs() {
            if (document.hidden) return 20000;
            return conversacionActivaId ? 3000 : 8000;
        }

        function tick() {
            const params = {};
            if (conversacionActivaId) {
                params.conversacion = conversacionActivaId;
                params.desde = ultimoMensajeId;
            }
            $.get(urls.sync, params).done(function (respuesta) {
                if (callback) callback(respuesta);
            });
        }

        function reprogramar() {
            if (timer) clearInterval(timer);
            timer = setInterval(tick, intervaloMs());
        }

        return {
            iniciar(alRecibir) {
                callback = alRecibir;
                reprogramar();
                document.addEventListener('visibilitychange', function () {
                    reprogramar();
                    if (!document.hidden) tick();
                });
            },
            forzar: tick,
            reprogramar,
        };
    })();

    let conversacionInicial = new URLSearchParams(window.location.search).get('conversacion');

    cargarContactos();
    ChatTransport.iniciar(alRecibirSync);
    ChatTransport.forzar();

    $('#chat-nuevo-btn').on('click', function () {
        cargarContactos();
        $('#chat-modal-nuevo').modal('show');
    });

    $('#chat-lista-contactos').on('change', 'input[type=checkbox]', function () {
        const seleccionados = $('#chat-lista-contactos input:checked').length;
        $('#chat-nombre-grupo-wrapper').toggle(seleccionados >= 2);
    });

    $('#chat-iniciar-btn').on('click', iniciarConversacion);

    $('#chat-form-envio').on('submit', enviarMensaje);

    inputCuerpo.on('input', function () {
        autoAltura(this);
        avisarEscribiendo();
    });

    inputCuerpo.on('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            $('#chat-form-envio').trigger('submit');
        }
    });

    inputAdjuntos.on('change', function () {
        adjuntosPreview.empty();
        Array.from(this.files).forEach(function (archivo, indice) {
            adjuntosPreview.append(
                '<span class="badge badge-light border">' + escapeHtml(archivo.name) +
                ' <a href="#" class="text-danger chat-quitar-adjunto" data-indice="' + indice + '">&times;</a></span>'
            );
        });
    });

    function cargarContactos() {
        $.get(urls.contactos).done(function (respuesta) {
            const contenedor = $('#chat-lista-contactos').empty();
            (respuesta.usuarios || []).forEach(function (usuario) {
                const punto = usuario.en_linea ? '<span class="chat-punto-online" title="En línea"></span>' : '';
                contenedor.append(
                    '<div class="custom-control custom-checkbox">' +
                    '<input type="checkbox" class="custom-control-input" id="chat-contacto-' + usuario.id + '" value="' + usuario.id + '">' +
                    '<label class="custom-control-label" for="chat-contacto-' + usuario.id + '">' + punto + escapeHtml(usuario.nombre) + '</label>' +
                    '</div>'
                );
            });
        });
    }

    function iniciarConversacion() {
        const seleccionados = $('#chat-lista-contactos input:checked').map(function () {
            return $(this).val();
        }).get();

        if (!seleccionados.length) {
            avisar('warning', 'Elegí al menos un destinatario.');
            return;
        }

        const tipo = seleccionados.length === 1 ? 'privada' : 'grupo';
        const nombre = $('#chat-input-nombre-grupo').val();

        if (tipo === 'grupo' && !nombre.trim()) {
            avisar('warning', 'Ingresá un nombre para el grupo.');
            return;
        }

        $.ajax({
            url: urls.iniciar,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { tipo: tipo, nombre: nombre, usuarios: seleccionados },
        }).done(function (respuesta) {
            $('#chat-modal-nuevo').modal('hide');
            $('#chat-lista-contactos input:checked').prop('checked', false);
            $('#chat-input-nombre-grupo').val('');
            $('#chat-nombre-grupo-wrapper').hide();

            upsertConversacion(respuesta.conversacion);
            abrirConversacion(respuesta.conversacion.id);
        }).fail(function (xhr) {
            avisar('error', xhr.responseJSON?.message || 'No se pudo iniciar la conversación.');
        });
    }

    function abrirConversacion(id) {
        conversacionActivaId = id;
        ultimoMensajeId = 0;
        lecturas = {};
        mensajesEl.empty();
        panelVacio.hide();
        panelActivo.show();
        renderizarListaConversaciones();

        const conversacion = conversaciones.find(function (c) { return c.id === id; });
        panelTitulo.text(conversacion ? conversacion.nombre : '');
        mostrarEstadoConexion();

        $.get(urls.conversacion.replace('__ID__', id)).done(function (respuesta) {
            lecturas = respuesta.lecturas || {};
            (respuesta.mensajes || []).forEach(agregarMensaje);
            desplazarAlFinal();
            actualizarEstadoLectura();
            marcarLeido(id);
        });

        ChatTransport.reprogramar();
        ChatTransport.forzar();
    }

    function alRecibirSync(respuesta) {
        conversaciones = respuesta.conversaciones || [];
        renderizarListaConversaciones();
        actualizarBadgeNavbar(respuesta.no_leidos_total || 0);

        if (window.ChatNotificador) {
            window.ChatNotificador.procesar(respuesta.conversaciones);
        }

        if (conversacionInicial) {
            const idInicial = parseInt(conversacionInicial, 10);
            conversacionInicial = null;
            if (conversaciones.some(function (c) { return c.id === idInicial; })) {
                abrirConversacion(idInicial);
                return;
            }
        }

        if (!conversacionActivaId) return;

        if (respuesta.lecturas) {
            lecturas = respuesta.lecturas;
        }

        let llegoAjeno = false;
        (respuesta.mensajes || []).forEach(function (mensaje) {
            agregarMensaje(mensaje);
            if (mensaje.usuario_id !== usuarioActualId) llegoAjeno = true;
        });

        if (respuesta.mensajes && respuesta.mensajes.length) {
            desplazarAlFinal();
        }

        actualizarEstadoLectura();
        mostrarEscribiendo(respuesta.escribiendo || []);

        if (llegoAjeno && !document.hidden) {
            marcarLeido(conversacionActivaId);
        }
    }

    function agregarMensaje(mensaje) {
        if (mensaje.id <= ultimoMensajeId) return;
        ultimoMensajeId = mensaje.id;

        const propio = mensaje.usuario_id === usuarioActualId;
        const adjuntosHtml = (mensaje.adjuntos || []).map(function (adjunto) {
            return '<a class="chat-mensaje-adjunto" href="' + adjunto.url + '" target="_blank" rel="noopener">' +
                '<i class="fas fa-paperclip"></i> ' + escapeHtml(adjunto.nombre) + '</a>';
        }).join('');

        const cuerpoHtml = mensaje.cuerpo ? '<div>' + escapeHtml(mensaje.cuerpo).replace(/\n/g, '<br>') + '</div>' : '';

        const html =
            '<div class="chat-mensaje ' + (propio ? 'propio' : '') + '" data-id="' + mensaje.id + '">' +
            '<div class="chat-mensaje-bloque">' +
            (propio ? '' : '<div class="chat-mensaje-autor">' + escapeHtml(mensaje.usuario) + '</div>') +
            '<div class="chat-mensaje-bubble">' + cuerpoHtml + adjuntosHtml + '</div>' +
            (propio ? '<div class="chat-mensaje-estado"></div>' : '') +
            '</div>' +
            '</div>';

        mensajesEl.append(html);
    }

    function actualizarEstadoLectura() {
        const propios = mensajesEl.find('.chat-mensaje.propio');
        if (!propios.length) return;

        const ultimoPropio = propios.last();
        const id = parseInt(ultimoPropio.attr('data-id'), 10);
        const valores = Object.values(lecturas || {}).map(function (v) { return v || 0; });
        const vistoPorTodos = valores.length > 0 && valores.every(function (v) { return v >= id; });

        propios.find('.chat-mensaje-estado').text('');
        ultimoPropio.find('.chat-mensaje-estado').text(vistoPorTodos ? 'Visto' : 'Enviado');
    }

    function mostrarEscribiendo(idsEscribiendo) {
        if (idsEscribiendo.length) {
            panelEscribiendo.text('Escribiendo...');
            return;
        }
        mostrarEstadoConexion();
    }

    function mostrarEstadoConexion() {
        const conversacion = conversaciones.find(function (c) { return c.id === conversacionActivaId; });
        if (!conversacion) {
            panelEscribiendo.text('');
            return;
        }

        if (conversacion.tipo === 'grupo') {
            panelEscribiendo.text(conversacion.en_linea_count > 0 ? conversacion.en_linea_count + ' en línea' : '');
        } else {
            panelEscribiendo.text(conversacion.en_linea ? 'En línea' : '');
        }
    }

    function avisarEscribiendo() {
        if (!conversacionActivaId) return;
        const ahora = Date.now();
        if (ahora - ultimoAviso < 3000) return;
        ultimoAviso = ahora;

        $.ajax({
            url: urls.escribiendo.replace('__ID__', conversacionActivaId),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
    }

    function marcarLeido(id) {
        $.ajax({
            url: urls.leido.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
    }

    function enviarMensaje(event) {
        event.preventDefault();
        if (!conversacionActivaId) return;

        const cuerpo = inputCuerpo.val().trim();
        const archivos = inputAdjuntos[0].files;
        if (!cuerpo && !archivos.length) return;

        const formData = new FormData();
        if (cuerpo) formData.append('cuerpo', cuerpo);
        Array.from(archivos).forEach(function (archivo) {
            formData.append('adjuntos[]', archivo);
        });

        $('#chat-enviar-btn').prop('disabled', true);

        $.ajax({
            url: urls.enviar.replace('__ID__', conversacionActivaId),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: formData,
            processData: false,
            contentType: false,
        }).done(function (respuesta) {
            inputCuerpo.val('');
            autoAltura(inputCuerpo[0]);
            inputAdjuntos.val('');
            adjuntosPreview.empty();
            agregarMensaje(respuesta.mensaje);
            desplazarAlFinal();
            actualizarEstadoLectura();
            ChatTransport.forzar();
        }).fail(function (xhr) {
            avisar('error', xhr.responseJSON?.message || 'No se pudo enviar el mensaje.');
        }).always(function () {
            $('#chat-enviar-btn').prop('disabled', false);
        });
    }

    function upsertConversacion(conversacion) {
        const indice = conversaciones.findIndex(function (c) { return c.id === conversacion.id; });
        if (indice >= 0) {
            conversaciones[indice] = conversacion;
        } else {
            conversaciones.unshift(conversacion);
        }
        renderizarListaConversaciones();
    }

    function renderizarListaConversaciones() {
        if (!conversaciones.length) {
            lista.html('<div class="chat-lista-vacia text-muted">Todavía no tenés conversaciones.</div>');
            return;
        }

        lista.empty();
        conversaciones.forEach(function (conversacion) {
            const activa = conversacion.id === conversacionActivaId;
            const badge = conversacion.no_leidos > 0
                ? '<span class="chat-conversacion-badge">' + (conversacion.no_leidos > 99 ? '99+' : conversacion.no_leidos) + '</span>'
                : '';
            const punto = conversacion.tipo === 'privada' && conversacion.en_linea
                ? '<span class="chat-punto-online" title="En línea"></span>'
                : '';

            const item = $(
                '<div class="chat-conversacion-item ' + (activa ? 'activa' : '') + '" data-id="' + conversacion.id + '">' +
                '<div class="chat-conversacion-nombre">' +
                '<span>' + punto + escapeHtml(conversacion.nombre) + '</span>' + badge +
                '</div>' +
                '<div class="chat-conversacion-preview">' + escapeHtml(conversacion.ultimo_mensaje || 'Sin mensajes todavía') + '</div>' +
                '</div>'
            );
            item.on('click', function () { abrirConversacion(conversacion.id); });
            lista.append(item);
        });
    }

    function actualizarBadgeNavbar(total) {
        if (!badgeNavbar.length) return;
        if (total > 0) {
            badgeNavbar.text(total > 99 ? '99+' : total).show();
        } else {
            badgeNavbar.hide();
        }
    }

    function desplazarAlFinal() {
        mensajesEl.scrollTop(mensajesEl[0].scrollHeight);
    }

    function autoAltura(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 105) + 'px';
    }

    function avisar(tipo, mensaje) {
        iziToast[tipo]({ title: tipo === 'error' ? 'Error' : 'Chat', message: mensaje, position: 'topRight', timeout: 3500 });
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
</script>
@endpush
