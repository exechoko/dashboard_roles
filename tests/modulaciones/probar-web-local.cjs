const { chromium } = require('playwright');
const path = require('node:path');
const fs = require('node:fs/promises');
const assert = require('node:assert/strict');

const root = path.resolve(__dirname, '../..');
const scratch = path.join(root, '.modulaciones-sandbox');
const profile = path.join(scratch, 'live-browser-' + Date.now());
const option = name => (process.argv.find(arg => arg.startsWith('--' + name + '=')) || '').split('=').slice(1).join('=');
const eventId = option('evento') || '3654695';
assert.match(eventId, /^\d+$/);
const eventUrl = 'http://127.0.0.1:8000/cecoco/' + eventId;
let context;
const privateValues = [];
process.stdin.setEncoding('utf8');
const envCredentials = process.env.MOD_TEST_EMAIL && process.env.MOD_TEST_PASSWORD ? { email: process.env.MOD_TEST_EMAIL, password: process.env.MOD_TEST_PASSWORD } : null;
if (envCredentials) { privateValues.push(envCredentials.email, envCredentials.password); }
delete process.env.MOD_TEST_EMAIL; delete process.env.MOD_TEST_PASSWORD;
const credentialsInput = envCredentials ? Promise.resolve(envCredentials) : new Promise(resolve => process.stdin.once('data', text => {
    const credentials = JSON.parse(text);
    privateValues.push(credentials.email, credentials.password);
    resolve(credentials);
}));
const trace = [];
const active = new Set();
const startedAt = new Map();
let maxActive = 0, audioRequests = 0;
function isSearch(url) { return url.includes('/api/cecoco/eventos/' + eventId + '/modulaciones'); }

(async () => {
    await fs.mkdir(profile, { recursive: true });
    context = await chromium.launchPersistentContext(profile, {
        channel: 'chrome', headless: process.argv.includes('--headless'), viewport: { width: 1366, height: 900 }
    });
    const page = context.pages()[0] || await context.newPage();
    page.on('request', request => {
        if (isSearch(request.url())) { active.add(request); startedAt.set(request, Date.now()); maxActive = Math.max(maxActive, active.size); }
        if (request.url().includes('/modulacion/stream')) { audioRequests++; }
    });
    page.on('requestfinished', request => active.delete(request));
    page.on('requestfailed', request => active.delete(request));
    page.on('response', async response => {
        if (!isSearch(response.url())) { return; }
        try {
            const data = await response.json();
            const entry = { http: response.status(), estado: data.estado, total: data.total,
                revision: data.revision, hayMas: data.hayMas, duracion_ms: Date.now() - startedAt.get(response.request()) };
            assert.ok((response.headers()['cache-control'] || '').includes('no-store'));
            trace.push(entry); console.log('BUSQUEDA ' + JSON.stringify(entry));
        } catch { console.log('BUSQUEDA respuesta no JSON'); }
    });
    await page.goto(eventUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
    if (new URL(page.url()).pathname.endsWith('/login')) {
        console.log(envCredentials ? 'AUTENTICACION_AUTOMATICA con cuenta de prueba' : 'INICIAR_SESION en la ventana de Chrome de prueba.');
        const credentials = await Promise.race([
            credentialsInput,
            page.waitForURL(url => !url.pathname.endsWith('/login'), { timeout: 300000 }).then(() => null)
        ]);
        if (credentials) {
            await page.locator('[name="email"]').fill(credentials.email);
            await page.locator('[name="password"]').fill(credentials.password);
            await Promise.all([
                page.waitForURL(url => !url.pathname.endsWith('/login'), { timeout: 30000 }),
                page.locator('button[type="submit"]').click()
            ]);
        }
        await page.goto(eventUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
    }
    await page.locator('#btnModulaciones').waitFor({ state: 'visible', timeout: 20000 });
    const record = await page.evaluate(() => {
        const rows = Array.from(document.querySelectorAll('tr'));
        const field = label => rows.find(row => row.querySelector('th')?.textContent.trim() === label)?.querySelector('td')?.textContent.trim();
        return { expediente: field('Nº Expediente:'), fecha: field('Fecha/Hora:') };
    });
    if (option('expediente')) { assert.equal(record.expediente, option('expediente')); }
    if (option('fecha')) { assert.ok(record.fecha.startsWith(option('fecha'))); }
    console.log('REGISTRO ' + JSON.stringify({ id_interno: eventId, ...record }));
    await page.locator('#btnModulaciones').click();
    await page.locator('#modalModulaciones').waitFor({ state: 'visible' });
    await page.waitForFunction(() => { const modal = window.jQuery('#modalModulaciones').data('bs.modal'); return modal && !modal._isTransitioning; });
    const initial = await page.evaluate(() => ({
        visible: getComputedStyle(document.getElementById('modulaciones-lista')).display !== 'none',
        filterDisabled: document.getElementById('modulaciones-filtro').disabled,
        empty: getComputedStyle(document.getElementById('modulaciones-empty')).display !== 'none',
        estado: window.modSearch && window.modSearch.estado
    }));
    if (!['completa', 'limite_alcanzado'].includes(initial.estado)) {
        assert.equal(initial.visible, false); assert.equal(initial.filterDisabled, true); assert.equal(initial.empty, false);
    }
    console.log('INICIAL ' + JSON.stringify(initial));
    // Close and reopen while preserving the server-owned search.
    await page.locator('#modalModulaciones [data-dismiss="modal"]').first().click();
    await page.locator('#modalModulaciones').waitFor({ state: 'hidden' });
    assert.equal(await page.evaluate(() => window.modOpen), false);
    await page.locator('#btnModulaciones').click();
    await page.waitForFunction(() => window.modSearch && (['completa', 'limite_alcanzado'].includes(window.modSearch.estado) || (window.modSearch.estado === 'pausada' && !document.getElementById('mod-search-continue').hidden)), null, { timeout: 150000 });
    const result = await page.evaluate(() => ({
        estado: window.modSearch.estado, total: window.modSearch.total,
        message: document.getElementById('mod-search-message').textContent,
        filterDisabled: document.getElementById('modulaciones-filtro').disabled,
        visible: getComputedStyle(document.getElementById('modulaciones-lista')).display !== 'none',
        empty: getComputedStyle(document.getElementById('modulaciones-empty')).display !== 'none',
        warning: !document.getElementById('mod-search-limit').hidden,
        progress: document.getElementById('mod-search-bar').style.width,
        players: document.querySelectorAll('#modulaciones-lista audio').length,
        downloads: document.querySelectorAll('#modulaciones-lista a[download]').length
    }));
    if (option('total')) { assert.equal(result.estado, 'completa'); assert.equal(result.total, Number(option('total'))); }
    if (result.estado === 'pausada') {
        assert.equal(result.visible, false); assert.equal(result.empty, false); assert.equal(result.filterDisabled, true);
    } else {
        assert.equal(result.filterDisabled, false);
        if (result.estado === 'limite_alcanzado') {
            assert.equal(result.warning, true); assert.notEqual(result.progress, '100%');
            assert.equal(result.message.includes('Búsqueda completa'), false);
        } else { assert.equal(result.progress, '100%'); }
        if (result.total > 0) {
            await page.locator('#modulaciones-filtro').fill('movil_inexistente_fixture');
            assert.equal(await page.locator('#modulaciones-lista .modulacion-card:visible').count(), 0);
            await page.locator('#modulaciones-filtro').fill('');
        }
    }
    assert.equal(audioRequests, 0, 'El listado no debe descargar audios antes de una acción del usuario');
    console.log('RESULTADO ' + JSON.stringify({ ...result, maxActive, audioRequests, requests: trace.length }));
    if (process.argv.includes('--audio') && result.estado === 'completa' && result.players > 0) {
        const firstAudio = page.locator('#modulaciones-lista audio').first();
        const responsePromise = page.waitForResponse(response => response.url().includes('/modulacion/stream'), { timeout: 40000 });
        const play = firstAudio.evaluate(audio => audio.play().then(() => true).catch(() => false));
        const response = await responsePromise;
        const played = await play;
        const listened = await firstAudio.evaluate(audio => audio.closest('.modulacion-card').classList.contains('mod-escuchada'));
        await firstAudio.evaluate(audio => audio.pause());
        const downloadUrl = await page.locator('#modulaciones-lista a[download]').first().getAttribute('href');
        const downloaded = await page.request.get(downloadUrl, { timeout: 40000 });
        const downloadBytes = (await downloaded.body()).length;
        const audioResult = { playback_http: response.status(), played, listened,
            download_http: downloaded.status(), download_type: downloaded.headers()['content-type'], download_bytes: downloadBytes };
        console.log('AUDIO ' + JSON.stringify(audioResult));
        assert.equal(response.status(), 200); assert.equal(played, true); assert.equal(listened, true);
        assert.equal(downloaded.status(), 200); assert.ok(audioResult.download_type.includes('audio')); assert.ok(downloadBytes > 0);
    }
    const snapshot = await page.evaluate(() => window.modSearch);
    await page.locator('#modalModulaciones [data-dismiss="modal"]').first().click();
    await page.locator('#modalModulaciones').waitFor({ state: 'hidden' });
    if (process.argv.includes('--estados') && snapshot.modulaciones) {
        const limited = { ...snapshot, estado: 'limite_alcanzado', total: 2000, hayMas: false,
            message: 'Se alcanzó el límite de 2000 modulaciones. La búsqueda puede estar incompleta y algunos móviles pueden no aparecer.' };
        await page.route('**/api/cecoco/eventos/' + eventId + '/modulaciones*', route => route.fulfill({ json: limited, headers: { 'Cache-Control': 'private, no-store' } }));
        await page.locator('#btnModulaciones').click();
        await page.locator('#mod-search-limit').waitFor({ state: 'visible' });
        assert.equal(await page.locator('#mod-search-bar').evaluate(bar => bar.style.width), '35%');
        assert.ok((await page.locator('#mod-search-limit').innerText()).includes('algunos móviles pueden no aparecer'));
        await page.locator('#modulaciones-filtro').fill('movil_fixture_inexistente');
        assert.equal(await page.locator('#mod-search-limit').isVisible(), true);
        assert.equal((await page.locator('#mod-search-message').innerText()).includes('Búsqueda completa'), false);
        console.log('LIMITE_SIMULADO aviso persistente y barra sin completar: correcto');
        await page.waitForFunction(() => { const modal = window.jQuery('#modalModulaciones').data('bs.modal'); return modal && !modal._isTransitioning; });
        await page.locator('#modalModulaciones [data-dismiss="modal"]').first().click();
        await page.locator('#modalModulaciones').waitFor({ state: 'hidden' });
    }
})().catch(error => {
    let safeMessage = error.message;
    privateValues.forEach(value => { if (value) { safeMessage = safeMessage.split(value).join('[omitido]'); } });
    console.error('PRUEBA_FALLIDA ' + safeMessage); process.exitCode = 1;
}).finally(async () => {
    if (context) { await context.close().catch(() => {}); }
    const resolved = path.resolve(profile);
    if (!resolved.startsWith(path.resolve(scratch) + path.sep)) { throw new Error('Cleanup outside scratch'); }
    await fs.rm(resolved, { recursive: true, force: true });
});
