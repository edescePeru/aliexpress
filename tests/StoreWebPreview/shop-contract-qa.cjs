// All business HTTP responses below are test doubles. No business request reaches Laravel.
const {chromium}=require('playwright');const assert=require('assert/strict');const fs=require('fs');
const base='http://127.0.0.1:8080';
const report={phase:'REAL NEXT 9.4',transport:'Controlled responses only; authenticated bridge tested separately by kernel QA',requests:[],checks:[]};
const sample={full_name:'Producto de prueba <b>literal</b>',image_url:base+'/store-web/img/shop/shop-2.jpg',detail_url:base+'/store-web/product/42',price_text:'Desde S/. 19.37',stock:7,category:'Categoría de prueba',rating:4};
(async()=>{
 const browser=await chromium.launch({headless:true,channel:'msedge'});
 try{
  const ctx=await browser.newContext({viewport:{width:390,height:900}}),page=await ctx.newPage();
  let fail=false,empty=false,hidePrices=false,facetFail=false,slowSeen=false;
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  // Browser-only fixture rewrite. It does not create an application bypass/route/setting.
  await page.route('**/store-web/catalogo*',async route=>{
   const response=await route.fetch();let body=await response.text();
   body=body.replace(/(<script id="store-web-catalog-contract" type="application\/json">)([\s\S]*?)(<\/script>)/,(_m,a,json,b)=>{const c=JSON.parse(json);c.preview=false;c.showPrices=!hidePrices;return a+JSON.stringify(c)+b;});
   await route.fulfill({response,body});
  });
  await page.route(/\/store-web\/(?:products|categories|sizes|colors)\/data(?:\/|\?|$)/,async route=>{
   const url=new URL(route.request().url());report.requests.push(url.pathname+url.search);
   const name=url.pathname;
   if(!name.includes('/products/')){
    if(facetFail){await route.fulfill({status:503,json:{}});return;}
    const data=name.includes('/categories/')?[{id:11,name:'Categoría A',subcategories:[{id:111,name:'Sub A'}]},{id:12,name:'Categoría B',subcategories:[]}]:[{id:21,name:'Opción uno'},{id:22,name:'Opción dos'}];
    await route.fulfill({json:{data}});return;
   }
   if(url.searchParams.get('search')==='lenta'){slowSeen=true;await new Promise(r=>setTimeout(r,600));}
   if(fail){await route.fulfill({status:503,json:{}});return;}
   const current=Number(name.split('/').pop());
   const product={...sample,full_name:url.searchParams.get('search')==='rápida'?'Respuesta reciente':sample.full_name};
   await route.fulfill({json:{data:empty?[]:[product],pagination:{currentPage:current,totalPages:empty?1:7,totalFilteredRecords:empty?0:61}}});
  });
  // No unrecognized business route may escape to the real application.
  await page.goto(base+'/store-web/catalogo?search=inicial');
  await page.waitForFunction(()=>document.querySelector('.catalog-product-grid').getAttribute('aria-busy')==='false');
  assert.equal(await page.locator('.catalog-product-card').count(),1);
  assert.equal(await page.locator('.catalog-product-card h2').textContent(),sample.full_name);
  assert.equal(await page.locator('.catalog-product-card b').count(),0);
  assert.equal(await page.locator('.catalog-product-card__price').textContent(),sample.price_text);
  assert.equal(await page.locator('.catalog-product-card').getAttribute('data-stock'),'7');
  assert.equal(await page.locator('.catalog-product-card').getAttribute('data-category'),sample.category);
  assert.equal(await page.locator('.catalog-product-card').getAttribute('href'),sample.detail_url);
  assert.equal(await page.locator('.catalog-product-card__badge, .catalog-product-card del').count(),0);
  assert.equal(await page.locator('[data-catalog-total]').textContent(),'61 productos');
  assert.equal(await page.locator('.catalog-shop-categories [aria-disabled="true"]').count(),4);
  report.checks.push('Exact card fields, literal text escaping, no invented badges/rating/discount, total PASS');
  function last(){return new URL(base+report.requests.filter(r=>r.includes('/products/')).at(-1));}
  async function settled(){await page.waitForFunction(()=>document.querySelector('.catalog-product-grid').getAttribute('aria-busy')==='false');}
  await page.locator('[data-page="2"]').first().click();await settled();assert.ok(last().pathname.endsWith('/2'));
  await page.locator('.catalog-filter-trigger').click();
  await page.locator('[name="category_id"][value="11"]').check();await settled();assert.equal(last().searchParams.get('category_id'),'11');assert.ok(last().pathname.endsWith('/1'));
  await page.locator('[name="subcategory_id"][value="111"]').check();await settled();assert.equal(last().searchParams.get('subcategory_id'),'111');
  await page.locator('[name="category_id"][value="12"]').check();await settled();assert.equal(last().searchParams.get('subcategory_id'),'');assert.equal(await page.locator('[name="category_id"]:checked').count(),1);
  await page.locator('[name="size_ids"][value="21"]').check();await settled();
  await page.locator('[name="size_ids"][value="22"]').check();await settled();assert.deepEqual(last().searchParams.getAll('size_ids[]'),['21','22']);
  await page.locator('[name="color_ids"][value="21"]').check();await settled();assert.deepEqual(last().searchParams.getAll('color_ids[]'),['21']);
  await page.locator('[data-price="min_price"]').fill('S/. 10');await page.locator('[data-price="max_price"]').fill('100');
  await page.locator('[data-filter-apply]').click();await settled();assert.equal(last().searchParams.get('min_price'),'10');assert.equal(last().searchParams.get('max_price'),'100');
  report.checks.push('Current GET endpoints, scalar category/subcategory, arrays sizes/colors, price cleaning, page reset PASS');
  await page.locator('#catalog-search-input').fill(' azul & niño ');await page.locator('#catalog-search-input').press('Enter');await settled();assert.equal(last().searchParams.get('search'),'azul & niño');assert.ok(last().pathname.endsWith('/1'));
  await page.locator('#catalog-search-input').fill('lenta');await page.locator('#catalog-search-input').press('Enter');
  await page.waitForTimeout(100);assert.equal(slowSeen,true);
  await page.locator('#catalog-search-input').fill('rápida');await page.locator('#catalog-search-input').press('Enter');await settled();await page.waitForTimeout(700);
  assert.equal(await page.locator('.catalog-product-card h2').textContent(),'Respuesta reciente');
  await page.locator('.catalog-search__clear').click();await settled();assert.equal(last().searchParams.get('search'),'');
  await page.locator('.catalog-filter-trigger').click();await page.locator('.catalog-filter-clear').click();await settled();
  assert.equal(last().searchParams.get('category_id'),'');assert.deepEqual(last().searchParams.getAll('size_ids[]'),[]);assert.equal(last().searchParams.get('min_price'),'');
  await page.keyboard.press('Escape');
  report.checks.push('Search encoding/clear, stale response protection, clear filters, Escape PASS');
  empty=true;await page.locator('#catalog-search-input').press('Enter');await settled();assert.match(await page.locator('.catalog-product-grid').textContent(),/No se encontraron/);assert.equal(await page.locator('.catalog-pagination a').count(),0);
  fail=true;await page.locator('#catalog-search-input').press('Enter');await settled();assert.match(await page.locator('.catalog-product-grid').textContent(),/No se pudieron cargar/);
  fail=false;empty=false;hidePrices=true;facetFail=true;await page.reload();await settled();assert.equal(await page.locator('.catalog-product-card__price').count(),0);
  await page.waitForFunction(()=>document.querySelector('[data-facet="category_id"]').textContent.includes('No se pudieron'));
  report.checks.push('Empty/product error/facet error and backend price visibility flag PASS');
  assert.deepEqual(errors,[]);report.status='PASS';
 }finally{await browser.close();fs.writeFileSync('docs/store-web/shop-contract-qa.json',JSON.stringify(report,null,2));}
 console.log(JSON.stringify({status:report.status,checks:report.checks,requests:report.requests.length},null,2));
})().catch(e=>{console.error(e);process.exitCode=1;});
