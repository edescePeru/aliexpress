const fs=require('fs'),path=require('path'),assert=require('assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const root=path.resolve(__dirname,'../..'),read=p=>fs.readFileSync(path.join(root,p),'utf8');
const blade=read('resources/views/material/stockItems_items.blade.php');
const table=blade.match(/<table id="dynamic-table"[\s\S]*?<\/table>/)[0];
const modal=blade.match(/<div class="modal fade next-aux-modal"[\s\S]*?@endcan/)[0].replace('@endcan','').replace('@csrf','<input name="_token" value="SIMULATED-CSRF" type="hidden">');
const report={simulated:true,cases:[],requests:[],errors:[]};
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH});
 try{for(const permitted of [false,true]){
 const page=await browser.newPage();page.on('pageerror',e=>report.errors.push(e.message));
 await page.route('**/*',async route=>{
  const req=route.request(),url=new URL(req.url());report.requests.push({method:req.method(),path:url.pathname});assert.equal(req.method(),'GET','No mutation may leave browser');
  if(url.pathname==='/fixture')return route.fulfill({contentType:'text/html',body:'<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body data-operational-context="0"><main class="container-fluid"><div id="items-read-error" class="d-none"></div>'+table+'</main>'+(permitted?modal:'')+'</body></html>'});
  if(url.pathname==='/fixture/items'){
   const start=Number(url.searchParams.get('start')),draw=Number(url.searchParams.get('draw'));
   const empty=url.searchParams.get('search[value]')==='NO_MATCH';
   return route.fulfill({json:{draw,recordsTotal:30,recordsFiltered:empty?0:30,data:empty?[]:Array.from({length:start?5:25},(_,i)=>({id:start+i+901,material_description:'SIMULATED MATERIAL '+('LONG NAME '.repeat(14)),code:'SIM-'+(start+i+901),price:'12.50',percentage:'20',state:'entered',state_item:'entered',location:{description:'SIMULATED LOCATION'}}))}});
  }
  const file=path.resolve(root,'public','.'+url.pathname);assert(file.startsWith(path.join(root,'public')+path.sep)&&fs.existsSync(file),url.pathname);
  await route.fulfill({body:fs.readFileSync(file),contentType:({'.js':'text/javascript','.css':'text/css','.woff2':'font/woff2','.woff':'font/woff','.ttf':'font/ttf'})[path.extname(file)]||'application/octet-stream'});
 });
 await page.goto('http://qa.invalid/fixture');
 for(const f of ['admin/dist/css/adminlte.min.css','admin/dist/css/venti.min.css','admin/dist/css/venti-next.css','admin/plugins/fontawesome-free/css/all.min.css','admin/plugins/datatables-bs4/css/dataTables.bootstrap4.css'])await page.addStyleTag({url:'/'+f});
 for(const f of ['admin/plugins/jquery/jquery.min.js','admin/plugins/bootstrap/js/bootstrap.bundle.min.js','admin/plugins/datatables/jquery.dataTables.js','admin/plugins/datatables-bs4/js/dataTables.bootstrap4.js','js/layout/admin2.js'])await page.addScriptTag({url:'/'+f});
 await page.evaluate(p=>{window.canEditItemCode=p;window.stockItemItemsDataUrl='/fixture/items';window.stockItemCodeUrl='/fixture/items/:id/code';},permitted);
 await page.addScriptTag({url:'/js/material/stockItems_items.js'});
 await page.waitForSelector('#dynamic-table tbody .item-detail-toggle',{state:'attached'});
 await page.waitForTimeout(300);
 // Force horizontal stress without introducing product CSS or extra columns.
 await page.addStyleTag({content:'#dynamic-table {min-width:2400px}'});
 for(const width of [375,768,1024,1440,1920]){
  await page.setViewportSize({width,height:900});await page.waitForTimeout(150);
  for(const position of [0,.5,1]){
   const geometry=await page.evaluate(p=>{const t=document.querySelector('#dynamic-table'),w=t.closest('.table-responsive');w.scrollLeft=(w.scrollWidth-w.clientWidth)*p;const c=t.tBodies[0].rows[0].lastElementChild.getBoundingClientRect(),h=t.tHead.rows[0].lastElementChild.getBoundingClientRect(),r=w.getBoundingClientRect();return {visible:c.left>=r.left-1&&c.right<=r.right+1,aligned:Math.abs(c.right-h.right)<1,overflow:document.documentElement.scrollWidth>innerWidth+1};},position);
   assert.deepEqual(geometry,{visible:true,aligned:true,overflow:false});
   if(permitted){await page.locator('#dynamic-table .next-row-actions-trigger').first().click();const r=await page.locator('body > .next-row-actions-menu').boundingBox();assert(r&&r.x>=0&&r.x+r.width<=width+1);await page.locator('body > .next-row-actions-menu .item-detail-toggle').click();}
   else await page.locator('#dynamic-table .item-detail-toggle').first().click();
   assert(await page.locator('#dynamic-table').getByText('SIMULATED LOCATION',{exact:true}).isVisible());
   if(permitted)await page.locator('#dynamic-table .next-row-actions-trigger').first().click();
   await page.locator(permitted?'body > .next-row-actions-menu .item-detail-toggle':'#dynamic-table .item-detail-toggle').first().click();
   assert.equal(await page.locator('#dynamic-table').getByText('SIMULATED LOCATION',{exact:true}).count(),0);
   report.cases.push({permitted,width,position,...geometry});
  }
 }
 assert.equal(await page.locator('#dynamic-table tbody tr:first-child td:not(:last-child) button').count(),0);
 assert.equal(await page.locator('#dynamic-table tbody tr:first-child .item-detail-toggle').count(),1);
 assert.equal(await page.locator('#dynamic-table tbody tr:first-child .btn-edit-item-code').count(),permitted?1:0);
 if(permitted){
  await page.locator('#dynamic-table .next-row-actions-trigger').first().click();await page.locator('body > .next-row-actions-menu .btn-edit-item-code').click();
  await page.waitForSelector('#modalEditarCodigoItem.show');assert.equal(await page.locator('#edit_item_id').inputValue(),'901');assert.equal(await page.locator('#edit_item_code').inputValue(),'SIM-901');
  await page.locator('#modalEditarCodigoItem button[data-dismiss="modal"]').first().click();await page.waitForSelector('#modalEditarCodigoItem.show',{state:'hidden'});
 }
 await page.evaluate(()=>$('#dynamic-table').DataTable().page(1).draw(false));await page.waitForFunction(()=>$('#dynamic-table').DataTable().row(0).data().id===926);await page.waitForTimeout(150);
 assert.equal(await page.locator('#dynamic-table tbody tr:first-child .item-detail-toggle').getAttribute('data-item-id'),'926');
 assert.equal(await page.locator('#dynamic-table tbody tr:first-child .next-sticky-action-cell').count(),1);
 await page.evaluate(()=>$('#dynamic-table').DataTable().search('NO_MATCH').draw());await page.waitForSelector('#dynamic-table .dataTables_empty');
 assert.equal(await page.locator('#dynamic-table tbody .item-detail-toggle').count(),0);
 await page.evaluate(()=>$('#dynamic-table').DataTable().search('').draw());await page.waitForSelector('#dynamic-table tbody .item-detail-toggle',{state:'attached'});
 await page.waitForTimeout(300);
 if(process.env.QA_OUTPUT){fs.mkdirSync(process.env.QA_OUTPUT,{recursive:true});await page.setViewportSize({width:375,height:900});await page.screenshot({path:path.join(process.env.QA_OUTPUT,'items-'+permitted+'.png')});}
 await page.close();
 }
 assert.deepEqual(report.errors,[]);
 if(process.env.QA_OUTPUT)fs.writeFileSync(path.join(process.env.QA_OUTPUT,'items-evidence.json'),JSON.stringify(report,null,2));
 console.log('PASS: 30 Items scroll cases, permission variants, detail open/close, edit modal, pagination/filter/redraw; GET intercepted only.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
