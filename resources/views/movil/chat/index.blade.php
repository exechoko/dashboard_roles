@extends('layouts.movil')

@section('title', 'Chat')

@section('content')
    <div class="m-install-banner" id="mPushBanner" hidden>
        <i class="fas fa-bell"></i>
        <span id="mPushBannerText">Activá las notificaciones para enterarte de mensajes nuevos.</span>
        <button type="button" id="mPushBtn" class="m-btn" style="padding:.35rem .8rem; font-size:.82rem;">Activar</button>
        <button type="button" id="mPushDismiss" class="m-install-banner__close" aria-label="Cerrar">&times;</button>
    </div>

    <button type="button" id="mNuevaConversacion" class="m-btn" style="width:100%; margin-bottom:1rem;">
        <i class="fas fa-plus"></i> Nueva conversación
    </button>

    <div id="mChatLista" class="m-list">
        <div class="m-empty"><p>Cargando conversaciones…</p></div>
    </div>

    <div class="m-chat-picker" id="mChatPicker" hidden>
        <div class="m-chat-picker__header">
            <button type="button" id="mChatPickerCerrar" class="m-topbar__back"><i class="fas fa-arrow-left"></i></button>
            <input type="text" id="mChatPickerBuscar" placeholder="Buscar persona…" style="flex:1; font-size:16px; padding:.5rem .7rem; border:1px solid var(--m-border); border-radius:.5rem; background:var(--m-surface); color:var(--m-text);">
        </div>
        <div class="m-chat-picker__list" id="mChatPickerLista"></div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var lista = document.getElementById('mChatLista');
            var baseUrl = '{{ url('/movil/chat') }}';
            var currentUserId = {{ auth()->id() }};

            function escapeHtml(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatearFecha(iso) {
                if (!iso) return '';
                var d = new Date(iso);
                return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' }) + ' ' +
                    d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
            }

            function render(conversaciones) {
                if (!conversaciones.length) {
                    lista.innerHTML = '<div class="m-empty"><i class="fas fa-comments" style="font-size:1.6rem;"></i><p>No tenés conversaciones todavía.</p></div>';
                    return;
                }

                lista.innerHTML = conversaciones.map(function (c) {
                    return '<a href="' + baseUrl + '/' + c.id + '" class="m-card m-chat-item">' +
                        '<span class="m-chat-item__dot' + (c.en_linea ? ' m-chat-item__dot--online' : '') + '"></span>' +
                        '<span class="m-chat-item__body">' +
                            '<div class="m-chat-item__row">' +
                                '<strong>' + escapeHtml(c.nombre) + '</strong>' +
                                '<span class="m-card__subtitle">' + formatearFecha(c.actualizado_en) + '</span>' +
                            '</div>' +
                            '<div class="m-chat-item__row">' +
                                '<span class="m-chat-item__preview">' + escapeHtml(c.ultimo_mensaje || 'Sin mensajes') + '</span>' +
                                (c.no_leidos > 0 ? '<span class="m-chat-item__badge">' + c.no_leidos + '</span>' : '') +
                            '</div>' +
                        '</span>' +
                    '</a>';
                }).join('');
            }

            function cargar() {
                fetch('{{ route('chat.sync') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { render(data.conversaciones || []); })
                    .catch(function () {});
            }

            cargar();
            setInterval(cargar, 10000);

            // Picker de nueva conversación (solo privadas, 1 a 1).
            var picker = document.getElementById('mChatPicker');
            var pickerLista = document.getElementById('mChatPickerLista');
            var pickerBuscar = document.getElementById('mChatPickerBuscar');
            var contactos = [];

            function renderContactos(filtro) {
                var texto = (filtro || '').toLowerCase();
                var filtrados = contactos.filter(function (u) {
                    return u.nombre.toLowerCase().indexOf(texto) !== -1;
                });

                pickerLista.innerHTML = filtrados.map(function (u) {
                    return '<div class="m-card mChatContacto" data-id="' + u.id + '" style="margin-bottom:.5rem; cursor:pointer;">' +
                        '<span class="m-chat-item__dot' + (u.en_linea ? ' m-chat-item__dot--online' : '') + '"></span> ' +
                        escapeHtml(u.nombre) +
                    '</div>';
                }).join('');

                pickerLista.querySelectorAll('.mChatContacto').forEach(function (el) {
                    el.addEventListener('click', function () {
                        iniciarConversacion(parseInt(el.dataset.id, 10));
                    });
                });
            }

            function iniciarConversacion(userId) {
                fetch('{{ route('chat.conversaciones.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ tipo: 'privada', usuarios: [userId] }),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.conversacion) {
                            window.location.href = baseUrl + '/' + data.conversacion.id;
                        }
                    });
            }

            document.getElementById('mNuevaConversacion').addEventListener('click', function () {
                picker.hidden = false;
                if (!contactos.length) {
                    fetch('{{ route('chat.contactos') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            contactos = data.usuarios || [];
                            renderContactos('');
                        });
                }
            });

            document.getElementById('mChatPickerCerrar').addEventListener('click', function () {
                picker.hidden = true;
            });

            pickerBuscar.addEventListener('input', function () {
                renderContactos(pickerBuscar.value);
            });

            // Banner de notificaciones push.
            var banner = document.getElementById('mPushBanner');
            var LS_KEY = 'movil-push-dismissed';

            function fueDescartado() {
                try { return localStorage.getItem(LS_KEY) === '1'; } catch (e) { return false; }
            }

            if (window.WebPush && window.WebPush.soportado() && Notification.permission !== 'denied' && !fueDescartado()) {
                window.WebPush.suscripcionActual().then(function (suscripcion) {
                    if (!suscripcion) {
                        banner.hidden = false;
                    }
                });
            }

            var pushBtn = document.getElementById('mPushBtn');
            var pushBannerText = document.getElementById('mPushBannerText');
            var pushBannerTextOriginal = pushBannerText.textContent;

            pushBtn.addEventListener('click', function () {
                pushBtn.disabled = true;

                window.WebPush.activar('movil').then(function () {
                    banner.hidden = true;
                }).catch(function () {
                    pushBtn.disabled = false;

                    if (Notification.permission === 'denied') {
                        // El usuario eligió no permitirlo: no insistir.
                        try { localStorage.setItem(LS_KEY, '1'); } catch (e) {}
                        banner.hidden = true;
                        return;
                    }

                    // Error real (red, servidor, etc.): mostrar y dejar
                    // reintentar, sin marcarlo como descartado.
                    pushBannerText.textContent = 'No se pudo activar. Volvé a intentar en un momento.';
                    setTimeout(function () {
                        pushBannerText.textContent = pushBannerTextOriginal;
                    }, 5000);
                });
            });

            document.getElementById('mPushDismiss').addEventListener('click', function () {
                try { localStorage.setItem(LS_KEY, '1'); } catch (e) {}
                banner.hidden = true;
            });
        })();
    </script>
@endsection
