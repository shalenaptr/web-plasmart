'use strict';
(() => {
  const BOOT=window.PLASMART_DATA||{};
  const PHONE=String(BOOT.site?.contact?.whatsapp||'6285810497915').replace(/\D/g,'');
  const FREE_MIN=Number(BOOT.site?.shipping?.free_minimum??200000);
  const CART_KEY = 'plasmart-cart-v1';
  const END_KEY = 'plasmart-flash-end-v1';
  const money = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const icon = (name, extra = '') => `<svg class="icon ${extra}" aria-hidden="true"><use href="#i-${name}"/></svg>`;
  const categories=Object.fromEntries((BOOT.categories||[]).map(c=>[c.slug,c.name]));
  const RAW_PRODUCTS=BOOT.products||[],FLASH=BOOT.flash||{};
  function flashState(now=Date.now()){if(!FLASH.active||!FLASH.products?.length||!Date.parse(FLASH.ends_at))return'idle';if(Date.parse(FLASH.ends_at)<=now)return'ended';if(Date.parse(FLASH.starts_at)>now)return'upcoming';return'live'}
  function normalizeProducts(){return RAW_PRODUCTS.map(p=>{const raw=p.variants?.length?p.variants:[{name:'Standar',price:p.price,stock:p.stock||0,wholesale:p.wholesale||null}],regular=Math.min(...raw.map(v=>Number(v.price))),offer=flashState()==='live'&&FLASH.products.includes(p.slug)?FLASH.offers?.[p.slug]:null,live=offer&&Number(offer.remaining_stock)>0&&regular>0&&Number(offer.price)>0&&Number(offer.price)<=regular;return{id:p.slug,numericId:p.id,aliases:p.aliases||[],name:p.title,short:p.title,category:p.category_slug,image:p.image,images:p.images?.length?p.images:(p.image?[p.image]:[]),brand:p.brand,meta_title:p.meta_title,meta_description:p.meta_description,description:p.description,short_description:p.short_description,weight:p.weight,featured:p.featured,specs:p.specs||[],isFlash:!!live,flashRemaining:live?Number(offer.remaining_stock):null,variants:raw.map(v=>{const normal=Number(v.price),amount=live?Math.round(normal*Number(offer.price)/regular):normal;return{label:v.name,regular:normal,price:amount,old:live&&amount<normal?normal:null,wholesale:v.wholesale||null,stock:Math.max(0,Math.min(Number(v.stock)||0,live?Number(offer.remaining_stock):1000000))}})}})}
  function priceQuote(p,index=0,qty=1){const v=p.variants[index];let price=v.price,old=v.old,source=old?'flash':'normal';const w=v.wholesale;if(w&&qty>=Number(w.min_qty)&&Number(w.price)>0&&Number(w.price)<price){price=Number(w.price);old=null;source='wholesale'}return{...v,price,old,source,subtotal:price*qty}}
  let PRODUCTS=normalizeProducts();
  const getProduct=id=>PRODUCTS.find(p=>p.id===id||p.aliases.includes(id));
  const read = key => { try { return localStorage.getItem(key); } catch { return null; } };
  const write = (key, value) => { try { localStorage.setItem(key, value); } catch { /* Shopping still works in memory if storage is unavailable. */ } };
  const sanitizeCart = value => {
    if (!Array.isArray(value)) return [];
    const clean = [];
    value.slice(0,100).forEach(item => {
      const p = getProduct(item?.id);
      const v = Number(item?.variant);
      const q = Number(item?.qty);
      if (!p || !Number.isInteger(v) || !p.variants[v] || !Number.isFinite(q) || q < 1) return;
      const used=clean.filter(x=>x.id===p.id).reduce((n,x)=>n+x.qty,0);const max=p.flashRemaining===null?p.variants[v].stock:Math.min(p.variants[v].stock,Math.max(0,p.flashRemaining-used));const qty=Math.min(max,Math.floor(q));if(qty<1)return;
      const same = clean.find(x => x.id === p.id && x.variant === v);
      if (same) same.qty = Math.min(p.variants[v].stock,same.qty + qty);
      else clean.push({id:p.id,variant:v,qty});
    });
    return clean;
  };
  let initial;
  try { initial = JSON.parse(read(CART_KEY) || '[]'); } catch { initial = []; }
  let cart = sanitizeCart(initial);
  let pageType = 'home';
  let selectedProduct = null;
  let selectedVariant = 0;
  let detailsQty = 1;let galleryIndex=0;
  let filters = {q:'',category:'',sort:'default',min:0,max:0,promo:false};
  let customerName = '';
  let customerNotes='';let customerPhone='',shippingMethod='Ambil di toko';
  let toastTimer;
  let searchTimer;
  const home = document.getElementById('homePage');
  const dynamic = document.getElementById('dynamicPage');
  const purchase = document.getElementById('purchaseBar');
  const dialogs = [...document.querySelectorAll('dialog')];

  function persistCart() { write(CART_KEY,JSON.stringify(cart)); updateBadges(); }
  function updateBadges() {
    const count = cart.reduce((sum,i) => sum+i.qty,0);
    document.querySelectorAll('.cart-badge').forEach(el => { el.textContent = count > 99 ? '99+' : count; el.hidden = count === 0; });
    document.querySelectorAll('a.cart-link').forEach(el => el.setAttribute('aria-label',`Keranjang belanja, ${count} barang`));
  }
  function toast(text, cartAction = true) {
    const el = document.getElementById('toast');
    clearTimeout(toastTimer);
    document.getElementById('toastText').textContent = text;
    el.querySelector('a').hidden = !cartAction;
    el.hidden = false;
    toastTimer = setTimeout(() => { el.hidden = true; },4200);
  }
  function addToCart(id, variant = 0, qty = 1) {
    const p = getProduct(id), v = p?.variants[variant];
    if (!v) return;
    qty = Math.max(1,Math.floor(Number(qty)||1));
    const existing = cart.find(i => i.id === id && i.variant === variant);
    const groupCount=cart.filter(i=>i.id===p.id).reduce((n,i)=>n+i.qty,0);
    if ((existing?.qty||0)+qty>v.stock || (p.flashRemaining!==null&&groupCount+qty>p.flashRemaining)) {
      toast(`Maksimal ${v.stock} untuk varian ${v.label}.`,false); return;
    }
    if (existing) existing.qty += qty; else cart.push({id,variant,qty});
    persistCart();
    toast(`${qty} ${p.short} ditambahkan.`);
  }
  function productCard(p, flash = false, listing = false) {
    const v = p.variants[0];
    const discount = v.old ? Math.round((1-v.price/v.old)*100) : 0;
    return `<article class="product-card ${listing?'listing-card':''}" data-product="${p.id}"><a href="#produk/${p.id}" class="product-visual" aria-label="Lihat ${esc(p.name)}"><img src="${p.image}" alt="${esc(p.name)}" width="600" height="600" loading="lazy" decoding="async">${discount?`<span class="discount-badge">-${discount}%</span>`:''}</a><div class="product-info">${listing?`<span class="listing-category">${esc(categories[p.category])}</span>`:''}<a class="product-name" href="#produk/${p.id}">${esc(p.name)}</a>${listing?`<p class="listing-description">${esc(p.short_description||'')}</p>`:''}<div class="card-prices"><span class="price">${money(v.price)}</span>${v.old?`<span class="old-price">${money(v.old)}</span>`:''}</div><div class="product-bottom"><div class="stock-label">Stok ${v.stock}${flash?`<span class="stock-bar" aria-hidden="true"><span style="width:${Math.max(22,Math.min(86,v.stock))}%"></span></span>`:''}</div><button class="card-cart" data-add="${p.id}" ${v.stock<=0?'disabled':''} aria-label="Tambah ${esc(p.name)} ke keranjang">${icon('cart')}</button></div></div></article>`;
  }
  document.getElementById('flashProducts').innerHTML = (FLASH.products||[]).map(getProduct).filter(Boolean).map(p=>productCard(p,true)).join('');
  document.getElementById('featuredProducts').innerHTML = PRODUCTS.filter(p=>p.featured).slice(0,8).map(p=>productCard(p)).join('');
  document.getElementById('year').textContent = new Date().getFullYear();

  function closeDialogs() { dialogs.forEach(d => { if (d.open) d.close(); }); document.querySelectorAll('.zoom-sheet').forEach(d=>d.remove()); }
  function openDialog(id) {
    closeDialogs();
    const el = document.getElementById(id);
    if (typeof el.showModal === 'function') el.showModal(); else el.setAttribute('open','');
    if(id==='searchDialog') setTimeout(()=>document.getElementById('headerSearchInput').focus(),30);
  }
  dialogs.forEach(d => {
    d.addEventListener('click',event => { if(event.target===d) { const r=d.getBoundingClientRect(); if(event.clientX<r.left || event.clientX>r.right || event.clientY<r.top || event.clientY>r.bottom) d.close(); } });
  });
  function filterQuery() {
    const q = new URLSearchParams();
    if(filters.q) q.set('q',filters.q);
    if(filters.category) q.set('kategori',filters.category);
    if(filters.sort!=='default') q.set('urut',filters.sort);
    if(filters.min) q.set('min',filters.min);
    if(filters.max) q.set('max',filters.max);
    if(filters.promo) q.set('promo','1');
    return q.toString() ? '?'+q.toString() : '';
  }
  function updateCatalog() {
    const query = filters.q.toLocaleLowerCase('id-ID').trim();
    let list = PRODUCTS.filter(p => (!filters.category||filters.category.split(',').includes(p.category)) && (!query || (p.name+' '+p.brand+' '+categories[p.category]).toLocaleLowerCase('id-ID').includes(query)) && (!filters.min||p.variants[0].price>=filters.min) && (!filters.max||p.variants[0].price<=filters.max) && (!filters.promo||p.isFlash));
    if(filters.sort==='price-low') list.sort((a,b)=>a.variants[0].price-b.variants[0].price);
    if(filters.sort==='price-high') list.sort((a,b)=>b.variants[0].price-a.variants[0].price);
    if(filters.sort==='name') list.sort((a,b)=>a.name.localeCompare(b.name,'id'));
    document.getElementById('catalogGrid').innerHTML = list.length ? list.map(p=>productCard(p,false,true)).join('') : `<div class="empty-state">${icon('search')}<h2>Produk belum ditemukan</h2><p>Coba kata kunci lain atau ubah filter pencarian.</p><button class="button" data-action="reset-filters">Tampilkan semua produk</button></div>`;
    document.getElementById('catalogCount').textContent = `Menampilkan ${list.length} dari ${PRODUCTS.length} produk`;
    document.getElementById('catalogSubtitle').textContent = filters.category&&categories[filters.category]?categories[filters.category]:(PRODUCTS.length+' produk pilihan untuk kebutuhanmu');
    document.querySelectorAll('[data-filter-category]').forEach(b=>b.checked=filters.category.split(',').includes(b.value));const n=document.getElementById('catalogNumber');if(n)n.textContent=list.length+' produk';
    const parts = [];
    if(filters.min) parts.push('Mulai '+money(filters.min));
    if(filters.max) parts.push('Maks. '+money(filters.max));
    if(filters.promo) parts.push('Flash sale');
    document.getElementById('activeFilters').textContent = parts.join(' · ');
    history.replaceState(null,'','#produk'+filterQuery());
  }
  function categoryChecks(){return Object.entries(categories).map(([slug,name])=>`<label class="checkbox"><input type="checkbox" value="${esc(slug)}" data-filter-category>${esc(name)}</label>`).join('')}
  function renderCatalog(params){
    filters={q:(params.get('q')||'').slice(0,100),category:(params.get('kategori')||'').split(',').filter(x=>categories[x]).join(','),sort:['price-low','price-high','name'].includes(params.get('urut'))?params.get('urut'):'default',min:Math.max(0,Math.min(99999999,Number(params.get('min'))||0)),max:Math.max(0,Math.min(99999999,Number(params.get('max'))||0)),promo:params.get('promo')==='1'};
    const pg=BOOT.pages?.shop||{};
    dynamic.innerHTML=`<div class="page-head"><div><h1 data-page-field="title">${esc(pg.title||'Semua Produk')}</h1><p data-page-field="description">${esc(pg.description||'Temukan kebutuhanmu.')}</p><p id="catalogSubtitle"></p></div></div><div class="catalog-layout"><aside class="catalog-sidebar"><h2>Filter pilihan</h2><fieldset><legend>Kategori</legend>${categoryChecks()}</fieldset><button class="text-link" data-action="reset-filters">Hapus filter</button><p class="catalog-filter-note">Stok dan pengiriman dikonfirmasi langsung dengan toko.</p></aside><div class="catalog-results"><div class="catalog-toolbar"><span id="catalogNumber"></span><div class="catalog-tools"><button class="filter-button mobile-filter" data-action="filters">${icon('filter')} Filter</button><div class="search-form">${icon('search')}<label class="sr-only" for="catalogSearch">Cari produk</label><input id="catalogSearch" type="search" placeholder="Cari produk…" value="${esc(filters.q)}" maxlength="100" autocomplete="off"></div><label class="sort-control" for="sortSelect">${icon('sort')}<span class="sr-only">Urutkan produk</span><select id="sortSelect"><option value="default">Pilihan toko</option><option value="price-low">Harga terendah</option><option value="price-high">Harga tertinggi</option><option value="name">Nama A–Z</option></select></label></div></div><p class="active-filter-note" id="activeFilters"></p><div class="product-grid" id="catalogGrid"></div><p class="catalog-count" id="catalogCount" aria-live="polite"></p></div></div>${helpPanel()}`;
    document.getElementById('sortSelect').value=filters.sort;updateCatalog();
  }
  function helpPanel() {
    return `<aside class="help-panel">${icon('headset')}<div><h3>Butuh bantuan memilih produk?</h3><p>Hubungi kami melalui WhatsApp.</p></div><a href="https://wa.me/${PHONE}" target="_blank" rel="noopener" class="button">${icon('whatsapp')} Chat WhatsApp</a></aside>`;
  }
  function priceHtml(v) {
    const discount=v.old?Math.round((1-v.price/v.old)*100):0;
    return `<span class="price">${money(v.price)}</span>${v.old?`<span class="old-price">${money(v.old)}</span><span class="discount-badge">-${discount}%</span>`:''}`;
  }
  function updateDetailPrice(){if(!selectedProduct)return;const v=priceQuote(selectedProduct,selectedVariant,detailsQty);document.getElementById('detailPrice').innerHTML=priceHtml(v);const note=document.getElementById('wholesaleNote');if(note){const w=v.wholesale;note.hidden=!w;if(w)note.innerHTML=`<strong>${v.source==='wholesale'?'Harga grosir berlaku':'Harga grosir tersedia'}</strong><span>Mulai ${Number(w.min_qty)} satuan: ${money(w.price)} / satuan.</span>${v.source==='wholesale'?`<small>Subtotal ${detailsQty} satuan: ${money(v.subtotal)}</small>`:v.source==='flash'&&v.price<=Number(w.price)?'<small>Harga flash sale saat ini lebih hemat atau sama.</small>':''}`}document.getElementById('productWa').href=productWaLink(selectedProduct,v);renderPurchaseBar()}
  function renderGallery(p){const n=p.images.length;return `<div class="product-gallery"><div class="gallery-stage" data-gallery-slider tabindex="0" role="region" aria-roledescription="carousel" aria-label="Foto ${esc(p.name)}"><div class="gallery-track" id="detailGalleryTrack">${p.images.map((src,i)=>`<div class="gallery-slide" aria-hidden="${i!==0}" ${i?'inert':''}><button class="gallery-main" data-action="zoom" aria-label="Perbesar foto ${i+1} ${esc(p.name)}"><img src="${esc(src)}" alt="Foto ${i+1} ${esc(p.name)}" width="900" height="900" ${i?'loading="lazy"':'fetchpriority="high"'}></button></div>`).join('')}</div>${n>1?`<button class="gallery-arrow prev" data-gallery-step="-1" aria-label="Foto sebelumnya">${icon('chevron','rotate')}</button><button class="gallery-arrow next" data-gallery-step="1" aria-label="Foto berikutnya">${icon('chevron')}</button><span class="gallery-counter" id="galleryCounter" aria-live="polite">1 / ${n}</span>`:''}</div>${n>1?`<div class="gallery-dots" aria-label="Pilih foto">${p.images.map((_,i)=>`<button data-gallery="${i}" class="${i===0?'is-active':''}" aria-label="Pilih foto ${i+1}" aria-pressed="${i===0}"></button>`).join('')}</div><div class="gallery-thumbnails">${p.images.map((src,i)=>`<button data-gallery="${i}" class="${i===0?'is-active':''}" aria-label="Thumbnail foto ${i+1}" aria-pressed="${i===0}"><img src="${esc(src)}" alt="Foto ${i+1} ${esc(p.name)}" width="90" height="90" loading="lazy"></button>`).join('')}</div>`:''}</div>`}
  function showGallery(index){if(!selectedProduct?.images.length)return;const n=selectedProduct.images.length;galleryIndex=(index+n)%n;const track=document.getElementById('detailGalleryTrack');if(track)track.style.transform=`translateX(-${galleryIndex*100}%)`;document.querySelectorAll('.gallery-slide').forEach((s,i)=>{s.inert=i!==galleryIndex;s.setAttribute('aria-hidden',String(i!==galleryIndex))});document.querySelectorAll('[data-gallery]').forEach(b=>{const active=Number(b.dataset.gallery)===galleryIndex;b.classList.toggle('is-active',active);b.setAttribute('aria-pressed',String(active))});const c=document.getElementById('galleryCounter');if(c)c.textContent=`${galleryIndex+1} / ${n}`}
  let gallerySwipe=null,gallerySwiped=false;
  document.addEventListener('pointerdown',event=>{if(event.pointerType==='touch'&&event.target.closest('[data-gallery-slider]'))gallerySwipe={x:event.clientX,y:event.clientY}});
  document.addEventListener('pointerup',event=>{if(!gallerySwipe)return;const dx=event.clientX-gallerySwipe.x,dy=event.clientY-gallerySwipe.y;gallerySwipe=null;if(Math.abs(dx)>40&&Math.abs(dx)>Math.abs(dy)&&selectedProduct?.images.length>1){gallerySwiped=true;showGallery(galleryIndex+(dx<0?1:-1));setTimeout(()=>gallerySwiped=false,50)}});
  document.addEventListener('pointercancel',()=>gallerySwipe=null);
  document.addEventListener('keydown',event=>{if(event.target.closest('[data-gallery-slider]')&&['ArrowLeft','ArrowRight'].includes(event.key)&&selectedProduct?.images.length>1){event.preventDefault();showGallery(galleryIndex+(event.key==='ArrowRight'?1:-1))}});
  function renderDetail(p) {
    selectedProduct=p;selectedVariant=0;detailsQty=1;galleryIndex=0;
    const v=p.variants[0];
    const related=PRODUCTS.filter(x=>x.category===p.category&&x.id!==p.id).slice(0,4);
    if(related.length<2) PRODUCTS.filter(x=>x.id!==p.id&&!related.includes(x)).slice(0,2-related.length).forEach(x=>related.push(x));
    dynamic.innerHTML=`<nav class="breadcrumb" aria-label="Jejak halaman"><a href="#produk" aria-label="Kembali ke semua produk">${icon('arrow','rotate')}</a><a href="#produk">Produk</a><span>/</span><a href="#produk?kategori=${p.category}">${categories[p.category]}</a></nav><article class="detail-layout">${renderGallery(p)}<div class="detail-summary"><span class="product-category">${categories[p.category]}</span><h1>${esc(p.name)}</h1><p class="detail-short-description">${esc(p.short_description||'')}</p><div class="detail-price-block" id="detailPrice">${priceHtml(v)}</div><div class="wholesale-note" id="wholesaleNote" hidden></div><p class="detail-stock" id="detailStock">Stok tersedia: ${v.stock}</p><h2 class="variant-heading">Pilih ${p.variants.length>1?'varian':'kemasan'}</h2><div class="variant-options" aria-label="Varian produk">${p.variants.map((a,i)=>`<button class="variant-option ${i===0?'is-active':''}" data-variant="${i}" aria-pressed="${i===0}">${esc(a.label)}</button>`).join('')}</div><div class="quantity-row"><label for="detailQty">Jumlah</label><div class="quantity-stepper"><button data-action="detail-minus" aria-label="Kurangi jumlah">${icon('minus')}</button><input id="detailQty" type="number" min="1" max="${v.stock}" value="1" inputmode="numeric" aria-label="Jumlah pembelian"><button data-action="detail-plus" aria-label="Tambah jumlah">${icon('plus')}</button></div></div><div class="detail-actions"><button class="button" data-action="add-detail">${icon('cart')} Masukkan Keranjang</button><a href="${productWaLink(p,v)}" id="productWa" class="button whatsapp-outline" target="_blank" rel="noopener">${icon('whatsapp')} Chat via WhatsApp</a></div><div class="detail-delivery">${icon('truck')}<span>Gratis ongkir minimal pembelian <strong>${money(FREE_MIN)}</strong></span></div><section class="detail-description"><h2>Detail Produk</h2><p>${esc(p.description)}</p><table class="spec-table"><tbody><tr><th scope="row">Merek</th><td>${esc(p.brand)}</td></tr>${p.specs.map(([a,b])=>`<tr><th scope="row">${esc(a)}</th><td>${esc(b)}</td></tr>`).join('')}<tr><th scope="row">Varian dipilih</th><td id="specVariant">${esc(v.label)}</td></tr></tbody></table></section></div></article><section class="related"><div class="section-top"><h2>Produk Terkait</h2><a class="text-link" href="#produk?kategori=${p.category}">Lihat semua ${icon('chevron')}</a></div><div class="product-grid">${related.map(x=>productCard(x)).join('')}</div></section><p class="order-note">Pesanan diteruskan melalui WhatsApp. Konfirmasikan ketersediaan dan area pengantaran bersama kami.</p>`;
    renderPurchaseBar();
    updateDetailQuantity(1);
  }
  function productWaLink(p,v) { return `https://wa.me/${PHONE}?text=${encodeURIComponent(`Halo ${BOOT.site.brand}, saya ingin bertanya tentang ${p.name}, varian ${v.label}.`)}`; }
  function renderPurchaseBar() {
    if(!selectedProduct)return;
    const v=priceQuote(selectedProduct,selectedVariant,detailsQty);
    purchase.innerHTML=`<div><span class="price">${money(v.price)}</span>${v.old?`<span class="old-price">${money(v.old)}</span>`:''}</div><button class="button" data-action="add-detail" ${v.stock<=0?'disabled':''}>${icon('cart')} ${v.stock<=0?'Stok habis':'Masukkan Keranjang'}</button>`;
  }
  function chooseVariant(index) {
    if(!selectedProduct?.variants[index])return;
    selectedVariant=index;
    const v=selectedProduct.variants[index];
    document.querySelectorAll('[data-variant]').forEach(b=>{const active=Number(b.dataset.variant)===index;b.classList.toggle('is-active',active);b.setAttribute('aria-pressed',String(active));});
    document.getElementById('detailPrice').innerHTML=priceHtml(v);
    document.getElementById('detailStock').textContent=`Stok tersedia: ${v.stock}`;
    document.getElementById('specVariant').textContent=v.label;
    document.getElementById('productWa').href=productWaLink(selectedProduct,v);
    document.getElementById('detailQty').max=v.stock;
    updateDetailQuantity(Math.min(detailsQty,v.stock));renderPurchaseBar();
  }
  function updateDetailQuantity(q) {
    if(!selectedProduct)return;
    const max=selectedProduct.variants[selectedVariant].stock;
    detailsQty=Math.max(1,Math.min(max,Math.floor(Number(q)||1)));
    const input=document.getElementById('detailQty');if(input)input.value=detailsQty;
    const minus=document.querySelector('[data-action="detail-minus"]');if(minus)minus.disabled=detailsQty<=1;
    const plus=document.querySelector('[data-action="detail-plus"]');if(plus)plus.disabled=detailsQty>=max;document.querySelectorAll('[data-action="add-detail"]').forEach(b=>b.disabled=max<=0);updateDetailPrice();
  }
  function cartTotal() { return cart.reduce((s,i)=>s+priceQuote(getProduct(i.id),i.variant,i.qty).subtotal,0); }
  function checkoutText() {
    const lines=[`Halo ${BOOT.site.brand}, saya ingin memesan:`,''];
    cart.forEach((i,index)=>{const p=getProduct(i.id),v=priceQuote(p,i.variant,i.qty);lines.push(`${index+1}. ${p.name}`,`Varian: ${v.label}`,`Jumlah: ${i.qty}`,`Harga satuan: ${money(v.price)}${v.source==='wholesale'?' (harga grosir)':v.source==='flash'?' (flash sale)':''}`,`Subtotal: ${money(v.price*i.qty)}`,'');});
    lines.push(`Total produk: ${money(cartTotal())}`);
    if(customerName.trim())lines.push(`Nama pemesan: ${customerName.trim()}`);
    if(customerPhone.trim())lines.push(`WhatsApp pemesan: ${customerPhone.trim()}`);lines.push(`Pengambilan / pengiriman: ${shippingMethod}`);
    if(customerNotes.trim())lines.push(`Catatan: ${customerNotes.trim()}`);
    lines.push('','Mohon konfirmasi stok, ketersediaan produk, dan ongkir. Terima kasih.');
    return lines.join('\n');
  }
  function updateCheckoutLink() {
    const link=document.getElementById('checkoutWa');if(link)link.href=`https://wa.me/${PHONE}?text=${encodeURIComponent(checkoutText())}`;
  }
  function renderCart() {
    const count=cart.reduce((s,i)=>s+i.qty,0);
    if(!cart.length){dynamic.innerHTML=`<div class="page-head"><div><h1>Keranjang Belanja</h1><p>Produk pilihanmu ada di sini.</p></div></div><div class="empty-state">${icon('cart')}<h2>Keranjang masih kosong</h2><p>Yuk, pilih kebutuhanmu dan masukkan ke keranjang.</p><a href="#produk" class="button">Lihat produk ${icon('arrow')}</a></div>`;return;}
    const total=cartTotal(),remaining=Math.max(0,FREE_MIN-total);
    dynamic.innerHTML=`<div class="page-head cart-page-head"><div><h1>Keranjang Belanja</h1><p>${count} barang · ${cart.length} pilihan produk</p></div><button class="clear-cart" data-action="clear-cart">Kosongkan</button></div><div class="cart-layout"><div><div class="cart-items">${cart.map((i,index)=>{const p=getProduct(i.id),v=priceQuote(p,i.variant,i.qty);return `<article class="cart-item"><a href="#produk/${p.id}"><img src="${p.image}" width="120" height="120" alt="${esc(p.name)}"></a><div><a href="#produk/${p.id}" class="cart-item-name">${esc(p.name)}</a><p class="cart-item-variant">${esc(v.label)} · ${money(v.price)} / satuan${v.source==='wholesale'?' · Harga grosir':''}</p><span class="price">${money(v.price*i.qty)}</span><div class="cart-item-controls"><div class="quantity-stepper"><button data-cart-step="-1" data-index="${index}" ${i.qty<=1?'disabled':''} aria-label="Kurangi jumlah ${esc(p.name)}">${icon('minus')}</button><input data-cart-qty="${index}" type="number" min="1" max="${v.stock}" value="${i.qty}" inputmode="numeric" aria-label="Jumlah ${esc(p.name)}"><button data-cart-step="1" data-index="${index}" ${i.qty>=v.stock?'disabled':''} aria-label="Tambah jumlah ${esc(p.name)}">${icon('plus')}</button></div><button class="remove-button" data-remove="${index}" aria-label="Hapus ${esc(p.name)}">${icon('trash')} Hapus</button></div></div></article>`;}).join('')}</div><a class="text-link continue-shopping" href="#produk">${icon('arrow','rotate')} Lanjut belanja</a></div><aside class="cart-summary"><h2>Ringkasan Pesanan</h2><div class="summary-line"><span>Jumlah barang</span><strong>${count}</strong></div><div class="summary-line"><span>Ongkir</span><span>Konfirmasi via WA</span></div><div class="summary-line summary-total"><span>Total produk</span><strong>${money(total)}</strong></div><p class="shipping-note">${icon('truck')}${remaining?`Tambah ${money(remaining)} untuk minimal belanja gratis ongkir.`:'Minimal belanja gratis ongkir terpenuhi.'}<br>Konfirmasikan area pengantaran melalui WhatsApp.</p><a class="button" href="#checkout">Lanjut Checkout ${icon('arrow')}</a><p class="checkout-info">Lengkapi data pemesan dan lanjutkan pesanan melalui WhatsApp.</p></aside></div>`;
    updateCheckoutLink();
  }
  function setCartQty(index,value) {
    const i=cart[index];if(!i)return;
    const p=getProduct(i.id),other=cart.filter((x,n)=>n!==index&&x.id===i.id).reduce((n,x)=>n+x.qty,0),max=p.flashRemaining===null?p.variants[i.variant].stock:Math.min(p.variants[i.variant].stock,p.flashRemaining-other);
    i.qty=Math.max(1,Math.min(max,Math.floor(Number(value)||1)));
    persistCart();renderCart();
  }
  function resetFilters() {
    closeDialogs();filters={q:'',category:'',sort:'default',min:0,max:0,promo:false};
    if(location.hash==='#produk')renderCatalog(new URLSearchParams());else location.hash='#produk';
  }

  function updateHomeProducts(){document.getElementById('featuredProducts').innerHTML=PRODUCTS.filter(p=>p.featured).slice(0,8).map(p=>productCard(p)).join('');document.getElementById('flashProducts').innerHTML=(FLASH.products||[]).map(getProduct).filter(Boolean).map(p=>productCard(p,true)).join('');}
  function renderCheckout(){
    if(!cart.length){renderCart();return;}
    dynamic.innerHTML=`<div class="page-head"><div><h1>Lengkapi pesananmu</h1><p>Isi data, lalu kirim ringkasan pesanan melalui WhatsApp.</p></div></div><div class="checkout-layout"><form class="checkout-form" id="checkoutForm"><h2>Data Pemesan</h2><label class="field">Nama lengkap *<input id="customerName" name="buyer_name" required maxlength="80" autocomplete="name" value="${esc(customerName)}"></label><label class="field">Nomor WhatsApp *<input id="customerPhone" name="phone" type="tel" inputmode="tel" required pattern="[+0-9 ()-]{8,22}" maxlength="22" autocomplete="tel" value="${esc(customerPhone)}" placeholder="08…"></label><label class="field">Pengambilan / pengiriman *<select id="shippingMethod"><option>Ambil di toko</option><option>Pengiriman (konfirmasi toko)</option></select></label><label class="field"><span id="notesLabel">Catatan pesanan</span><textarea id="customerNotes" maxlength="500" rows="3" placeholder="Catatan untuk pesananmu">${esc(customerNotes)}</textarea></label><p class="checkout-info">Konfirmasikan stok, jadwal, ongkir, dan rincian pembayaran bersama toko.</p></form><aside class="cart-summary"><h2>Ringkasan Pesanan</h2>${cart.map(i=>{const p=getProduct(i.id),v=priceQuote(p,i.variant,i.qty);return`<div class="checkout-item"><span>${esc(p.name)}<small>${esc(v.label)} × ${i.qty}</small></span><strong>${money(v.price*i.qty)}</strong></div>`}).join('')}<div class="summary-line summary-total"><span>Subtotal produk</span><strong>${money(cartTotal())}</strong></div><p class="checkout-info">Belum termasuk biaya pengiriman.</p><a id="checkoutWa" href="#" class="button whatsapp-button" data-checkout-order target="_blank" rel="noopener">${icon('whatsapp')} Lanjut ke WhatsApp</a><p class="checkout-info">Periksa draf pesan dan tekan Kirim di WhatsApp agar toko menerima pesanan.</p><a class="text-link continue-shopping" href="#keranjang">Kembali ke keranjang</a><div id="orderFeedback" hidden></div></aside></div>`;
    document.getElementById('shippingMethod').value=shippingMethod;updateShippingFields();updateCheckoutLink();
  }
  function updateShippingFields(){const shipping=shippingMethod.startsWith('Pengiriman'),notes=document.getElementById('customerNotes');if(notes){notes.required=shipping;document.getElementById('notesLabel').textContent=shipping?'Alamat lengkap / catatan *':'Catatan pesanan';}}
  function renderOrderSummary(){let data={};try{data=JSON.parse(sessionStorage.getItem('plasmart_last_order')||'{}');}catch{}dynamic.innerHTML=`<div class="success-card"><span class="success-icon">${icon('whatsapp')}</span><h1>Ringkasan siap dikirim</h1><p>Pastikan pesan sudah dikirim di WhatsApp, lalu tunggu konfirmasi toko.</p>${data.id?`<p>ID: <strong>${esc(data.id)}</strong></p><p>Subtotal produk: <strong>${money(data.total||0)}</strong></p>`:''}<a class="button whatsapp-button" href="${typeof data.url==='string'&&data.url.startsWith('https://wa.me/'+PHONE+'?')?esc(data.url):'https://wa.me/'+PHONE}" target="_blank" rel="noopener">${icon('whatsapp')} Buka WhatsApp</a><a class="text-link" href="#produk">Kembali ke katalog ${icon('arrow')}</a></div>`;}
  function renderPromo(){const pg=BOOT.pages.promo,state=flashState(),list=(FLASH.products||[]).map(getProduct).filter(Boolean);dynamic.innerHTML=`<div class="page-head"><div><h1 data-page-field="title">${esc(pg.title)}</h1><p data-page-field="description">${esc(pg.description)}</p></div></div><div class="promo-information">${icon('flash')}<strong>${esc(FLASH.heading||'Flash Sale')}</strong><span>${state==='upcoming'?'Promo akan dimulai':state==='live'?'Promo sedang berlangsung':state==='ended'?'Promo telah berakhir':'Belum ada promo saat ini'}</span></div>${['live','upcoming'].includes(state)&&list.length?`<div class="product-grid">${list.map(p=>productCard(p,false,true)).join('')}</div>`:'<div class="empty-state"><h2>Nantikan penawaran berikutnya</h2><a class="button" href="#produk">Lihat katalog</a></div>'}${helpPanel()}`;}
  function renderCategories(){const pg=BOOT.pages.categories;dynamic.innerHTML=`<div class="page-head"><div><h1 data-page-field="title">${esc(pg.title)}</h1><p data-page-field="description">${esc(pg.description)}</p></div></div><div class="category-page-grid">${BOOT.categories.map((c,i)=>`<a class="category-page-card" href="#produk?kategori=${esc(c.slug)}"><img src="${esc(c.image)}" alt="${esc(c.name)}" loading="lazy"><div><span class="eyebrow">0${i+1}</span><h2>${esc(c.name)}</h2><p>${esc(c.description)}</p><span class="text-link">Lihat produk ${icon('arrow')}</span></div></a>`).join('')}</div>`;}
  function socialContacts(){const s=BOOT.site.social||{};return ['instagram','tiktok'].map(key=>{const account=String(s[key]||'').replace(/^@/,'').trim(),href=s[key+'_url']||(account?(key==='instagram'?'https://www.instagram.com/':'https://www.tiktok.com/@')+encodeURIComponent(account):'');return href?`<a class="contact-page-card" href="${esc(href)}" target="_blank" rel="noopener"><h2>${key==='instagram'?'Instagram':'TikTok'}</h2><p>${esc(account?'@'+account:'Kunjungi akun toko')}</p><span class="text-link">Lihat akun ${icon('arrow')}</span></a>`:''}).join('')}
  function renderInfoPage(key){const pg=BOOT.pages[key];let body=pg.body?`<div class="body-copy" data-page-field="body">${esc(pg.body)}</div>`:'';const image=pg.about_image||pg.steps_image;dynamic.innerHTML=`<div class="page-head"><div><h1 data-page-field="title">${esc(pg.title)}</h1><p data-page-field="description">${esc(pg.description)}</p></div></div>${key==='contact'?`<div class="contact-page"><a class="contact-page-card" href="https://wa.me/${PHONE}" target="_blank" rel="noopener">${icon('whatsapp')}<h2>${esc(BOOT.site.contact.whatsapp_label)}</h2><p>Produk, stok, dan pengantaran.</p><span class="text-link">Mulai percakapan ${icon('arrow')}</span></a>${BOOT.site.contact.address?`<div class="contact-page-card"><h2>Alamat toko</h2><p>${esc(BOOT.site.contact.address)}</p></div>`:''}${BOOT.site.contact.hours?`<div class="contact-page-card"><h2>Jam operasional</h2><p>${esc(BOOT.site.contact.hours)}</p></div>`:''}${socialContacts()}${BOOT.site.contact.email?`<a class="contact-page-card" href="mailto:${esc(BOOT.site.contact.email)}"><h2>Email</h2><p>${esc(BOOT.site.contact.email)}</p></a>`:''}</div>`:`<div class="info-layout"><article>${image?`<img class="info-image" data-page-image="${pg.about_image?'about_image':'steps_image'}" src="${esc(image)}" alt="${esc(pg.title)}" width="1360" height="765" loading="lazy">`:''}${body}${pg.google_url&&/^https?:\/\//.test(pg.google_url)?`<a class="button google-profile-link" data-page-link="google_url" href="${esc(pg.google_url)}" target="_blank" rel="noopener"><span data-page-field="google_label">${esc(pg.google_label||'Lihat di Google')}</span>${icon('arrow')}</a>`:''}</article><aside class="info-aside">${icon('headset')}<h2>Ada pertanyaan?</h2><p>Kami bisa membantu melalui WhatsApp.</p><a class="button" href="https://wa.me/${PHONE}" target="_blank" rel="noopener">Chat toko ${icon('arrow')}</a></aside></div>`}`;}
  function route() {
    clearTimeout(searchTimer);closeDialogs();
    const raw=(location.hash||BOOT.initialRoute||'#beranda').slice(1),split=raw.indexOf('?');
    let path=split>=0?raw.slice(0,split):raw;try{path=decodeURIComponent(path);}catch{}
    const params=new URLSearchParams(split>=0?raw.slice(split+1):'');
    selectedProduct=null;purchase.hidden=true;document.body.classList.remove('has-purchase-bar');
    let title=(BOOT.pages.home.title||BOOT.site.brand)+' — Plastik, Alat Tulis & Perlengkapan Hajatan',description=BOOT.pages.home.description;
    if(path==='produk'){
      pageType='products';home.hidden=true;dynamic.hidden=false;renderCatalog(params);title=(BOOT.pages.shop.title||'Semua Produk')+' — '+BOOT.site.brand;description=BOOT.pages.shop.description;
    }else if(path.startsWith('produk/')){
      const p=getProduct(path.slice(7));home.hidden=true;dynamic.hidden=false;pageType='products';
      if(p){renderDetail(p);purchase.hidden=false;document.body.classList.add('has-purchase-bar');title=p.meta_title||p.name+' — '+BOOT.site.brand;description=p.meta_description||p.short_description;}
      else dynamic.innerHTML=`<div class="empty-state">${icon('search')}<h1>Produk tidak ditemukan</h1><p>Produk ini belum tersedia di katalog.</p><a href="#produk" class="button">Lihat semua produk</a></div>`;
    }else if(path==='keranjang'){
      pageType='cart';home.hidden=true;dynamic.hidden=false;renderCart();title='Keranjang Belanja — CV PLASMART';
    }else if(path==='checkout'){pageType='cart';home.hidden=true;dynamic.hidden=false;renderCheckout();title='Checkout — '+BOOT.site.brand;
    }else if(path==='order-success'){pageType='cart';home.hidden=true;dynamic.hidden=false;renderOrderSummary();title='Ringkasan Pesanan — '+BOOT.site.brand;
    }else if(path==='categories'){pageType='products';home.hidden=true;dynamic.hidden=false;renderCategories();title=BOOT.pages.categories.title+' — '+BOOT.site.brand;description=BOOT.pages.categories.description;
    }else if(path==='promo'){pageType='products';home.hidden=true;dynamic.hidden=false;renderPromo();title=BOOT.pages.promo.title+' — '+BOOT.site.brand;description=BOOT.pages.promo.description;
    }else if(BOOT.pages?.[path]&&path!=='home'){pageType='other';home.hidden=true;dynamic.hidden=false;renderInfoPage(path);title=BOOT.pages[path].title+' — '+BOOT.site.brand;description=BOOT.pages[path].description;
    }else if(path==='not-found'){pageType='other';home.hidden=true;dynamic.hidden=false;dynamic.innerHTML='<div class="empty-state"><h1>Halaman tidak ditemukan</h1><a class="button" href="#produk">Lihat katalog</a></div>';
    }else{
      pageType='home';home.hidden=false;dynamic.hidden=true;dynamic.innerHTML='';updateHomeProducts();
    }
    document.title=title;document.querySelector('meta[name=description]')?.setAttribute('content',description||'');document.querySelector('meta[property=\"og:title\"]')?.setAttribute('content',title);document.querySelector('meta[property=\"og:description\"]')?.setAttribute('content',description||'');
    document.querySelectorAll('[data-nav]').forEach(el=>{const active=el.dataset.nav===pageType;el.classList.toggle('is-active',active);if(active)el.setAttribute('aria-current','page');else el.removeAttribute('aria-current');});
    window.scrollTo({top:0,behavior:'instant'});
    if(pageType==='home'&&params.get('section')==='tentang')requestAnimationFrame(()=>document.getElementById('tentang').scrollIntoView({behavior:'smooth'}));
  }
  window.addEventListener('hashchange',route);
  window.addEventListener('storage',event=>{if(event.key===CART_KEY){let value;try{value=JSON.parse(event.newValue||'[]');}catch{value=[];}cart=sanitizeCart(value);updateBadges();if(pageType==='cart')renderCart();}});

  document.addEventListener('click',event=>{
    const target=event.target.closest('button,a');if(!target)return;
    if(target.matches('[data-close]')){target.closest('dialog')?.close();return;}
    if(target.closest('dialog')&&target.matches('a[href^="#"]')){closeDialogs();if(target.getAttribute('href')===location.hash)route();}
    if(target.hasAttribute('data-checkout-order')){const f=document.getElementById('checkoutForm');if(!f.reportValidity()){event.preventDefault();return;}const record={id:'PLS-'+Date.now().toString().slice(-8),total:cartTotal(),url:target.href};try{sessionStorage.setItem('plasmart_last_order',JSON.stringify(record));}catch{}const feedback=document.getElementById('orderFeedback');feedback.hidden=false;feedback.innerHTML='<a class="text-link" href="#order-success">Lihat ringkasan pesanan '+icon('arrow')+'</a>';return;}
    if(target.dataset.add){addToCart(target.dataset.add);return;}
    if(target.hasAttribute('data-category')){filters.category=target.dataset.category;updateCatalog();return;}
    if(target.hasAttribute('data-gallery')){showGallery(Number(target.dataset.gallery));return;}
    if(target.hasAttribute('data-gallery-step')){showGallery(galleryIndex+Number(target.dataset.galleryStep));return;}
    if(target.dataset.action==='zoom'&&gallerySwiped){event.preventDefault();return;}
    if(target.hasAttribute('data-variant')){chooseVariant(Number(target.dataset.variant));return;}
    if(target.hasAttribute('data-remove')){cart.splice(Number(target.dataset.remove),1);persistCart();renderCart();return;}
    if(target.hasAttribute('data-cart-step')){const index=Number(target.dataset.index);if(cart[index])setCartQty(index,cart[index].qty+Number(target.dataset.cartStep));return;}
    if(target.hasAttribute('data-slide')){goSlide(Number(target.dataset.slide));restartSlider();return;}
    switch(target.dataset.action){
      case 'menu':openDialog('menuDialog');break;
      case 'search':openDialog('searchDialog');break;
      case 'filters':{
        const form=document.getElementById('filterForm');form.querySelectorAll('[name=filter_category]').forEach(c=>c.checked=filters.category.split(',').includes(c.value));form.elements.min.value=filters.min||'';form.elements.max.value=filters.max||'';form.elements.promo.checked=filters.promo;openDialog('filterDialog');break;
      }
      case 'reset-filters':resetFilters();break;
      case 'detail-minus':updateDetailQuantity(detailsQty-1);break;
      case 'detail-plus':updateDetailQuantity(detailsQty+1);break;
      case 'add-detail':if(selectedProduct)addToCart(selectedProduct.id,selectedVariant,detailsQty);break;
      case 'clear-cart':{
        const d=document.createElement('dialog');d.className='sheet';d.innerHTML=`<div class="sheet-heading"><h2>Keranjang dikosongkan?</h2><button class="icon-button" data-close aria-label="Tutup">${icon('close')}</button></div><p class="muted">Semua pilihan produk akan dihapus dari keranjang.</p><div class="form-buttons" style="margin-top:22px"><button class="button button-light" data-close>Batal</button><button class="button" data-action="confirm-clear">Kosongkan</button></div>`;document.body.append(d);d.addEventListener('close',()=>d.remove());d.showModal();break;
      }
      case 'confirm-clear':target.closest('dialog').close();cart=[];persistCart();renderCart();break;
      case 'zoom':{
        if(!selectedProduct)return;const d=document.createElement('dialog');d.className='sheet zoom-sheet';d.innerHTML=`<button class="icon-button" data-close aria-label="Tutup foto">${icon('close')}</button><img src="${selectedProduct.images[galleryIndex]||selectedProduct.image}" alt="${esc(selectedProduct.name)}">`;document.body.append(d);d.addEventListener('close',()=>d.remove());d.addEventListener('click',e=>{if(e.target===d)d.close();});d.showModal();break;
      }
      case 'prev-slide':goSlide(currentSlide-1);restartSlider();break;
      case 'next-slide':goSlide(currentSlide+1);restartSlider();break;
    }
  });
  document.addEventListener('input',event=>{
    const el=event.target;
    if(el.id==='catalogSearch'){filters.q=el.value.slice(0,100);clearTimeout(searchTimer);searchTimer=setTimeout(updateCatalog,160);}
    if(el.id==='customerName'){customerName=el.value.slice(0,80);updateCheckoutLink();}
    if(el.id==='customerPhone'){customerPhone=el.value.slice(0,22);updateCheckoutLink();}
    if(el.id==='customerNotes'){customerNotes=el.value.slice(0,500);updateCheckoutLink();}
  });
  document.addEventListener('change',event=>{
    const el=event.target;
    if(el.hasAttribute('data-filter-category')){filters.category=[...document.querySelectorAll('[data-filter-category]:checked')].map(c=>c.value).join(',');updateCatalog();}
    if(el.id==='sortSelect'){filters.sort=el.value;updateCatalog();}
    if(el.id==='shippingMethod'){shippingMethod=el.value;updateShippingFields();updateCheckoutLink();}
    if(el.id==='detailQty')updateDetailQuantity(el.value);
    if(el.hasAttribute('data-cart-qty'))setCartQty(Number(el.dataset.cartQty),el.value);
  });
  document.getElementById('headerSearch').addEventListener('submit',event=>{event.preventDefault();const q=document.getElementById('headerSearchInput').value.trim();closeDialogs();const next='#produk'+(q?'?q='+encodeURIComponent(q):'');if(location.hash===next)route();else location.hash=next;});
  document.getElementById('filterForm').addEventListener('submit',event=>{
    event.preventDefault();const form=event.currentTarget;
    let min=Math.max(0,Math.min(99999999,Number(form.elements.min.value)||0)),max=Math.max(0,Math.min(99999999,Number(form.elements.max.value)||0));
    if(max&&min>max)[min,max]=[max,min];
    filters.category=[...form.querySelectorAll('[name=filter_category]:checked')].map(c=>c.value).join(',');filters.min=min;filters.max=max;filters.promo=form.elements.promo.checked;
    closeDialogs();updateCatalog();
  });

  let priorFlashState=flashState();
  function countdown(){const state=flashState(),target=Date.parse(state==='upcoming'?FLASH.starts_at:FLASH.ends_at),seconds=Math.max(0,Math.floor((target-Date.now())/1000)),values={hours:Math.floor(seconds/3600),minutes:Math.floor(seconds/60)%60,seconds:seconds%60};for(const [part,value]of Object.entries(values))document.querySelectorAll(`[data-countdown="${part}"]`).forEach(el=>el.textContent=String(value).padStart(2,'0'));const section=document.querySelector('.flash-section'),clock=document.querySelector('.countdown');section.hidden=state==='idle';clock.hidden=['idle','ended'].includes(state);clock.setAttribute('aria-label',state==='upcoming'?'Waktu hingga flash sale dimulai':'Waktu tersisa flash sale');if(state==='ended')document.getElementById('flashProducts').innerHTML='<p class="muted">Flash sale telah berakhir. Nantikan penawaran berikutnya.</p>';if(state!==priorFlashState){priorFlashState=state;PRODUCTS=normalizeProducts();cart=sanitizeCart(cart);persistCart();if(pageType==='home')updateHomeProducts();else route();}}
  countdown();setInterval(countdown,1000);
  let currentSlide=0;
  let slideTimer;
  const hero=document.querySelector('.hero');hero.addEventListener('banner-preview',event=>{const fields=['hero_image','hero_2_image','hero_3_image'];const i=fields.indexOf(event.detail?.field);if(i>=0)goSlide(i);});
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
  function goSlide(index){
    currentSlide=(index+3)%3;
    document.querySelectorAll('.hero-slide').forEach((s,i)=>{const active=i===currentSlide;s.classList.toggle('is-active',active);s.setAttribute('aria-hidden',String(!active));s.inert=!active;});
    document.querySelectorAll('[data-slide]').forEach((b,i)=>{const active=i===currentSlide;b.classList.toggle('is-active',active);if(active)b.setAttribute('aria-current','true');else b.removeAttribute('aria-current');});
  }
  function restartSlider(){clearInterval(slideTimer);if(!reduced.matches&&!document.hidden&&!new URLSearchParams(location.search).has('_preview'))slideTimer=setInterval(()=>{if(pageType==='home'&&!hero.matches(':hover')&&!hero.contains(document.activeElement))goSlide(currentSlide+1);},6500);}
  let swipeStart=null;
  hero.addEventListener('pointerdown',event=>{if(event.pointerType==='touch')swipeStart={x:event.clientX,y:event.clientY};});
  hero.addEventListener('pointerup',event=>{if(!swipeStart)return;const dx=event.clientX-swipeStart.x,dy=event.clientY-swipeStart.y;swipeStart=null;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)){goSlide(currentSlide+(dx<0?1:-1));restartSlider();}});
  hero.addEventListener('pointercancel',()=>{swipeStart=null;});
  document.addEventListener('visibilitychange',restartSlider);
  reduced.addEventListener?.('change',restartSlider);
  route();updateBadges();restartSlider();
})();
