/* Isolated synthetic browser QA. Every request is intercepted; no application/DB/provider access. */
const fs = require('fs');
const path = require('path');
const assert = require('assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../..');
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const output = process.env.QA_OUTPUT;
const evidence = { simulated: true, pagination: [], responsive: [], requests: [], errors: [] };
const requestUrl = 'http://qa.invalid/dashboard/traslados/almacenes/crear?source_warehouse_id=71&stock_item_id=91&form_tenant_id=11&form_company_id=21&form_branch_id=31';
const snapshot = { product: { id: 91, text: 'SIMULATED product' }, checked_at: 'SIMULATED', groups: [
    { key: 'general', title: 'SIMULATED warehouse group', warehouses: [
        { id: 71, name: 'Origen simulado', on_hand: '5.00', reserved: '0.00', available: '5.00', incoming: '0.00', outgoing: '0.00', requires_review: false, request_url: requestUrl },
        { id: 72, name: 'Sin autorización simulada', available: '0.00', requires_review: true, request_url: null },
        { id: 73, name: 'Contrato sin URL', available: '5.00', requires_review: false }
    ] }, { key: 'empty', title: 'Grupo vacío', warehouses: [] }
] };
const asset = (url, tag = 'script') => tag === 'script' ? `<script src="/${url}"></script>` : `<link rel="stylesheet" href="/${url}">`;
const css = ['admin/dist/css/adminlte.min.css', 'admin/dist/css/venti.min.css', 'admin/dist/css/venti-next.css', 'admin/plugins/fontawesome-free/css/all.min.css', 'admin/plugins/select2/css/select2.min.css'].map(p => asset(p, 'link')).join('');
const vendor = ['admin/plugins/jquery/jquery.min.js', 'admin/plugins/bootstrap/js/bootstrap.bundle.min.js', 'js/layout/admin2.js'].map(p => asset(p)).join('');
function shell(body, script) { return `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${css}</head><body data-operational-context="0"><main class="container-fluid">${body}</main>${vendor}${script}</body></html>`; }
const wa = shell(`<section class="next-operational-list"><form id="wa-search" data-stock-url="/fixture/stock" data-products-url="/fixture/products"><input name="form_tenant_id" value="11" type="hidden"><input name="form_company_id" value="21" type="hidden"><input name="form_branch_id" value="31" type="hidden"><select id="wa-product"><option value="91">SIMULATED</option></select><button id="wa-refresh">Actualizar</button></form><div id="wa-error" class="d-none"></div><div id="wa-loading"></div><div id="wa-empty"></div><div id="wa-summary"><span id="wa-selected"></span><span id="wa-checked"></span></div><div id="wa-groups"></div></section>`, asset('admin/plugins/select2/js/select2.full.min.js') + asset('js/warehouseAvailability/index.js'));
function profit(detail) {
    const blade = read(`resources/views/ganancia/${detail ? 'indexDetail' : 'index'}.blade.php`);
    const templates = [...blade.matchAll(/<template\b[\s\S]*?<\/template>/g)].map(m => m[0]).join('');
    return shell(`<input id="permissions" value="[]" type="hidden"><input id="ganancia_id" value="${detail ? 88 : ''}" type="hidden"><span id="numberItems"></span><table><tbody id="body-table"></tbody></table><span id="textPagination"></span><ul id="pagination" class="pagination"></ul>${templates}`, asset('js/ganancias/index.js'));
}
(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const context = await browser.newContext();
        const page = await context.newPage();
        page.on('pageerror', e => evidence.errors.push(e.message));
        await context.route('**/*', async route => {
            const req = route.request(), url = new URL(req.url());
            evidence.requests.push({ method: req.method(), path: url.pathname, query: url.search });
            assert.equal(req.method(), 'GET', 'No mutation requests allowed');
            if (url.pathname === '/availability') return route.fulfill({ contentType: 'text/html', body: wa });
            if (url.pathname === '/profit' || url.pathname === '/detail') return route.fulfill({ contentType: 'text/html', body: profit(url.pathname === '/detail') });
            if (url.pathname === '/fixture/stock') return route.fulfill({ json: snapshot });
            if (req.url() === requestUrl) return route.fulfill({ contentType: 'text/html', body: '<h1>SIMULATED destination intercepted, not an authorized HTTP certification</h1>' });
            if (url.pathname.includes('/get/data/ganancia')) {
                const currentPage = Number(url.pathname.split('/').pop());
                return route.fulfill({ json: { data: [], pagination: { currentPage, totalPages: 10, totalFilteredRecords: 0, startRecord: 0, endRecord: 0 } } });
            }
            const file = path.resolve(root, 'public', '.' + url.pathname);
            if (file.startsWith(path.join(root, 'public') + path.sep) && fs.existsSync(file) && fs.statSync(file).isFile()) return route.fulfill({ body: fs.readFileSync(file), contentType: ({ '.js': 'application/javascript', '.css': 'text/css', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf' })[path.extname(file)] || 'application/octet-stream' });
            throw new Error('Unexpected request: ' + req.url());
        });
        for (const width of [375, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('http://qa.invalid/availability');
            await page.waitForFunction(() => !!window.jQuery && !!jQuery('#wa-product').data('select2'));
            await page.evaluate(() => jQuery('#wa-product').trigger('change'));
            await page.waitForSelector('[data-warehouse-id="71"]');
            assert.equal(await page.locator('.next-row-actions-trigger').count(), 1);
            assert.equal(await page.locator('[colspan="8"]').count(), 1);
            await page.locator('.next-row-actions-trigger').click();
            await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth, null, {timeout:2000}).catch(async e => {
                console.log(await page.evaluate(() => ({width:innerWidth,scroll:document.documentElement.scrollWidth, nodes:[...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>innerWidth+1).map(e=>[e.tagName,e.className,e.getBoundingClientRect().right,getComputedStyle(e).overflow])})));
                if(output) await page.screenshot({path:path.join(output,'r82a-debug.png')}); throw e;
            });
            const link = page.getByRole('link', { name: 'Solicitar traslado' });
            assert.equal(await link.getAttribute('href'), requestUrl);
            assert.equal(await link.locator('.fa-exchange-alt.next-row-action-item-icon').count(), 1);
            const box = await link.boundingBox();
            assert.ok(box && box.x >= 0 && box.x + box.width <= width + 1);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, JSON.stringify(await page.evaluate(() => [...document.querySelectorAll('body *')].filter(e => e.getBoundingClientRect().right > innerWidth + 1 && !e.closest('.table-responsive')).map(e => [e.tagName, e.className, e.getBoundingClientRect().right]))));
            if (output) await page.screenshot({ path: path.join(output, `r82a-availability-${width}.png`) });
            await page.keyboard.press('Escape');
            assert.equal(await link.isVisible(), false);
            await page.locator('.next-row-actions-trigger').click();
            await link.click();
            await page.waitForURL(requestUrl);
            evidence.responsive.push({ width, action: true, absentWithoutUrl: true, contextExact: true, globalOverflow: false, navigation: 'INTERCEPTED' });
        }
        for (const surface of ['profit', 'detail']) {
            await page.goto('http://qa.invalid/' + surface);
            await page.waitForFunction(() => document.querySelector('#textPagination').textContent.includes('Mostrando'));
            for (const [currentPage, totalPages, expected] of [[1,10,[1,2,3,10]], [4,10,[1,2,3,4,5,6,10]], [10,10,[1,8,9,10]], [1,1,[]]]) {
                const actual = await page.evaluate(({currentPage,totalPages}) => {
                    renderDataGanancias({data:[],pagination:{currentPage,totalPages,totalFilteredRecords:100,startRecord:1,endRecord:10}});
                    const items = [...document.querySelectorAll('#pagination li')];
                    return { numbers: items.filter(n => n.hasAttribute('data-active')).map(n => Number(n.querySelector('[data-item]').dataset.item)), active: items.filter(n => n.classList.contains('active')).map(n => Number(n.textContent.trim())), previous: document.querySelector('#pagination .previous [data-item]')?.dataset.item || null, next: document.querySelector('#pagination .next [data-item]')?.dataset.item || null, disabled: items.filter(n => n.classList.contains('disabled')).every(n => !n.querySelector('[data-item]')) };
                }, {currentPage,totalPages});
                assert.deepEqual(actual.numbers, expected);
                assert.deepEqual(actual.active, totalPages > 1 ? [currentPage] : []);
                assert.equal(actual.previous, currentPage > 1 ? String(currentPage-1) : null);
                assert.equal(actual.next, currentPage < totalPages ? String(currentPage+1) : null);
                assert.equal(actual.disabled, true);
                evidence.pagination.push({surface,currentPage,totalPages,...actual});
            }
            await page.evaluate(() => renderDataGanancias({data:[],pagination:{currentPage:4,totalPages:10,totalFilteredRecords:100,startRecord:31,endRecord:40}}));
            const [request] = await Promise.all([page.waitForRequest(r => r.url().includes('/get/data/ganancia') && r.url().includes('/5')), page.locator('#pagination [data-active] [data-item="5"]').click()]);
            const clicked = new URL(request.url());
            assert.equal(clicked.pathname, surface === 'detail' ? '/dashboard/get/data/ganancia/details/V2/5' : '/dashboard/get/data/ganancias/V2/5');
            assert.equal(clicked.searchParams.get('ganancia_id'), surface === 'detail' ? '88' : null);
        }
        assert.deepEqual(evidence.errors, []);
        evidence.status = 'PASS';
        if (output) fs.writeFileSync(path.join(output, 'r82a-evidence.json'), JSON.stringify(evidence, null, 2));
        console.log(JSON.stringify({status:evidence.status, paginationCases:evidence.pagination.length, numericNavigation:2, responsive:evidence.responsive, pageErrors:evidence.errors}));
    } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode = 1; });
