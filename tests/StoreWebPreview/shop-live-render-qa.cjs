// Browser rendering of responses captured by real authenticated Laravel sessions.
// Replay is explicit: this test does not create browser login cookies or claim live HTTP authentication.
const {chromium}=require('playwright');const fs=require('fs'),path=require('path'),assert=require('assert/strict');
const base='http://127.0.0.1:8080',source=process.argv[2],out=process.argv[3];
if(!source||!out)throw Error('Pass authenticated response snapshot and screenshot output directory');
const snapshots=JSON.parse(fs.readFileSync(source,'utf8').replaceAll('aliexpress.site:8080','127.0.0.1:8080'));fs.mkdirSync(out,{recursive:true});
const report={mode:'Browser replay of actual authenticated kernel HTML/JSON; local asset origin normalized',rows:[],errors:[],failedImages:[]};
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  for(const [label,snapshot] of Object.entries(snapshots)){
   const ctx=await browser.newContext(),page=await ctx.newPage();
   page.on('pageerror',e=>report.errors.push(e.message));page.on('response',r=>{if(r.status()>=400)(r.request().resourceType()==='image'?report.failedImages:report.errors).push(r.url());});
   await page.route('**/store-web/catalogo',route=>route.fulfill({contentType:'text/html',body:snapshot.html}));
   await page.route(/\/store-web\/(?:products|categories|sizes|colors)\/data(?:\/|\?|$)/,route=>{
    const uri=new URL(route.request().url()).pathname;
    const key=uri.split('/')[2];return route.fulfill({json:key==='products'?snapshot.products:snapshot.facets[key]});
   });
   for(const width of [320,375,390,576,767,768,991,992,1200,1440]){
    await page.setViewportSize({width,height:900});await page.goto(base+'/store-web/catalogo');
    await page.waitForFunction(()=>document.querySelector('.catalog-product-grid').getAttribute('aria-busy')==='false');
    await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})));});
    assert.equal(await page.locator('.catalog-product-card').count(),snapshot.products.data.length);
    assert.equal(await page.locator('.catalog-product-card__badge, .catalog-product-card del').count(),0);
    assert.equal(await page.locator('body').getAttribute('data-store-web-fixture'),'false');
    const metrics=await page.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,columns:getComputedStyle(document.querySelector('.catalog-product-grid')).gridTemplateColumns.split(' ').length,missingImages:[...document.images].filter(i=>!i.naturalWidth).length}));
    assert.equal(metrics.scrollWidth,width);assert.equal(metrics.missingImages,0);report.rows.push({label,width,...metrics});
    if([390,1440].includes(width))await page.screenshot({path:path.join(out,`${label}-${width}.png`),fullPage:true});
    if(width===390){
     await page.locator('.catalog-filter-trigger').click();await page.locator('.catalog-filter-drawer.active').waitFor();await page.waitForFunction(()=>Math.abs(document.querySelector('.catalog-filter-drawer__panel').getBoundingClientRect().x)<1);
     for(const key of ['category_id','subcategory_id','size_ids','color_ids'])assert.equal(await page.locator('[data-facet="'+key+'"]').isVisible(),true);
     assert.ok(Math.abs(await page.locator('.catalog-filter-drawer__panel').evaluate(e=>e.getBoundingClientRect().width)-390)<0.01);
     await page.screenshot({path:path.join(out,`${label}-drawer.png`)});await page.keyboard.press('Escape');
    }
   }
   await ctx.close();
  }
  assert.deepEqual(report.errors,[]);report.failedImages=[...new Set(report.failedImages)];report.status=report.failedImages.length?'PASS WITH EXISTING ASSET GAPS':'PASS';
 }finally{await browser.close();fs.writeFileSync('docs/store-web/shop-live-render-qa.json',JSON.stringify(report,null,2));}
 console.log(JSON.stringify({status:report.status,rows:report.rows.length,errors:report.errors,failedImages:report.failedImages},null,2));
})().catch(e=>{console.error(e);process.exitCode=1;});
