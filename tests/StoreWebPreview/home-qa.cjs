// Reference server 8097 is QA-only. Actual target is the normal Laravel kernel 8080.
const fs=require('fs'),path=require('path'),assert=require('assert/strict');
const {chromium}=require('playwright'),{PNG}=require('pngjs');
const out=process.argv[2];if(!out)throw Error('Pass screenshot output directory');fs.mkdirSync(out,{recursive:true});
const base='http://127.0.0.1:8080',ref='http://127.0.0.1:8097';
const report={phase:'REAL NEXT 9.3',rows:[],referenceWarnings:[],errors:[],businessRequests:[],interactions:[]};
function raster(a,b){assert.equal(a.width,b.width);assert.equal(a.height,b.height);let raw=0,material=0,max=0;for(let i=0;i<a.data.length;i+=4){let d=0;for(let j=0;j<4;j++)d=Math.max(d,Math.abs(a.data[i+j]-b.data[i+j]));if(d)raw++;if(d>1)material++;max=Math.max(max,d);}return {raw,material,maxChannelDelta:max};}
function visualTree(){const tree=e=>({tag:e.classList.contains('catalog-social-links__item')?'SOCIAL':e.tagName,classes:e.className.baseVal===undefined?e.className:e.className.baseVal,children:[...e.childNodes].filter(n=>n.nodeType===1?n.tagName!=='SCRIPT':n.nodeType===3&&n.textContent.trim()).map(n=>n.nodeType===1?tree(n):n.textContent.trim().replace(/\s+/g,' '))});return tree(document.body);}
async function ready(p){await p.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode()));});await p.waitForTimeout(1500);}
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  for(const width of [320,375,390,576,767,768,991,992,1200,1440]){
   const ctx=await browser.newContext({viewport:{width,height:900},deviceScaleFactor:1});
   const r=await ctx.newPage(),p=await ctx.newPage();
   for(const page of [r,p]){
    page.on('pageerror',e=>report.errors.push(e.message));page.on('console',m=>{if(m.type()==='error' && page===r && m.location().url===ref+'/favicon.ico'){report.referenceWarnings.push('Reference-only implicit favicon.ico 404; not a runtime Home asset');return;}if(m.type()==='error')report.errors.push((page===r?'reference: ':'actual: ')+m.text()+' '+JSON.stringify(m.location()));});
    page.on('response',r=>{if(r.status()>=400)report.errors.push(r.status()+' '+r.url());});
    page.on('requestfailed',r=>report.errors.push(r.url()));
   }
   p.on('request',r=>{if(['fetch','xhr'].includes(r.resourceType())||!r.url().startsWith(base+'/'))report.businessRequests.push(r.url());});
   assert.equal((await r.goto(ref+'/reference/index.html')).status(),200);await r.locator('[data-theme-tester]').evaluateAll(ns=>ns.forEach(n=>n.remove()));
   assert.equal((await p.goto(base+'/store-web/inicio')).status(),200);
   await ready(r);await ready(p);
   assert.deepEqual(await p.evaluate(visualTree),await r.evaluate(visualTree));
   await r.bringToFront();const a=PNG.sync.read(await r.screenshot({path:path.join(out,`home-${width}-reference.png`),fullPage:true}));
   await p.bringToFront();const b=PNG.sync.read(await p.screenshot({path:path.join(out,`home-${width}-actual.png`),fullPage:true}));
   const pixels=raster(a,b);
   const metrics=await p.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,font:document.fonts.check('12px Montserrat'),bottomNav:getComputedStyle(document.querySelector('.catalog-bottom-nav')).display!=='none',snap:[...document.querySelectorAll('.catalog-featured-list,.catalog-offer-list')].map(e=>({type:getComputedStyle(e).scrollSnapType,overflow:e.scrollWidth>e.clientWidth})),safeArea:[...document.styleSheets].some(s=>[...s.cssRules].some(r=>r.cssText.includes('safe-area-inset-bottom')))}));
   report.rows.push({width,...pixels,...metrics});assert.equal(pixels.material,0);assert.equal(metrics.scrollWidth,width);assert.equal(metrics.font,true);assert.equal(metrics.safeArea,true);
   assert.equal(await p.locator('.catalog-featured-product').count(),4);assert.equal(await p.locator('.catalog-offer-card').count(),2);
   assert.equal(await p.locator('.catalog-social-links a[href]').count(),0);assert.equal(await p.locator('.catalog-category-strip a[aria-disabled="true"]').count(),4);
   assert.equal((await p.content()).includes('project-catalog'),false);
   if(width===390){
    const initial=p.url();await p.locator('[data-social="instagram"]').click();assert.equal(p.url(),initial);
    await p.locator('.catalog-category-strip a').first().click({force:true});assert.equal(p.url(),initial);
    await p.locator('.catalog-featured-list').evaluate(e=>{e.style.scrollBehavior='auto';e.scrollLeft=e.scrollWidth;});
    await p.waitForTimeout(250);assert.ok(await p.locator('.catalog-featured-list').evaluate(e=>e.scrollLeft>0));
    await p.locator('.catalog-offer-list').evaluate(e=>{e.style.scrollBehavior='auto';e.scrollLeft=e.scrollWidth;});
    await p.waitForTimeout(250);assert.ok(await p.locator('.catalog-offer-list').evaluate(e=>e.scrollLeft>0));
    await p.locator('[data-catalog-menu-open]').click();await p.waitForFunction(()=>document.activeElement.className==='catalog-mobile-menu__close');
    await p.keyboard.press('Shift+Tab');assert.equal(await p.evaluate(()=>document.activeElement.textContent.trim()),'Contacto');
    await p.keyboard.press('Escape');assert.ok(await p.evaluate(()=>document.activeElement.hasAttribute('data-catalog-menu-open')));
    await p.emulateMedia({reducedMotion:'reduce'});await p.locator('.catalog-bottom-nav [data-catalog-search-focus]').click();assert.equal(await p.evaluate(()=>document.activeElement.id),'catalog-search-input');
    await p.locator('#catalog-search-input').fill('test');await p.locator('.catalog-search__clear').click();assert.equal(await p.locator('#catalog-search-input').inputValue(),'');assert.equal(await p.evaluate(()=>document.activeElement.id),'catalog-search-input');
    await p.locator('#catalog-search-input').fill('   ');await p.locator('#catalog-search-input').press('Enter');assert.equal(p.url(),initial);
    await p.locator('#catalog-search-input').fill('  azul & niño / + ?  ');await Promise.all([p.waitForURL('**/store-web/catalogo?*'),p.locator('#catalog-search-input').press('Enter')]);
    assert.equal(new URL(p.url()).searchParams.get('search'),'azul & niño / + ?');assert.equal(await p.locator('body').getAttribute('data-store-web-fixture'),'true');
    await p.goto(base+'/store-web/inicio');await p.locator('.catalog-bottom-nav a').nth(1).click();assert.equal(new URL(p.url()).pathname,'/store-web/catalogo');
    await p.goto(base+'/store-web/inicio');await p.locator('.catalog-featured-product').first().click();assert.equal(new URL(p.url()).pathname,'/store-web/product/frontend-fixture');
    report.interactions.push('Inert socials/chips; carousel scroll; menu trap/Escape/focus; reduced-motion search focus/clear/empty/encoded submit; bottom nav/catalog and fixture detail PASS');
   }
   console.log(width+': '+JSON.stringify(pixels));await ctx.close();
  }
  assert.deepEqual(report.errors,[]);assert.deepEqual(report.businessRequests,[]);report.status='PASS';
 }finally{await browser.close();fs.writeFileSync('docs/store-web/home-qa.json',JSON.stringify(report,null,2));}
})().catch(e=>{console.error(e);process.exitCode=1;});
