@can('ver-expediente-cecoco')
@if(config('ia.enabled'))
<style>
    @keyframes ia-spin-anim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .ia-spin { animation: ia-spin-anim 1s linear infinite; display: inline-block; }
</style>
<div class="card mb-4 border-info">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-robot"></i> Resumen con IA</h5>
        <div>
            <button type="button" class="btn btn-sm btn-info" data-resumen-ia-btn>
                <i class="bi bi-stars"></i> Generar resumen
            </button>
            <button type="button" class="btn btn-sm btn-outline-info ml-2" data-resumen-ia-refrescar style="display:none;" title="Volver a generar el resumen con IA">
                <i class="bi bi-arrow-clockwise"></i> Regenerar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ml-2" data-resumen-ia-copiar style="display:none;" title="Copiar el resumen para enviarlo por mensaje">
                <i class="fas fa-copy"></i> Copiar
            </button>
        </div>
    </div>
    <div class="card-body" data-resumen-ia-body style="display:none;">
        <div data-resumen-ia-loading class="text-center py-3" style="display:none;">
            <i class="bi bi-arrow-repeat ia-spin"></i> <span data-resumen-ia-estado-text>Generando resumen con IA…</span>
            <div class="small text-muted mt-1">El resumen se genera en segundo plano. Esta pantalla se actualiza sola; podés esperar o volver más tarde.</div>
        </div>
        <div data-resumen-ia-error class="alert alert-danger py-2 mb-0" style="display:none;"></div>
        <div data-resumen-ia-result style="display:none;">
            <p data-ia-resumen class="mb-3" style="font-size:14px; white-space:pre-line;"></p>
            <div class="mb-3">
                <span class="badge badge-dark" data-ia-tipo style="display:none; font-size:.85rem;"></span>
                <span class="badge badge-success ml-1" data-ia-estado style="display:none; font-size:.85rem;"></span>
            </div>
            <div data-ia-resultado-wrap class="mb-3" style="display:none;">
                <div class="small text-muted">Resultado</div>
                <div data-ia-resultado style="font-size:14px;"></div>
            </div>
            <div data-ia-lugar-wrap class="mb-3" style="display:none;">
                <div class="small text-muted"><i class="bi bi-geo-alt"></i> Lugar del hecho</div>
                <div data-ia-lugar style="font-size:14px;"></div>
            </div>
            <div data-ia-personas-wrap class="mb-3" style="display:none;">
                <div class="small text-muted mb-1">Personas involucradas</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-secondary"><tr><th>Nombre</th><th>Rol</th><th>DNI</th></tr></thead>
                        <tbody data-ia-personas></tbody>
                    </table>
                </div>
            </div>
            <div data-ia-vehiculos-wrap class="mb-3" style="display:none;">
                <div class="small text-muted mb-1"><i class="bi bi-car-front"></i> Vehículos involucrados</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-secondary"><tr><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Color</th><th>Distintivo</th><th>Dominio</th></tr></thead>
                        <tbody data-ia-vehiculos></tbody>
                    </table>
                </div>
            </div>
            <div data-ia-generado class="small text-muted"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    while (root && !root.querySelector('[data-resumen-ia-body]')) { root = root.previousElementSibling; }
    if (!root) { return; }

    var url        = @json(route('api.cecoco.resumen-ia', $eventoCecoco));
    var btn        = root.querySelector('[data-resumen-ia-btn]');
    var btnRe      = root.querySelector('[data-resumen-ia-refrescar]');
    var btnCopiar  = root.querySelector('[data-resumen-ia-copiar]');
    var textoMensaje = '';
    var body       = root.querySelector('[data-resumen-ia-body]');
    var loading    = root.querySelector('[data-resumen-ia-loading]');
    var estadoText = root.querySelector('[data-resumen-ia-estado-text]');
    var errorBox   = root.querySelector('[data-resumen-ia-error]');
    var result     = root.querySelector('[data-resumen-ia-result]');

    var INTERVALO_MS = 3000;   // cada cuánto consulta el estado
    var MAX_INTENTOS = 100;    // ~5 min de espera máxima
    var intentos = 0;
    var pollTimer = null;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function setText(sel, txt, wrap) {
        var el = root.querySelector(sel);
        if (txt && String(txt).trim() !== '') {
            el.textContent = txt;
            el.style.display = '';
            if (wrap) { root.querySelector(wrap).style.display = ''; }
        } else {
            el.style.display = 'none';
            if (wrap) { root.querySelector(wrap).style.display = 'none'; }
        }
    }

    function render(data) {
        var r = data.resumen || {};
        root.querySelector('[data-ia-resumen]').textContent = r.resumen || 'Sin resumen.';
        setText('[data-ia-tipo]', r.tipo);
        setText('[data-ia-estado]', r.estado_final);
        setText('[data-ia-resultado]', r.resultado, '[data-ia-resultado-wrap]');

        // Lugar del hecho
        var lugar = r.lugar || {};
        var partesLugar = [lugar.direccion, lugar.interseccion, lugar.localidad]
            .filter(function (x) { return x && String(x).trim() !== ''; });
        setText('[data-ia-lugar]', partesLugar.join(' · '), '[data-ia-lugar-wrap]');

        var tbody = root.querySelector('[data-ia-personas]');
        tbody.innerHTML = '';
        var personas = Array.isArray(r.personas) ? r.personas : [];
        if (personas.length) {
            personas.forEach(function (p) {
                tbody.innerHTML += '<tr><td>' + esc(p.nombre) + '</td><td>' + esc(p.rol) + '</td><td>' + esc(p.dni) + '</td></tr>';
            });
            root.querySelector('[data-ia-personas-wrap]').style.display = '';
        } else {
            root.querySelector('[data-ia-personas-wrap]').style.display = 'none';
        }

        var tbodyV = root.querySelector('[data-ia-vehiculos]');
        tbodyV.innerHTML = '';
        var vehiculos = Array.isArray(r.vehiculos) ? r.vehiculos : [];
        if (vehiculos.length) {
            vehiculos.forEach(function (v) {
                tbodyV.innerHTML += '<tr><td>' + esc(v.tipo) + '</td><td>' + esc(v.marca) + '</td><td>' +
                    esc(v.modelo) + '</td><td>' + esc(v.color) + '</td><td>' + esc(v.distintivo) + '</td><td>' +
                    (v.dominio ? '<strong>' + esc(v.dominio) + '</strong>' : '') + '</td></tr>';
            });
            root.querySelector('[data-ia-vehiculos-wrap]').style.display = '';
        } else {
            root.querySelector('[data-ia-vehiculos-wrap]').style.display = 'none';
        }

        var gen = root.querySelector('[data-ia-generado]');
        var iaLabel = 'IA local' + (r.modelo ? ' · modelo ' + r.modelo : '');
        gen.textContent = data.generado_en
            ? ('Generado el ' + data.generado_en + ' · ' + iaLabel)
            : '';

        // Texto listo para enviar por mensaje. Los resúmenes viejos (cacheados antes
        // del cambio de formato) no traen "mensaje": se usa el resumen solo.
        textoMensaje = r.mensaje || r.resumen || '';
        btnCopiar.style.display = textoMensaje ? '' : 'none';

        result.style.display = '';
        btnRe.style.display = '';
        btn.innerHTML = '<i class="bi bi-stars"></i> Ver resumen';
    }

    function copiarMensaje() {
        if (!textoMensaje) { return; }
        var confirmar = function () {
            btnCopiar.innerHTML = '<i class="fas fa-check"></i> Copiado';
            setTimeout(function () { btnCopiar.innerHTML = '<i class="fas fa-copy"></i> Copiar'; }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textoMensaje).then(confirmar);
            return;
        }
        // Fallback para contextos sin HTTPS (red interna)
        var ta = document.createElement('textarea');
        ta.value = textoMensaje;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); confirmar(); } catch (e) {}
        document.body.removeChild(ta);
    }

    function finalizar() {
        loading.style.display = 'none';
        btn.disabled = false;
        btnRe.disabled = false;
    }

    function mostrarError(msg) {
        finalizar();
        errorBox.style.display = '';
        errorBox.textContent = msg || 'No se pudo generar el resumen.';
        btnRe.style.display = '';
    }

    function consultar(refrescar) {
        // El parámetro refrescar solo se envía en la primera consulta (encola/reencola).
        fetch(url + (refrescar ? '?refrescar=1' : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { mostrarError(data.message); return; }

                if (data.estado === 'completado') { finalizar(); render(data); return; }
                if (data.estado === 'error') { mostrarError(data.error); return; }

                // pendiente / procesando → seguir esperando
                estadoText.textContent = (data.estado === 'pendiente')
                    ? 'En cola, esperando al generador…'
                    : 'Generando resumen con IA…';

                intentos++;
                if (intentos > MAX_INTENTOS) {
                    mostrarError('El resumen está tardando más de lo esperado. Probá de nuevo en unos minutos.');
                    return;
                }
                pollTimer = setTimeout(function () { consultar(false); }, INTERVALO_MS);
            })
            .catch(function () {
                // Error de red puntual: reintentar sin abortar (la red/Cloudflare puede fallar una vez).
                intentos++;
                if (intentos > MAX_INTENTOS) {
                    mostrarError('Error de red al consultar el resumen.');
                    return;
                }
                pollTimer = setTimeout(function () { consultar(false); }, INTERVALO_MS);
            });
    }

    function generar(refrescar) {
        if (pollTimer) { clearTimeout(pollTimer); }
        intentos = 0;
        body.style.display = '';
        loading.style.display = '';
        estadoText.textContent = 'Encolando resumen…';
        errorBox.style.display = 'none';
        result.style.display = 'none';
        btnCopiar.style.display = 'none';
        btn.disabled = true; btnRe.disabled = true;

        consultar(refrescar);
    }

    btn.addEventListener('click', function () { generar(false); });
    btnRe.addEventListener('click', function () { generar(true); });
    btnCopiar.addEventListener('click', copiarMensaje);
})();
</script>
@endif
@endcan
