@extends('layouts.movil')

@section('title', $nombre)
@section('back', route('movil.chat.index'))
@section('hideNav', true)

@section('scripts')
    <script>
        (function () {
            var hilo = document.getElementById('mChatThread');
            var conversacionId = {{ $conversacion->id }};
            var esGrupo = {{ $conversacion->tipo === 'grupo' ? 'true' : 'false' }};
            var currentUserId = {{ auth()->id() }};
            var ultimoId = 0;
            var mensajesRenderizados = [];

            function escapeHtml(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatearHora(iso) {
                if (!iso) return '';
                return new Date(iso).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
            }

            function burbuja(m) {
                var mia = m.usuario_id === currentUserId;
                var adjuntosHtml = (m.adjuntos || []).map(function (a) {
                    return '<div><a href="' + a.url + '" target="_blank">' +
                        '<i class="fas fa-paperclip"></i> ' + escapeHtml(a.nombre) + '</a></div>';
                }).join('');

                return '<div class="m-chat-bubble' + (mia ? ' m-chat-bubble--mine' : '') + '">' +
                    (esGrupo && !mia ? '<div class="m-chat-bubble__usuario">' + escapeHtml(m.usuario) + '</div>' : '') +
                    (m.cuerpo ? '<div>' + escapeHtml(m.cuerpo) + '</div>' : '') +
                    adjuntosHtml +
                    '<div class="m-chat-bubble__hora">' + formatearHora(m.creado_en) + '</div>' +
                '</div>';
            }

            function agregarMensajes(mensajes) {
                if (!mensajes.length) return;

                mensajes.forEach(function (m) {
                    if (mensajesRenderizados.indexOf(m.id) !== -1) return;
                    mensajesRenderizados.push(m.id);
                    hilo.insertAdjacentHTML('beforeend', burbuja(m));
                    ultimoId = Math.max(ultimoId, m.id);
                });

                window.scrollTo(0, document.body.scrollHeight);
            }

            function marcarLeido() {
                fetch('{{ route('chat.conversaciones.leido', $conversacion) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).catch(function () {});
            }

            fetch('{{ route('chat.conversaciones.show', $conversacion) }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    hilo.innerHTML = '';
                    agregarMensajes(data.mensajes || []);
                    marcarLeido();
                });

            var pollTimer = setInterval(function () {
                fetch('{{ route('chat.sync') }}?conversacion=' + conversacionId + '&desde=' + ultimoId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var nuevos = (data.mensajes || []).filter(function (m) { return m.conversacion_id === conversacionId; });
                        if (nuevos.length) {
                            agregarMensajes(nuevos);
                            marcarLeido();
                        }
                    })
                    .catch(function () {});
            }, 4000);

            window.addEventListener('beforeunload', function () {
                clearInterval(pollTimer);
            });

            var form = document.getElementById('mChatForm');
            var input = document.getElementById('mChatInput');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var texto = input.value.trim();
                if (!texto) return;

                input.disabled = true;

                var datos = new FormData();
                datos.append('cuerpo', texto);

                fetch('{{ route('chat.mensajes.store', $conversacion) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: datos,
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        input.value = '';
                        if (data.mensaje) {
                            agregarMensajes([data.mensaje]);
                        }
                    })
                    .finally(function () {
                        input.disabled = false;
                        input.focus();
                    });
            });
        })();
    </script>
@endsection

@push('styles')
    <style>
        .m-page { padding-bottom: 5rem; }
    </style>
@endpush

@section('content')
    <div class="m-chat-thread" id="mChatThread">
        <div class="m-empty"><p>Cargando…</p></div>
    </div>

    <form id="mChatForm" class="m-chat-composer">
        <textarea id="mChatInput" rows="1" placeholder="Escribí un mensaje…" required></textarea>
        <button type="submit" class="m-btn" style="padding:.55rem .9rem;"><i class="fas fa-arrow-right"></i></button>
    </form>
@endsection
