const fs=require('fs'),path=require('path'),crypto=require('crypto'),assert=require('assert/strict'),cp=require('child_process');
const hash=b=>crypto.createHash('sha256').update(b).digest('hex');
const walk=p=>fs.readdirSync(p,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(p+'/'+e.name):[p+'/'+e.name]);
const report={phase:'REAL NEXT 9.6',hashes:[],legacy:[],runtimeReferences:[],fixtureImages:[],notices:[]};
for(const entry of JSON.parse(fs.readFileSync('docs/store-web/asset-migration-manifest.json')).files){const actual=hash(fs.readFileSync(entry.destination));assert.equal(actual,entry.finalHash,entry.destination);report.hashes.push({path:entry.destination,sha256:actual});}
for(const file of [...walk('public/shop'),'public/js/shop/catalogNoPrice.js','resources/views/layouts/appShop.blade.php','resources/views/shop/catalogNoPrice.blade.php','resources/views/shop/detailCatalogNotPrice.blade.php','routes/web.php','app/Http/Controllers/StoreWebController.php','app/Scopes/TenantScope.php','webpack.mix.js']){
 const git=cp.spawnSync('git',['show','HEAD:'+file],{maxBuffer:32*1024*1024});assert.equal(git.status,0,file);const actual=fs.readFileSync(file);
 // Git working trees may normalize CRLF; compare original content without newline conversion differences.
 const normalize=b=>b.toString('utf8').replace(/\r\n/g,'\n');assert.ok(actual.equals(git.stdout)||normalize(actual)===normalize(git.stdout),file);report.legacy.push({path:file,sha256:hash(actual),matchesHEAD:true});
}
const roots=['app','config','resources','routes','bootstrap'];let files=roots.flatMap(walk).filter(f=>!f.includes('bootstrap/cache/'));
files.push(...walk('public/store-web'),'webpack.mix.js','package.json','composer.json','phpunit.xml');
for(const file of files){if(/\.(php|js|cjs|mjs|css|scss|sass|json|xml|map)$/.test(file)&&fs.readFileSync(file,'utf8').toLowerCase().includes('project-catalog'))report.runtimeReferences.push(file);}
assert.deepEqual(report.runtimeReferences,[]);
for(const file of walk('public/store-web/img'))report.fixtureImages.push({path:file,classification:/no_image/.test(file)?'PERMANENT PRODUCT FALLBACK':/logo.png/.test(file)?'APPROVED SHELL / LOGO FALLBACK':'REQUIRED LOCAL PREVIEW FIXTURE',moveOutsidePublicNow:false});
for(const file of ['public/store-web/fonts/Montserrat-OFL.txt','docs/store-web/vendor/REFERENCE-NOTICE.txt']){assert.ok(fs.statSync(file).size>0);report.notices.push({path:file,sha256:hash(fs.readFileSync(file))});}
report.styleHash=hash(fs.readFileSync('public/store-web/css/style.css'));assert.equal(report.styleHash,'eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431');
report.safeAreaOccurrences=(fs.readFileSync('public/store-web/css/style.css','utf8').match(/env\(safe-area-inset-bottom\)/g)||[]).length;
report.status='PASS';fs.writeFileSync('docs/store-web/independence-static-qa.json',JSON.stringify(report,null,2));console.log(JSON.stringify({status:report.status,hashes:report.hashes.length,legacy:report.legacy.length,runtimeReferences:report.runtimeReferences.length,notices:report.notices.length}));
