<script>
$(function () {
    const badge = $('#chat-nav-badge');
    if (!badge.length) return;

    const syncUrl = @json(route('chat.sync'));
    const usuarioActualId = {{ (int) auth()->id() }};
    let conversaciones = {};

    function pintar(total) {
        if (total > 0) {
            badge.text(total > 99 ? '99+' : total).show();
        } else {
            badge.hide();
        }
    }

    $.get(syncUrl).done(function (respuesta) {
        (respuesta.conversaciones || []).forEach(function (c) { conversaciones[c.id] = c; });
        pintar(respuesta.no_leidos_total || 0);
    });

    if (window.ChatRealtime) {
        window.ChatRealtime.onMensaje(function (evento, conversacionCreada) {
            if (conversacionCreada) {
                conversaciones[conversacionCreada.id] = conversacionCreada;
                return;
            }

            const mensaje = evento.mensaje;
            const conversacion = conversaciones[evento.conversacion_id];
            if (!conversacion || mensaje.usuario_id === usuarioActualId) return;

            conversacion.no_leidos = (conversacion.no_leidos || 0) + 1;
            conversacion.ultimo_mensaje = mensaje.cuerpo || 'Adjunto';
            conversacion.actualizado_en = mensaje.creado_en;

            const total = Object.values(conversaciones).reduce(function (acc, c) {
                return acc + (c.no_leidos || 0);
            }, 0);
            pintar(total);

            if (window.ChatNotificador) {
                window.ChatNotificador.notificarMensaje(conversacion);
            }
        });
    }
});
</script>
