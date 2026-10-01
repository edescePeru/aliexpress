const fs=require('fs'),path=require('path'),assert=require('assert/strict');
const {chromium}=require('playwright'),{PNG}=require('pngjs');
const out=process.argv[2];if(!out)throw Error('Screenshot output directory required');fs.mkdirSync(out,{recursive:true});
const base='http://127.0.0.1:8080',ref='http://127.0.0.1:8097';
const report={phase:'REAL NEXT 9.4',mode:'anonymous frozen preview vs approved shop.html',rows:[],errors:[]};
function raster(a,b){assert.equal(a.width,b.width);assert.equal(a.height,b.height);let raw=0,material=0;for(let i=0;i<a.data.length;i+=4){let d=0;for(let j=0;j<4;j++)d=Math.max(d,Math.abs(a.data[i+j]-b.data[i+j]));if(d)raw++;if(d>1)material++;}return{raw,material};}
(async()=>{
const browser=await chromium.launch({channel:'msedge',headless:true});
try{
 for(const width of [320,375,390,576,767,768,991,992,1200,1440]){
  const ctx=await browser.newContext({viewport:{width,height:900},deviceScaleFactor:1});const r=await ctx.newPage(),p=await ctx.newPage();
  p.on('pageerror',e=>report.errors.push(e.message));p.on('console',m=>{if(m.type()==='error')report.errors.push(m.text());});
  p.on('response',r=>{if(r.status()>=400)report.errors.push(r.status()+' '+r.url());});
  p.on('request',r=>{if(['xhr','fetch'].includes(r.resourceType()))report.errors.push('Unexpected anonymous business request '+r.url());});
  assert.equal((await r.goto(ref+'/reference/shop.html')).status(),200);await r.locator('[data-theme-tester]').evaluateAll(ns=>ns.forEach(n=>n.remove()));
  assert.equal((await p.goto(base+'/store-web/catalogo')).status(),200);
  for(const page of [r,p]){await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.images].map(i=>i.decode()));});await page.waitForTimeout(1500);}
  await r.bringToFront();const a=PNG.sync.read(await r.screenshot({path:path.join(out,`shop-${width}-reference.png`),fullPage:true}));
  await p.bringToFront();const b=PNG.sync.read(await p.screenshot({path:path.join(out,`shop-${width}-actual.png`),fullPage:true}));
  const pixels=raster(a,b);const m=await p.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,columns:getComputedStyle(document.querySelector('.catalog-product-grid')).gridTemplateColumns.split(' ').length,font:document.fonts.check('12px Montserrat'),bottomNav:getComputedStyle(document.querySelector('.catalog-bottom-nav')).display!=='none'}));
  report.rows.push({width,...pixels,...m});assert.equal(m.scrollWidth,width);assert.equal(m.font,true);assert.equal(pixels.material,0);
  console.log(width+': '+JSON.stringify(pixels));await ctx.close();
 }
 assert.deepEqual(report.errors,[]);report.status='PASS';
}finally{await browser.close();fs.writeFileSync('docs/store-web/shop-visual-qa.json',JSON.stringify(report,null,2));}
})().catch(e=>{console.error(e);process.exitCode=1;});
