// Explicit browser replay of authenticated kernel responses and separate synthetic edge cases.
// Never creates cookies/records or contacts WhatsApp; window.open is captured before scripts run.
const fs=require('fs'),path=require('path'),assert=require('assert/strict'),{chromium}=require('playwright');
const [liveFile,casesFile,out]=process.argv.slice(2);if(!out)throw Error('Pass live JSON, cases JSON and output directory');
const load=p=>JSON.parse(fs.readFileSync(p,'utf8').replaceAll('aliexpress.site:8080','127.0.0.1:8080'));
const live=load(liveFile),cases=load(casesFile),base='http://127.0.0.1:8080';fs.mkdirSync(out,{recursive:true});
const report={mode:'Browser replay: authenticated kernel HTML + explicitly synthetic edge cases',rows:[],errors:[],missingImages:[],businessRequests:[],whatsapp:{captured:0,sent:0}};
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  for(const [label,snapshot] of Object.entries({...live,...cases})){
   const ctx=await browser.newContext(),page=await ctx.newPage();
   await page.addInitScript(()=>{window.qaOpened=[];window.open=(...args)=>{window.qaOpened.push(args);return null;};});
   page.on('pageerror',e=>report.errors.push(e.message));
   page.on('response',r=>{if(r.status()>=400)(r.request().resourceType()==='image'?report.missingImages:report.errors).push(r.url());});
   page.on('request',r=>{if(['xhr','fetch'].includes(r.resourceType()))report.businessRequests.push(r.url());assert.ok(!r.url().includes('/no-image.png'));});
   await page.route('**/store-web/product/qa-render',r=>r.fulfill({contentType:'text/html',body:snapshot.html}));
   const widths=live[label]?[320,375,390,576,767,768,991,992,1200,1440]:[390,1440];
   for(const width of widths){
    await page.setViewportSize({width,height:900});await page.goto(base+'/store-web/product/qa-render');
    await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})));});
    await page.waitForFunction(()=>[...document.images].every(i=>i.complete&&i.naturalWidth));
    const metrics=await page.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,
     mobileCTA:getComputedStyle(document.querySelector('.catalog-product-sticky-cta')).display!=='none',
     inlineCTA:getComputedStyle(document.querySelector('.catalog-whatsapp-cta--inline')).display!=='none',
     sticky:getComputedStyle(document.querySelector('.catalog-product-summary')).position,
     font:document.fonts.check('12px Montserrat'),disabledOptions:[...document.querySelectorAll('.catalog-product-variation input')].every(i=>i.disabled&&!i.checked)}));
    assert.equal(metrics.scrollWidth,width,label);assert.equal(metrics.mobileCTA,width<768);assert.equal(metrics.inlineCTA,width>=768);
    if(width>=992)assert.equal(metrics.sticky,'sticky');assert.ok(metrics.disabledOptions&&metrics.font);
    assert.equal(await page.locator('.catalog-product-summary__badge, .catalog-product-summary del, .catalog-related-card').count(),0);
    assert.equal(await page.locator('.catalog-social-links a[href="#"]').count(),0);
    const thumbs=page.locator('[data-gallery-image]'),count=await thumbs.count();assert.ok(count>=1);
    for(let i=0;i<count;i++){
     await thumbs.nth(i).click();await page.waitForFunction(()=>{const img=document.getElementById('catalog-product-main-image');return img.complete&&img.naturalWidth;});
     assert.equal(await page.locator('.catalog-product-gallery__counter').textContent(),`${i+1} / ${count}`);
     assert.equal(await thumbs.nth(i).getAttribute('aria-pressed'),'true');assert.equal(await page.locator('.catalog-product-gallery__thumb.active').count(),1);
    }
    const cta=page.locator(width<768?'.catalog-product-sticky-cta button':'.catalog-whatsapp-cta--inline');
    const config=await page.locator('#store-web-detail-contract').evaluate(e=>JSON.parse(e.textContent));
    if(config.phone){
     await cta.click();const calls=await page.evaluate(()=>window.qaOpened);assert.equal(calls.length,1);const link=new URL(calls[0][0]);
     assert.equal(link.hostname,'wa.me');assert.equal(link.pathname,'/'+config.phone);assert.ok(link.searchParams.get('text').includes(config.name));assert.ok(link.searchParams.get('text').includes(config.url));
     assert.equal(calls[0][2],'noopener,noreferrer');report.whatsapp.captured++;
    }else assert.ok(await cta.isDisabled());
    if([390,1440].includes(width))await page.screenshot({path:path.join(out,`${label}-${width}.png`),fullPage:true});
    report.rows.push({label,width,thumbnails:count,...metrics});
   }
   await ctx.close();
  }
  assert.deepEqual(report.errors,[]);assert.deepEqual(report.businessRequests,[]);
  report.missingImages=[...new Set(report.missingImages)];report.status=report.missingImages.length?'PASS WITH DOCUMENTED IMAGE FALLBACKS':'PASS';
 }finally{await browser.close();fs.writeFileSync(process.argv[5] || 'docs/store-web/detail-render-qa.json',JSON.stringify(report,null,2));}
 console.log(JSON.stringify({status:report.status,rows:report.rows.length,errors:report.errors,missingImages:report.missingImages.length,whatsapp:report.whatsapp},null,2));
})().catch(e=>{console.error(e);process.exitCode=1;});
