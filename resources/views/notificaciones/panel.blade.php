<li class="dropdown" id="notificaciones-dropdown">
    <a href="#" data-toggle="dropdown" class="nav-link nav-link-lg" title="Notificaciones de Infraestructura" style="position: relative;">
        <i class="fas fa-bell"></i>
        <span id="notificaciones-nav-badge" class="badge badge-danger badge-sm ml-1"
            style="display:none; position:absolute; top:6px; right:2px; font-size:9px;"></span>
    </a>
    <div class="dropdown-menu dropdown-menu-right notificaciones-panel">
        <div class="notificaciones-panel-header">
            <span><i class="fas fa-server"></i> Infraestructura</span>
            <div>
                <button type="button" id="notificaciones-vaciar" class="btn btn-xs btn-outline-danger" title="Vaciar historial">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div id="notificaciones-lista" class="notificaciones-panel-lista">
            <div class="notificaciones-panel-vacia">Sin notificaciones.</div>
        </div>
    </div>
</li>

<style>
    .notificaciones-panel {
        width: 360px;
        max-width: 90vw;
        padding: 0;
        overflow: hidden;
    }

    .notificaciones-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .08));
        font-weight: 600;
        color: var(--text-primary, #212529) !important;
    }

    .notificaciones-panel-lista {
        max-height: 380px;
        overflow-y: auto;
    }

    .notificaciones-panel-vacia {
        padding: 24px 14px;
        text-align: center;
        font-size: 13px;
        color: var(--text-secondary, #6c757d) !important;
    }

    .notificacion-item {
        display: flex;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .05));
    }

    .notificacion-item:hover {
        background-color: var(--bg-tertiary, rgba(0, 0, 0, .02));
    }

    .notificacion-item.no-leida {
        background-color: rgba(220, 53, 69, .1);
    }

    .notificacion-item__icono {
        flex: 0 0 auto;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 9px;
        margin-top: 1px;
    }

    .notificacion-item__icono.nivel-danger { background-color: #dc3545; }
    .notificacion-item__icono.nivel-warning { background-color: #ffc107; color: #212529; }
    .notificacion-item__icono.nivel-success { background-color: #28a745; }

    .notificacion-item__texto {
        min-width: 0;
        flex: 1 1 auto;
    }

    .notificacion-item__titulo {
        font-weight: 600;
        font-size: 13px;
        display: block;
        color: var(--text-primary, #212529) !important;
    }

    .notificacion-item__mensaje {
        font-size: 12px;
        color: var(--text-secondary, #6c757d) !important;
        display: block;
        word-break: break-word;
    }

    .notificacion-item__fecha {
        font-size: 10.5px;
        color: var(--text-secondary, #adb5bd) !important;
        opacity: .8;
    }

    @keyframes notifBadgePulso {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .55); }
        50%      { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0); }
    }

    #notificaciones-nav-badge.notif-badge-pulso {
        animation: notifBadgePulso 1.6s ease-in-out infinite;
    }
</style>

<script>
$(function () {
    const badge = $('#notificaciones-nav-badge');
    const lista = $('#notificaciones-lista');
    const dropdown = $('#notificaciones-dropdown');
    if (!badge.length) return;

    const syncUrl = @json(route('notificaciones.sync'));
    const marcarLeidasUrl = @json(route('notificaciones.marcar-leidas'));
    const vaciarUrl = @json(route('notificaciones.vaciar'));

    function pintarBadge(total) {
        if (total > 0) {
            badge.text(total > 99 ? '99+' : total).show().addClass('notif-badge-pulso');
        } else {
            badge.hide().removeClass('notif-badge-pulso');
        }
    }

    function escapeHtml(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    function pintarLista(notificaciones) {
        if (!notificaciones.length) {
            lista.html('<div class="notificaciones-panel-vacia">Sin notificaciones.</div>');
            return;
        }

        lista.html(notificaciones.map(function (n) {
            const icono = n.icono || 'fas fa-bell';
            const claseLeida = n.leida ? '' : 'no-leida';
            return '<div class="notificacion-item ' + claseLeida + '">'
                + '<div class="notificacion-item__icono nivel-' + n.nivel + '"><i class="' + icono + '"></i></div>'
                + '<div class="notificacion-item__texto">'
                + '<span class="notificacion-item__titulo">' + escapeHtml(n.titulo) + '</span>'
                + '<span class="notificacion-item__mensaje">' + escapeHtml(n.mensaje) + '</span>'
                + '<span class="notificacion-item__fecha">' + escapeHtml(n.creado_en_humano) + '</span>'
                + '</div>'
                + '</div>';
        }).join(''));
    }

    function sincronizar() {
        $.get(syncUrl).done(function (respuesta) {
            pintarLista(respuesta.notificaciones || []);
            pintarBadge(respuesta.no_leidas_total || 0);
        });
    }

    sincronizar();
    setInterval(sincronizar, 60000);

    const csrf = @json(csrf_token());

    dropdown.on('show.bs.dropdown', function () {
        if (badge.is(':visible')) {
            $.ajax({
                url: marcarLeidasUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function () {
                pintarBadge(0);
                lista.find('.notificacion-item').removeClass('no-leida');
            });
        }
    });

    $('#notificaciones-vaciar').on('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (!confirm('¿Vaciar todo el historial de notificaciones?')) return;

        $.ajax({
            url: vaciarUrl,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function () {
            pintarLista([]);
            pintarBadge(0);
        });
    });
});
</script>
