/* CURRENT TEMPORARY BACKEND CONTRACT. Authorization belongs to Laravel. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const settings = document.getElementById('store-web-catalog-contract');
    if (!settings) return;
    const config = JSON.parse(settings.textContent);
    const form = document.querySelector('.catalog-search__form');
    const input = document.getElementById('catalog-search-input');
    const grid = document.querySelector('.catalog-product-grid');
    const pager = document.querySelector('.catalog-pagination');
    const summary = document.querySelector('[data-catalog-total]');
    const drawer = document.getElementById('catalog-filter-drawer');
    let search = config.search || new URLSearchParams(location.search).get('search') || '';
    input.value = search; input.dispatchEvent(new Event('input'));
    // No session/tenant inference or network fallback from the isolated visual preview.
    if (config.preview === true) {
        form.addEventListener('submit', event => {
            event.preventDefault(); const url = new URL(config.catalog, location.href);
            if(input.value.trim())url.searchParams.set('search',input.value.trim());
            location.assign(url.href);
        });
        return;
    }
    const state = {category_id:'',subcategory_id:'',size_ids:[],color_ids:[],min_price:'',max_price:''};
    let categories = [], currentPage = 1, version = 0, pending;
    function el(tag,css,text){const n=document.createElement(tag);if(css)n.className=css;if(text!==undefined)n.textContent=String(text);return n;}
    function message(target,text){target.replaceChildren(el('p','',text));}
    function safeUrl(value,fallback){try{const u=new URL(value,location.href);if(['http:','https:'].includes(u.protocol))return u.href;}catch(_){}return fallback;}
    async function json(url,signal){const r=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'},signal});if(!r.ok)throw Error('HTTP '+r.status);return r.json();}
    function products(items){
        grid.replaceChildren();if(!items.length){message(grid,'No se encontraron productos.');return;}
        items.forEach(p=>{
            const name=p.full_name||'Producto sin nombre',a=el('a','catalog-product-card');
            const detail=p.detail_url?safeUrl(p.detail_url,null):null;
            if(detail)a.href=detail;else{a.setAttribute('aria-disabled','true');a.tabIndex=-1;}
            a.dataset.stock=p.stock==null?'':String(p.stock);a.dataset.category=p.category||'';
            const media=el('div','catalog-product-card__media'),img=el('img');img.alt=name;
            img.src=safeUrl(p.image_url||config.defaultImage,config.defaultImage);
            img.addEventListener('error',()=>{if(img.src!==config.defaultImage)img.src=config.defaultImage;},{once:true});media.append(img);
            const body=el('div','catalog-product-card__body');body.append(el('h2','',name));
            if(config.showPrices)body.append(el('p','catalog-product-card__price',p.price_text||'S/. 0.00'));
            // Use supplied aggregate stock; no inventory/price calculation or commercial badge.
            const known=p.stock!=null&&Number.isFinite(Number(p.stock)),available=known&&Number(p.stock)>0;
            body.append(el('span','catalog-product-card__status'+(known&&!available?' catalog-product-card__status--muted':''),known?(available?'Disponible':'No disponible'):'Disponibilidad no informada'));
            if(known&&!available)a.classList.add('catalog-product-card--unavailable');
            a.append(media,body);grid.append(a);
        });
    }
    // Preserve the current catalog.js page-window algorithm.
    function pages(c,t){if(t<=5)return Array.from({length:t},(_,i)=>i+1);if(c<=2)return[1,2,3,'...',t];if(c>=t-1)return[1,'...',t-2,t-1,t];return[c-1,c,c+1,'...',t];}
    function pagination(data){
        currentPage=Number(data.currentPage||1);const total=Number(data.totalPages||1);pager.replaceChildren();
        summary.textContent=String(data.totalFilteredRecords||0)+' productos';if(total<=1)return;
        function link(page,label,icon){const a=el('a',page===currentPage?'active':'',icon?undefined:label);a.href='#';a.dataset.page=page;a.setAttribute('aria-label',icon?label:'Página '+page);if(page===currentPage)a.setAttribute('aria-current','page');if(icon){const s=el('span',icon);s.setAttribute('aria-hidden','true');a.append(s);}pager.append(a);}
        if(currentPage>1)link(currentPage-1,'Página anterior','arrow_left');
        pages(currentPage,total).forEach(p=>p==='...'?pager.append(el('span','pagination-dots','...')):link(p,p));
        if(currentPage<total)link(currentPage+1,'Página siguiente','arrow_right');
    }
    async function load(page){
        const own=++version;if(pending)pending.abort();pending=new AbortController();
        const url=new URL(config.products.replace(':page',page),location.href);
        ['category_id','subcategory_id','min_price','max_price'].forEach(k=>url.searchParams.set(k,state[k]));
        ['size_ids','color_ids'].forEach(k=>state[k].forEach(v=>url.searchParams.append(k+'[]',v)));
        url.searchParams.set('search',search);grid.setAttribute('aria-busy','true');summary.textContent='Cargando productos…';
        try{const r=await json(url,pending.signal);if(own!==version)return;if(!Array.isArray(r.data)||!r.pagination)throw Error('Invalid catalog response');products(r.data);pagination(r.pagination);}
        catch(e){if(own!==version||e.name==='AbortError')return;message(grid,'No se pudieron cargar los productos.');pager.replaceChildren();summary.textContent='No se pudieron cargar los productos.';}
        finally{if(own===version)grid.setAttribute('aria-busy','false');}
    }
    function options(key,items,multiple){
        const target=drawer.querySelector('[data-facet="'+key+'"]');target.replaceChildren();
        if(!items.length){message(target,'No hay opciones disponibles.');return;}
        (multiple?items:[{id:'',name:'Todas'},...items]).forEach(item=>{
            const label=el('label','catalog-filter-option'),check=el('input');check.type='checkbox';check.name=key;check.value=String(item.id);
            check.checked=multiple?state[key].includes(String(item.id)):state[key]===String(item.id);
            label.append(check,el('span','',item.name));target.append(label);
        });
    }
    function subcategories(){const c=categories.find(c=>String(c.id)===state.category_id);options('subcategory_id',c?c.subcategories||[]:[],false);}
    async function facets(key,url,multiple){
        const target=drawer.querySelector('[data-facet="'+key+'"]');message(target,'Cargando…');
        try{const r=await json(url);if(!Array.isArray(r.data))throw Error('Invalid facet response');if(key==='category_id')categories=r.data;options(key,r.data,multiple);if(key==='category_id')subcategories();}
        catch(_){message(target,'No se pudieron cargar las opciones.');}
    }
    function counter(){const n=Number(!!state.category_id)+Number(!!state.subcategory_id)+state.size_ids.length+state.color_ids.length+Number(!!state.min_price)+Number(!!state.max_price);const badge=document.querySelector('.catalog-filter-trigger__count');badge.textContent=n;badge.hidden=n===0;}
    drawer.querySelectorAll('[data-real-group]').forEach(g=>g.hidden=false);
    drawer.querySelectorAll('[data-price]').forEach(i=>i.disabled=false);
    drawer.addEventListener('change',event=>{
        const check=event.target,key=check.name;if(!['category_id','subcategory_id','size_ids','color_ids'].includes(key))return;
        if(key==='category_id'||key==='subcategory_id'){
            // Scalar category/subcategory: same checkbox shape, explicitly one selection.
            state[key]=check.checked?check.value:'';drawer.querySelectorAll('input[name="'+key+'"]').forEach(i=>i.checked=i.value===state[key]);
            if(key==='category_id'){state.subcategory_id='';subcategories();}
        }else state[key]=[...drawer.querySelectorAll('input[name="'+key+'"]:checked')].map(i=>i.value);
        counter();load(1);
    });
    function cleanPrice(v){return String(v||'').replace('S/.','').replace('$','').replace(',','').trim();}
    drawer.querySelector('[data-filter-apply]').addEventListener('click',()=>{
        state.min_price=cleanPrice(drawer.querySelector('[data-price="min_price"]').value);state.max_price=cleanPrice(drawer.querySelector('[data-price="max_price"]').value);counter();load(1);
    });
    drawer.querySelector('.catalog-filter-clear').addEventListener('click',()=>{
        Object.assign(state,{category_id:'',subcategory_id:'',size_ids:[],color_ids:[],min_price:'',max_price:''});drawer.querySelectorAll('[data-facet] input').forEach(i=>i.checked=i.value==='');drawer.querySelectorAll('[data-price]').forEach(i=>i.value='');subcategories();counter();load(1);
    });
    pager.addEventListener('click',event=>{const a=event.target.closest('[data-page]');if(!a)return;event.preventDefault();if(Number(a.dataset.page)!==currentPage)load(Number(a.dataset.page));});
    function updateSearch(){const url=new URL(location.href);if(search)url.searchParams.set('search',search);else url.searchParams.delete('search');history.replaceState(null,'',url);load(1);}
    form.addEventListener('submit',event=>{event.preventDefault();search=input.value.trim();updateSearch();});
    document.querySelector('.catalog-search__clear').addEventListener('click',()=>{search='';updateSearch();});
    facets('category_id',config.categories,false);facets('size_ids',config.sizes,true);facets('color_ids',config.colors,true);load(1);
});
