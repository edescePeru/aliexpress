// R7.6 renderer checks: no HTTP, database, documents or business mutations.
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
function renderer() {
    function $(value) {
        if (typeof value === 'function') return;
        return {text(v) { this.value = String(v); return this; }, html() {
            return this.value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }};
    }
    const ctx = {window: {}, jQuery: $};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/orderPurchase/list-operations.js'), 'utf8'), ctx);
    return ctx.window.PurchaseListOperations.render;
}
const normal = {id: 4, type: 'n', code: 'OC-00004'};
test('Normal edit needs its permission and explicit active-screen opt-in', () => {
    const render = renderer();
    assert.match(render(normal, ['update_orderPurchaseNormal'], false, true), /href="\/dashboard\/editar\/orden\/compra\/normal\/4"/);
    assert.doesNotMatch(render(normal, ['list_orderPurchaseNormal'], false, true), /data-editar/);
    assert.doesNotMatch(render(normal, ['update_orderPurchaseNormal'], false), /data-editar/);
    assert.doesNotMatch(render({...normal, type: 'e'}, ['update_orderPurchaseNormal'], false, true), /data-editar/);
});
test('Deleted print uses its protected controller URL and list permission', () => {
    const render = renderer();
    const html = render({...normal, id: 1}, ['list_orderPurchaseNormal'], true);
    assert.match(html, /href="\/dashboard\/imprimir\/orden\/compra\/eliminada\/1"/);
    assert.match(html, /fa-print next-row-action-item-icon/);
    assert.match(html, /rel="noopener"/);
    assert.doesNotMatch(render(normal, ['destroy_orderPurchaseNormal'], true), /data-imprimir/);
    assert.doesNotMatch(html, /data-editar|data-delete|data-estado/);
});
test('Existing operational capabilities and icon pattern remain available', () => {
    const p = ['list_orderPurchaseNormal', 'update_orderPurchaseNormal', 'destroy_orderPurchaseNormal'];
    const html = renderer()(normal, p, false, true);
    for (const hook of ['data-ver_orden', 'data-editar', 'data-imprimir', 'data-estado', 'data-delete']) assert.ok(html.includes(hook));
    assert.equal((html.match(/role="menuitem"/g) || []).length, 5);
    assert.equal((html.match(/next-row-action-item-icon/g) || []).length, 5);
    assert.match(html, /dropdown-item text-danger/);
    assert.match(renderer()(normal, p, true), /data-restore/);
    assert.equal(renderer()(normal, [], false, true), '—');
});
test('Row values cannot inject action attributes or an external URL', () => {
    const html = renderer()({...normal, id: '4" onclick="evil()', code: '<img src=x>'}, ['update_orderPurchaseNormal'], false, true);
    assert.doesNotMatch(html, / onclick="|<img/);
    assert.match(html, /normal\/4%22%20onclick%3D%22evil/);
});
