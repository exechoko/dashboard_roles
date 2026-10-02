const { test, expect } = require('playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/eventos-cecoco/show.blade.php'), 'utf8');
const modalStart = blade.indexOf('<div class="modal fade" id="modalModulaciones"');
const scriptStart = blade.indexOf('<script>', modalStart);
const modal = blade.slice(modalStart, scriptStart).replace(/\{\{[\s\S]*?\}\}/g, '73');
const helpersStart = blade.indexOf('function duracionASegundos');
const helpers = blade.slice(helpersStart, blade.indexOf('</script>', helpersStart));
const script = blade.slice(scriptStart + 8, blade.indexOf('</script>', scriptStart))
    .replace(/@php[\s\S]*?@endphp/g, '').replace(/@json\(\$unidadesEvento\)/g, '["M1"]')
    .replace(/var MOD_URL_BASE = .*?;/, 'var MOD_URL_BASE = "/api/modulaciones";')
    .replace(/\{\{[\s\S]*?\}\}/g, '73');
const fixture = `<meta name="csrf-token" content="dummy"><style>.progress{height:10px}.progress-bar{height:10px;background:blue}</style>${modal}<script>
window.modalHandlers = {};
window.$ = function() { return { modal: function() {}, on: function(name, fn) { modalHandlers[name] = fn; } }; };
${helpers}\n${script}</script>`;
const token = 'a'.repeat(64);
function status(estado, total = 0, modulaciones) {
    return { success: true, estado, busqueda_id: token, revision: 1, total, limite: 2000,
        hayMas: !['completa', 'limite_alcanzado'].includes(estado), avance: 1,
        ventana: { desde: '2026-10-02 10:00:00', hasta: '2026-10-02 11:00:00' }, fuente: 'grabador', reintentar_en: 1.5,
        message: estado === 'completa' ? `Búsqueda completa: ${total} modulaciones` : estado === 'limite_alcanzado'
            ? 'Se alcanzó el límite de 2000 modulaciones. La búsqueda puede estar incompleta y algunos móviles pueden no aparecer.'
            : estado === 'pausada' ? 'Búsqueda pausada; conservamos el avance' : 'Buscando modulaciones de la ventana horaria…',
        ...(modulaciones ? { modulaciones } : {}) };
}
async function open(page, responder) {
    await page.route('http://fixture.invalid/**', async route => {
        if (route.request().url().endsWith('/fixture')) { return route.fulfill({ contentType: 'text/html', body: fixture }); }
        if (route.request().url().includes('/api/modulaciones')) { return route.fulfill({ json: responder(route.request()) }); }
        return route.abort();
    });
    await page.goto('http://fixture.invalid/fixture');
    await page.evaluate(() => abrirModulaciones());
}

test('buscando y pausada mantienen inaccesibles lista y filtro, sin vacío', async ({ page }) => {
    await open(page, () => status('pausada', 999));
    await expect(page.locator('#mod-search-message')).toContainText('Reintentando', { timeout: 25000 });
    await expect(page.locator('#modulaciones-lista')).toBeHidden();
    await expect(page.locator('#modulaciones-empty')).toBeHidden();
    await expect(page.locator('#modulaciones-filtro')).toBeDisabled();
    await expect(page.locator('#mod-search-message')).toContainText('conservamos el avance');
    await expect(page.locator('#mod-search-continue')).toHaveCount(0);
});

test('el límite conserva siempre la advertencia y no llena la barra', async ({ page }) => {
    const item = { itemid: '1_20261002100000', recurso: 'M1', fechaInicio: '2026-10-02 10:00:00', duracion: '3', url: '/audio?itemid=1' };
    await open(page, () => status('limite_alcanzado', 2000, [item]));
    await expect(page.locator('#modulaciones-lista')).toBeVisible();
    await expect(page.locator('#modulaciones-filtro')).toBeEnabled();
    await expect(page.locator('#mod-search-limit')).toContainText('algunos móviles pueden no aparecer');
    await expect(page.locator('#mod-search-bar')).toHaveAttribute('style', 'width: 35%;');
    await page.locator('#modulaciones-filtro').fill('no existe');
    await expect(page.locator('#mod-search-limit')).toBeVisible();
    await expect(page.locator('#mod-search-message')).not.toContainText('Búsqueda completa');
});

test('solo completar muestra vacío y llena la barra', async ({ page }) => {
    await open(page, () => status('completa', 0, []));
    await expect(page.locator('#modulaciones-empty')).toBeVisible();
    await expect(page.locator('#mod-search-message')).toHaveText('Búsqueda completa: 0 modulaciones');
    await expect(page.locator('#mod-search-bar')).toHaveAttribute('style', 'width: 100%;');
});

test('no solicita audio al listar; reintento, marcas y cierre conservan el estado', async ({ page }) => {
    const item = { itemid: '1_20261002100000', recurso: '<img src=x onerror=alert(1)> M1', fechaInicio: '2026-10-02 10:00:00', duracion: '3', url: '/audio?itemid=1' };
    let requests = 0;
    page.on('request', r => { if (r.url().includes('/audio?')) { requests++; } });
    await open(page, () => status('completa', 1, [item]));
    await expect(page.locator('audio')).toHaveAttribute('preload', 'none');
    await expect(page.locator('#modulaciones-lista img')).toHaveCount(0);
    expect(requests).toBe(0);
    await page.evaluate(() => {
        const audio = document.querySelector('audio');
        audio.dispatchEvent(new Event('play'));
        audio.querySelector('source').dispatchEvent(new Event('error'));
    });
    await expect(page.locator('.modulacion-card')).toHaveClass(/mod-escuchada/);
    await expect(page.getByRole('button', { name: 'Reintentar audio' })).toBeVisible();
    await expect(page.locator('#modulaciones-lista a[download]')).toHaveAttribute('href', /download=1/);
    await page.evaluate(() => modalHandlers['hide.bs.modal']());
    expect(await page.evaluate(() => modOpen)).toBe(false);
    await page.evaluate(() => abrirModulaciones());
    await expect(page.locator('.modulacion-card')).toHaveClass(/mod-escuchada/);
});
