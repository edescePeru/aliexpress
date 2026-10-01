const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const crypto=require('node:crypto');
const base='http://127.0.0.1:8080';
const report={phase:'REAL NEXT 9.2',base,rows:[],errors:[],interactions:[],assets:[],businessRequests:[]};
(async()=>{
 const manifest=JSON.parse(fs.readFileSync('docs/store-web/asset-migration-manifest.json'));
 for(const f of manifest.files)assert.equal(crypto.createHash('sha256').update(fs.readFileSync(f.destination)).digest('hex'),f.finalHash,f.destination);
 report.unchangedAssets=manifest.files.length;
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  const context=await browser.newContext({viewport:{width:390,height:900}});
  const page=await context.newPage();
  page.on('pageerror',e=>report.errors.push(e.message));
  page.on('console',m=>{if(m.type()==='error')report.errors.push(m.text());});
  page.on('requestfailed',r=>report.errors.push(r.url()));
  page.on('response',r=>{if(r.status()>=400)report.errors.push(r.status()+' '+r.url());if(r.url().includes('/store-web/')&&r.request().resourceType()!=='document')report.assets.push({url:r.url(),status:r.status()});});
  page.on('request',r=>{if(['xhr','fetch'].includes(r.resourceType())||!r.url().startsWith(base+'/'))report.businessRequests.push(r.url());});
  for(const uri of ['inicio','catalogo','product/999999999']){
   for(const width of [320,375,390,576,767,768,991,992,1200,1440]){
    await page.setViewportSize({width,height:900});
    const response=await page.goto(base+'/store-web/'+uri);
    assert.equal(response.status(),200);
    assert.equal(response.headers()['x-store-web-preview'],'visual-qa-frontend-fixture');
    await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode()));});
    const m=await page.evaluate(()=>({width:innerWidth,scrollWidth:document.documentElement.scrollWidth,font:document.fonts.check('12px Montserrat'),missingImages:[...document.images].filter(i=>!i.naturalWidth).length,bottomNav:!!document.querySelector('.catalog-bottom-nav')&&getComputedStyle(document.querySelector('.catalog-bottom-nav')).display!=='none',stickyCTA:!!document.querySelector('.catalog-product-sticky-cta')&&getComputedStyle(document.querySelector('.catalog-product-sticky-cta')).display!=='none'}));
    assert.equal(m.scrollWidth,width);assert.equal(m.font,true);assert.equal(m.missingImages,0);
    assert.equal(await page.locator('body').getAttribute('data-store-web-fixture'),'true');
    assert.equal((await page.content()).includes('project-catalog'),false);
    report.rows.push({uri,...m});
   }
  }
  await page.setViewportSize({width:390,height:900});
  await page.goto(base+'/store-web/catalogo');
  assert.equal(await page.locator('.catalog-bottom-nav').isVisible(),true);
  await page.locator('[data-catalog-menu-open]').click();
  await page.waitForFunction(()=>document.activeElement.className==='catalog-mobile-menu__close');
  await page.keyboard.press('Shift+Tab');assert.equal(await page.evaluate(()=>document.activeElement.textContent.trim()),'Contacto');
  await page.keyboard.press('Escape');assert.equal(await page.evaluate(()=>document.activeElement.hasAttribute('data-catalog-menu-open')),true);
  report.interactions.push('Menu: open, focus loop, Escape and focus restoration PASS');
  for(const width of [390,1200]){
   await page.setViewportSize({width,height:900});await page.locator('.catalog-filter-trigger').click();
   assert.ok(Math.abs(await page.locator('.catalog-filter-drawer__panel').evaluate(e=>e.getBoundingClientRect().width)-(width===390?390:400))<0.01);
   await page.locator('[data-filter-apply]').click();assert.equal(await page.locator('.catalog-filter-trigger').getAttribute('aria-expanded'),'false');
  }
  report.interactions.push('Drawer mobile/desktop open and apply-close PASS');
  await page.locator('#catalog-search-input').fill('fixture');await page.locator('.catalog-search__clear').click();assert.equal(await page.locator('#catalog-search-input').inputValue(),'');
  await page.setViewportSize({width:390,height:900});await page.goto(base+'/store-web/product/1');
  await page.locator('.catalog-product-gallery__thumb').nth(2).click();assert.equal(await page.locator('.catalog-product-gallery__counter').textContent(),'3 / 4');
  assert.match(await page.locator('#catalog-product-main-image').getAttribute('src'),/product-2.jpg$/);
  assert.equal(await page.locator('.catalog-product-sticky-cta').isVisible(),true);
  report.interactions.push('Gallery, bottom navigation and mobile CTA visibility PASS; commercial controls remain fixtures');
  for(const uri of ['products/data/1','categories/data','sizes/data','colors/data','search-product','producto/1','tienda'])assert.equal((await context.request.get(base+'/store-web/'+uri)).status(),404);
  report.interactions.push('Business endpoints and legacy aliases return 404 PASS');
  assert.deepEqual(report.errors,[]);assert.deepEqual(report.businessRequests,[]);
  assert.ok(report.assets.length>0);assert.ok(report.assets.every(a=>a.status===200));
  report.status='PASS';
 }finally{await browser.close();fs.writeFileSync('docs/store-web/local-preview-qa.json',JSON.stringify(report,null,2));}
 console.log(JSON.stringify({status:report.status,viewports:report.rows.length,unchangedAssets:report.unchangedAssets,assetResponses:report.assets.length,errors:report.errors,businessRequests:report.businessRequests,interactions:report.interactions},null,2));
})().catch(e=>{console.error(e);process.exitCode=1;});
