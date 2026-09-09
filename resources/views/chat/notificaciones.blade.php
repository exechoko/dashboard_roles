<script>
window.ChatNotificador = (function () {
    const urlChat = @json(route('chat.index'));

    function activarPushEscritorio() {
        if (window.WebPush && window.WebPush.soportado()) {
            window.WebPush.activar('escritorio').catch(function () {});
        }
    }

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(function (permiso) {
            if (permiso === 'granted') {
                activarPushEscritorio();
            }
        });
    } else if ('Notification' in window && Notification.permission === 'granted') {
        activarPushEscritorio();
    }

    function sonido() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
            osc.connect(gain).connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) { /* Web Audio no disponible */ }
    }

    function notificar(conversacion) {
        sonido();

        if (!('Notification' in window) || Notification.permission !== 'granted' || !document.hidden) return;

        const notificacion = new Notification(conversacion.nombre, {
            body: conversacion.ultimo_mensaje || 'Nuevo mensaje',
            icon: @json(asset('img/logo.ico')),
            tag: 'chat-conversacion-' + conversacion.id,
        });

        notificacion.onclick = function () {
            window.focus();
            window.location.href = urlChat + '?conversacion=' + conversacion.id;
            notificacion.close();
        };
    }

    return { notificarMensaje: notificar };
})();
</script>
