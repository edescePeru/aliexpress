/* Synthetic, isolated rendering only. No application, database or provider requests. */
const fs = require('fs'), path = require('path'), assert = require('assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../..');
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const out = process.env.QA_OUTPUT;
const menu = '<div class="dropdown next-row-actions"><button class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-label="Acciones simuladas"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><button class="dropdown-item" data-simulated-action><i class="fas fa-eye next-row-action-item-icon"></i>Ver detalle simulado</button></div></div>';
const report = { simulated: true, viewInventory: [], cases: [], requests: [] };
(async () => {
 const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH });
 try {
  const page = await browser.newPage();
  await page.route('**/*', async route => {
   const url = new URL(route.request().url()); report.requests.push(url.pathname);
   assert.equal(route.request().method(), 'GET');
   if (url.pathname === '/fixture') return route.fulfill({contentType:'text/html',body:'<!doctype html><html><head></head><body data-operational-context="0"></body></html>'});
   const file = path.resolve(root, 'public', '.' + url.pathname);
   assert(file.startsWith(path.join(root, 'public') + path.sep)); assert(fs.existsSync(file), url.pathname);
   await route.fulfill({body:fs.readFileSync(file), contentType:({'.css':'text/css','.js':'text/javascript','.woff2':'font/woff2','.woff':'font/woff','.ttf':'font/ttf'})[path.extname(file)] || 'application/octet-stream'});
  });
  await page.goto('http://qa.invalid/fixture');
  for (const f of ['admin/dist/css/adminlte.min.css','admin/dist/css/venti.min.css','admin/dist/css/venti-next.css','admin/plugins/fontawesome-free/css/all.min.css']) await page.addStyleTag({url:'/'+f});
  for (const f of ['admin/plugins/jquery/jquery.min.js','admin/plugins/bootstrap/js/bootstrap.bundle.min.js','admin/plugins/datatables/jquery.dataTables.min.js','js/layout/admin2.js']) await page.addScriptTag({url:'/'+f});
  // Inspect only historical A/B views, never C or exclusions. Includes are inventoried too.
  const source = read('docs/REAL-NEXT-R8-SEMANTIC-SURFACE-INVENTORY.md');
  const surfaces = source.split('\n').filter(l => /^\| R8-S\d+ \| [AB] \|/.test(l)).map(l => l.split('|').slice(1,-1).map(x=>x.trim()));
  function sources(view, seen = new Set()) {
   if(seen.has(view)) return ''; seen.add(view);
   const f='resources/views/'+view.replaceAll('.','/')+'.blade.php';
   if(!fs.existsSync(path.join(root,f))) return '';
   let s=read(f); for(const m of s.matchAll(/@include(?:If|When)?\(\s*['"]([^'"]+)['"]/g)) s+='\n'+sources(m[1],seen);
   return s;
  }
  for(const r of surfaces) {
   const raw = sources(r[4].replaceAll('`',''));
   const jsFiles = [...raw.matchAll(/(?:asset|mix)\(\s*['"]\/?(js\/[^'"]+\.js)['"]/g)].map(m=>m[1]);
   const scripts=jsFiles.filter(f=>fs.existsSync(path.join(root,'public',f))).map(f=>read('public/'+f)).join('\n');
   const tables=await page.evaluate(raw=>{
    const doc=new DOMParser().parseFromString(raw.replace(/{{--[\s\S]*?--}}/g,''),'text/html');
    return [...doc.querySelectorAll('table')].map(t=>({headers:[...t.querySelectorAll('thead th')].map(c=>c.textContent.trim()),actionMarker:!!t.querySelector('thead th:last-child[data-buttons]'),headRows:t.tHead ? t.tHead.rows.length : 0,spanned:!!t.querySelector('thead [colspan],thead [rowspan]'),classes:t.className,material:t.classList.contains('next-materials-table')}));
   },raw);
   const dynamic = /title\s*:\s*['"]Acciones?['"]|<th[^>]*>Acciones<\/th>|data-buttons/.test(scripts);
   report.viewInventory.push({id:r[0],view:r[4],url:r[3],tables,dynamic,jsFiles});
  }
  report.corpus = [];
  const candidates = report.viewInventory.flatMap(v=>v.tables.map((t,i)=>({...t,surface:v.id,table:i}))).filter(t=>t.headRows===1&&!t.spanned&&t.headers.length&&(t.actionMarker||/^(accion|acciones|opciones)$/i.test(t.headers[t.headers.length-1].normalize('NFD').replace(/[\u0300-\u036f]/g,''))));
  await page.evaluate(({candidates,menu})=>{
   document.body.innerHTML='<main class="container-fluid"></main>';
   const main=document.querySelector('main');
   candidates.forEach(t=>{
    const wrap=document.createElement('div');wrap.className='table-responsive';
    const table=document.createElement('table');table.className=t.classes+' sticky-corpus';
    const head=table.createTHead().insertRow(),row=table.createTBody().insertRow();
    t.headers.forEach((label,i)=>{
     const th=document.createElement('th');th.textContent=label;if(t.actionMarker&&i===t.headers.length-1)th.setAttribute('data-buttons','');head.append(th);
     const td=row.insertCell();
     if((t.actionMarker&&i===t.headers.length-1)||/^(accion|acciones|opciones)$/i.test(label.normalize('NFD').replace(/[\u0300-\u036f]/g,''))) td.innerHTML=menu;
     else td.textContent='SIMULATED LONG CONTENT FOR SCROLL';
    });wrap.append(table);main.append(wrap);
   });
  },{candidates,menu});
  await page.addStyleTag({content:'.sticky-corpus { min-width:2400px }'});
  await page.waitForTimeout(60);
  for(const width of [375,768,1024,1440,1920]) {
   await page.setViewportSize({width,height:800});
   await page.waitForTimeout(60);
   for(const position of [0,.5,1]) {
    const results=await page.evaluate(position=>[...document.querySelectorAll('.sticky-corpus')].map(t=>{
     if(getComputedStyle(t).display!=='table')return 'RESPONSIVE CARD — N/A';
     const w=t.parentElement;w.scrollLeft=(w.scrollWidth-w.clientWidth)*position;
     const cell=t.querySelector('tbody .next-sticky-action-cell'),head=t.querySelector('thead .next-sticky-action-cell');
     if(!cell||!head)return false;
     const c=cell.getBoundingClientRect(),h=head.getBoundingClientRect(),b=w.getBoundingClientRect();
     return c.left>=b.left-1&&c.right<=b.right+1&&Math.abs(c.right-h.right)<1;
    }),position);
    results.forEach((pass,i)=>{assert(pass,JSON.stringify({candidate:candidates[i],width,position}));report.corpus.push({surface:candidates[i].surface,table:candidates[i].table,width,position,pass});});
   }
  }
  const fixture = (dt=false) => '<main class="container-fluid"><div class="table-responsive"><table id="fixture-table" class="table table-bordered table-hover table-sm next-data-table"><thead><tr>'+Array.from({length:10},(_,i)=>'<th>'+ (i===9?'Acciones':'Columna '+i) +'</th>').join('')+'</tr></thead><tbody>'+ (dt?'': '<tr>'+Array.from({length:10},(_,i)=>'<td>'+(i===9?menu:'SIMULATED LONG VALUE '+i)+'</td>').join('')+'</tr>')+'</tbody></table></div></main>';
  await page.addStyleTag({content:'/* synthetic stress dataset only */ #fixture-table { min-width:2400px }'});
  for(const mode of ['blade','ajax','datatables']) for(const width of [375,768,1024,1440,1920]) {
   await page.setViewportSize({width,height:800});
   await page.evaluate(html=>{document.body.innerHTML=html;document.body.setAttribute('data-operational-context','0');},fixture(mode==='datatables'));
   if(mode==='datatables') await page.evaluate(menu=>$('#fixture-table').DataTable({data:Array.from({length:12},()=>Array.from({length:10},(_,i)=>i===9?menu:'SIMULATED '+i)),pageLength:5}),menu);
   await page.waitForTimeout(60);
   for(const position of [0,.5,1]) {
    await page.evaluate(p=>{const w=document.querySelector('.table-responsive');w.scrollLeft=(w.scrollWidth-w.clientWidth)*p;},position);
    const metrics=await page.evaluate(()=>{
     const table=document.querySelector('#fixture-table'),cell=table.tBodies[0].rows[0].cells[9],head=table.tHead.rows[0].cells[9],w=table.closest('.table-responsive').getBoundingClientRect(),c=cell.getBoundingClientRect(),h=head.getBoundingClientRect();
     return {visible:c.left>=w.left-1&&c.right<=w.right+1,aligned:Math.abs(c.right-h.right)<1,sticky:getComputedStyle(cell).position,opaque:getComputedStyle(cell).backgroundColor!=='rgba(0, 0, 0, 0)',overflow:document.documentElement.scrollWidth>innerWidth+1};
    });
    assert.deepEqual(metrics,{visible:true,aligned:true,sticky:'sticky',opaque:true,overflow:false},JSON.stringify({mode,width,position,metrics}));
    await page.locator('#fixture-table tbody .next-row-actions-trigger').first().click();
    const bounds=await page.locator('body > .next-row-actions-menu').boundingBox();
    assert(bounds && bounds.x>=0 && bounds.x+bounds.width<=width+1,'menu clipped');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('body > .next-row-actions-menu').count(),0);
    report.cases.push({mode,width,position,...metrics});
   }
   if(mode==='ajax') {
    await page.evaluate(()=>{const row=document.querySelector('#fixture-table tbody tr');row.outerHTML=row.outerHTML.replaceAll('next-sticky-action-cell','');});
   } else if(mode==='datatables') await page.evaluate(()=>$('#fixture-table').DataTable().page(1).draw(false));
   await page.waitForTimeout(60);
   assert.equal(await page.locator('#fixture-table tbody tr:first-child td.next-sticky-action-cell').count(),1);
   if(mode==='datatables') {
    await page.evaluate(()=>$('#fixture-table').DataTable().column(1).visible(false));
    await page.waitForTimeout(60);
    assert.equal(await page.locator('#fixture-table tbody tr:first-child td.next-sticky-action-cell').count(),1);
    await page.evaluate(()=>$('#fixture-table').DataTable().search('NO_MATCH_SIMULATED').draw());
    await page.waitForTimeout(60);
    assert.equal(await page.locator('#fixture-table tbody .next-sticky-action-cell').count(),0);
    await page.evaluate(()=>$('#fixture-table').DataTable().search('').draw());
    await page.waitForTimeout(60);
    assert.equal(await page.locator('#fixture-table tbody tr:first-child td.next-sticky-action-cell').count(),1);
   }
   if(out && width===375 && mode==='blade') { fs.mkdirSync(out,{recursive:true}); await page.screenshot({path:path.join(out,'sticky-mobile.png')}); }
  }
  // Empty colspan and unrelated tables must not gain action columns.
  await page.evaluate(()=>{document.querySelector('#fixture-table tbody').innerHTML='<tr><td colspan="10">Sin resultados simulados</td></tr>';});
  await page.waitForTimeout(60);
  assert.equal(await page.locator('#fixture-table tbody .next-sticky-action-cell').count(),0);
  await page.evaluate(()=>{
   document.body.innerHTML='<table id="plain"><thead><tr><th>Nombre</th><th>Estado</th></tr></thead><tbody><tr><td>A</td><td>B</td></tr></tbody></table><table id="owned"><thead><tr><th>Nombre</th><th>Acciones</th></tr></thead><tbody><tr><td>A</td><td class="dtfc-fixed-right">B</td></tr></tbody></table>';
  });
  await page.waitForTimeout(60);
  assert.equal(await page.locator('.next-sticky-action-cell').count(),0);
  if(out) fs.writeFileSync(path.join(out,'evidence.json'),JSON.stringify(report,null,2));
  console.log(JSON.stringify({surfaces:report.viewInventory.length,cases:report.cases.length,status:'PASS',simulated:true}));
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
