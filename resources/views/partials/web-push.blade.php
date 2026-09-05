<script>
    // Helper compartido para activar/desactivar notificaciones push, usado
    // tanto por la app móvil (/movil/chat) como por el chat de escritorio.
    // Requiere que la página ya haya registrado un service worker propio
    // (con el scope que corresponda) antes de llamar a estas funciones.
    window.WebPush = (function () {
        function base64UrlToUint8Array(base64Url) {
            var padding = '='.repeat((4 - base64Url.length % 4) % 4);
            var base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
            var raw = atob(base64);
            var salida = new Uint8Array(raw.length);
            for (var i = 0; i < raw.length; ++i) {
                salida[i] = raw.charCodeAt(i);
            }
            return salida;
        }

        function csrfHeaders(extra) {
            return Object.assign({
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            }, extra || {});
        }

        function soportado() {
            return 'serviceWorker' in navigator && 'PushManager' in window;
        }

        function suscripcionActual() {
            if (!soportado()) return Promise.resolve(null);
            return navigator.serviceWorker.ready.then(function (reg) {
                return reg.pushManager.getSubscription();
            });
        }

        function activar(plataforma) {
            if (!soportado()) return Promise.reject(new Error('no soportado'));

            var suscripcionCreada = null;

            return Notification.requestPermission().then(function (permiso) {
                if (permiso !== 'granted') {
                    throw new Error('permiso denegado');
                }
                return navigator.serviceWorker.ready;
            }).then(function (reg) {
                var vapidKey = document.querySelector('meta[name="vapid-public-key"]').content;
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: base64UrlToUint8Array(vapidKey),
                });
            }).then(function (suscripcion) {
                suscripcionCreada = suscripcion;
                var datos = suscripcion.toJSON();
                datos.plataforma = plataforma || 'movil';
                return fetch('{{ route('movil.push.store') }}', {
                    method: 'POST',
                    headers: csrfHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                    body: JSON.stringify(datos),
                });
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('No se pudo guardar la suscripción en el servidor (status ' + response.status + ').');
                }
                return suscripcionCreada;
            }).catch(function (error) {
                // Si el navegador ya llegó a crear la suscripción pero algo
                // después falló (guardado en el servidor, red, etc.), la
                // deshacemos: si no, queda "suscripto" en el navegador sin
                // que el servidor lo sepa, y nunca van a llegar avisos sin
                // que se note nada raro.
                if (suscripcionCreada) {
                    return suscripcionCreada.unsubscribe().catch(function () {}).then(function () {
                        throw error;
                    });
                }
                throw error;
            });
        }

        function desactivar() {
            return suscripcionActual().then(function (suscripcion) {
                if (!suscripcion) return;
                var endpoint = suscripcion.endpoint;
                return suscripcion.unsubscribe().then(function () {
                    return fetch('{{ route('movil.push.destroy') }}', {
                        method: 'DELETE',
                        headers: csrfHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                        body: JSON.stringify({ endpoint: endpoint }),
                    });
                });
            });
        }

        return {
            soportado: soportado,
            suscripcionActual: suscripcionActual,
            activar: activar,
            desactivar: desactivar,
        };
    })();
</script>
