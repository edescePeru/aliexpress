// REAL NEXT 9.6: run while the temporary reference directory is absent.
// Browser replay is explicit; authentication is tested separately in the Laravel kernel.
const fs=require('fs'),assert=require('assert/strict'),{chromium}=require('playwright');
const [shopFile,detailFile]=process.argv.slice(2),base='http://127.0.0.1:8080';
assert.ok(!fs.existsSync('project-catalog'),'Reference must be disabled for this audit');
const load=p=>JSON.parse(fs.readFileSync(p,'utf8').replaceAll('aliexpress.site:8080','127.0.0.1:8080'));
const shops=load(shopFile),details=load(detailFile),widths=[320,375,390,576,767,768,991,992,1200,1440];
const report={phase:'REAL NEXT 9.6',referenceAbsent:true,mode:'Fresh authenticated kernel responses replayed in browser; permanent assets served by normal Laravel',rows:[],errors:[],missingUploads:[],assetPaths:[]};
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try{
  for(const [tenant,shop]of Object.entries(shops)){
   const key=tenant==='Gamarra'?'Gamarra-19':'EDESCE-16';
   for(const [view,html,url]of [['Home',shop.homeHtml,'/store-web/inicio'],['Shop',shop.html,'/store-web/catalogo'],['Detail',details[key].html,'/store-web/product/'+key.split('-')[1]]]){
    const ctx=await browser.newContext(),p=await ctx.newPage();
    p.on('pageerror',e=>report.errors.push(e.message));
    p.on('response',r=>{if(r.status()>=400)(r.request().resourceType()==='image'?report.missingUploads:report.errors).push(r.url());});
    p.on('request',r=>{const u=new URL(r.url());if(['stylesheet','script','font','image'].includes(r.resourceType())){
     report.assetPaths.push(u.pathname);assert.ok(u.pathname.startsWith('/store-web/')||u.pathname.startsWith('/images/'),u.pathname);
    }if(['xhr','fetch'].includes(r.resourceType()))assert.ok(view==='Shop'&&/\/store-web\/(products|categories|sizes|colors)\/data/.test(u.pathname));});
    await p.route(base+url,r=>r.fulfill({contentType:'text/html',body:html}));
    await p.route(/\/store-web\/(products|categories|sizes|colors)\/data/,r=>{const type=new URL(r.request().url()).pathname.split('/')[2];return r.fulfill({json:type==='products'?shop.products:shop.facets[type]});});
    for(const width of widths){
     await p.setViewportSize({width,height:900});await p.goto(base+url);
     if(view==='Shop')await p.waitForFunction(()=>document.querySelector('.catalog-product-grid').getAttribute('aria-busy')==='false');
     await p.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})));});
     await p.waitForFunction(()=>[...document.images].every(i=>i.complete&&i.naturalWidth));
     const m=await p.evaluate(()=>{const visible=s=>!!document.querySelector(s)&&getComputedStyle(document.querySelector(s)).display!=='none';return{
      overflow:document.documentElement.scrollWidth-innerWidth,fixture:document.body.dataset.storeWebFixture,
      bottomNav:visible('.catalog-bottom-nav'),mobileCTA:visible('.catalog-product-sticky-cta'),inlineCTA:visible('.catalog-whatsapp-cta--inline'),
      sticky:document.querySelector('.catalog-product-summary')?getComputedStyle(document.querySelector('.catalog-product-summary')).position:null,
      snap:[...document.querySelectorAll('.catalog-featured-list,.catalog-offer-list,.catalog-related-list')].map(e=>getComputedStyle(e).scrollSnapType),font:document.fonts.check('12px Montserrat')};});
     assert.equal(m.overflow,0);assert.ok(m.font);assert.equal(m.fixture,view==='Home'?'true':'false');
     if(view==='Detail'){assert.equal(m.mobileCTA,width<768);assert.equal(m.inlineCTA,width>=768);if(width>=992)assert.equal(m.sticky,'sticky');
      assert.equal(await p.locator('.catalog-related-card,.catalog-product-summary__badge').count(),0);
      const thumbs=p.locator('[data-gallery-image]');await thumbs.last().click();await p.waitForFunction(()=>{const i=document.getElementById('catalog-product-main-image');return i.complete&&i.naturalWidth;});
      const n=await thumbs.count();assert.equal(await p.locator('.catalog-product-gallery__counter').textContent(),`${n} / ${n}`);
     }else assert.equal(m.bottomNav,width<768);
     if(view==='Shop'){assert.equal(await p.locator('.catalog-product-card').count(),shop.products.data.length);assert.equal(await p.locator('.catalog-product-card__badge').count(),0);}
     if(width===390){await p.locator('[data-catalog-menu-open]').click();
      await p.waitForFunction(()=>document.activeElement.className==='catalog-mobile-menu__close');
      await p.keyboard.press('Escape');await p.waitForFunction(()=>document.getElementById('catalog-mobile-menu').getAttribute('aria-hidden')==='true');
      if(view==='Shop'){await p.locator('.catalog-filter-trigger').click();await p.locator('[data-filter-apply]').click();}
     }
     report.rows.push({tenant,view,width,...m});
    }
    await ctx.close();
   }
  }
  assert.deepEqual(report.errors,[]);report.status='PASS';
 }finally{await browser.close();report.assetPaths=[...new Set(report.assetPaths)].sort();report.missingUploads=[...new Set(report.missingUploads)].sort();fs.writeFileSync('docs/store-web/independence-responsive-qa.json',JSON.stringify(report,null,2));}
 console.log(JSON.stringify({status:report.status,rows:report.rows.length,missingUploads:report.missingUploads.length,errors:report.errors}));
})().catch(e=>{console.error(e);process.exitCode=1;});
