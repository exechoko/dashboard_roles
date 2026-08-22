<style>
    .car-chatbot-launcher {
        position: fixed;
        right: 94px;
        bottom: 30px;
        z-index: 1040;
        width: 54px;
        height: 54px;
        border: 0;
        border-radius: 18px;
        color: #fff;
        background: linear-gradient(145deg, #176b87, #0b3142);
        box-shadow: 0 10px 28px rgba(5, 42, 58, .32);
        cursor: pointer;
    }

    .car-chatbot-launcher:hover {
        transform: translateY(-2px);
    }

    .car-chatbot-panel {
        position: fixed;
        right: 30px;
        bottom: 98px;
        z-index: 1041;
        width: 390px;
        height: min(610px, calc(100vh - 130px));
        display: none;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--border-color, #dce3e8);
        border-radius: 18px;
        color: var(--text-primary, #263238);
        background: var(--card-bg, #fff);
        box-shadow: 0 22px 55px rgba(10, 35, 47, .28);
    }

    .car-chatbot-panel.is-open {
        display: flex;
    }

    .car-chatbot-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 17px;
        color: #fff;
        background: linear-gradient(110deg, #0b3142, #176b87);
    }

    .car-chatbot-header-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
    }

    .car-chatbot-header small {
        display: block;
        color: rgba(255, 255, 255, .72);
        font-weight: 400;
    }

    .car-chatbot-header button {
        border: 0;
        color: rgba(255, 255, 255, .82);
        background: transparent;
        cursor: pointer;
    }

    .car-chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 17px;
        background:
            radial-gradient(circle at top right, rgba(23, 107, 135, .08), transparent 35%),
            var(--body-bg, #f5f7fa);
    }

    .car-chatbot-empty {
        margin: 48px 18px 0;
        text-align: center;
        color: var(--text-secondary, #6c757d);
    }

    .car-chatbot-empty i {
        margin-bottom: 12px;
        color: #176b87;
        font-size: 32px;
    }

    .car-chatbot-message {
        display: flex;
        margin-bottom: 12px;
    }

    .car-chatbot-message.user {
        justify-content: flex-end;
    }

    .car-chatbot-bubble {
        max-width: 88%;
        padding: 10px 13px;
        border-radius: 14px;
        font-size: .9rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .car-chatbot-message.user .car-chatbot-bubble {
        border-bottom-right-radius: 4px;
        color: #fff;
        background: #176b87;
    }

    .car-chatbot-message.assistant .car-chatbot-bubble {
        border: 1px solid var(--border-color, #dce3e8);
        border-bottom-left-radius: 4px;
        color: var(--text-primary, #263238);
        background: var(--card-bg, #fff);
    }

    .car-chatbot-message.error .car-chatbot-bubble {
        border-color: #e8a5aa;
        color: #b32631;
    }

    .car-chatbot-bubble a {
        color: #1686aa;
        font-weight: 600;
        text-decoration: underline;
    }

    .car-chatbot-composer {
        padding: 12px;
        border-top: 1px solid var(--border-color, #dce3e8);
        background: var(--card-bg, #fff);
    }

    .car-chatbot-composer-row {
        display: flex;
        gap: 8px;
    }

    .car-chatbot-composer textarea {
        min-height: 44px;
        max-height: 105px;
        resize: none;
        border-radius: 12px;
    }

    .car-chatbot-send {
        width: 44px;
        border: 0;
        border-radius: 12px;
        color: #fff;
        background: #176b87;
    }

    .car-chatbot-send:disabled {
        opacity: .55;
    }

    .car-chatbot-hint {
        display: block;
        margin-top: 6px;
        color: var(--text-secondary, #6c757d);
        font-size: .72rem;
    }

    @media (max-width: 575.98px) {
        .car-chatbot-panel {
            right: 10px;
            bottom: 92px;
            width: calc(100vw - 20px);
            height: calc(100vh - 112px);
        }

        .car-chatbot-launcher {
            right: 90px;
            bottom: 28px;
        }
    }
</style>

<button type="button" id="car-chatbot-launcher" class="car-chatbot-launcher" aria-label="Abrir asistente" aria-expanded="false">
    <i class="fas fa-comment-dots fa-lg"></i>
</button>

<section id="car-chatbot-panel" class="car-chatbot-panel" aria-label="Asistente de C.A.R. 911">
    <header class="car-chatbot-header">
        <div class="car-chatbot-header-title">
            <i class="fas fa-headset"></i>
            <div>Asistente C.A.R. 911<small>Guía de uso del sistema</small></div>
        </div>
        <div>
            <button type="button" id="car-chatbot-clear" title="Nueva conversación"><i class="fas fa-trash-alt"></i></button>
            <button type="button" id="car-chatbot-close" title="Cerrar"><i class="fas fa-times"></i></button>
        </div>
    </header>

    <div id="car-chatbot-messages" class="car-chatbot-messages">
        <div class="car-chatbot-empty">
            <i class="fas fa-compass"></i>
            <p>Consultá cómo realizar una tarea, dónde encontrar una función o pedime datos del sistema: cuántos equipos hay operativos, cuántas cámaras hay en una localidad, los movimientos de un equipo.</p>
        </div>
    </div>

    <form id="car-chatbot-form" class="car-chatbot-composer">
        <div class="car-chatbot-composer-row">
            <textarea id="car-chatbot-question" class="form-control" maxlength="1000" rows="1"
                placeholder="¿Cómo puedo ayudarte?"></textarea>
            <button type="submit" id="car-chatbot-send" class="car-chatbot-send" aria-label="Enviar consulta">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <small class="car-chatbot-hint">Enter para enviar. No ingreses datos personales ni contraseñas.</small>
    </form>
</section>

<script>
$(function () {
    const urls = {
        history: @json(route('chatbot.history')),
        ask: @json(route('chatbot.ask')),
        status: @json(route('chatbot.status', ['message' => '__ID__'])),
        clear: @json(route('chatbot.clear')),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const safePaths = [
        '/home', '/equipos', '/equipos/estadisticas', '/flota', '/busqueda-avanzada',
        '/vehiculos', '/recursos', '/terminales',
        '/camaras', '/tipo-camara', '/camaras_fisicas', '/sitios', '/dependencias',
        '/bodycams', '/mapa',
        '/entrega-equipos', '/entrega-bodycams', '/entrega-combustible',
        '/patrimonio/dashboard', '/patrimonio/cargos', '/patrimonio/bienes',
        '/patrimonio/tipos-bien',
        '/tareas', '/tareas/personal-efectivo', '/tareas/activaciones-totem',
        '/usuarios', '/roles', '/auditoria', '/constancias-credenciales', '/chat',
        '/manuales/instructivos', '/manuales/cecoco',
        '/cecoco', '/cecoco/analitica', '/cecoco/mapa-calor', '/cecoco/historico-movil',
        '/cecoco/historico-movil-gis', '/cecoco/recursos-alias', '/cecoco/mapa-gis',
        '/cecoco/mapa-gis-historico', '/indexMapaCecocoEnVivo', '/get-eventos',
        '/transcribir', '/rag', '/armas/retenciones', '/armas/personal',
        '/armas/motivos', '/armas/tipos', '/armas/armeria/armas', '/armas/armeria/chalecos',
        '/incidencias/periodos', '/incidencias/tickets-pg',
        '/web-admin/contadores', '/web-admin/textos', '/noticias',
        '/herramientas/hash-archivo', '/herramientas/mails', '/herramientas/mails/buzones',
        '/plano-edificio', '/password-vault'
    ];
    const panel = $('#car-chatbot-panel');
    const launcher = $('#car-chatbot-launcher');
    const messages = $('#car-chatbot-messages');
    const question = $('#car-chatbot-question');
    const send = $('#car-chatbot-send');
    let busy = false;

    launcher.on('click', function () {
        panel.toggleClass('is-open');
        launcher.attr('aria-expanded', panel.hasClass('is-open') ? 'true' : 'false');
        if (panel.hasClass('is-open')) question.trigger('focus');
    });

    $('#car-chatbot-close').on('click', function () {
        panel.removeClass('is-open');
        launcher.attr('aria-expanded', 'false');
    });

    $('#car-chatbot-clear').on('click', function () {
        if (!confirm('¿Iniciar una conversación nueva?')) return;

        $.ajax({
            url: urls.clear,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function () {
            messages.html(emptyState());
            setBusy(false);
        }).fail(showRequestError);
    });

    question.on('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            $('#car-chatbot-form').trigger('submit');
        }
    });

    $('#car-chatbot-form').on('submit', function (event) {
        event.preventDefault();
        const text = question.val().trim();
        if (!text || busy) return;

        removeEmptyState();
        appendMessage({ role: 'user', content: text, status: 'completed' });
        question.val('');
        setBusy(true);

        $.ajax({
            url: urls.ask,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { question: text, context_path: window.location.pathname },
        }).done(function (response) {
            appendMessage(response.message);
            poll(response.message.id);
        }).fail(function (xhr) {
            appendMessage({ role: 'assistant', status: 'failed', error: errorText(xhr) });
            setBusy(false);
        });
    });

    function loadHistory() {
        $.get(urls.history).done(function (response) {
            const history = response.messages || [];
            if (!history.length) return;

            messages.empty();
            history.forEach(appendMessage);
            const pending = history.find(item => item.role === 'assistant' && ['pending', 'processing'].includes(item.status));
            if (pending) {
                setBusy(true);
                poll(pending.id);
            }
        });
    }

    function poll(messageId) {
        let attempts = 0;
        const loading = $('#car-chatbot-message-' + messageId);
        const timer = setInterval(function () {
            attempts++;
            $.get(urls.status.replace('__ID__', messageId)).done(function (response) {
                const item = response.message;
                if (item.status === 'completed' || item.status === 'failed') {
                    clearInterval(timer);
                    loading.replaceWith(messageHtml(item));
                    scrollToBottom();
                    setBusy(false);
                    question.trigger('focus');
                }
            }).fail(function () {
                clearInterval(timer);
                loading.replaceWith(messageHtml({ role: 'assistant', status: 'failed', error: 'No se pudo consultar el estado de la respuesta.' }));
                setBusy(false);
            });

            if (attempts >= 120) {
                clearInterval(timer);
                loading.replaceWith(messageHtml({ role: 'assistant', status: 'failed', error: 'La respuesta está demorando más de lo esperado.' }));
                setBusy(false);
            }
        }, 2500);
    }

    function appendMessage(item) {
        removeEmptyState();
        messages.append(messageHtml(item));
        scrollToBottom();
    }

    function messageHtml(item) {
        const id = item.id ? ` id="car-chatbot-message-${item.id}"` : '';
        if (item.status === 'pending' || item.status === 'processing') {
            return `<div class="car-chatbot-message assistant"${id}><div class="car-chatbot-bubble"><i class="fas fa-circle-notch fa-spin mr-2"></i>Buscando la respuesta...</div></div>`;
        }

        const isError = item.status === 'failed';
        const content = isError ? (item.error || 'No se pudo obtener una respuesta.') : (item.content || '');
        return `<div class="car-chatbot-message ${item.role === 'user' ? 'user' : 'assistant'}${isError ? ' error' : ''}"${id}><div class="car-chatbot-bubble">${safeContent(content)}</div></div>`;
    }

    function safeContent(text) {
        let safe = escapeHtml(String(text || ''));
        safe = safe.replace(/\[([^\]]+)\]\((\/[A-Za-z0-9\/_\-.]+)\)/g, function (match, label, path) {
            return safePaths.includes(path) ? `<a href="${path}">${label}</a>` : label;
        });
        return safe.replace(/\n/g, '<br>');
    }

    function escapeHtml(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function setBusy(value) {
        busy = value;
        question.prop('disabled', value);
        send.prop('disabled', value);
    }

    function removeEmptyState() {
        messages.find('.car-chatbot-empty').remove();
    }

    function emptyState() {
        return '<div class="car-chatbot-empty"><i class="fas fa-compass"></i><p>Consultá cómo realizar una tarea, dónde encontrar una función o pedime datos del sistema: cuántos equipos hay operativos, cuántas cámaras hay en una localidad, los movimientos de un equipo.</p></div>';
    }

    function scrollToBottom() {
        messages.scrollTop(messages[0].scrollHeight);
    }

    function errorText(xhr) {
        if (xhr.status === 429) return 'Alcanzaste el límite de consultas. Esperá un minuto.';
        if (xhr.status === 409) return xhr.responseJSON?.message || 'Esperá la respuesta anterior.';
        if (xhr.status === 422) return xhr.responseJSON?.message || 'Revisá la consulta ingresada.';
        return 'No se pudo contactar al asistente.';
    }

    function showRequestError(xhr) {
        appendMessage({ role: 'assistant', status: 'failed', error: errorText(xhr) });
    }

    loadHistory();
});
</script>
