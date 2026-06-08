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
        </div>
    </div>
    <div class="card-body" data-resumen-ia-body style="display:none;">
        <div data-resumen-ia-loading class="text-center py-3" style="display:none;">
            <i class="bi bi-arrow-repeat ia-spin"></i> Generando resumen con IA…
            <div class="small text-muted mt-1">Puede tardar hasta ~1 o 2 minutos en eventos extensos.</div>
        </div>
        <div data-resumen-ia-error class="alert alert-danger py-2 mb-0" style="display:none;"></div>
        <div data-resumen-ia-result style="display:none;">
            <p data-ia-resumen class="mb-3" style="font-size:14px;"></p>
            <div class="mb-3">
                <span class="badge badge-dark" data-ia-tipo style="display:none; font-size:.85rem;"></span>
                <span class="badge badge-success ml-1" data-ia-estado style="display:none; font-size:.85rem;"></span>
            </div>
            <div data-ia-resultado-wrap class="mb-3" style="display:none;">
                <div class="small text-muted">Resultado</div>
                <div data-ia-resultado style="font-size:14px;"></div>
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
            <div data-ia-generado class="small text-muted"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    while (root && !root.querySelector('[data-resumen-ia-body]')) { root = root.previousElementSibling; }
    if (!root) { return; }

    var url      = @json(route('api.cecoco.resumen-ia', $eventoCecoco));
    var btn      = root.querySelector('[data-resumen-ia-btn]');
    var btnRe    = root.querySelector('[data-resumen-ia-refrescar]');
    var body     = root.querySelector('[data-resumen-ia-body]');
    var loading  = root.querySelector('[data-resumen-ia-loading]');
    var errorBox = root.querySelector('[data-resumen-ia-error]');
    var result   = root.querySelector('[data-resumen-ia-result]');

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

        var gen = root.querySelector('[data-ia-generado]');
        gen.textContent = data.generado_en
            ? ('Generado el ' + data.generado_en + (data.cacheado ? ' (cacheado)' : '') + ' · IA local')
            : '';

        result.style.display = '';
        btnRe.style.display = '';
        btn.innerHTML = '<i class="bi bi-stars"></i> Ver resumen';
    }

    function generar(refrescar) {
        body.style.display = '';
        loading.style.display = '';
        errorBox.style.display = 'none';
        result.style.display = 'none';
        btn.disabled = true; btnRe.disabled = true;

        fetch(url + (refrescar ? '?refrescar=1' : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                loading.style.display = 'none';
                btn.disabled = false; btnRe.disabled = false;
                if (!data.success) {
                    errorBox.style.display = '';
                    errorBox.textContent = data.message || 'No se pudo generar el resumen.';
                    return;
                }
                render(data);
            })
            .catch(function () {
                loading.style.display = 'none';
                btn.disabled = false; btnRe.disabled = false;
                errorBox.style.display = '';
                errorBox.textContent = 'Error de red al generar el resumen.';
            });
    }

    btn.addEventListener('click', function () { generar(false); });
    btnRe.addEventListener('click', function () { generar(true); });
})();
</script>
@endif
@endcan
