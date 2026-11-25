// No Laravel boot, database, browser profile or network. DTO eligibility contract tests.
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname, '../../public/js/puntoVenta/list-operations.js'), 'utf8');
const all = {print:true,dispatch:true,payments:true,documents:true,annul:true,emit:true};
function runtime(permissions=all) {
    const node={dataset:{permissions:JSON.stringify(permissions)}};
    function $(value) {
        if(typeof value==='function') return; // no ready handlers in this unit suite
        return {text(v){this.value=String(v);return this;},html(){return this.value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}};
    }
    const ctx={window:{},document:{getElementById:()=>node},jQuery:$};
    vm.runInNewContext(code,ctx);
    return ctx.window.SaleListOperations;
}
const active={id:122,code:'VENTA-122',is_annulled:0,can_annul:true,annulment_status:null,type_document:null,pagos_parciales_venta:'n',dispatch_status:'despachado'};
const hooks=(api,row,errors=false)=>Array.from(api.choices(row,errors),x=>x.hook);
test('no-document sale retains emit/annul/dispatch, not payments or NC',()=>{
 assert.deepEqual(hooks(runtime(),active),['generar_comprobante','anular','cambiar_despacho']);
});
test('valid fiscal document never offers a second emission',()=>{
 const h=hooks(runtime(),{...active,type_document:'01',sunat_status:'Aceptada con observaciones'});
 assert.equal(h.includes('generar_comprobante'),false);assert.equal(h.includes('generar_nota_credito'),false);assert.ok(h.includes('generar_nota_credito_parcial'));
});
test('partial-payment history remains accessible at zero balance without mutation permission',()=>{
 assert.deepEqual(hooks(runtime({}),{...active,pagos_parciales_venta:'s',saldo_pendiente:'0.00'}),['pagos_parciales']);
});
test('each capability is gated by its effective permission, no owner bypass',()=>{
 for(const permission of ['emit','annul','dispatch','documents']) {
 const h=hooks(runtime({...all,[permission]:false}),{...active,can_retry_nubefact_files:true});
 const map={emit:'generar_comprobante',annul:'anular',dispatch:'cambiar_despacho',documents:'recuperar_archivos_nubefact'};
 assert.equal(h.includes(map[permission]),false);
 }
});
test('pending annulment offers consult but not a new annulment',()=>{
 const h=hooks(runtime(),{...active,annulment_status:'pending'});assert.ok(h.includes('consultar_anulacion'));assert.equal(h.includes('anular'),false);
});
test('pending/rejected NC and FreeSale suppress incompatible NC generation',()=>{
 const fiscal={...active,type_document:'01',sunat_status:'Aceptada',can_generate_credit_note:true};
 const pending=hooks(runtime(),{...fiscal,has_pending_credit_note:true});assert.ok(pending.includes('consultar_nota_credito'));assert.equal(pending.includes('generar_nota_credito'),false);assert.equal(pending.includes('generar_nota_credito_parcial'),false);
 const rejected=hooks(runtime(),{...fiscal,has_rejected_credit_note:true});assert.ok(rejected.includes('ver_error_nota_credito'));assert.equal(rejected.includes('generar_nota_credito'),false);
 assert.equal(hooks(runtime(),{...fiscal,free_sale:true}).includes('generar_nota_credito_parcial'),false);
});
test('annulled rows cannot expose financial operations',()=>{
 assert.deepEqual(hooks(runtime(),{...active,is_annulled:'1',pagos_parciales_venta:'s',can_retry_nubefact_files:true}),[]);
});
test('SUNAT errors use their own eligibility flags, not normal-list operations',()=>{
 assert.deepEqual(hooks(runtime(),{...active,can_discard_error:true},true),['descartar_error_comprobante','ver_error_sunat']);
 assert.deepEqual(hooks(runtime({}),{...active,can_discard_error:true},true),['ver_error_sunat']);
 assert.deepEqual(hooks(runtime(),{...active,can_discard_error:false},true),['ver_error_sunat']);
});
test('historical diagnostics remain reachable; menu attributes escape hostile DTO content',()=>{
 const api=runtime();assert.ok(hooks(api,{...active,sunat_error_is_discarded:true}).includes('ver_solucion_sunat_error'));
 const html=api.menu({...active,id:'1" onclick="bad()',type_document:'<script>'},false);
 assert.equal(html.includes('<script>'),false);assert.ok(html.includes('&quot;'));assert.ok(html.includes('data-anular'));
});
