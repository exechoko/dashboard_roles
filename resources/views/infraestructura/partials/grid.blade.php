{{--
    Partial reusable de grilla de dispositivos (ping + SNMP) para las pantallas
    de Infraestructura. Espera: $grupo (pcs|servidores|camaras|red), $titulo,
    $icono (clase fas fa-*).
--}}
@push('styles')
    <style>
        .infra-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: .9rem 1rem;
            height: 100%;
            transition: box-shadow .15s;
        }
        .infra-card:hover { box-shadow: 0 4px 14px var(--shadow); }
        .infra-nombre { color: var(--text-primary); font-weight: 600; }
        .infra-meta { color: var(--text-secondary); font-size: .8rem; }
        .infra-estado-dot {
            width: 10px; height: 10px; border-radius: 50%;
            display: inline-block; margin-right: .35rem;
        }
        .infra-estado-ok, .infra-estado-sin_snmp { background: var(--accent-success); }
        .infra-estado-alerta { background: var(--accent-warning); }
        .infra-estado-caido { background: var(--accent-danger); }
        .infra-estado-pendiente, .infra-estado-ip_invalida, .infra-estado-deshabilitado { background: var(--text-secondary); }
        .infra-card.infra-pausada { opacity: .6; }
        .infra-metricas small { color: var(--text-secondary); }
        .infra-barra { height: 6px; border-radius: 4px; background: var(--bg-tertiary); overflow: hidden; margin-top: 2px; }
        .infra-barra > div { height: 100%; }
        .infra-refresh, .infra-toggle { border: none; background: transparent; color: var(--text-secondary); cursor: pointer; padding: 0 .25rem; }
        .infra-refresh:hover, .infra-toggle:hover { color: var(--accent-primary); }
        .infra-toggle.activo { color: var(--accent-success); }
        .infra-refresh.girando i { animation: infra-spin .8s linear infinite; }
        @keyframes infra-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .btn-filtro-infra {
            --neon: #64748b;
            --neon-soft: rgba(100, 116, 139, 0.15);
            color: var(--neon);
            border: 1px solid var(--neon);
            background: transparent;
            font-weight: 600;
            transition: box-shadow .2s ease, background-color .2s ease, color .2s ease;
        }
        .btn-filtro-infra .badge { background: var(--neon-soft); color: var(--neon); }
        .btn-filtro-infra:hover {
            box-shadow: 0 0 6px var(--neon-soft), 0 0 14px var(--neon-soft);
            background: var(--neon-soft); text-decoration: none; color: var(--neon);
        }
        .btn-filtro-infra.activo {
            background: var(--neon); border-color: var(--neon); color: #fff;
            box-shadow: 0 0 8px var(--neon-soft), 0 0 18px var(--neon-soft);
        }
        .btn-filtro-infra.activo .badge { background: rgba(255, 255, 255, 0.28); color: #fff; }
        [data-theme="dark"] .btn-filtro-infra.activo { color: #06101f; }
        [data-theme="dark"] .btn-filtro-infra.activo .badge { background: rgba(6, 16, 31, 0.35); color: #06101f; }

        .filtro-todos     { --neon: #0891b2; --neon-soft: rgba(8, 145, 178, 0.16); }
        .filtro-ok        { --neon: #059669; --neon-soft: rgba(5, 150, 105, 0.16); }
        .filtro-alerta    { --neon: #d97706; --neon-soft: rgba(217, 119, 6, 0.16); }
        .filtro-caido     { --neon: #dc2626; --neon-soft: rgba(220, 38, 38, 0.16); }
        .filtro-pendiente { --neon: #64748b; --neon-soft: rgba(100, 116, 139, 0.16); }
        .filtro-pausado   { --neon: #7c3aed; --neon-soft: rgba(124, 58, 237, 0.16); }

        [data-theme="dark"] .filtro-todos     { --neon: #00e5ff; --neon-soft: rgba(0, 229, 255, 0.22); }
        [data-theme="dark"] .filtro-ok        { --neon: #00f2a6; --neon-soft: rgba(0, 242, 166, 0.22); }
        [data-theme="dark"] .filtro-alerta    { --neon: #ffb020; --neon-soft: rgba(255, 176, 32, 0.22); }
        [data-theme="dark"] .filtro-caido     { --neon: #ff355d; --neon-soft: rgba(255, 53, 93, 0.22); }
        [data-theme="dark"] .filtro-pendiente { --neon: #93a8c0; --neon-soft: rgba(147, 168, 192, 0.22); }
        [data-theme="dark"] .filtro-pausado   { --neon: #a78bfa; --neon-soft: rgba(167, 139, 250, 0.22); }
    </style>
@endpush

<div class="card shadow-sm border-0">
    <div class="card-header-modern">
        <div class="card-header-left">
            <div class="header-icon"><i class="{{ $icono }}"></i></div>
            <div>
                <h5 class="header-title">{{ $titulo }}</h5>
                <small class="text-muted">
                    <span class="badge-total" id="infra-total-{{ $grupo }}">0</span> dispositivos
                    &mdash; <span id="infra-actualizado-{{ $grupo }}">cargando...</span>
                </small>
            </div>
        </div>
    </div>
    <div class="card-body pt-3">
        <div class="btn-group flex-wrap mb-3" id="infra-filtros-{{ $grupo }}">
            <a href="#" data-filtro="" class="btn btn-sm btn-filtro-infra filtro-todos activo">Todos <span class="badge ml-1" data-count="">0</span></a>
            <a href="#" data-filtro="ok" class="btn btn-sm btn-filtro-infra filtro-ok">OK <span class="badge ml-1" data-count="ok">0</span></a>
            <a href="#" data-filtro="alerta" class="btn btn-sm btn-filtro-infra filtro-alerta">Alerta <span class="badge ml-1" data-count="alerta">0</span></a>
            <a href="#" data-filtro="caido" class="btn btn-sm btn-filtro-infra filtro-caido">Caídos <span class="badge ml-1" data-count="caido">0</span></a>
            <a href="#" data-filtro="pendiente" class="btn btn-sm btn-filtro-infra filtro-pendiente">Pendientes <span class="badge ml-1" data-count="pendiente">0</span></a>
            <a href="#" data-filtro="deshabilitado" class="btn btn-sm btn-filtro-infra filtro-pausado">Pausados <span class="badge ml-1" data-count="deshabilitado">0</span></a>
        </div>

        <div class="row" id="infra-grid-{{ $grupo }}">
            <div class="col-12 text-center text-muted py-4">Cargando dispositivos...</div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function infraMonitor{{ ucfirst($grupo) }}() {
            var grupo = '{{ $grupo }}';
            var url = '{{ route('api.infraestructura.estado-grupo', $grupo) }}';
            var refreshUrlTemplate = '{{ route('api.infraestructura.refrescar-dispositivo', ['dispositivo' => '__ID__']) }}';
            var toggleUrlTemplate = '{{ route('api.infraestructura.toggle-monitoreo', ['dispositivo' => '__ID__']) }}';
            var filtroActual = '';

            // Contenedores: no se reemplazan nunca, solo su contenido — se
            // resuelven una sola vez en vez de volver a buscarlos en cada poll.
            var elGrid = document.getElementById('infra-grid-' + grupo);
            var elFiltros = document.getElementById('infra-filtros-' + grupo);
            var elTotal = document.getElementById('infra-total-' + grupo);
            var elActualizado = document.getElementById('infra-actualizado-' + grupo);

            function escapar(texto) {
                var div = document.createElement('div');
                div.textContent = texto === null || texto === undefined ? '' : String(texto);
                return div.innerHTML;
            }

            var etiquetasEstado = {
                ok: 'OK', alerta: 'Alerta', caido: 'Caído', sin_snmp: 'En línea',
                pendiente: 'Pendiente', ip_invalida: 'IP inválida', deshabilitado: 'Monitoreo pausado'
            };

            function colorBarra(pct) {
                if (pct >= 90) return 'var(--accent-danger)';
                if (pct >= 75) return 'var(--accent-warning)';
                return 'var(--accent-success)';
            }

            function barra(etiqueta, pct, detalle) {
                if (pct === null || pct === undefined) return '';
                var sufijo = detalle ? ' (' + detalle + ')' : '';
                return '<div class="infra-metricas mt-1">'
                    + '<small>' + etiqueta + ' ' + pct + '%' + sufijo + '</small>'
                    + '<div class="infra-barra"><div style="width:' + Math.min(pct, 100) + '%;background:' + colorBarra(pct) + ';"></div></div>'
                    + '</div>';
            }

            function detalleGb(usado, total) {
                if (usado === null || usado === undefined || total === null || total === undefined) return null;
                return usado + '/' + total + ' GB';
            }

            function iconoSistemaOperativo(so) {
                if (!so) return null;
                if (/windows/i.test(so)) return 'fab fa-windows';
                if (/(ubuntu|debian|centos|linux)/i.test(so)) return 'fab fa-linux';
                return 'fas fa-info-circle';
            }

            function tarjeta(d) {
                var estado = d.estado || 'pendiente';
                var pausado = !d.monitoreo_habilitado;
                var metricas = pausado ? '' : barra('CPU', d.cpu_pct)
                    + barra('RAM', d.ram_pct, detalleGb(d.ram_usado_gb, d.ram_total_gb))
                    + barra('Disco', d.disco_pct, detalleGb(d.disco_usado_gb, d.disco_total_gb));
                var iconoSo = iconoSistemaOperativo(d.sistema_operativo);
                var lineaSo = iconoSo
                    ? '<div class="infra-meta"><i class="' + iconoSo + ' mr-1"></i>' + escapar(d.sistema_operativo) + '</div>'
                    : '';
                var lineaCpu = d.cpu_modelo
                    ? '<div class="infra-meta"><i class="fas fa-microchip mr-1"></i>' + escapar(d.cpu_modelo) + '</div>'
                    : '';
                var btnRefresh = pausado
                    ? ''
                    : '<button type="button" class="infra-refresh" data-id="' + d.id + '" title="Refrescar ahora"><i class="fas fa-sync-alt"></i></button>';
                var btnToggle = '<button type="button" class="infra-toggle' + (pausado ? '' : ' activo') + '" data-id="' + d.id
                    + '" title="' + (pausado ? 'Reanudar monitoreo' : 'Pausar monitoreo') + '">'
                    + '<i class="fas ' + (pausado ? 'fa-toggle-off' : 'fa-toggle-on') + '"></i></button>';

                return '<div class="col-xl-3 col-lg-4 col-md-6 mb-3 infra-item" data-estado="' + estado + '">'
                    + '<div class="infra-card' + (pausado ? ' infra-pausada' : '') + '">'
                    + '<div class="d-flex justify-content-between align-items-start">'
                    + '<div><i class="' + escapar(d.icono || 'fas fa-cube') + ' mr-1"></i>'
                    + '<span class="infra-nombre">' + escapar(d.nombre) + '</span></div>'
                    + '<div>' + btnRefresh + btnToggle + '</div>'
                    + '</div>'
                    + '<div class="infra-meta">' + escapar(d.ip || 'sin IP') + (d.oficina ? ' &mdash; ' + escapar(d.oficina) : '') + '</div>'
                    + lineaSo
                    + lineaCpu
                    + '<div class="mt-1"><span class="infra-estado-dot infra-estado-' + estado + '"></span>'
                    + '<small>' + (etiquetasEstado[estado] || escapar(estado)) + (d.latencia_ms ? ' (' + d.latencia_ms + ' ms)' : '') + '</small></div>'
                    + metricas
                    + '</div></div>';
            }

            // Agrupa los 6 estados posibles en los 4 baldes de filtro: un
            // dispositivo que responde ping pero no tiene SNMP (sin_snmp) está
            // "OK" a todos los efectos visuales — el balde "Pendientes" es
            // solo para lo que todavía no se sabe (no relevado / IP inválida).
            function normalizarEstado(estado) {
                if (estado === 'sin_snmp') return 'ok';
                if (estado === 'ip_invalida') return 'pendiente';
                return estado;
            }

            function renderFiltros(datos) {
                var conteos = { ok: 0, alerta: 0, caido: 0, pendiente: 0, deshabilitado: 0 };
                datos.forEach(function (d) {
                    var e = normalizarEstado(d.estado);
                    if (conteos[e] !== undefined) conteos[e]++;
                });

                if (!elFiltros) return;
                elFiltros.querySelectorAll('[data-count]').forEach(function (el) {
                    var k = el.getAttribute('data-count');
                    el.textContent = k === '' ? datos.length : (conteos[k] || 0);
                });
            }

            function aplicarFiltro() {
                elGrid.querySelectorAll('.infra-item').forEach(function (el) {
                    var normalizado = normalizarEstado(el.getAttribute('data-estado'));
                    el.style.display = (!filtroActual || normalizado === filtroActual) ? '' : 'none';
                });
            }

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            }

            function vincularBotonesRefresh() {
                elGrid.querySelectorAll('.infra-refresh').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (btn.classList.contains('girando')) return;
                        btn.classList.add('girando');
                        fetch(refreshUrlTemplate.replace('__ID__', btn.getAttribute('data-id')), {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() }
                        })
                            .then(function () { return verificar(); })
                            .catch(function () {})
                            .finally(function () { btn.classList.remove('girando'); });
                    });
                });
            }

            function vincularBotonesToggle() {
                elGrid.querySelectorAll('.infra-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        btn.disabled = true;
                        fetch(toggleUrlTemplate.replace('__ID__', btn.getAttribute('data-id')), {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() }
                        })
                            .then(function () { return verificar(); })
                            .catch(function () {})
                            .finally(function () { btn.disabled = false; });
                    });
                });
            }

            function render(datos) {
                if (!elGrid) return;
                if (!datos.length) {
                    elGrid.innerHTML = '<div class="col-12 text-center text-muted py-4">Sin dispositivos cargados para este grupo.</div>';
                    return;
                }
                elGrid.innerHTML = datos.map(tarjeta).join('');
                aplicarFiltro();
                vincularBotonesRefresh();
                vincularBotonesToggle();
            }

            function verificar() {
                return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        var datos = d.dispositivos || [];
                        if (elTotal) elTotal.textContent = datos.length;
                        if (elActualizado) {
                            elActualizado.textContent = d.consultado_en
                                ? ('Última lectura: ' + d.consultado_en.substring(11, 16))
                                : 'sin lecturas aún';
                        }
                        renderFiltros(datos);
                        render(datos);
                    })
                    .catch(function () {
                        if (elGrid) {
                            elGrid.innerHTML = '<div class="col-12 text-center text-warning py-4">'
                                + '<i class="fas fa-exclamation-circle mr-1"></i>No se pudo consultar el estado.</div>';
                        }
                    });
            }

            if (elFiltros) {
                elFiltros.addEventListener('click', function (ev) {
                    var link = ev.target.closest('[data-filtro]');
                    if (!link) return;
                    ev.preventDefault();
                    filtroActual = link.getAttribute('data-filtro');
                    elFiltros.querySelectorAll('.btn-filtro-infra').forEach(function (b) { b.classList.remove('activo'); });
                    link.classList.add('activo');
                    aplicarFiltro();
                });
            }

            verificar();
            setInterval(verificar, 60000);
        })();
    </script>
@endpush
