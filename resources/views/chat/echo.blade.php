<script>
window.ChatRealtime = (function () {
    const usuarioActualId = {{ (int) auth()->id() }};

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: @json(config('broadcasting.connections.ably.public_key')),
        // pusher-js@8 exige "cluster" en las opciones aunque no se use (Ably se conecta
        // por wsHost/wsPort, no por cluster). Sin este valor el constructor tira una
        // excepción y ninguna página con el chat termina de cargar.
        cluster: 'ably',
        wsHost: 'realtime-pusher.ably.io',
        wsPort: 443,
        disableStats: true,
        encrypted: true,
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        },
    });

    const usuariosOnline = new Set();
    const listenersMensaje = [];
    const listenersPresencia = [];

    window.Echo.private('chat.usuario.' + usuarioActualId)
        .listen('.chat.mensaje', function (evento) {
            listenersMensaje.forEach(function (callback) { callback(evento); });
        })
        .listen('.chat.conversacion.creada', function (evento) {
            listenersMensaje.forEach(function (callback) { callback(null, evento); });
        });

    window.Echo.join('chat.presencia')
        .here(function (usuarios) {
            usuarios.forEach(function (u) { usuariosOnline.add(u.id); });
            listenersPresencia.forEach(function (callback) { callback(); });
        })
        .joining(function (u) {
            usuariosOnline.add(u.id);
            listenersPresencia.forEach(function (callback) { callback(); });
        })
        .leaving(function (u) {
            usuariosOnline.delete(u.id);
            listenersPresencia.forEach(function (callback) { callback(); });
        });

    return {
        onMensaje(callback) { listenersMensaje.push(callback); },
        onPresencia(callback) { listenersPresencia.push(callback); },
        estaEnLinea(id) { return usuariosOnline.has(id); },
        conversacion(id) { return window.Echo.private('chat.conversacion.' + id); },
        salirDe(id) { window.Echo.leave('chat.conversacion.' + id); },
    };
})();
</script>
