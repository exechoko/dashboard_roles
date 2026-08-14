<script>
$(function () {
    const badge = $('#chat-nav-badge');
    if (!badge.length) return;

    const syncUrl = @json(route('chat.sync'));
    let timer = null;

    function actualizar() {
        $.get(syncUrl).done(function (respuesta) {
            const total = respuesta.no_leidos_total || 0;
            if (total > 0) {
                badge.text(total > 99 ? '99+' : total).show();
            } else {
                badge.hide();
            }

            if (window.ChatNotificador) {
                window.ChatNotificador.procesar(respuesta.conversaciones);
            }
        });
    }

    function intervalo() {
        return document.hidden ? 60000 : 20000;
    }

    function reprogramar() {
        if (timer) clearInterval(timer);
        timer = setInterval(actualizar, intervalo());
    }

    document.addEventListener('visibilitychange', function () {
        reprogramar();
        if (!document.hidden) actualizar();
    });

    actualizar();
    reprogramar();
});
</script>
