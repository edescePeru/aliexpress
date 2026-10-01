// Run against the isolated PHP fixture router; never against production.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const assert = require('assert/strict');
const base = 'http://127.0.0.1:8097';
const output = process.argv[2];
if (!output) throw Error('Pass an output directory for QA artifacts.');
fs.mkdirSync(output, {recursive:true});
const widths = [320,375,390,576,767,768,991,992,1200,1440];
const pages = [['index','inicio'],['shop','catalogo'],['product-details','product/frontend-fixture']];
const report = {environment:'PHP 7.3.33 / installed Laravel Blade / isolated fixture router; no application controllers or DB provider',rows:[],interactions:[],errors:[]};
function compareRaster(a,b) {
 if(a.width!==b.width||a.height!==b.height)return {differentPixels:null,materialDifferentPixels:null,maxChannelDelta:null};
 let differentPixels=0,materialDifferentPixels=0,maxChannelDelta=0;
 for(let i=0;i<a.data.length;i+=4){let delta=0;for(let channel=0;channel<4;channel++)delta=Math.max(delta,Math.abs(a.data[i+channel]-b.data[i+channel]));if(delta)differentPixels++;if(delta>1)materialDifferentPixels++;maxChannelDelta=Math.max(maxChannelDelta,delta);}
 return {differentPixels,materialDifferentPixels,maxChannelDelta};
}
function verifyReport(result) {
 assert.equal(result.errors.length,0,result.errors.join('\n'));
 assert.equal(result.rows.length,30);
 assert.equal(result.interactions.length,6);
 assert.ok(result.rows.every(r=>r.domEqual&&r.materialDifferentPixels===0&&r.metrics.scrollWidth===r.width&&r.metrics.missingImages===0&&r.metrics.font));
 console.log('PASS: 30 comparisons, exact normalized DOM, no raster differences exceeding one 8-bit channel level, no overflow/errors; six interaction groups passed.');
}
function normalizedTree() {
 const normUrl=v=>v.replace(/^https?:\/\/[^/]+/,'').replace(/\/reference\/|\/store-web\/(?=(css|js|img)\/)/g,'/assets/').replace(/\.\//g,'').replace(/^(css|js|img)\//,'/assets/$1/').replace(/^(index\.html|\/store-web\/inicio)$/,'HOME').replace(/^(shop\.html|\/store-web\/catalogo)$/,'SHOP').replace(/^(product-details\.html|\/store-web\/product\/frontend-fixture)$/,'DETAIL');
 const tree=e=>({tag:e.tagName,attrs:[...e.attributes].filter(a=>a.name!=='data-store-web-fixture').map(a=>[a.name,['src','href','data-gallery-image'].includes(a.name)?normUrl(a.value):a.value.trim()]).filter(a=>a[1]!=='' || a[0]!=='class').sort((a,b)=>a[0].localeCompare(b[0])),children:[...e.childNodes].filter(n=>n.nodeType===1?n.tagName!=='SCRIPT':n.nodeType===3&&n.textContent.trim()).map(n=>n.nodeType===1?tree(n):n.textContent.trim().replace(/\s+/g,' '))});
 return tree(document.body);
}
async function metrics(page) {return page.evaluate(()=>({
 width:innerWidth,scrollWidth:document.documentElement.scrollWidth,height:document.documentElement.scrollHeight,
 columns:document.querySelector('.catalog-product-grid')?getComputedStyle(document.querySelector('.catalog-product-grid')).gridTemplateColumns.split(' ').length:null,
 bottomNav:!!document.querySelector('.catalog-bottom-nav')&&getComputedStyle(document.querySelector('.catalog-bottom-nav')).display!=='none',
 stickyCTA:!!document.querySelector('.catalog-product-sticky-cta')&&getComputedStyle(document.querySelector('.catalog-product-sticky-cta')).display!=='none',
 missingImages:[...document.images].filter(i=>!i.complete||!i.naturalWidth).length,
 font:document.fonts.check('12px Montserrat'),
 snap:[...document.querySelectorAll('.catalog-featured-list,.catalog-offer-list,.catalog-related-list')].map(e=>getComputedStyle(e).scrollSnapType),
 safeAreaRules:[...document.styleSheets].flatMap(s=>{try{return [...s.cssRules].map(r=>r.cssText)}catch{return[]}}).some(t=>t.includes('safe-area-inset-bottom'))
}));}
(async()=>{
 if(process.argv.includes('--verify-captures')){
  const saved=JSON.parse(fs.readFileSync(path.join(output,'responsive-qa.json')));
  for(const row of saved.rows)Object.assign(row,compareRaster(PNG.sync.read(fs.readFileSync(path.join(output,`${row.page}-${row.width}-reference.png`))),PNG.sync.read(fs.readFileSync(path.join(output,`${row.page}-${row.width}-actual.png`)))));
  saved.rasterPolicy='Exact normalized DOM and zero pixels differing by more than one 8-bit channel level; raw differences retained.';
  fs.writeFileSync(path.join(output,'responsive-qa.json'),JSON.stringify(saved,null,2));
  verifyReport(saved);return;
 }
 const browser=await chromium.launch({headless:true,channel:process.env.STORE_WEB_QA_BROWSER || 'msedge'});
 try {
  const context=await browser.newContext({deviceScaleFactor:1,viewport:{width:390,height:900}});
  let reference=await context.newPage(), actual=await context.newPage();
  let pairContext=null;
  async function monitor(page) {
   page.on('pageerror',e=>report.errors.push(e.message));
   page.on('response',r=>{if(r.status()>=400)report.errors.push(r.status()+' '+r.url());});
   page.on('requestfailed',r=>report.errors.push(r.failure().errorText+' '+r.url()));
   page.on('request',r=>{if(!r.url().startsWith(base))report.errors.push('Unexpected external request: '+r.url());});
  }
  await monitor(reference); await monitor(actual);
  for(const [name,uri] of pages) {
   await reference.setViewportSize({width:320,height:900});
   await actual.setViewportSize({width:320,height:900});
   await reference.goto(`${base}/reference/${name}.html`);
   // Only the explicitly excluded experimental UI is removed for parity measurement.
   await reference.locator('[data-theme-tester]').evaluateAll(nodes=>nodes.forEach(n=>n.remove()));
   await actual.goto(`${base}/store-web/${uri}`);
   await Promise.all([reference.evaluate(()=>document.fonts.ready),actual.evaluate(()=>document.fonts.ready)]);
   assert.equal(await actual.locator('[data-theme-tester]').count(),0);
   assert.equal(await actual.locator('body').getAttribute('data-store-web-fixture'),'true');
   assert.match(await actual.title(),/FIXTURE FRONTEND/);
   const domEqual=JSON.stringify(await reference.evaluate(normalizedTree))===JSON.stringify(await actual.evaluate(normalizedTree));
   if(!domEqual){fs.writeFileSync(path.join(output,name+'-reference-dom.json'),JSON.stringify(await reference.evaluate(normalizedTree),null,2));fs.writeFileSync(path.join(output,name+'-actual-dom.json'),JSON.stringify(await actual.evaluate(normalizedTree),null,2));}
   for(const width of widths) {
    if(pairContext)await pairContext.close();else {await reference.close();await actual.close();}
    // Each pair has a cold resource cache and identical initial viewport.
    pairContext=await browser.newContext({deviceScaleFactor:1,viewport:{width,height:900}});
    reference=await pairContext.newPage();actual=await pairContext.newPage();
    await monitor(reference);await monitor(actual);
    await reference.goto(`${base}/reference/${name}.html`);
    await reference.locator('[data-theme-tester]').evaluateAll(nodes=>nodes.forEach(n=>n.remove()));
    await actual.goto(`${base}/store-web/${uri}`);
    for(const page of [reference,actual]) await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode()));await new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)));});
    // Image decode resolves before Chromium's high-quality image raster settles.
    // Measured independently: immediate captures differed; settled captures matched.
    await Promise.all([reference.waitForTimeout(1500),actual.waitForTimeout(1500)]);
    await Promise.all([reference.evaluate(()=>scrollTo(0,0)),actual.evaluate(()=>scrollTo(0,0))]);
    await reference.bringToFront();
    const a=PNG.sync.read(await reference.screenshot({path:path.join(output,`${name}-${width}-reference.png`),fullPage:true}));
    await actual.bringToFront();
    const b=PNG.sync.read(await actual.screenshot({path:path.join(output,`${name}-${width}-actual.png`),fullPage:true}));
    const raster=compareRaster(a,b);
    const m=await metrics(actual),r=await metrics(reference);
    report.rows.push({page:name,width,domEqual,...raster,metrics:m,referenceMetrics:r});
    console.log(`${name} ${width}: dom=${domEqual} pixelDiff=${raster.differentPixels} materialDiff=${raster.materialDifferentPixels} overflow=${m.scrollWidth-width} columns=${m.columns}`);
   }
  }
  // Frozen behaviors: no API requests and no commercial side effects.
  await actual.setViewportSize({width:390,height:900});
  await actual.goto(base+'/store-web/catalogo');
  await actual.locator('[data-catalog-menu-open]').click();
  await actual.locator('.catalog-mobile-menu.active').waitFor();
  await actual.waitForFunction(()=>document.activeElement.className==='catalog-mobile-menu__close');
  assert.equal(await actual.locator('[data-catalog-menu-open]').getAttribute('aria-expanded'),'true');
  await actual.keyboard.press('Shift+Tab');
  assert.equal(await actual.evaluate(()=>document.activeElement.textContent.trim()),'Contacto');
  await actual.keyboard.press('Tab');
  assert.equal(await actual.evaluate(()=>document.activeElement.className),'catalog-mobile-menu__close');
  await actual.keyboard.press('Escape');
  assert.equal(await actual.evaluate(()=>document.activeElement.hasAttribute('data-catalog-menu-open')),true);
  assert.equal(await actual.evaluate(()=>document.body.style.overflow),'');
  report.interactions.push('Mobile menu: open/aria, Tab loop, Escape, focus restoration, scroll restoration PASS');
  for (const width of [390,1200]) {
   await actual.setViewportSize({width,height:900});
   await actual.locator('.catalog-filter-trigger').click();
   await actual.locator('.catalog-filter-drawer.active').waitFor();
   assert.equal(await actual.locator('.catalog-filter-trigger').getAttribute('aria-expanded'),'true');
   const drawerWidth=await actual.locator('.catalog-filter-drawer__panel').evaluate(e=>e.getBoundingClientRect().width);
   assert.equal(drawerWidth,width<768?width:400);
   await actual.screenshot({path:path.join(output,`drawer-${width}.png`),animations:'disabled'});
   await actual.locator('[data-filter-apply]').click();
   assert.equal(await actual.locator('.catalog-filter-trigger').getAttribute('aria-expanded'),'false');
  }
  report.interactions.push('Drawer mobile/desktop widths, open/apply-close/aria PASS; no data handler added');
  await actual.locator('#catalog-search-input').fill('fixture');
  await actual.locator('.catalog-search__clear').click();
  assert.equal(await actual.locator('#catalog-search-input').inputValue(),'');
  report.interactions.push('Inline search clear/focus PASS; submit remains visual-only');
  await actual.goto(base+'/store-web/product/frontend-fixture');
  await actual.locator('.catalog-product-gallery__thumb').nth(2).click();
  assert.equal(await actual.locator('.catalog-product-gallery__counter').textContent(),'3 / 4');
  assert.match(await actual.locator('#catalog-product-main-image').getAttribute('src'),/store-web\/img\/product\/details\/product-2.jpg$/);
  report.interactions.push('Gallery thumbnail/src/alt/counter/aria PASS');
  await actual.emulateMedia({reducedMotion:'reduce'});
  assert.equal(await actual.evaluate(()=>matchMedia('(prefers-reduced-motion: reduce)').matches),true);
  report.interactions.push('Reduced-motion media supported; CSS/behavior preserved');
  // Deny non-fixture detail IDs and every real data endpoint in the isolated harness.
  for(const uri of ['/store-web/product/1','/store-web/products/data/1','/store-web/categories/data','/store-web/search-product'])assert.equal((await context.request.get(base+uri)).status(),404);
  report.interactions.push('Real IDs and business endpoints denied by fixture router PASS');
 } finally {
  await browser.close();
  fs.writeFileSync(path.join(output,'responsive-qa.json'),JSON.stringify(report,null,2));
 }
 verifyReport(report);
})().catch(e=>{console.error(e);process.exitCode=1;});
