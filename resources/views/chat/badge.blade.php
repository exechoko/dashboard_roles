<style>
    @keyframes chatBadgePop {
        0%   { transform: scale(1); }
        30%  { transform: scale(1.7); }
        55%  { transform: scale(0.85); }
        75%  { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    @keyframes chatBadgePulso {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .55); }
        50%      { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0); }
    }

    #chat-nav-badge.chat-badge-pulso {
        animation: chatBadgePulso 1.6s ease-in-out infinite;
    }

    #chat-nav-badge.chat-badge-pop {
        animation: chatBadgePop .6s ease, chatBadgePulso 1.6s ease-in-out infinite .6s;
    }
</style>
<script>
$(function () {
    const badge = $('#chat-nav-badge');
    if (!badge.length) return;

    const syncUrl = @json(route('chat.sync'));
    const usuarioActualId = {{ (int) auth()->id() }};
    let conversaciones = {};

    function pintar(total, esNuevo) {
        if (total > 0) {
            badge.text(total > 99 ? '99+' : total).show().addClass('chat-badge-pulso');

            if (esNuevo) {
                badge.removeClass('chat-badge-pop');
                void badge[0].offsetWidth;
                badge.addClass('chat-badge-pop');
            }
        } else {
            badge.hide().removeClass('chat-badge-pulso chat-badge-pop');
        }
    }

    $.get(syncUrl).done(function (respuesta) {
        (respuesta.conversaciones || []).forEach(function (c) { conversaciones[c.id] = c; });
        pintar(respuesta.no_leidos_total || 0, false);
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
            pintar(total, true);

            if (window.ChatNotificador) {
                window.ChatNotificador.notificarMensaje(conversacion);
            }
        });
    }
});
</script>
